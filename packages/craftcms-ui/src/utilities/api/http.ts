/**
 * A small fetch-based HTTP client for CP requests.
 *
 * It replaces axios, and deliberately mirrors the subset of axios behavior that
 * Craft and its plugins rely on, so callers can move over without surprises:
 *
 * - Responses are `{data, status, statusText, headers, config}`, with header
 *   names lowercased.
 * - Non-2xx responses reject with an {@link HttpError} carrying `response`.
 * - JSON response bodies are parsed even without a JSON content type.
 * - Plain-object request bodies are sent as JSON; strings and
 *   `URLSearchParams` as a urlencoded form; `FormData` and `Blob` as-is.
 * - `params` are serialized with bracket notation (`a[]=1`, `a[b]=2`), which
 *   PHP parses back into arrays.
 * - Interceptors run as a promise chain around the request.
 */

export type HttpHeaders = Record<string, string | null | undefined>;
export type HttpParams = Record<string, unknown> | URLSearchParams;
export type HttpResponseType = 'json' | 'text' | 'blob' | 'arraybuffer';

export interface HttpRequestConfig<D = any> {
  url?: string;
  method?: string;
  baseURL?: string;
  params?: HttpParams;
  data?: D;
  headers?: HttpHeaders;
  signal?: AbortSignal;
  /** Milliseconds before the request is aborted. `0` means no timeout. */
  timeout?: number;
  responseType?: HttpResponseType;
  /** Which statuses resolve. `null` resolves every status. */
  validateStatus?: ((status: number) => boolean) | null;
  /** Free-form per-request state for interceptors, e.g. a retry marker. */
  meta?: Record<string, unknown>;
}

/** A request config after defaults are merged in. */
export interface ResolvedHttpRequestConfig<
  D = any,
> extends HttpRequestConfig<D> {
  headers: HttpHeaders;
}

export interface HttpResponse<T = any, D = any> {
  data: T;
  status: number;
  statusText: string;
  headers: Record<string, string>;
  config: ResolvedHttpRequestConfig<D>;
}

export type HttpErrorCode =
  | 'ERR_BAD_REQUEST'
  | 'ERR_BAD_RESPONSE'
  | 'ERR_NETWORK'
  | 'ERR_CANCELED'
  | 'ECONNABORTED';

export class HttpError<T = any, D = any> extends Error {
  readonly isHttpError = true;
  code: HttpErrorCode;
  config: ResolvedHttpRequestConfig<D>;
  response?: HttpResponse<T, D>;

  constructor(
    message: string,
    code: HttpErrorCode,
    config: ResolvedHttpRequestConfig<D>,
    response?: HttpResponse<T, D>
  ) {
    super(message);
    this.name = 'HttpError';
    this.code = code;
    this.config = config;
    this.response = response;
  }
}

/** Thrown when a request is aborted through its `signal`. */
export class HttpCancelledError<D = any> extends HttpError<never, D> {
  constructor(config: ResolvedHttpRequestConfig<D>) {
    super('canceled', 'ERR_CANCELED', config);
    this.name = 'HttpCancelledError';
  }
}

export function isHttpError<T = any, D = any>(
  error: unknown
): error is HttpError<T, D> {
  return (
    error instanceof HttpError ||
    (typeof error === 'object' &&
      error !== null &&
      (error as {isHttpError?: unknown}).isHttpError === true)
  );
}

export function isCancel(error: unknown): error is HttpCancelledError {
  return isHttpError(error) && error.code === 'ERR_CANCELED';
}

type Fulfilled<V> = (value: V) => V | Promise<V>;
type Rejected = (error: any) => any;

interface InterceptorHandler<V> {
  fulfilled?: Fulfilled<V>;
  rejected?: Rejected;
}

export class InterceptorManager<V> {
  handlers: Array<InterceptorHandler<V> | null> = [];

  /** Registers an interceptor and returns an id for {@link eject}. */
  use(fulfilled?: Fulfilled<V>, rejected?: Rejected): number {
    this.handlers.push({fulfilled, rejected});
    return this.handlers.length - 1;
  }

  eject(id: number): void {
    this.handlers[id] = null;
  }

  forEach(fn: (handler: InterceptorHandler<V>) => void): void {
    this.handlers.forEach((handler) => handler && fn(handler));
  }
}

const DEFAULT_ACCEPT = 'application/json, text/plain, */*';

function defaultValidateStatus(status: number): boolean {
  return status >= 200 && status < 300;
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  if (typeof value !== 'object' || value === null) {
    return false;
  }
  const proto = Object.getPrototypeOf(value);
  return proto === Object.prototype || proto === null;
}

function isVisitable(value: unknown): boolean {
  return Array.isArray(value) || isPlainObject(value);
}

function convertValue(value: unknown): string {
  if (value instanceof Date) {
    return value.toISOString();
  }
  return String(value);
}

function renderKey(path: Array<string | number>): string {
  return path
    .map((token, i) => {
      const key = String(token).replace(/\[\]$/, '');
      return i ? `[${key}]` : key;
    })
    .join('');
}

