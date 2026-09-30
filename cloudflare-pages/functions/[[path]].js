const ALLOWED_PATHS = new Set([
  "/analytics.php",
  "/authenticate.php",
  "/redeemvip.php",
  "/session.php",
  "/verify.php",
]);

// Phase 1 control target: reproduce the working modified APK's compatibility
// service behavior through an API-capable Cloudflare hostname.
const DEFAULT_UPSTREAM = "https://m.cabahug.xyz";

export async function onRequest(context) {
  const request = context.request;
  const incoming = new URL(request.url);

  if (!ALLOWED_PATHS.has(incoming.pathname)) {
    return new Response("Not Found", {
      status: 404,
      headers: { "content-type": "text/plain; charset=utf-8" },
    });
  }

  const upstreamOrigin = context.env.UPSTREAM_ORIGIN || DEFAULT_UPSTREAM;
  const target = new URL(incoming.pathname + incoming.search, upstreamOrigin);

  const headers = new Headers(request.headers);
  headers.delete("host");
  headers.delete("cf-connecting-ip");
  headers.delete("cf-ipcountry");
  headers.delete("cf-ray");
  headers.delete("cf-visitor");
  headers.set("x-cgm-gateway", "wtn-pages-v1");

  const init = {
    method: request.method,
    headers,
    redirect: "manual",
  };

  if (request.method !== "GET" && request.method !== "HEAD") {
    init.body = request.body;
  }

  try {
    const upstream = await fetch(target.toString(), init);
    const responseHeaders = new Headers(upstream.headers);

    // Do not cache protocol responses while we are validating compatibility.
    responseHeaders.set("cache-control", "no-store");
    responseHeaders.set("x-cgm-gateway", "wtn-pages-v1");

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
        "x-cgm-gateway": "wtn-pages-v1",
      },
    });
  }
}
