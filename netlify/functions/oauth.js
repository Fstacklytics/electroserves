/**
 * ElectroServes — GitHub OAuth provider for the Decap CMS `github` backend.
 *
 * Netlify's Git Gateway / Identity is deprecated (sunset 2026), so the CMS
 * authenticates editors directly against GitHub with an OAuth App. This
 * Netlify Function plays the role of the "external OAuth provider" that
 * `backend.base_url` / `auth_endpoint` in public/admin/config.yml point at:
 *
 *   backend:
 *     name: github
 *     base_url: https://electroserve.netlify.app
 *     auth_endpoint: /.netlify/functions/oauth/auth
 *
 * Flow
 * ----
 * 1. Decap opens a pop-up at <base_url>/<auth_endpoint>?state=<decap-state>&scope=<scope>.
 * 2. GET /auth  ->  302 to https://github.com/login/oauth/authorize with our
 *    client_id, our own redirect_uri (this function's /auth/callback, derived
 *    from the request host + protocol), the requested scope, and an opaque
 *    `state` that wraps Decap's original state + provider + the app's /admin/
 *    redirect.
 * 3. GitHub redirects back to /auth/callback?code=...&state=<opaque>.
 * 4. GET /auth/callback exchanges the code for an access token, then returns a
 *    tiny same-origin HTML bridge that completes Decap's required popup
 *    postMessage protocol (`authorizing:github` -> `authorization:github:success`).
 *    Decap's GitHub backend does not consume tokens from the main /admin URL.
 *
 * Secrets — NEVER commit these
 * ---------------------------------
 *   OAUTH_GITHUB_CLIENT_ID     The GitHub App / OAuth App client id.
 *   OAUTH_GITHUB_CLIENT_SECRET The GitHub App / OAuth App client secret.
 * Both are read from the Netlify site environment variables, not from code.
 */

const GITHUB_AUTHORIZE_URL = 'https://github.com/login/oauth/authorize';
const GITHUB_TOKEN_URL = 'https://github.com/login/oauth/access_token';
const DEFAULT_SCOPE = 'repo,read:user';

/** Build the site origin (scheme + host) the request arrived on. */
function baseUrlFromEvent(event) {
  const protoHeader =
    event.headers['x-forwarded-proto'] || event.headers['X-Forwarded-Proto'] || 'https';
  const proto = protoHeader
    .toString()
    .split(',')[0]
    .trim() || 'https';
  const host = event.headers.host || event.headers.Host;
  if (!host) return null;
  return `${proto}://${host}`;
}

/** Read query params from either the structured field or a raw URL fallback. */
function getQuery(event) {
  if (event.queryStringParameters && typeof event.queryStringParameters === 'object') {
    return event.queryStringParameters;
  }
  try {
    const raw =
      event.rawUrl || `http://${event.headers.host || 'localhost'}${event.path || '/'}`;
    return Object.fromEntries(new URL(raw).searchParams.entries());
  } catch {
    return {};
  }
}

function textResponse(statusCode, message) {
  return {
    statusCode,
    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    body: message,
  };
}

function htmlResponse(statusCode, html) {
  return {
    statusCode,
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Cache-Control': 'no-store, max-age=0',
      'Referrer-Policy': 'no-referrer',
      'X-Content-Type-Options': 'nosniff',
    },
    body: html,
  };
}

function scriptJson(value) {
  return JSON.stringify(value)
    .replace(/</g, '\\u003c')
    .replace(/>/g, '\\u003e')
    .replace(/&/g, '\\u0026')
    .replace(/\u2028/g, '\\u2028')
    .replace(/\u2029/g, '\\u2029');
}

function encodeState(obj) {
  return Buffer.from(JSON.stringify(obj), 'utf8').toString('base64url');
}

function decodeState(value) {
  const json = Buffer.from(value, 'base64url').toString('utf8');
  return JSON.parse(json);
}

function oauthPopupBridge({ provider, targetOrigin, payload, fallbackUrl, error }) {
  const messagePrefix = error ? 'error' : 'success';
  const messagePayload = error || payload;

  return htmlResponse(
    200,
    `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Completing GitHub login…</title>
  <style>
    body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 3rem; color: #334155; }
    strong { color: #0f172a; }
    a { color: #1d4ed8; }
  </style>
</head>
<body>
  <p><strong>Completing GitHub login…</strong></p>
  <p id="status">You can close this window if it does not close automatically.</p>
  <script>
    (function () {
      var provider = ${scriptJson(provider)};
      var targetOrigin = ${scriptJson(targetOrigin)};
      var fallbackUrl = ${scriptJson(fallbackUrl)};
      var payload = ${scriptJson(messagePayload)};
      var message = 'authorization:' + provider + ':${messagePrefix}:' + JSON.stringify(payload);
      var handshake = 'authorizing:' + provider;
      var sent = false;

      function setStatus(text) {
        var status = document.getElementById('status');
        if (status) status.textContent = text;
      }

      function sendAuthorization() {
        if (sent || !window.opener || window.opener.closed) return;
        sent = true;
        window.opener.postMessage(message, targetOrigin);
        setStatus('Login complete. Returning to the content manager…');
      }

      if (window.opener && !window.opener.closed) {
        window.addEventListener('message', function (event) {
          if (event.origin === targetOrigin && event.data === handshake) {
            sendAuthorization();
          }
        }, false);

        // Decap first waits for this authorizing message, then replies with the
        // same message so this bridge can send the final authorization payload.
        window.opener.postMessage(handshake, targetOrigin);

        // Be tolerant of browser timing quirks: by this point Decap should have
        // swapped from the handshake listener to the authorization listener.
        window.setTimeout(sendAuthorization, 1200);
      } else {
        setStatus('Login opened outside the Decap popup. Please allow popups for this site, then try again.');
        window.setTimeout(function () {
          window.location.replace(fallbackUrl + '?cms_oauth_popup=required');
        }, 2500);
      }
    }());
  </script>
</body>
</html>`,
  );
}

