import {
  isCancel,
  isHttpError,
  type HttpClient,
  type HttpRequestConfig,
  type HttpResponse,
} from './http.js';

/**
 * The parts of an axios `CancelToken` that legacy callers pass in.
 */
export interface LegacyCancelToken {
  promise: Promise<unknown>;
  reason?: unknown;
}

/**
 * Request options accepted by `Craft.sendActionRequest()` and
 * `Craft.sendApiRequest()`, which historically took axios options.
 */
export interface LegacyRequestOptions extends HttpRequestConfig {
  /**
   * An axios cancel token, or an AbortSignal (what
   * `BaseElementIndex._createCancelToken()` returns now).
   *
   * @deprecated Pass an AbortSignal as `signal` instead.
   */
  cancelToken?: LegacyCancelToken | AbortSignal | null;
}

/**
 * Converts legacy options to client options, turning an axios-style
 * `cancelToken` into an AbortSignal.
 */
export function toHttpConfig(options: LegacyRequestOptions): HttpRequestConfig {
  const {cancelToken, ...config} = options;

  if (!cancelToken) {
    return config;
  }

  const controller = new AbortController();
  const {signal} = config;

  if (signal?.aborted) {
    controller.abort();
  } else {
    signal?.addEventListener('abort', () => controller.abort(), {once: true});
  }

  if (cancelToken instanceof AbortSignal) {
    if (cancelToken.aborted) {
      controller.abort();
    } else {
      cancelToken.addEventListener('abort', () => controller.abort(), {
        once: true,
      });
    }
  } else if (cancelToken.reason) {
    controller.abort();
  } else {
    void cancelToken.promise.then(() => controller.abort());
  }

  return {...config, signal: controller.signal};
}

/**
 * Adds the flags axios-era plugin code checks for, so existing `catch`
 * blocks keep working: `isAxiosError` on every request error, and
 * `__CANCEL__` on cancellations, which is what `axios.isCancel()` reads.
 *
 * @deprecated Plugins should use `isHttpError()` and `isCancel()`.
 */
export function markLegacyError<E>(error: E): E {
  if (isHttpError(error)) {
    Object.assign(error, {isAxiosError: true});

    if (isCancel(error)) {
      Object.assign(error, {__CANCEL__: true});
    }
  }

  return error;
}

/**
 * Sends a request on behalf of a legacy `Craft.*` request helper.
 *
 * @internal
 */
export function sendLegacyRequest<T = any>(
  client: HttpClient,
  options: LegacyRequestOptions
): Promise<HttpResponse<T>> {
  return client.request<T>(toHttpConfig(options)).catch((error: unknown) => {
    throw markLegacyError(error);
  });
}