/**
 * Flattens params into key/value pairs using the same rules as axios: a
 * top-level flat array becomes `key[]=…`, anything nested gets bracketed
 * keys with indexes for arrays, and `null`/`undefined` values are dropped.
 */
function flattenParams(
  params: Record<string, unknown>
): Array<[string, string]> {
  const pairs: Array<[string, string]> = [];

  const visit = (value: unknown, path: Array<string | number>) => {
    if (isVisitable(value)) {
      Object.entries(value as object).forEach(([key, child]) => {
        if (child !== undefined && child !== null) {
          visit(child, [
            ...path,
            Array.isArray(value) ? Number(key) : key.trim(),
          ]);
        }
      });
      return;
    }
    pairs.push([renderKey(path), convertValue(value)]);
  };

  Object.entries(params).forEach(([rawKey, value]) => {
    if (value === undefined || value === null) {
      return;
    }
    const key = rawKey.trim();
    if (Array.isArray(value) && !value.some(isVisitable)) {
      const name = key.replace(/\[\]$/, '');
      value.forEach((item) => {
        if (item !== undefined && item !== null) {
          pairs.push([`${name}[]`, convertValue(item)]);
        }
      });
      return;
    }
    visit(value, [key]);
  });

  return pairs;
}

function encode(value: string): string {
  return encodeURIComponent(value)
    .replace(/%3A/gi, ':')
    .replace(/%24/g, '$')
    .replace(/%2C/gi, ',')
    .replace(/%20/g, '+');
}

function serializeParams(params: HttpParams): string {
  if (params instanceof URLSearchParams) {
    return params.toString();
  }
  return flattenParams(params)
    .map(([key, value]) => `${encode(key)}=${encode(value)}`)
    .join('&');
}

function isAbsoluteUrl(url: string): boolean {
  return /^([a-z][a-z\d+\-.]*:)?\/\//i.test(url);
}

function buildUrl(config: HttpRequestConfig): string {
  let url = config.url ?? '';

  if (config.baseURL && !isAbsoluteUrl(url)) {
    url = url
      ? `${config.baseURL.replace(/\/?\/$/, '')}/${url.replace(/^\/+/, '')}`
      : config.baseURL;
  }

  const query = config.params ? serializeParams(config.params) : '';
  if (query) {
    const hashIndex = url.indexOf('#');
    if (hashIndex !== -1) {
      url = url.slice(0, hashIndex);
    }
    url += (url.includes('?') ? '&' : '?') + query;
  }

  return url;
}

function buildHeaders(headers: HttpHeaders): Headers {
  // Set entries one at a time so a later key overrides an earlier one that
  // differs only in case, rather than being joined into one value.
  const result = new Headers();
  Object.entries(headers).forEach(([name, value]) => {
    if (value === null || value === undefined) {
      result.delete(name);
    } else {
      result.set(name, value);
    }
  });
  return result;
}

function buildBody(data: unknown, headers: Headers): BodyInit | undefined {
  if (data === undefined || data === null) {
    return undefined;
  }

  if (typeof data === 'string') {
    // Like axios: serialized forms are the common case for string bodies.
    if (!headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/x-www-form-urlencoded');
    }
    return data;
  }

  if (
    data instanceof FormData ||
    data instanceof Blob ||
    data instanceof ArrayBuffer ||
    ArrayBuffer.isView(data)
  ) {
    return data as BodyInit;
  }

  if (data instanceof URLSearchParams) {
    if (!headers.has('Content-Type')) {
      headers.set(
        'Content-Type',
        'application/x-www-form-urlencoded;charset=utf-8'
      );
    }
    return data.toString();
  }

  if (
    headers.get('Content-Type')?.includes('application/x-www-form-urlencoded')
  ) {
    return serializeParams(data as Record<string, unknown>);
  }

  if (!headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }
  return JSON.stringify(data);
}

async function readBody(
  response: Response,
  responseType: HttpResponseType | undefined
): Promise<unknown> {
  switch (responseType) {
    case 'blob':
      return response.blob();
    case 'arraybuffer':
      return response.arrayBuffer();
    case 'text':
      return response.text();
  }

  const text = await response.text();
  if (!text) {
    return text;
  }
  try {
    return JSON.parse(text);
  } catch (e) {
    if (responseType === 'json') {
      throw e;
    }
    return text;
  }
}

function responseHeaders(response: Response): Record<string, string> {
  const headers: Record<string, string> = {};
  response.headers.forEach((value, name) => {
    headers[name.toLowerCase()] = value;
  });
  return headers;
}

function mergeConfig<D>(
  defaults: HttpRequestConfig,
  config: HttpRequestConfig<D>
): ResolvedHttpRequestConfig<D> {
  return {
    ...defaults,
    ...config,
    headers: {...defaults.headers, ...config.headers},
  } as ResolvedHttpRequestConfig<D>;
}

