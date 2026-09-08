import {BaseUploader} from './base-uploader';
import {UploadNotification} from './upload-notification';
import {AssetUpload, UploadError} from '@/upload-client';
import {store} from '@/routes/craft/actions/craft/cp/uploads';
import {upload, replaceFile} from '@actions/Assets/UploadController';

// blueimp jQuery File Upload plugin seam — see base-uploader.ts.
declare const $: any;
declare const Craft: any;

const DEFAULTS = {
  autoUpload: false,
  sequentialUploads: true,
  // Resolved from `Craft.maxUploadSize` via the static getter (see below).
  // SAFETY: Upload size is nullable until the runtime Craft config is available.
  maxFileSize: null as number | null,
  replaceFileInput: false,
  createAction: 'assets/upload',
  replaceAction: 'assets/replace-file',
  deleteAction: 'assets/delete-asset',
};

/**
 * Uploader — a port of `Craft.Uploader` onto {@link BaseUploader}. Wires the
 * blueimp jQuery File Upload plugin (`$element.fileupload(...)`) to Craft's
 * validation (allowed kinds, max size, field limits) before submitting each
 * file. The default uploader class for `Craft.createUploader`.
 */
export class Uploader extends BaseUploader {
  uploader: any = null;
  _totalFileCounter = 0;
  _validFileCounter = 0;
  _onFileAdd: any = null;
  private sessionUploads = false;
  private destroyed = false;
  private queue: Promise<void> = Promise.resolve();
  private queued = new Set<AssetUpload>();
  private uploads = new Map<AssetUpload, UploadNotification>();

  static override get defaults(): any {
    return {...DEFAULTS, maxFileSize: Craft.maxUploadSize};
  }

  constructor($element?: any, settings?: any) {
    super();
    if (new.target === Uploader) {
      this.init($element, settings);
    }
  }

  override init($element: any, settings: any): void {
    const customMaxFileSize = settings?.maxFileSize;
    settings = $.extend({}, Uploader.defaults, settings);
    super.init($element, settings);
    delete this.settings.events;

    const target = new URL(this.settings.url, location.href);
    const expected = new URL(
      this.settings.replace ? replaceFile.url() : upload.url(),
      location.href
    );
    this.sessionUploads =
      this.settings.uploadSessions !== false &&
      (target.pathname === expected.pathname ||
        target.searchParams.get('action') ===
          (this.settings.replace ? 'assets/replace-file' : 'assets/upload'));

    if (this.sessionUploads && customMaxFileSize == null) {
      this.settings.maxFileSize = Craft.maxAssetUploadSize;
    }

    this.uploader = this.$element.fileupload(this.settings);

    Object.entries(this.events).forEach(([name, handler]) => {
      this.$element.on(name, handler);
    });

    this._onFileAdd = this.onFileAdd.bind(this);
    this.$element.on('fileuploadadd', this._onFileAdd);
  }

  /**
   * Set uploader parameters.
   */
  override setParams(paramObject: any): void {
    super.setParams(paramObject);

    // Only set params if the uploader has been initialized
    // It won't be if the input is disabled
    if (this.uploader.data('blueimpFileupload')) {
      this.uploader.fileupload('option', {formData: this.formData});
    }
  }

  /**
   * Get the number of uploads in progress.
   */
  override getInProgress(): number {
    return this.sessionUploads
      ? this._inProgressCounter
      : this.uploader.fileupload('active');
  }

