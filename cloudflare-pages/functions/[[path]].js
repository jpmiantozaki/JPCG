const ALLOWED_PATHS = new Set([
  "/analytics.php",
  "/authenticate.php",
  "/redeemvip.php",
  "/session.php",
  "/verify.php",
]);

const DEFAULT_ANL_UPSTREAM = "https://anlgarden.com";
const DEFAULT_CGM_REDEEM_UPSTREAM = "https://jpcg.onrender.com";

export async function onRequest(context) {
  const request = context.request;
  const incoming = new URL(request.url);

  if (!ALLOWED_PATHS.has(incoming.pathname)) {
    return new Response("Not Found", {
      status: 404,
      headers: { "content-type": "text/plain; charset=utf-8" },
    });
  }

  // Stage 2B: preserve every proven ANL endpoint except redemption.
  // Only redeemvip.php is handled by the CGM backend.
  const isCgmRedeem = incoming.pathname === "/redeemvip.php";
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
  headers.set("x-cgm-gateway", "wtn-pages-v2");

  const init = { method: request.method, headers, redirect: "manual" };
  if (request.method !== "GET" && request.method !== "HEAD") init.body = request.body;

  try {
    const upstream = await fetch(target.toString(), init);
    const responseHeaders = new Headers(upstream.headers);
    responseHeaders.set("cache-control", "no-store");
    responseHeaders.set("x-cgm-gateway", "wtn-pages-v2");
    responseHeaders.set("x-cgm-route", isCgmRedeem ? "cgm-redeem" : "anl-original");

    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: responseHeaders,
    });
  } catch (error) {
    return new Response("Upstream connection failed", {
      status: 502,
      headers: {
        "content-type": "text/plain; charset=utf-8",
        "cache-control": "no-store",
        "x-cgm-gateway": "wtn-pages-v2",
        "x-cgm-route": isCgmRedeem ? "cgm-redeem" : "anl-original",
      },
    });
  }
}
