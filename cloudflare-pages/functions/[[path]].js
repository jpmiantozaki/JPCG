const ALLOWED_PATHS = new Set([
  "/__cgm_backend.php",
  "/analytics.php",
  "/authenticate.php",
  "/redeemvip.php",
  "/session.php",
  "/verify.php",
  "/__cgm_stage.php",
]);

const DEFAULT_SESSION_BACKEND = "https://jpcg.onrender.com";
const DEFAULT_CGM_UPSTREAM = "https://jpcg.onrender.com";
const TRACE_VERSION = "cgm-render-session-relay-v1";

function cleanForwardHeaders(request) {
  const headers = new Headers(request.headers);
  headers.delete("host");
  headers.delete("cf-connecting-ip");
  headers.delete("cf-ipcountry");
  headers.delete("cf-ray");
  headers.delete("cf-visitor");
  headers.set("x-cgm-gateway", "cgm-render-session-relay-v1");
  return headers;
}

function responseHeaders(source, routeName) {
  const h = new Headers(source?.headers || {});
  h.set("cache-control", "no-store");
  h.set("x-cgm-gateway", "cgm-render-session-relay-v1");
  h.set("x-cgm-route", routeName);
  h.set("x-cgm-trace", TRACE_VERSION);
  return h;
}

async function forward(request, target) {
  const init = {
    method: request.method,
    headers: cleanForwardHeaders(request),
    redirect: "manual",
  };

  if (request.method !== "GET" && request.method !== "HEAD") {
    init.body = request.body;
  }

  return fetch(target.toString(), init);
}


// Diagnostic only: observe a bounded response clone and never modify the body.
// The packet decoder follows the supplied _secure_packet.php contract.
// Its checksum is a compatibility check, not proof of authenticated identity.
const SUMMARY_PATHS = new Set(['/authenticate.php', '/session.php', '/verify.php', '/redeemvip.php']);
const MAX_DIAGNOSTIC_BYTES = 128 * 1024;

async function digestHex(algorithm, value) {
  const result = await crypto.subtle.digest(algorithm, new TextEncoder().encode(value));
  return Array.from(new Uint8Array(result), b => b.toString(16).padStart(2, '0')).join('');
}

async function decodeProtected(packet) {
  const match = /^([a-fA-F0-9]+):([a-f0-9]{6})([a-f0-9]{40})([0-2])$/.exec(packet);
  if (!match || match[1].length % 2 !== 0) throw new Error('packet_format');
  const [, hex, key, checksum, switching] = match;
  const [packetHash, keyHash] = await Promise.all([
    digestHex('SHA-1', hex + ':' + key), digestHex('SHA-1', key)
  ]);
  const expected = switching === '0' ? packetHash.slice(0, 20) + keyHash.slice(20)
    : switching === '1' ? packetHash.slice(20) + keyHash.slice(0, 20)
    : keyHash.slice(20) + packetHash.slice(0, 20);
  if (checksum !== expected) throw new Error('packet_checksum');
  const bytes = new Uint8Array(hex.length / 2);
  for (let i = 0; i < bytes.length; i++) {
    bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16) ^ key.charCodeAt(i % key.length);
  }
  return new TextDecoder('utf-8', { fatal: true }).decode(bytes);
}

async function readBounded(response) {
  if (!response.body) return { text: '', bytes: 0 };
  const reader = response.body.getReader();
  let timer;
  let complete = false;
  try {
    const timeout = new Promise((_, reject) => {
      timer = setTimeout(() => reject(new Error('timeout')), 3000);
    });
    const chunks = [];
    let size = 0;
    while (true) {
      const { value, done } = await Promise.race([reader.read(), timeout]);
      if (done) { complete = true; break; }
      size += value.byteLength;
      if (size > MAX_DIAGNOSTIC_BYTES) throw new Error('size_limit');
      chunks.push(value);
    }
    const bytes = new Uint8Array(size);
    let offset = 0;
    for (const chunk of chunks) { bytes.set(chunk, offset); offset += chunk.byteLength; }
    return { text: new TextDecoder('utf-8', { fatal: true }).decode(bytes), bytes: size };
  } finally {
    clearTimeout(timer);
    // Do not await clone cancellation: tee cancellation can wait on the client.
    if (!complete) reader.cancel().catch(() => {});
    reader.releaseLock();
  }
}

function knownType(value) {
  if (value === undefined) return 'missing';
  if (value === null) return 'null';
  if (Array.isArray(value)) return 'array';
  return typeof value;
}