  /**
   * Called on file add.
   */
  onFileAdd(e: any, data: any): boolean {
    e.stopPropagation();

    let validateExtension = false;

    if (this.allowedKinds) {
      if (!this._extensionList) {
        this._createExtensionList();
      }

      validateExtension = true;
    }

    // Make sure that file API is there before relying on it
    data.process().done(() => {
      const file = data.files[0];
      let pass = true;
      if (validateExtension) {
        const matches = file.name.match(/\.([a-z0-4_]+)$/i);
        const fileExtension = matches?.[1] ?? '';
        if (
          $.inArray(fileExtension.toLowerCase(), this._extensionList) === -1
        ) {
          pass = false;
          this._rejectedFiles.type.push('“' + file.name + '”');
        }
      }

      if (file.size > this.settings.maxFileSize) {
        this._rejectedFiles.size.push('“' + file.name + '”');
        pass = false;
      }

      // If the validation has passed for this file up to now, check if we're not hitting any limits
      if (
        pass &&
        this.settings.canAddMoreFiles instanceof Function &&
        !this.settings.canAddMoreFiles(this._validFileCounter)
      ) {
        this._rejectedFiles.limit.push('“' + file.name + '”');
        pass = false;
      }

      if (pass) {
        this._validFileCounter++;
        if (this.sessionUploads) {
          this.uploadFile(file, data);
        } else {
          data.submit();
        }
      }

      if (++this._totalFileCounter === data.originalFiles.length) {
        this._totalFileCounter = 0;
        this._validFileCounter = 0;
        this.processErrorMessages();
      }
    });

    return true;
  }

  private uploadFile(file: File, data: any): void {
    const task = new AssetUpload(file, {
      url: store.url(),
      parameters: {
        ...this.formData,
        operation: this.settings.replace ? 'replace' : 'upload',
      },
      csrfToken: Craft.csrfTokenValue,
      onProgress: (loaded, total) => {
        this.uploads.get(task)?.updateProgress(loaded);
        this.$element.trigger('fileuploadprogressall', {loaded, total});
      },
      onStateChange: (state) => this.uploads.get(task)?.updateState(state),
    });

    const cancel = async () => {
      await task.cancel();
      this.uploads.delete(task);
    };

    const notification = new UploadNotification(
      file,
      () => this.enqueue(task, data),
      () => {
        cancel().catch((error: Error) => Craft.cp.displayError(error.message));
      }
    );
    this.uploads.set(task, notification);
    data.abort = cancel;
    data.submit = () => this.enqueue(task, data);
    this.enqueue(task, data);
  }

  private enqueue(task: AssetUpload, data: any): void {
    if (
      this.destroyed ||
      this.queued.has(task) ||
      ['uploading', 'completing', 'completed', 'canceled'].includes(task.state)
    ) {
      return;
    }

    this.queued.add(task);
    this._inProgressCounter++;
    if (this._inProgressCounter === 1) {
      this.$element.trigger('fileuploadstart');
    }

    this.queue = this.queue
      .then(async () => {
        try {
          if (this.destroyed) {
            return;
          }

          data.result = await task.upload();
          this.uploads.delete(task);
          this.$element.trigger('fileuploaddone', data);
        } catch (error) {
          if (!this.destroyed) {
            data.errorThrown = task.state === 'canceled' ? 'abort' : 'error';
            data.jqXHR = {
              responseJSON:
                error instanceof UploadError
                  ? {message: error.message, ...error.data}
                  : {
                      message:
                        error instanceof Error
                          ? error.message
                          : Craft.t('app', 'Upload failed.'),
                    },
            };
            this.$element.trigger('fileuploadfail', data);
          }
        } finally {
          this.queued.delete(task);
          if (!this.destroyed) {
            this.$element.trigger('fileuploadalways', data);
          }
          this._inProgressCounter--;
          if (!this.destroyed && this._inProgressCounter === 0) {
            this.$element.trigger('fileuploadstop');
          }
        }
      })
      .catch((error) => reportError(error));
  }

  override destroy(): void {
    this.destroyed = true;
    for (const [task, notification] of this.uploads) {
      if (task.state !== 'completing') {
        task
          .cancel()
          .catch((error: Error) => Craft.cp.displayError(error.message));
      }
      notification.close();
    }
    this.uploads.clear();

    if (this.uploader.fileupload('instance')) {
      this.uploader.fileupload('destroy');
    }

    this.$element.off('fileuploadadd', this._onFileAdd);

    Object.entries(this.events).forEach(([name, handler]) => {
      this.$element.off(name, handler);
    });
  }
}
