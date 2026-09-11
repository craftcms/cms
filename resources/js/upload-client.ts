import type Uppy from '@uppy/core';
import {
  transportInstance,
  type UploadSession,
} from './upload-transports/registry';
import {
  uploadRequest,
  responseError,
  requestFailed,
  UploadError,
  type UploadRequestOptions,
} from './upload-request';

export {UploadError} from './upload-request';
export {registerTransport} from './upload-transports/registry';
export type {
  UploadSession,
  PrepareUpload,
  UploadTransport,
  UploadTransportContext,
} from './upload-transports/registry';

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

/** Uploads Craft sessions, retaining completed parts for same-page retries. */
export class FileUpload<Result = Record<string, unknown>> {
  state: UploadState = 'ready';

  private session: UploadSession | null = null;
  private uppy: Uppy | null = null;
  private transferred = false;
  private result: {value: Result} | null = null;
  private running: Promise<Result> | null = null;
  private fileId: string | null = null;
  private cleanup: (() => void) | void = undefined;
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
    const file = this.fileId ? this.uppy?.getFile(this.fileId) : undefined;

    return (
      this.state === 'uploading' &&
      !!this.uppy?.getState().capabilities.resumableUploads &&
      !!file?.progress.uploadStarted &&
      !file.progress.uploadComplete
    );
  }

  pause(): void {
    if (this.state === 'paused') {
      return;
    }

    if (!this.canPause) {
      throw new UploadError('This upload cannot be paused.', 409);
    }

    this.uppy!.pauseResume(this.fileId!);
    this.setState('paused');
  }

  resume(): void {
    if (this.state !== 'paused') {
      return;
    }

    this.setState('uploading');
    this.uppy!.pauseResume(this.fileId!);
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
    this.releaseFile();

    if (this.session) {
      await this.request(this.session.urls.cancel, 'DELETE', undefined, {
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
        this.session = await this.request<UploadSession>(
          this.options.url,
          'POST',
          {
            ...this.options.parameters,
            filename: this.file.name,
            size: this.file.size,
          }
        );
      }

      const session = this.session;

      if (!this.transferred) {
        const {uploaded} = await this.request<{uploaded: boolean}>(
          session.urls.status,
          'GET'
        );

        if (!uploaded) {
          await this.transfer(session);
        }

        this.controller.signal.throwIfAborted();
        this.transferred = true;
        this.releaseFile();
      }

      this.setState('completing');
      this.result = {
        value: await this.request<Result>(session.urls.complete, 'POST'),
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
    if (!this.uppy) {
      const {uppy, prepare} = transportInstance(session.transport.type);
      this.controller.signal.throwIfAborted();
      this.uppy = uppy;

      try {
        this.fileId = uppy.addFile({
          name: this.file.name,
          type: this.file.type,
          data: this.file,
        });

        if (prepare) {
          this.cleanup = prepare(this.fileId, {
            session,
            headers: this.headers(),
            request: this.request.bind(this),
            beginCompletion: () => this.setState('completing'),
          });
        }
      } catch (error) {
        this.releaseFile();

        throw error;
      }
    }

    const uppy = this.uppy;
    const fileId = this.fileId!;

    const onProgress = (
      file: ReturnType<Uppy['getFile']> | undefined,
      progress: {bytesUploaded: number | null}
    ) => {
      if (file?.id === fileId) {
        this.options.onProgress?.(progress.bytesUploaded ?? 0, this.file.size);
      }
    };

    let failure: unknown;
    const onError = (
      file: ReturnType<Uppy['getFile']> | undefined,
      error: Error,
      response?: {status: number; body?: {xhr?: XMLHttpRequest}}
    ) => {
      if (file?.id === fileId) {
        failure = response?.body?.xhr
          ? responseError(response.status, response.body.xhr.responseText)
          : error;
      }
    };

    const {signal} = this.controller;
    let onAbort = () => {};
    const canceled = new Promise<never>((_resolve, reject) => {
      onAbort = () => reject(signal.reason);
      signal.addEventListener('abort', onAbort, {once: true});
    });

    uppy.on('upload-progress', onProgress);
    uppy.on('upload-error', onError);

    try {
      signal.throwIfAborted();

      // upload() also retries other failed files; retryUpload() starts only this file.
      const result = await Promise.race([uppy.retryUpload(fileId), canceled]);

      signal.throwIfAborted();
      if (!result?.successful?.length) {
        throw failure ?? new UploadError(requestFailed, 0);
      }
    } finally {
      signal.removeEventListener('abort', onAbort);
      uppy.off('upload-progress', onProgress);
      uppy.off('upload-error', onError);
    }
  }

  private releaseFile(): void {
    if (this.fileId) {
      this.uppy?.removeFile(this.fileId);
      this.cleanup?.();
    }

    this.cleanup = undefined;
    this.fileId = null;
    this.uppy = null;
  }

  private headers(): Record<string, string> {
    return {
      ...this.options.headers,
      ...(this.options.csrfToken
        ? {'X-CSRF-TOKEN': this.options.csrfToken}
        : {}),
    };
  }

  private request<T>(
    url: string,
    method: string,
    data?: unknown,
    {signal = this.controller.signal, ...options}: UploadRequestOptions = {}
  ): Promise<T> {
    return uploadRequest<T>(url, method, data, {
      headers: this.headers(),
      signal,
      ...options,
    });
  }

  private setState(state: UploadState): void {
    this.state = state;
    this.options.onStateChange?.(state);
  }
}
