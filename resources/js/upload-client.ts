import Uppy from '@uppy/core';
import {TaskQueue} from '@uppy/core/utils';
import {configureTus} from './upload-transports/tus';
import {configureS3} from './upload-transports/s3';
import {assertSameOrigin, UploadError} from './upload-request';
import {useFetch} from '@/common/composables/useFetch';

export {UploadError} from './upload-request';

export type UploadSession = CraftCms.Cms.Filesystem.Data.UploadSessionData;

export type UploadState =
  | 'ready'
  | 'uploading'
  | 'paused'
  | 'completing'
  | 'completed'
  | 'failed'
  | 'canceled';

export interface UploadOptions {
  url: string;
  parameters?: Record<string, unknown>;
  csrfToken?: string;
  headers?: Record<string, string>;
  onProgress?: (loaded: number, total: number) => void;
  onStateChange?: (state: UploadState) => void;
}

interface UploadRequestOptions {
  retry?: boolean;
  signal?: AbortSignal | null;
}

export interface UploadTransportContext {
  uppy: Uppy;
  session: UploadSession;
  headers: Record<string, string>;
  request: <T>(
    url: string,
    method: string,
    data?: unknown,
    options?: UploadRequestOptions
  ) => Promise<T>;
  beginCompletion: () => void;
}

export type UploadTransport = (
  context: UploadTransportContext
) => void | Promise<void>;

const transports = new Map<string, UploadTransport>();
const transferQueues = new Map<string, TaskQueue>();

export function registerTransport(
  type: string,
  configure: UploadTransport
): void {
  transports.set(type, configure);
  transferQueues.delete(type);
}

/** Uploads Craft sessions, retaining completed parts for same-page retries. */
export class FileUpload<Result = Record<string, unknown>> {
  state: UploadState = 'ready';
  private session: UploadSession | null = null;
  private uppy: Uppy | null = null;
  private transferred = false;
  private result: {value: Result} | null = null;
  private running: Promise<Result> | null = null;
  private transferQueue: TaskQueue | undefined;
  private controller = new AbortController();

  constructor(
    public readonly file: File,
    private readonly options: UploadOptions
  ) {}

  upload(): Promise<Result> {
    if (this.running) {
      return this.running;
    }

    this.running = this.run().finally(() => {
      this.running = null;
    });

    return this.running;
  }

  get canPause(): boolean {
    return (
      this.state === 'uploading' &&
      !!this.uppy?.getState().capabilities.resumableUploads &&
      this.uppy
        .getFiles()
        .some(
          ({progress}) => progress.uploadStarted && !progress.uploadComplete
        )
    );
  }

  pause(): void {
    if (this.state === 'paused') {
      return;
    }
    if (!this.canPause) {
      throw new UploadError('This upload cannot be paused.', 409);
    }
    this.uppy!.pauseAll();
    this.setState('paused');
  }

  resume(): void {
    if (this.state !== 'paused') {
      return;
    }
    this.setState('uploading');
    this.uppy!.resumeAll();
  }

  async cancel(): Promise<void> {
    if (this.state === 'completed') {
      return;
    }

    if (this.state === 'completing') {
      throw new UploadError(
        'The file is being saved and can no longer be canceled.',
        409
      );
    }

    this.setState('canceled');
    this.controller.abort();
    this.uppy?.destroy();
    this.uppy = null;

    if (this.session) {
      await this.json(this.session.urls.cancel, 'DELETE', undefined, {
        signal: null,
      });
    }
  }

  private async run(): Promise<Result> {
    this.controller.signal.throwIfAborted();

    if (this.result) {
      return this.result.value;
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

      if (!this.transferred) {
        const {uploaded} = await this.json<{uploaded: boolean}>(
          session.urls.status,
          'GET'
        );
        if (!uploaded) {
          await this.transfer(session);
        }
        this.controller.signal.throwIfAborted();
        this.transferred = true;
        this.uppy?.destroy();
        this.uppy = null;
      }

      this.setState('completing');
      this.result = {
        value: await this.json<Result>(session.urls.complete, 'POST'),
      };
      this.setState('completed');

      return this.result.value;
    } catch (error) {
      if (!this.controller.signal.aborted) {
        this.setState('failed');
      }

      throw error;
    }
  }

  private async transfer(session: UploadSession): Promise<void> {
    let failure: unknown;
    const retrying = this.uppy !== null;
    const uppy = (this.uppy ??= new Uppy({autoProceed: false}));
    const onError = (
      _file: unknown,
      error: Error,
      response?: {status: number; body?: {xhr?: XMLHttpRequest}}
    ) => {
      failure = response?.body?.xhr
        ? responseError(response.status, response.body.xhr.responseText)
        : error;
    };
    uppy.on('upload-error', onError);

    if (!retrying) {
      uppy.on('upload-start', () => this.setState('uploading'));
      uppy.on('upload-progress', (_file, progress) => {
        this.options.onProgress?.(progress.bytesUploaded ?? 0, this.file.size);
      });

      try {
        const configure = transports.get(session.transport.type);
        if (!configure) {
          throw new UploadError(
            `No upload transport is registered for "${session.transport.type}".`,
            400
          );
        }
        await configure({
          uppy,
          session,
          headers: this.headers(),
          request: this.json.bind(this),
          beginCompletion: () => this.setState('completing'),
        });
        uppy.iteratePlugins((plugin) => {
          if (plugin.type === 'uploader' && 'limit' in plugin.opts) {
            if (!transferQueues.has(session.transport.type)) {
              transferQueues.set(
                session.transport.type,
                new TaskQueue({concurrency: plugin.opts.limit as number})
              );
            }
            this.transferQueue = transferQueues.get(session.transport.type);
          }
        });
        this.controller.signal.throwIfAborted();
        uppy.addFile({
          name: this.file.name,
          type: this.file.type,
          data: this.file,
        });
      } catch (error) {
        this.uppy = null;
        uppy.destroy();
        throw error;
      }
    }

    try {
      const upload = () => {
        this.controller.signal.throwIfAborted();
        this.setState('uploading');
        return retrying ? uppy.retryAll() : uppy.upload();
      };
      this.setState('ready');
      const result = await (this.transferQueue
        ? this.transferQueue.add(upload).abortOn(this.controller.signal)
        : upload());
      this.controller.signal.throwIfAborted();
      if (!result?.successful?.length) {
        throw failure ?? new UploadError(requestFailed, 0);
      }
    } finally {
      uppy.off('upload-error', onError);
    }
  }

  private headers(): Record<string, string> {
    return {
      ...this.options.headers,
      ...(this.options.csrfToken
        ? {'X-CSRF-TOKEN': this.options.csrfToken}
        : {}),
    };
  }

  private async json<T>(
    url: string,
    method: string,
    data?: unknown,
    {retry = true, signal = this.controller.signal}: UploadRequestOptions = {}
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
      headers: {Accept: 'application/json', ...this.headers()},
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

  private setState(state: UploadState): void {
    this.state = state;
    this.options.onStateChange?.(state);
  }
}

function retryableStatus(status: number): boolean {
  return [0, 408, 429].includes(status) || status >= 500;
}

const requestFailed =
  'The upload request failed. Check the connection and storage configuration.';

function responseError(status: number, response: unknown): UploadError {
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

registerTransport('tus', configureTus);
registerTransport('s3', configureS3);
