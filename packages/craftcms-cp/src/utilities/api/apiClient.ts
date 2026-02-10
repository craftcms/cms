import axios, {
  type AxiosProxyConfig,
  type CancelToken,
  type InternalAxiosRequestConfig,
} from 'axios';
import {actionClient} from './actionClient.js';

let loadingApiHeaders = false;
let apiHeaderWaitlist: Promise<any>[] = [];
let apiHeaders: Record<string, string> | null = null;

/**
 * @TODO Make this configurable
 */
export function getApiUrl(action: string = '') {
  return `https://api.craftcms.com/v1/${action}`;
}

async function getApiHeaders(cancelToken?: CancelToken) {
  if (loadingApiHeaders) {
    // @TODO: I'm not sure we need a queue here
    // apiHeaderWaitlist.push(
    //   sendApiRequest("POST", "app/api-headers", { cancelToken }),
    // );
    return;
  }

  if (apiHeaders) {
    return apiHeaders;
  }

  loadingApiHeaders = true;
  try {
    const response = await actionClient.post<Record<string, string>>(
      'app/api-headers',
      undefined,
      {
        cancelToken,
      }
    );

    return response.data;
  } catch (error) {
    // @TODO: I'm not sure we need a queue here
    // while (apiHeaderWaitlist.length) {
    //   apiHeaderWaitlist.shift()[1](error);
    // }
  } finally {
    loadingApiHeaders = false;
  }
}

export const apiClient = axios.create({
  baseURL: 'https://api.craftcms.com/v1/', // @TODO Make configurable
});

async function apiHeadersRequestInterceptor(
  config: InternalAxiosRequestConfig
) {
  if (apiHeaders) {
    Object.entries(apiHeaders).forEach(([key, value]) => {
      config.headers.set(key, value);
    });
  } else {
    config.params = config.params || {};
    config.params.processCraftHeaders = 1;
  }

  return config;
}

async function processApiHeaders(headers: any, cancelToken: CancelToken) {
  if (apiHeaders) {
    return;
  }

  const {data} = await actionClient.post(
    'app/process-api-response-headers',
    {headers},
    {cancelToken}
  );

  // @TODO look into this, the previous code was checking if the headers were already processed but we don't seem to need to.
  // if (!loadingApiHeaders) {
  //   console.log('API headers already processed');
  //   return;
  // }

  apiHeaders = data;
  loadingApiHeaders = false;

  return apiHeaders;
}

async function apiHeadersResponseInterceptor(response: any) {
  await processApiHeaders(response.headers, response.config.cancelToken);
  return response;
}

apiClient.interceptors.request.use(async (config) => {
  const {cancelToken} = config;

  const headers = await getApiHeaders(cancelToken);
  // Force the API to process the Craft headers if this is the first API request
  if (headers) {
    Object.entries(headers).forEach(([key, value]) => {
      config.headers.set(key, value);
    });
  }

  const finalConfig = {
    ...config,
    params: {
      ...(Craft.apiParams || {}),
      ...config.params,
      v: new Date().getTime(),
    },
  };

  if (!headers) {
    finalConfig.params.processCraftHeaders = 1;
  }

  if (Craft.httpProxy) {
    finalConfig.proxy = Craft.httpProxy as AxiosProxyConfig;
  }

  return finalConfig;
});

apiClient.interceptors.request.use(apiHeadersRequestInterceptor);
apiClient.interceptors.response.use(apiHeadersResponseInterceptor);

export function sendApiRequest(
  method: string,
  uri: string,
  options: Record<any, any> = {}
) {
  return apiClient.request({
    method,
    url: uri,
    ...options,
  });
}