async function logResponseSummary(response, details) {
  const record = { ...details };
  try {
    const body = await readBounded(response);
    record.body_bytes = body.bytes;
    let decoded = body.text.trim();
    record.format = 'json';
    if (!decoded.startsWith('{')) {
      try { decoded = await decodeProtected(decoded); record.format = 'protected-json'; }
      catch { record.format = 'unrecognized'; }
    }
    let payload;
    try { payload = JSON.parse(decoded); }
    catch { record.summary = 'unparsed'; }
    if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
      record.summary = 'parsed';
      record.error_type = knownType(payload.error);
      record.error = typeof payload.error === 'boolean' ? payload.error : null;
      record.data_type = knownType(payload.data);
      record.data_present = typeof payload.data === 'string' ? payload.data.length > 0 : null;
      record.session_type = knownType(payload.session);
      record.session_present = typeof payload.session === 'string' ? payload.session.length > 0 : null;
      record.message_present = typeof payload.message === 'string' ? payload.message.length > 0 : null;
      record.servertype = Number.isInteger(payload.servertype) && payload.servertype >= 0 && payload.servertype <= 8 ? payload.servertype : null;
      record.vip_expiry = Number.isInteger(payload.vip_expiry) && payload.vip_expiry >= 0 && payload.vip_expiry <= 4102444800 ? payload.vip_expiry : null;
      record.state = ['ACTIVE', 'INACTIVE', 'EXPIRED', 'ERROR'].includes(payload.state) ? payload.state : null;
    } else if (!record.summary) { record.summary = 'unparsed'; }
  } catch {
    record.summary = 'unavailable';
  }
  // No raw bodies, packets, account IDs, message strings, codes or session values.
  console.log('[CGM_RESPONSE] ' + JSON.stringify(record));
}

export async function onRequest(context) {
  const request = context.request;
  const incoming = new URL(request.url);

  if (!ALLOWED_PATHS.has(incoming.pathname)) {
    return new Response("Not Found", {
      status: 404,
      headers: { "content-type": "text/plain; charset=utf-8" },
    });
  }

  // Deployment-only health marker. It contains no account or protocol data.
  // Check this before testing either account so an old Pages deployment cannot
  // be mistaken for Stage 2E.
  if (incoming.pathname === "/__cgm_stage.php") {
    console.log(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=/__cgm_stage.php route=stage-marker method=${request.method} status=200`);
    return new Response(JSON.stringify({
      ok: true,
      stage: TRACE_VERSION,
      gateway: "cgm-render-session-relay-v1",
      diagnostics_enabled: context.env.CGM_DIAGNOSTICS === "1",
    }), {
      status: 200,
      headers: {
        "content-type": "application/json; charset=utf-8",
        "cache-control": "no-store",
        "x-cgm-gateway": "cgm-render-session-relay-v1",
        "x-cgm-route": "stage-marker",
        "x-cgm-trace": TRACE_VERSION,
      },
    });
  }

  const anlOrigin = "https://jpcg.onrender.com";
  const cgmOrigin = context.env.CGM_REDEEM_ORIGIN || DEFAULT_CGM_UPSTREAM;

  try {
    const isCgmRedeem = incoming.pathname === "/redeemvip.php";
    const isCgmVerify = incoming.pathname === "/verify.php";
    const routeName = isCgmRedeem ? "cgm-redeem"
      : isCgmVerify ? "cgm-membership-verify"
      : incoming.pathname === "/__cgm_backend.php" ? "cgm-backend-marker" : "cgm-session-bridge";
    const origin = (isCgmRedeem || isCgmVerify) ? cgmOrigin : anlOrigin;
    const target = new URL(incoming.pathname + incoming.search, origin);
    const upstream = await forward(request, target);

    console.log(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=${incoming.pathname} route=${routeName} method=${request.method} status=${upstream.status}`);
    if (context.env.CGM_DIAGNOSTICS === '1' && SUMMARY_PATHS.has(incoming.pathname)) {
      try {
        const diagnostic = logResponseSummary(upstream.clone(), {
          trace: TRACE_VERSION,
          request_id: crypto.randomUUID(),
          path: incoming.pathname,
          route: routeName,
          upstream_host: target.hostname,
          status: upstream.status,
        });
        context.waitUntil(diagnostic);
      } catch {
        console.log('[CGM_RESPONSE] ' + JSON.stringify({ trace: TRACE_VERSION, path: incoming.pathname, summary: 'unavailable' }));
      }
    }
    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: responseHeaders(upstream, routeName),
    });
  } catch (error) {
    console.error(`[CGM_GATEWAY_ERROR] trace=${TRACE_VERSION} path=${incoming.pathname} reason=upstream_failure`);
    return new Response("Upstream connection failed", {
      status: 502,
      headers: {
        "content-type": "text/plain; charset=utf-8",
        "cache-control": "no-store",
        "x-cgm-gateway": "cgm-render-session-relay-v1",
        "x-cgm-route": "gateway-error",
        "x-cgm-trace": TRACE_VERSION,
      },
    });
  }
}
