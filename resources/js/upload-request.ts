import {useFetch} from '@/common/composables/useFetch';

export interface UploadRequestOptions {
  retry?: boolean;
  signal?: AbortSignal | null;
}

export class UploadError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly data: Record<string, unknown> = {}
  ) {
    super(message);
    this.name = 'UploadError';
  }
}

export function assertSameOrigin(url: string): void {
  if (new URL(url, location.href).origin !== location.origin) {
    throw new UploadError(
      'Upload control requests must use the current origin.',
      400
    );
  }
}

export async function uploadRequest<T>(
  url: string,
  method: string,
  data?: unknown,
  {
    retry = true,
    signal = null,
    headers = {},
  }: UploadRequestOptions & {
    headers?: Record<string, string>;
  } = {}
): Promise<T> {
  assertSameOrigin(url);

  let failure: UploadError | undefined;
  const request = useFetch<T>(url, {
    immediate: false,
    refetch: false,
    method,
    data,
    signal: signal ?? undefined,
    timeout: 120_000,
    headers: {Accept: 'application/json', ...headers},
    onError: (error) => {
      failure = responseError(
        error.response?.status ?? 0,
        error.response?.data ?? {message: error.message}
      );
    },
  });

  const retries = retry ? 3 : 0;

  for (let attempt = 0; ; attempt++) {
    signal?.throwIfAborted();

    try {
      failure = undefined;
      await request.execute();
      signal?.throwIfAborted();

      if (request.state.value === 'aborted') {
        throw new DOMException('Upload canceled.', 'AbortError');
      }

      if (request.state.value === 'error') {
        throw failure ?? new UploadError(String(request.error.value), 0);
      }

      return request.data.value as T;
    } catch (error) {
      if (
        attempt >= retries ||
        !(error instanceof UploadError) ||
        !retryableStatus(error.status)
      ) {
        throw error;
      }

      await delay(300 * 2 ** attempt, signal);
    }
  }
}

function retryableStatus(status: number): boolean {
  return [0, 408, 429].includes(status) || status >= 500;
}

export const requestFailed =
  'The upload request failed. Check the connection and storage configuration.';

export function responseError(status: number, response: unknown): UploadError {
  if (typeof response === 'string') {
    try {
      response = JSON.parse(response);
    } catch {
      // Storage errors and proxy failures may return XML or HTML.
    }
  }

  const data =
    response && typeof response === 'object'
      ? (response as Record<string, unknown>)
      : {};

  return new UploadError(
    typeof data.message === 'string' ? data.message : requestFailed,
    status,
    data
  );
}

function delay(
  milliseconds: number,
  signal: AbortSignal | null
): Promise<void> {
  signal?.throwIfAborted();

  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      signal?.removeEventListener('abort', abort);
      resolve();
    }, milliseconds);

    function abort() {
      clearTimeout(timer);
      reject(signal?.reason);
    }

    signal?.addEventListener('abort', abort, {once: true});
  });
}
