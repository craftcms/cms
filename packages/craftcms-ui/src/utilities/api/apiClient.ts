import {actionClient} from './actionClient.js';
import {createHttpClient, type HttpRequestConfig} from './http.js';

let loadingApiHeaders = false;
let apiHeaders: Record<string, string> | null = null;

/**
 * @TODO Make this configurable
 */
export function getApiUrl(action: string = '') {
  return `https://api.craftcms.com/v1/${action}`;
}

async function getApiHeaders(signal?: AbortSignal) {
  if (loadingApiHeaders) {
    // @TODO: I'm not sure we need a queue here
    // apiHeaderWaitlist.push(
    //   sendApiRequest("POST", "app/api-headers", { signal }),
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
      {signal}
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

export const apiClient = createHttpClient({
  baseURL: 'https://api.craftcms.com/v1/', // @TODO Make configurable
});

async function processApiHeaders(
  headers: Record<string, string>,
  signal?: AbortSignal
) {
  if (apiHeaders) {
    return;
  }

  const {data} = await actionClient.post(
    'app/process-api-response-headers',
    {headers},
    {signal}
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

apiClient.interceptors.request.use(async (config) => {
  const headers = await getApiHeaders(config.signal);

  const params = {
    ...(Cp.apiParams || {}),
    ...(config.params instanceof URLSearchParams
      ? Object.fromEntries(config.params)
      : config.params),
    v: new Date().getTime(),
    // Force the API to process the Craft headers if this is the first API request
    ...(headers ? {} : {processCraftHeaders: 1}),
  };

  return {
    ...config,
    headers: {...config.headers, ...headers},
    params,
  };
});

apiClient.interceptors.response.use(async (response) => {
  await processApiHeaders(response.headers, response.config.signal);
  return response;
});

export function sendApiRequest(
  method: string,
  uri: string,
  options: HttpRequestConfig = {}
) {
  return apiClient.request({
    method,
    url: uri,
    ...options,
  });
}
