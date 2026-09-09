import {t} from '@craftcms/ui';
import {FileUpload, type UploadOptions} from '@/upload-client';
import {UploadNotification} from './upload-notification';

declare const Craft: any;

interface UploadCallbacks<Result> {
  queued?: () => void;
  progress?: (loaded: number, total: number) => void;
  done?: (result: Result) => void;
  fail?: (error: unknown, canceled: boolean) => void;
  settled?: () => void;
}

export interface UploadJob<Result, Context = undefined> {
  upload: FileUpload<Result>;
  notification: UploadNotification;
  context: Context;
  callbacks: UploadCallbacks<Result>;
  queued: boolean;
  result?: Result;
}

/** Sequential transfers, retry notifications, and cancellation for one upload owner. */
export class UploadQueue<
  Result = unknown,
  Context = undefined,
> extends EventTarget {
  protected jobs = new Set<UploadJob<Result, Context>>();
  private queue: Promise<void> = Promise.resolve();

  get hasPending(): boolean {
    return this.jobs.size > 0;
  }

  add(
    file: File,
    options: UploadOptions,
    context: Context,
    callbacks: UploadCallbacks<Result> = {}
  ): UploadJob<Result, Context> {
    const job: UploadJob<Result, Context> = {
      context,
      callbacks,
      queued: false,
      upload: new FileUpload<Result>(file, {
        ...options,
        onProgress: (loaded, total) => {
          if (this.jobs.has(job)) {
            job.notification.updateProgress(loaded);
            callbacks.progress?.(loaded, total);
          }
        },
        onStateChange: (state) => {
          if (
            this.jobs.has(job) &&
            state !== 'completed' &&
            state !== 'canceled'
          ) {
            job.notification.updateState(state);
          }
        },
      }),
      notification: new UploadNotification(
        file,
        () => this.retry(job),
        () =>
          this.cancel(job).catch((error) =>
            Craft.cp.displayError(uploadErrorMessage(error))
          )
      ),
    };
    this.jobs.add(job);
    this.retry(job);
    return job;
  }

  retry(job: UploadJob<Result, Context>): void {
    if (
      !this.jobs.has(job) ||
      job.queued ||
      ['uploading', 'completing', 'completed', 'canceled'].includes(
        job.upload.state
      )
    ) {
      return;
    }
    job.queued = true;
    job.notification.updateState('ready', t('Waiting to upload.'));
    job.callbacks.queued?.();
    this.queue = this.queue
      .then(() => this.run(job))
      .catch((error) => reportError(error));
  }

  async cancel(job: UploadJob<Result, Context>): Promise<void> {
    if (!this.jobs.has(job) || job.upload.state === 'completing') {
      return;
    }
    this.complete(job);
    await job.upload.cancel();
  }

  async cancelAll(): Promise<void> {
    const jobs = [...this.jobs];
    const cancellations = jobs.map((job) => this.cancel(job));
    for (const job of jobs) {
      this.complete(job);
    }
    const results = await Promise.allSettled(cancellations);
    for (const result of results) {
      if (result.status === 'rejected') {
        Craft.cp.displayError(uploadErrorMessage(result.reason));
      }
    }
  }

  protected complete(job: UploadJob<Result, Context>): void {
    this.jobs.delete(job);
    job.notification.close();
  }

  protected succeeded(job: UploadJob<Result, Context>, result: Result): void {
    this.complete(job);
    job.callbacks.done?.(result);
  }

  private async run(job: UploadJob<Result, Context>): Promise<void> {
    try {
      if (!this.jobs.has(job)) {
        if (job.upload.state === 'canceled') {
          throw new DOMException('Upload canceled.', 'AbortError');
        }
        return;
      }
      const result = await job.upload.upload();
      if (this.jobs.has(job)) {
        this.succeeded(job, result);
      }
    } catch (error) {
      if (this.jobs.has(job)) {
        job.notification.updateState('failed', uploadErrorMessage(error));
        job.callbacks.fail?.(error, false);
      } else if (job.upload.state === 'canceled') {
        job.callbacks.fail?.(error, true);
      } else {
        throw error;
      }
    } finally {
      job.queued = false;
      job.callbacks.settled?.();
    }
  }
}

export function uploadErrorMessage(error: unknown): string {
  return error instanceof Error ? error.message : t('Upload failed.');
}
