#!/usr/bin/env python3
"""Loopback-only HTTP smoke for unconfigured upstream. Never game requests."""
import json
import socket
import subprocess
import sys
import time
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

root = Path(__file__).resolve().parents[1]
with socket.socket() as s:
    s.bind(('127.0.0.1', 0))
    port=s.getsockname()[1]
proc=subprocess.Popen(['php','-S',f'127.0.0.1:{port}','-t',str(root/'public')],
                      stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
checks=0

def fetch(path, method='GET', data=None, content_type='application/json'):
    req=Request(f'http://127.0.0.1:{port}{path}',method=method,data=data)
    if data is not None:
        req.add_header('Content-Type',content_type)
    try:
        with urlopen(req,timeout=2) as response:
            return response.status, response.headers, response.read()
    except HTTPError as response:
        return response.code,response.headers,response.read()

def check(value,reason):
    global checks
    if not value: raise AssertionError(reason)
    checks+=1

try:
    for attempt in range(35):
        try:
            status, headers, body=fetch('/health.php')
            break
        except (URLError,ConnectionError):
            time.sleep(0.08)
    else:
        raise AssertionError('local PHP server unavailable')
    health=json.loads(body)
    check(status==200,'health 200')
    check(health['official_provider_configured'] is False,'no official provider configured')
    check(health['independent_session_processor'] is False,'no session processor')
    status,headers,body=fetch('/session.php')
    check(status==503,'legacy endpoint blocked')
    check(json.loads(body)['game_session_issued'] is False,'no synthetic session')
    check('no-store' in headers['Cache-Control'],'cache disabled')
    status,headers,body=fetch('/admission.php')
    check(status==405,'GET admission rejected')
    check(json.loads(body)['session_issued'] is False,'no game session')
    payload={"schema":"cgm-authorized-upstream-admission-v1","attempt_id":"testAttempt1234567",
             "claimed_email":"sample@example.test","provider_proof":"synthetic-proof"}
    status,headers,body=fetch('/admission.php','POST',json.dumps(payload).encode())
    result=json.loads(body)
    check(status==503,'without official verifier, deny')
    check(result['decision']=='DENIED','default admission denies')
    check(result['session_issued'] is False,'no game session')
    check('sample@example.test' not in body.decode(),'no email reflection')
    check('synthetic-proof' not in body.decode(),'no proof reflection')
    check(headers.get('X-Content-Type-Options')=='nosniff','JSON nosniff')
    status,_,body=fetch('/admission.php','POST',b'{"schema":"invalid"}')
    check(status==400,'incomplete input rejected')
    print(f'CGM_PHASE30_HTTP_SMOKE_PASS assertions={checks} (LOOPBACK ONLY)')
finally:
    proc.terminate()
    try: proc.wait(timeout=3)
    except subprocess.TimeoutExpired: proc.kill()