async function dispatch<T, D>(
  config: ResolvedHttpRequestConfig<D>
): Promise<HttpResponse<T, D>> {
  if (config.signal?.aborted) {
    throw new HttpCancelledError(config);
  }

  const controller = new AbortController();
  const onAbort = () => controller.abort();
  config.signal?.addEventListener('abort', onAbort, {once: true});

  let timedOut = false;
  const timer = config.timeout
    ? setTimeout(() => {
        timedOut = true;
        controller.abort();
      }, config.timeout)
    : null;

  const method = (config.method ?? 'get').toUpperCase();
  const headers = buildHeaders(config.headers);
  const body =
    method === 'GET' || method === 'HEAD'
      ? undefined
      : buildBody(config.data, headers);

  try {
    let fetchResponse: Response;
    try {
      fetchResponse = await fetch(buildUrl(config), {
        method,
        headers,
        body,
        credentials: 'same-origin',
        signal: controller.signal,
      });
    } catch (e) {
      if (timedOut) {
        throw new HttpError(
          `timeout of ${config.timeout}ms exceeded`,
          'ECONNABORTED',
          config
        );
      }
      if (controller.signal.aborted) {
        throw new HttpCancelledError(config);
      }
      throw new HttpError(
        e instanceof Error ? e.message : 'Network Error',
        'ERR_NETWORK',
        config
      );
    }

    let data: unknown;
    try {
      data = await readBody(fetchResponse, config.responseType);
    } catch (e) {
      if (controller.signal.aborted && !timedOut) {
        throw new HttpCancelledError(config);
      }
      throw new HttpError(
        e instanceof Error ? e.message : 'Invalid response body',
        'ERR_BAD_RESPONSE',
        config
      );
    }

    const response: HttpResponse<T, D> = {
      data: data as T,
      status: fetchResponse.status,
      statusText: fetchResponse.statusText,
      headers: responseHeaders(fetchResponse),
      config,
    };

    const validateStatus =
      config.validateStatus === undefined
        ? defaultValidateStatus
        : config.validateStatus;

    if (validateStatus && !validateStatus(response.status)) {
      throw new HttpError(
        `Request failed with status code ${response.status}`,
        response.status >= 500 ? 'ERR_BAD_RESPONSE' : 'ERR_BAD_REQUEST',
        config,
        response
      );
    }

    return response;
  } finally {
    if (timer) {
      clearTimeout(timer);
    }
    config.signal?.removeEventListener('abort', onAbort);
  }
}

export class HttpClient {
  defaults: ResolvedHttpRequestConfig;
  interceptors = {
    request: new InterceptorManager<ResolvedHttpRequestConfig>(),
    response: new InterceptorManager<HttpResponse>(),
  };

  constructor(defaults: HttpRequestConfig = {}) {
    this.defaults = {
      ...defaults,
      headers: {Accept: DEFAULT_ACCEPT, ...defaults.headers},
    };
  }

  /**
   * Sends a request. Request interceptors run in the order they were
   * registered, then the request is sent, then response interceptors run.
   */
  request<T = any, R = HttpResponse<T>, D = any>(
    config: HttpRequestConfig<D>
  ): Promise<R> {
    let chain: Promise<any> = Promise.resolve(
      mergeConfig(this.defaults, config)
    );

    this.interceptors.request.forEach(({fulfilled, rejected}) => {
      chain = chain.then(fulfilled, rejected);
    });

    chain = chain.then((resolved: ResolvedHttpRequestConfig<D>) =>
      dispatch<T, D>(resolved)
    );

    this.interceptors.response.forEach(({fulfilled, rejected}) => {
      chain = chain.then(fulfilled, rejected);
    });

    return chain;
  }

  get<T = any, R = HttpResponse<T>>(url: string, config?: HttpRequestConfig) {
    return this.request<T, R>({...config, url, method: 'get'});
  }

  delete<T = any, R = HttpResponse<T>>(
    url: string,
    config?: HttpRequestConfig
  ) {
    return this.request<T, R>({...config, url, method: 'delete'});
  }

  head<T = any, R = HttpResponse<T>>(url: string, config?: HttpRequestConfig) {
    return this.request<T, R>({...config, url, method: 'head'});
  }

  post<T = any, R = HttpResponse<T>, D = any>(
    url: string,
    data?: D,
    config?: HttpRequestConfig<D>
  ) {
    return this.request<T, R, D>({...config, url, data, method: 'post'});
  }

  put<T = any, R = HttpResponse<T>, D = any>(
    url: string,
    data?: D,
    config?: HttpRequestConfig<D>
  ) {
    return this.request<T, R, D>({...config, url, data, method: 'put'});
  }

  patch<T = any, R = HttpResponse<T>, D = any>(
    url: string,
    data?: D,
    config?: HttpRequestConfig<D>
  ) {
    return this.request<T, R, D>({...config, url, data, method: 'patch'});
  }
}

/**
 * Creates a client. Its defaults are copied, so later changes to another
 * client's defaults don't affect it.
 */
export function createHttpClient(defaults: HttpRequestConfig = {}): HttpClient {
  return new HttpClient(defaults);
}

/** The shared client for requests that don't need a dedicated instance. */
export const http = createHttpClient();
