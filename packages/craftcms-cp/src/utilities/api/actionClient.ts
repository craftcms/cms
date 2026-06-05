import axios, {type RawAxiosRequestHeaders} from 'axios';
import {Csrf} from '@src/services/Csrf';

declare const Craft: any;

/**
 * Get the action URL for a given action path.
 * Delegates to Craft.getActionUrl() when available (runtime),
 * falls back to a path-based URL for early/test contexts.
 */
export function getActionUrl(action: string = '') {
  if (typeof Craft !== 'undefined' && Craft.getActionUrl) {
    return Craft.getActionUrl(action);
  }
  return `/admin/actions/${action}`;
}

/**
 * Get the CP URL for a given path.
 * Delegates to Craft.getCpUrl() when available.
 */
export function getCpUrl(path: string = '', params?: object) {
  if (typeof Craft !== 'undefined' && Craft.getCpUrl) {
    return Craft.getCpUrl(path, params);
  }
  return `/admin/${path}`;
}

export function actionHeaders(): RawAxiosRequestHeaders {
  let headers: Record<string, string> = {
    'X-Registered-Asset-Bundles': [
      ...new Set(Craft.registeredAssetBundles),
    ].join(','),
    'X-Registered-Js-Files': [...new Set(Craft.registeredJsFiles)].join(','),
  };

  return headers;
}

export const actionClient = axios.create({
  baseURL: getActionUrl(),
});

const csrf = new Csrf();

actionClient.interceptors.request.use(async (config) => {
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
