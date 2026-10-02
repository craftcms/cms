import {Csrf} from '@src/services/Csrf';
import {ConfigService} from '@src/services/Config';
import {createHttpClient} from './http.js';

/**
 * Builds an action URL using the runtime-configured action base
 * (`Url::actionUrl()`), preserving root-relative and absolute URLs.
 */
export function getActionUrl(action: string = '') {
  return ConfigService.getInstance().getActionUrl(action);
}

/**
 * @TODO
 */
export function actionHeaders(): Record<string, string> {
  // The body-end sync script records what the page has loaded on the `Craft`
  // global (see PHP's RegisteredClientAssets); fall back to the Cp config.
  const craftGlobal = (window as {Craft?: Record<string, unknown>}).Craft;
  const registeredAssetBundles =
    (craftGlobal?.registeredAssetBundles as string[] | undefined) ??
    Cp.registeredAssetBundles;
  const registeredJsFiles =
    (craftGlobal?.registeredJsFiles as string[] | undefined) ??
    Cp.registeredJsFiles;

  let headers: Record<string, string> = {
    'X-Registered-Asset-Bundles': [...new Set(registeredAssetBundles)].join(
      ','
    ),
    'X-Registered-Js-Files': [...new Set(registeredJsFiles)].join(','),
  };

  // @TODO Make sure we really don't need this anymore
  // if (Cp.csrfTokenValue) {
  //   headers['X-CSRF-Token'] = Cp.csrfTokenValue;
  // }

  return headers;
}

export const actionClient = createHttpClient();

export const csrf = new Csrf();

actionClient.interceptors.request.use(async (config) => {
  // Resolve the URL lazily so it reflects the runtime CP trigger; the config
  // isn't guaranteed to be initialized when this module is first imported.
  // Request URLs come in three shapes:
  //
  // - A bare action path (e.g. `users/confirm-password`) is expanded to the
  //   full action URL via `ConfigService.getActionUrl()`, which inserts the
  //   path into the *pathname*. This preserves any query string on the action
  //   base URL — notably `?site=` on multi-site installs — which naive
  //   `baseURL` + path string concatenation would corrupt by appending the
  //   path after the query (`?site=default/users/confirm-password`).
  // - A route path starting with `/` (e.g. a Wayfinder-generated
  //   `/admin/actions/fields/render-settings`) already carries the CP/action
  //   triggers, so it resolves against the origin only. `URL.origin` supplies
  //   scheme + host (+ port) without the `protocol` trailing-colon /
  //   port-doubling pitfalls.
  // - An absolute URL is left untouched.
  if (config.url) {
    config.url = getActionUrl(config.url);

    if (config.url.startsWith('/')) {
      config.baseURL = new URL(getActionUrl()).origin;
    }
  }

  config.headers = {
    ...config.headers,
    'X-Requested-With': 'XMLHttpRequest',
    ...actionHeaders(),
  };

  // @TODO Make sure we really don't need this anymore
  // if (
  //   ['post', 'put', 'patch', 'delete'].includes(
  //     config.method?.toLowerCase() || ''
  //   ) &&
  //   !config.url?.includes('users/session-info')
  // ) {
  //   const tokenValue = await csrf.getToken();
  //   if (tokenValue) {
  //     config.headers['X-CSRF-Token'] = tokenValue;
  //   }
  // }

  return config;
});

actionClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    const status = error.response?.status;

    // Retry once with a fresh CSRF token. Requests that already retried are
    // marked through `meta` so a persistent 419/403 can't loop.
    if ((status === 419 || status === 403) && !originalRequest?.meta?.retried) {
      try {
        csrf.clearToken();
        const token = await csrf.refreshToken();

        return actionClient.request({
          ...originalRequest,
          headers: {...originalRequest.headers, 'X-CSRF-Token': token},
          meta: {...originalRequest.meta, retried: true},
        });
      } catch (refreshError) {
        console.error('Failed to refresh CSRF token:', refreshError);
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);
