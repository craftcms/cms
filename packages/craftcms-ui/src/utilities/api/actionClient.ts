import axios, {type RawAxiosRequestHeaders} from 'axios';
import {Csrf} from '@src/services/Csrf';
import {ConfigService} from '@src/services/Config';

/**
 * Builds an action URL using the runtime-configured action base
 * (`Url::actionUrl()`), so the CP trigger isn't hard-coded to `/admin`.
 */
export function getActionUrl(action: string = '') {
  return ConfigService.getInstance().getActionUrl(action);
}

/**
 * @TODO
 */
export function actionHeaders(): RawAxiosRequestHeaders {
  let headers: Record<string, string> = {
    'X-Registered-Asset-Bundles': [...new Set(Cp.registeredAssetBundles)].join(
      ','
    ),
    'X-Registered-Js-Files': [...new Set(Cp.registeredJsFiles)].join(','),
  };

  // @TODO Make sure we really don't need this anymore
  // if (Cp.csrfTokenValue) {
  //   headers['X-CSRF-Token'] = Cp.csrfTokenValue;
  // }

  return headers;
}

export const actionClient = axios.create();

const csrf = new Csrf();

actionClient.interceptors.request.use(async (config) => {
  // Resolve the base URL lazily so it reflects the runtime CP trigger. Config
  // isn't guaranteed to be initialized when this module is first imported.
  config.baseURL = getActionUrl();

  // Set X-Requested-With header
  config.headers.set('X-Requested-With', 'XMLHttpRequest');

  // Merge action headers
  const headers = actionHeaders();
  Object.entries(headers).forEach(([key, value]) => {
    config.headers.set(key, value);
  });

  // @TODO Make sure we really don't need this anymore
  // if (
  //   ['post', 'put', 'patch', 'delete'].includes(
  //     config.method?.toLowerCase() || ''
  //   ) &&
  //   !config.url?.includes('users/session-info')
  // ) {
  //   const tokenValue = await csrf.getToken();
  //   if (tokenValue) {
  //     config.headers.set('X-CSRF-Token', tokenValue);
  //   }
  // }

  return config;
});

actionClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (
      error.response?.status === 419 ||
      (error.response?.status === 403 && !originalRequest._retry)
    ) {
      originalRequest._retry = true;

      try {
        csrf.clearToken();
        originalRequest.headers['X-CSRF-Token'] = await csrf.refreshToken();

        return axios(originalRequest);
      } catch (refreshError) {
        console.error('Failed to refresh CSRF token:', refreshError);
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);
