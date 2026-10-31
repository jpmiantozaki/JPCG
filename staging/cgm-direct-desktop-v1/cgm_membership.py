"""CGM email-only membership precheck. No PlayPark credentials or game packets.

From archived Phase2.8.6. Strictly HTTPS JPCG /authfirst.php, no redirects,
validated response identity/expiry/TTL. Transport injectable for offline tests.
"""
from __future__ import annotations
from dataclasses import dataclass
import json
import re
import secrets
import time
from typing import Callable, Optional
from urllib import error, request

SCHEMA = "cgm-email-membership-v1"
MEMBERSHIP_URL = "https://jpcg.onrender.com/authfirst.php"
MAX_BODY_BYTES = 4096
MAX_EXPIRY_SECONDS = 253402300799


class _NoRedirect(request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def valid_email_candidate(value: str) -> bool:
    return (isinstance(value, str) and 3 <= len(value.encode("utf-8")) <= 254
            and value == value.strip() and "~" not in value
            and not any(ord(ch) < 33 or ord(ch) == 127 for ch in value)
            and re.fullmatch(r"[^@\s]+@[^@\s]+\.[^@\s]+", value, re.ASCII) is not None)


@dataclass(frozen=True)
class MembershipDecision:
    status: str
    request_id: str
    expiry_utc: int = 0
    detail: str = ""

    @property
    def eligible(self) -> bool:
        return self.status == "ACTIVE"


def _reject_duplicate_keys(pairs):
    obj = {}
    for key, value in pairs:
        if key in obj:
            raise ValueError("duplicate JSON keys")
        obj[key] = value
    return obj


def _reject_nonfinite(_):
    raise ValueError("non-finite JSON number")


def validate_membership_reply(raw: bytes, request_id: str, now_utc: int) -> MembershipDecision:
    error_decision = MembershipDecision("ERROR", request_id, detail="Invalid membership response")
    if not isinstance(raw, bytes) or not (0 < len(raw) <= MAX_BODY_BYTES):
        return error_decision
    try:
        data = json.loads(raw.decode("utf-8", errors="strict"),
                          object_pairs_hook=_reject_duplicate_keys,
                          parse_constant=_reject_nonfinite)
    except (UnicodeError, ValueError, TypeError):
        return error_decision
    if not isinstance(data, dict):
        return error_decision
    required = {"schema", "request_id", "status", "server_time",
                "membership_expires_at", "ttl_ms"}
    if not required.issubset(data) or data["schema"] != SCHEMA or data["request_id"] != request_id:
        return error_decision
    if data["status"] not in ("ACTIVE", "INACTIVE", "ERROR"):
        return error_decision
    if any(type(data[key]) is not int for key in ("server_time", "membership_expires_at", "ttl_ms")):
        return error_decision
    server_time = data["server_time"]
    expiry = data["membership_expires_at"]
    ttl_ms = data["ttl_ms"]
    if not isinstance(now_utc, int) or now_utc < 1 or abs(server_time - now_utc) > 300:
        return error_decision
    if data["status"] == "INACTIVE":
        return MembershipDecision("INACTIVE", request_id, detail="CGM membership inactive")
    if data["status"] == "ERROR":
        return MembershipDecision("ERROR", request_id, detail="CGM backend unavailable")
    if not (server_time < expiry <= MAX_EXPIRY_SECONDS and now_utc < expiry and 0 < ttl_ms <= 5000):
        return error_decision
    if ttl_ms > (expiry - now_utc) * 1000:
        return error_decision
    return MembershipDecision("ACTIVE", request_id, expiry_utc=expiry,
                              detail="CGM membership active; official game authentication NOT verified")


class MembershipClient:
    """Send exactly schema/request_id/email; do not supply any game credentials."""

    def __init__(self, transport: Optional[Callable] = None, clock: Callable = time.time):
        self._transport = transport or self._live_transport
        self._clock = clock

    @staticmethod
    def _live_transport(body: bytes, timeout: float) -> tuple[int, str, bytes]:
        req = request.Request(MEMBERSHIP_URL, data=body, method="POST", headers={
            "Content-Type": "application/json", "Accept": "application/json",
            "Cache-Control": "no-store", "User-Agent": "CGM-GardenViewer-Eligibility/1"})
        opener = request.build_opener(_NoRedirect())
        with opener.open(req, timeout=timeout) as resp:
            payload = resp.read(MAX_BODY_BYTES + 1)
            return resp.status, resp.headers.get("Content-Type", ""), payload

    def check(self, email: str, request_id: Optional[str] = None,
              timeout: float = 8.0) -> MembershipDecision:
        rid = request_id or secrets.token_hex(16)
        if not isinstance(rid, str) or re.fullmatch(r"[A-Za-z0-9-]{1,64}", rid) is None:
            return MembershipDecision("INVALID", "", detail="Invalid request identifier")
        if not valid_email_candidate(email):
            return MembershipDecision("INVALID", rid, detail="Enter a valid CGM membership email")
        body = json.dumps({"schema": SCHEMA, "request_id": rid, "email": email},
                          separators=(",", ":")).encode("utf-8")
        try:
            code, content_type, response_bytes = self._transport(body, timeout)
            if code != 200 or not content_type.lower().split(";", 1)[0].strip() == "application/json":
                return MembershipDecision("ERROR", rid, detail="Membership service unavailable")
            return validate_membership_reply(response_bytes, rid, int(self._clock()))
        except (TimeoutError, OSError, error.URLError, ValueError, TypeError):
            return MembershipDecision("ERROR", rid, detail="Membership lookup failed; retry safely")
