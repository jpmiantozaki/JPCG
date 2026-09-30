const ALLOWED_PATHS = new Set([
  "/analytics.php",
  "/authenticate.php",
  "/redeemvip.php",
  "/session.php",
  "/verify.php",
]);

const DEFAULT_ANL_UPSTREAM = "https://anlgarden.com";
const DEFAULT_CGM_REDEEM_UPSTREAM = "https://jpcg.onrender.com";
const TRACE_VERSION = "stage2c-session-trace-v2";

export async function onRequest(context) {
  const request = context.request;
  const incoming = new URL(request.url);

  if (!ALLOWED_PATHS.has(incoming.pathname)) {
    return new Response("Not Found", {
      status: 404,
      headers: { "content-type": "text/plain; charset=utf-8" },
    });
  }

  const isCgmRedeem = incoming.pathname === "/redeemvip.php";
  const routeName = isCgmRedeem ? "cgm-redeem" : "anl-original";
  const upstreamOrigin = isCgmRedeem
    ? (context.env.CGM_REDEEM_ORIGIN || DEFAULT_CGM_REDEEM_UPSTREAM)
    : (context.env.UPSTREAM_ORIGIN || DEFAULT_ANL_UPSTREAM);

  const target = new URL(incoming.pathname + incoming.search, upstreamOrigin);
  const headers = new Headers(request.headers);
  headers.delete("host");
  headers.delete("cf-connecting-ip");
  headers.delete("cf-ipcountry");
  headers.delete("cf-ray");
  headers.delete("cf-visitor");
  headers.set("x-cgm-gateway", "wtn-pages-v2-trace");

  const init = { method: request.method, headers, redirect: "manual" };
  if (request.method !== "GET" && request.method !== "HEAD") init.body = request.body;

  try {
    const upstream = await fetch(target.toString(), init);

    // Use error-level console output during this temporary trace so it is easy to spot.
    // Never log the query string, login ID, redemption code, or request body.
    console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=${incoming.pathname} route=${routeName} method=${request.method} status=${upstream.status}`);

    if (incoming.pathname === "/session.php") {
      // Read only a clone. The original upstream body is returned byte-for-byte to the APK.
      const protectedSessionResponse = await upstream.clone().text();
      console.error(`[CGM_SESSION_RESPONSE] ${protectedSessionResponse}`);
    }

    const responseHeaders = new Headers(upstream.headers);
    responseHeaders.set("cache-control", "no-store");
    responseHeaders.set("x-cgm-gateway", "wtn-pages-v2-trace");
    responseHeaders.set("x-cgm-route", routeName);
    responseHeaders.set("x-cgm-trace", TRACE_VERSION);

    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: responseHeaders,
    });
  } catch (error) {
    console.error(`[CGM_GATEWAY_ERROR] trace=${TRACE_VERSION} path=${incoming.pathname} route=${routeName} message=${String(error)}`);
    return new Response("Upstream connection failed", {
      status: 502,
      headers: {
        "content-type": "text/plain; charset=utf-8",
        "cache-control": "no-store",
        "x-cgm-gateway": "wtn-pages-v2-trace",
        "x-cgm-route": routeName,
        "x-cgm-trace": TRACE_VERSION,
      },
    });
  }
}
