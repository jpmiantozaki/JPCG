const ALLOWED_PATHS = new Set([
  "/analytics.php",
  "/authenticate.php",
  "/redeemvip.php",
  "/session.php",
  "/verify.php",
  "/__cgm_stage.php",
]);

const DEFAULT_ANL_UPSTREAM = "https://anlgarden.com";
const DEFAULT_CGM_UPSTREAM = "https://jpcg.onrender.com";
const TRACE_VERSION = "cgm-membership-lookup-v1";

function cleanForwardHeaders(request) {
  const headers = new Headers(request.headers);
  headers.delete("host");
  headers.delete("cf-connecting-ip");
  headers.delete("cf-ipcountry");
  headers.delete("cf-ray");
  headers.delete("cf-visitor");
  headers.set("x-cgm-gateway", "cgm-membership-lookup-v1");
  return headers;
}

function responseHeaders(source, routeName) {
  const h = new Headers(source?.headers || {});
  h.set("cache-control", "no-store");
  h.set("x-cgm-gateway", "cgm-membership-lookup-v1");
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
    console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=/__cgm_stage.php route=stage-marker method=${request.method} status=200`);
    return new Response(JSON.stringify({
      ok: true,
      stage: TRACE_VERSION,
      gateway: "cgm-membership-lookup-v1",
    }), {
      status: 200,
      headers: {
        "content-type": "application/json; charset=utf-8",
        "cache-control": "no-store",
        "x-cgm-gateway": "cgm-membership-lookup-v1",
        "x-cgm-route": "stage-marker",
        "x-cgm-trace": TRACE_VERSION,
      },
    });
  }

  const anlOrigin = context.env.UPSTREAM_ORIGIN || DEFAULT_ANL_UPSTREAM;
  const cgmOrigin = context.env.CGM_REDEEM_ORIGIN || DEFAULT_CGM_UPSTREAM;

  try {
    const isCgmRedeem = incoming.pathname === "/redeemvip.php";
    const isCgmVerify = incoming.pathname === "/verify.php";
    const routeName = isCgmRedeem ? "cgm-redeem"
      : isCgmVerify ? "cgm-membership-verify" : "anl-original";
    const origin = (isCgmRedeem || isCgmVerify) ? cgmOrigin : anlOrigin;
    const target = new URL(incoming.pathname + incoming.search, origin);
    const upstream = await forward(request, target);

    console.error(`[CGM_GATEWAY] trace=${TRACE_VERSION} path=${incoming.pathname} route=${routeName} method=${request.method} status=${upstream.status}`);
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
        "x-cgm-gateway": "cgm-membership-lookup-v1",
        "x-cgm-route": "gateway-error",
        "x-cgm-trace": TRACE_VERSION,
      },
    });
  }
}