export const handler = async (event) => {
  const path = (event.path || '').split('?')[0].replace(/\/+$/, '');
  if (path.endsWith('/auth/callback')) {
    return handleCallback(event);
  }
  if (path.endsWith('/auth')) {
    return handleAuth(event);
  }
  return textResponse(
    404,
    'Not found. Expected /.netlify/functions/oauth/auth or /auth/callback.',
  );
};

async function handleAuth(event) {
  const base = baseUrlFromEvent(event);
  if (!base) {
    return textResponse(500, 'Could not determine request origin (missing Host header).');
  }

  const query = getQuery(event);
  const decapState = query.state || '';
  const provider = query.provider || 'github';
  const scope = query.scope || DEFAULT_SCOPE;

  // The Function is same-origin with the site, so its own callback is just the
  // request origin + the function path. The fallback redirect is the /admin/
  // page, also on the same origin.
  const callbackUri = `${base}/.netlify/functions/oauth/auth/callback`;
  const appRedirect = `${base}/admin/`;

  const state = encodeState({
    state: decapState,
    provider,
    app_redirect: appRedirect,
    callback_uri: callbackUri,
    scope,
  });

  const authorizeUrl = new URL(GITHUB_AUTHORIZE_URL);
  authorizeUrl.searchParams.set('client_id', process.env.OAUTH_GITHUB_CLIENT_ID || '');
  authorizeUrl.searchParams.set('redirect_uri', callbackUri);
  authorizeUrl.searchParams.set('scope', scope);
  authorizeUrl.searchParams.set('state', state);

  return {
    statusCode: 302,
    headers: { Location: authorizeUrl.toString() },
    body: '',
  };
}

async function handleCallback(event) {
  const query = getQuery(event);
  const code = query.code;
  const stateParam = query.state;

  if (!code || !stateParam) {
    return textResponse(
      400,
      'GitHub OAuth callback is missing the required code or state parameter.',
    );
  }

  let envelope;
  try {
    envelope = decodeState(stateParam);
  } catch {
    return textResponse(400, 'Invalid OAuth state parameter.');
  }

  const clientId = process.env.OAUTH_GITHUB_CLIENT_ID;
  const clientSecret = process.env.OAUTH_GITHUB_CLIENT_SECRET;
  if (!clientId || !clientSecret) {
    return textResponse(
      500,
      'OAuth provider is not configured. Set OAUTH_GITHUB_CLIENT_ID and ' +
        'OAUTH_GITHUB_CLIENT_SECRET in the Netlify site environment variables.',
    );
  }

  let tokenResponse;
  try {
    tokenResponse = await fetch(GITHUB_TOKEN_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        client_id: clientId,
        client_secret: clientSecret,
        code,
        redirect_uri: envelope.callback_uri,
      }),
    });
  } catch (err) {
    return textResponse(502, `Failed to contact GitHub token endpoint: ${err.message}`);
  }

  let data;
  try {
    data = await tokenResponse.json();
  } catch {
    return textResponse(502, 'GitHub returned a response that could not be parsed as JSON.');
  }

  const currentBase = baseUrlFromEvent(event);
  const appRedirect = currentBase ? `${currentBase}/admin/` : envelope.app_redirect;
  const targetOrigin = currentBase || new URL(appRedirect).origin;
  const provider = envelope.provider || 'github';

  if (!tokenResponse.ok || !data || !data.access_token) {
    const detail =
      data && (data.error_description || data.error)
        ? data.error_description || data.error
        : 'no access token returned';
    return oauthPopupBridge({
      provider,
      targetOrigin,
      fallbackUrl: appRedirect,
      error: { message: `GitHub OAuth exchange failed: ${detail}` },
    });
  }

  const payload = {
    token: data.access_token,
    provider,
  };
  if (data.refresh_token) {
    payload.refresh_token = data.refresh_token;
  }
  if (envelope.state) {
    payload.state = envelope.state;
  }

  return oauthPopupBridge({
    provider,
    targetOrigin,
    fallbackUrl: appRedirect,
    payload,
  });
}
