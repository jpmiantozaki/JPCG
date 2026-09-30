const ALLOWED_PATHS = new Set([
  "/analytics.php",
  "/authenticate.php",
  "/redeemvip.php",
  "/session.php",
  "/verify.php",
  "/cgmchk.php",
]);

const DEFAULT_ANL_UPSTREAM = "https://anlgarden.com";
const DEFAULT_CGM_UPSTREAM = "https://jpcg.onrender.com";
const TRACE_VERSION = "stage2e-dual-entitlement-v1";

function cleanForwardHeaders(request) {
  const headers = new Headers(request.headers);
  headers.delete("host");
  headers.delete("cf-connecting-ip");
  headers.delete("cf-ipcountry");
  headers.delete("cf-ray");
  headers.delete("cf-visitor");
  headers.set("x-cgm-gateway", "wtn-pages-stage2e-dual");
  return headers;
}

function responseHeaders(source, routeName) {
  const h = new Headers(source?.headers || {});
  h.set("cache-control", "no-store");
  h.set("x-cgm-gateway", "wtn-pages-stage2e-dual");
  h.set("x-cgm-route", routeName);
  h.set("x-cgm-trace", TRACE_VERSION);
  return h;
}

function decryptProtectedForRouting(packet) {
  // Client packet format recovered from the unchanged base APK:
  // lowercase-hex(XOR(plaintext,key)) : 6-char-key + 40-char-checksum + switch
  // We only decrypt enough to classify ANL's own verify result. We never alter
  // or manufacture an ANL success response.
  if (typeof packet !== "string") return null;
  packet = packet.trim();
  const colon = packet.indexOf(":");
  if (colon <= 0) return null;
  const hex = packet.slice(0, colon);
  const trailer = packet.slice(colon + 1);
  if (!/^[0-9a-fA-F]+$/.test(hex) || (hex.length & 1) !== 0) return null;
  if (trailer.length < 47) return null;
  const key = trailer.slice(0, 6);
  if (key.length !== 6) return null;

  const out = new Uint8Array(hex.length / 2);
  for (let i = 0; i < out.length; i++) {
    const b = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    out[i] = b ^ key.charCodeAt(i % key.length);
  }
  try {
    return new TextDecoder("utf-8", { fatal: true }).decode(out);
  } catch (_) {
    return null;
  }
}

function classifyAnlVerify(body) {
  const plain = decryptProtectedForRouting(body);
  if (!plain) return "unreadable";
  try {
    const v = JSON.parse(plain);
    if (v && v.error === false) return "success";
    if (v && v.error === true) {
      const message = String(v.message || "").trim().toUpperCase();
      // Fall back to CGM only for an explicit VIP entitlement denial.
      if (message === "VIP" || message.includes("VIP")) return "vip-denied";
      return "other-error";
    }
  } catch (_) {}
  return "unreadable";
}

async function forward(request, target) {
  const init = {
    method: request.method,
    headers: cleanForwardHeaders(request),
    redirect: "manual",
  };
  if (request.method !== "GET" && request.method !== "HEAD") init.body = request.body;
  return fetch(target.toString(), init);
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

  const anlOrigin = context.env.UPSTREAM_ORIGIN || DEFAULT_ANL_UPSTREAM;
  const cgmOrigin = context.env.CGM_REDEEM_ORIGIN || DEFAULT_CGM_UPSTREAM;

  try {
    // Stage 2E: /cgmchk.php is a dual-entitlement router.
    // 1) Ask ANL's real verify.php first.
    // 2) Return an ANL success byte-for-byte to preserve official VIP.
    // 3) Only an explicit ANL VIP denial may fall back to CGM membership.
    if (incoming.pathname === "/cgmchk.php") {
      const anlTarget = new URL("/verify.php" + incoming.search, anlOrigin);
      const anl = await forward(request, anlTarget);
      const anlBody = await anl.text();
      const classification = anl.ok ? classifyAnlVerify(anlBody) : "http-error";

      if (classification === "success") {
        console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=/cgmchk.php route=anl-verify-success method=${request.method} status=${anl.status}`);
        return new Response(anlBody, {
          status: anl.status,
          statusText: anl.statusText,
          headers: responseHeaders(anl, "anl-verify-success"),
        });
      }

      if (classification !== "vip-denied") {
        // Conservative failure mode: never convert an unknown ANL error into
        // CGM authorization. Return ANL's original response unchanged.
        console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=/cgmchk.php route=anl-verify-${classification} method=${request.method} status=${anl.status}`);
        return new Response(anlBody, {
          status: anl.status,
          statusText: anl.statusText,
          headers: responseHeaders(anl, `anl-verify-${classification}`),
        });
      }

      const cgmTarget = new URL("/cgmchk.php" + incoming.search, cgmOrigin);
      const cgm = await forward(request, cgmTarget);
      console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=/cgmchk.php route=cgm-after-anl-vip-denial method=${request.method} status=${cgm.status}`);
      return new Response(cgm.body, {
        status: cgm.status,
        statusText: cgm.statusText,
        headers: responseHeaders(cgm, "cgm-after-anl-vip-denial"),
      });
    }

    const isCgmRedeem = incoming.pathname === "/redeemvip.php";
    const routeName = isCgmRedeem ? "cgm-redeem" : "anl-original";
    const origin = isCgmRedeem ? cgmOrigin : anlOrigin;
    const target = new URL(incoming.pathname + incoming.search, origin);
    const upstream = await forward(request, target);

    console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=${incoming.pathname} route=${routeName} method=${request.method} status=${upstream.status}`);
    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: responseHeaders(upstream, routeName),
    });
  } catch (error) {
    console.error(`[CGM_GATEWAY_ERROR] trace=${TRACE_VERSION} path=${incoming.pathname} message=${String(error)}`);
    return new Response("Upstream connection failed", {
      status: 502,
      headers: {
        "content-type": "text/plain; charset=utf-8",
        "cache-control": "no-store",
        "x-cgm-gateway": "wtn-pages-stage2e-dual",
        "x-cgm-route": "gateway-error",
        "x-cgm-trace": TRACE_VERSION,
      },
    });
  }
}
