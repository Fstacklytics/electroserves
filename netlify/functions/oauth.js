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
 *     base_url: https://electroserve.netlify.app/.netlify/functions/oauth
 *     auth_endpoint: /auth
 *
 * Flow
 * ----
 * 1. Decap opens a pop-up at <base_url>/auth?state=<decap-state>&scope=<scope>.
 * 2. GET /auth  ->  302 to https://github.com/login/oauth/authorize with our
 *    client_id, our own redirect_uri (this function's /auth/callback, derived
 *    from the request host + protocol), the requested scope, and an opaque
 *    `state` that wraps Decap's original state + the app's /admin/ redirect.
 * 3. GitHub redirects back to /auth/callback?code=...&state=<opaque>.
 * 4. GET /auth/callback exchanges the code for an access token, then 302s the
 *    editor to <app-redirect>?provider=github&token=<access_token>
 *    [&refresh_token=...] so Decap can read the token straight from the URL.
 *
 * Secrets — NEVER commit these
 * ---------------------------------
 *   OAUTH_GITHUB_CLIENT_ID     The GitHub App / OAuth App client id.
 *   OAUTH_GITHUB_CLIENT_SECRET The GitHub App / OAuth App client secret.
 * Both are read from the Netlify site environment variables, not from code.
 */

const GITHUB_AUTHORIZE_URL = 'https://github.com/login/oauth/authorize';
const GITHUB_TOKEN_URL = 'https://github.com/login/oauth/access_token';
const DEFAULT_SCOPE = 'repo';

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

function encodeState(obj) {
  return Buffer.from(JSON.stringify(obj), 'utf8').toString('base64url');
}

function decodeState(value) {
  const json = Buffer.from(value, 'base64url').toString('utf8');
  return JSON.parse(json);
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
  const scope = query.scope || DEFAULT_SCOPE;

  // The Function is same-origin with the site, so its own callback is just the
  // request origin + the function path. The app's redirect back to Decap is the
  // /admin/ page, also on the same origin.
  const callbackUri = `${base}/.netlify/functions/oauth/auth/callback`;
  const appRedirect = `${base}/admin/`;

  const state = encodeState({
    state: decapState,
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

  if (!tokenResponse.ok || !data || !data.access_token) {
    const detail =
      data && (data.error_description || data.error)
        ? data.error_description || data.error
        : 'no access token returned';
    return textResponse(502, `GitHub OAuth exchange failed: ${detail}`);
  }

  const finalUrl = new URL(envelope.app_redirect);
  finalUrl.searchParams.set('provider', 'github');
  finalUrl.searchParams.set('token', data.access_token);
  if (data.refresh_token) {
    finalUrl.searchParams.set('refresh_token', data.refresh_token);
  }
  if (envelope.state) {
    finalUrl.searchParams.set('state', envelope.state);
  }

  return {
    statusCode: 302,
    headers: { Location: finalUrl.toString() },
    body: '',
  };
}
