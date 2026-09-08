import axios, {type AxiosRequestConfig, type AxiosResponse} from 'axios';

const client = axios.create({adapter: 'xhr'});

export type UploadSession = CraftCms.Cms.Asset.Data.UploadSessionData;
export type UploadPartRequest = CraftCms.Cms.Asset.Data.UploadPartRequest;
export type UploadResult = Omit<CraftCms.Cms.Asset.Data.UploadResult, 'status'>;

export type UploadState =
  | 'ready'
  | 'uploading'
  | 'completing'
  | 'completed'
  | 'failed'
  | 'canceled';

export interface UploadOptions {
  url: string;
  parameters?: Record<string, unknown>;
  csrfToken?: string;
  headers?: Record<string, string>;
  retries?: 0 | 1 | 2 | 3;
  timeout?: number;
  onProgress?: (loaded: number, total: number) => void;
  onStateChange?: (state: UploadState) => void;
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

/** Uploads Craft asset sessions, retaining completed parts for same-page retries. */
export class AssetUpload {
  state: UploadState = 'ready';
  private session: UploadSession | null = null;
  private completedParts = 0;
  private result: UploadResult | null = null;
  private running: Promise<UploadResult> | null = null;
  private controller = new AbortController();

  constructor(
    public readonly file: File,
    private readonly options: UploadOptions
  ) {}

  upload(): Promise<UploadResult> {
    if (this.running) {
      return this.running;
    }

    this.running = this.run().finally(() => {
      this.running = null;
    });

    return this.running;
  }

  async cancel(): Promise<void> {
    if (this.state === 'completed') {
      return;
    }

    if (this.state === 'completing') {
      throw new UploadError(
        'The asset is being saved and can no longer be canceled.',
        409
      );
    }

    this.setState('canceled');
    this.controller.abort();

    if (this.session) {
      await this.json(this.session.urls.cancel, 'DELETE', undefined, {
        signal: null,
      });
    }
  }

  private async run(): Promise<UploadResult> {
    this.controller.signal.throwIfAborted();

    if (this.result) {
      return this.result;
    }

    this.setState('uploading');

    try {
      if (!this.session) {
        this.session = await this.json<UploadSession>(
          this.options.url,
          'POST',
          {
            ...this.options.parameters,
            filename: this.file.name,
            size: this.file.size,
          },
          {retry: false}
        );

        if (
          !Number.isSafeInteger(this.session.chunkSize) ||
          this.session.chunkSize < 1 ||
          this.session.partCount !==
            Math.max(1, Math.ceil(this.file.size / this.session.chunkSize))
        ) {
          throw new UploadError(
            'The server returned an invalid upload session.',
            502
          );
        }
      }

      const session = this.session;

      for (
        let part = this.completedParts + 1;
        part <= session.partCount;
        part++
      ) {
        const offset = (part - 1) * session.chunkSize;
        const bytes = this.file.slice(offset, offset + session.chunkSize);
        await this.request(
          async () => {
            const request = await this.json<UploadPartRequest>(
              session.urls.part,
              'POST',
              {part}
            );

            return {
              ...request,
              data: bytes,
              headers: {
                Accept: false,
                'Content-Type': false,
                ...request.headers,
              },
              withXSRFToken: false,
              withCredentials: false,
              onUploadProgress: ({loaded}) => {
                this.options.onProgress?.(
                  offset + Math.min(loaded, bytes.size),
                  this.file.size
                );
              },
            };
          },
          {retryForbidden: true}
        );

        this.completedParts = part;
        this.options.onProgress?.(offset + bytes.size, this.file.size);
      }

      this.setState('completing');
      this.result = await this.json<UploadResult>(
        session.urls.complete,
        'POST'
      );
      this.setState('completed');

      return this.result;
    } catch (error) {
      if (!this.controller.signal.aborted) {
        this.setState('failed');
      }

      throw uploadError(error);
    }
  }

  private async request<T>(
    prepare: () => AxiosRequestConfig | Promise<AxiosRequestConfig>,
    {
      retry = true,
      retryForbidden = false,
      signal = this.controller.signal,
    }: {
      retry?: boolean;
      retryForbidden?: boolean;
      signal?: AbortSignal | null;
    } = {}
  ): Promise<AxiosResponse<T>> {
    const retries = retry ? (this.options.retries ?? 3) : 0;

    for (let attempt = 0; ; attempt++) {
      signal?.throwIfAborted();
      const config = await prepare();

      try {
        return await client.request<T>({
          ...config,
          signal: signal ?? undefined,
          timeout: this.options.timeout ?? 120_000,
        });
      } catch (error) {
        const failure = uploadError(error);
        if (
          attempt >= retries ||
          !(failure instanceof UploadError) ||
          !(
            retryableStatus(failure.status) ||
            (retryForbidden && failure.status === 403)
          )
        ) {
          throw failure;
        }

        await delay(300 * 2 ** attempt, signal);
      }
    }
  }

  private async json<T>(
    url: string,
    method: string,
    data?: unknown,
    {
      retry = true,
      signal = this.controller.signal,
    }: {retry?: boolean; signal?: AbortSignal | null} = {}
  ): Promise<T> {
    if (new URL(url, location.href).origin !== location.origin) {
      throw new UploadError(
        'Upload control requests must use the current origin.',
        400
      );
    }

    const response = await this.request<T>(
      () => ({
        url,
        method,
        data,
        responseType: 'json',
        headers: {
          Accept: 'application/json',
          ...this.options.headers,
          ...(this.options.csrfToken
            ? {'X-CSRF-TOKEN': this.options.csrfToken}
            : {}),
        },
      }),
      {retry, signal}
    );

    return response.data;
  }

  private setState(state: UploadState): void {
    this.state = state;
    this.options.onStateChange?.(state);
  }
}

function retryableStatus(status: number): boolean {
  return [0, 408, 429].includes(status) || status >= 500;
}

function uploadError(error: unknown): unknown {
  if (axios.isCancel(error)) {
    return new DOMException('Aborted', 'AbortError');
  }

  if (axios.isAxiosError(error)) {
    const response = error.response?.data;
    const data = response && typeof response === 'object' ? response : {};
    return new UploadError(
      data.message ??
        (error.code === 'ECONNABORTED'
          ? 'The upload request timed out.'
          : 'The upload request failed. Check the connection and storage configuration.'),
      error.response?.status ?? 0,
      data
    );
  }

  return error;
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
