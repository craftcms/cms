import {BaseUploader} from './base-uploader';
import {UploadQueue} from './upload-queue';
import {UploadError} from '@/upload-client';
import {store} from '@/routes/craft/actions/craft/cp/uploads';

// blueimp jQuery File Upload plugin seam — see base-uploader.ts.
declare const $: any;
declare const Craft: any;

const DEFAULTS = {
  autoUpload: false,
  sequentialUploads: true,
  replaceFileInput: false,
  resolveConflictAction: 'assets/resolve-upload-conflict',
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
  private destroyed = false;
  private uploads = new UploadQueue();

  static override get defaults(): any {
    return {
      ...DEFAULTS,
      url: store.url(),
      maxFileSize: Craft.maxAssetUploadSize,
    };
  }

  constructor($element?: any, settings?: any) {
    super();
    if (new.target === Uploader) {
      this.init($element, settings);
    }
  }

  override init($element: any, settings: any): void {
    settings = $.extend({}, Uploader.defaults, settings);
    super.init($element, settings);
    delete this.settings.events;

    this.uploader = this.$element.fileupload(this.settings);

    Object.entries(this.events).forEach(([name, handler]) => {
      this.$element.on(name, handler);
    });

    this._onFileAdd = this.onFileAdd.bind(this);
    this.$element.on('fileuploadadd', this._onFileAdd);
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
        if (this.settings.enqueueUpload) {
          this.settings.enqueueUpload(file, data.originalFiles);
        } else {
          this.uploadFile(file, data);
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
    const job = this.uploads.add(
      file,
      {
        url: this.settings.url,
        parameters: {
          ...this.formData,
          ...(this.settings.replace ? {operation: 'replace'} : {}),
        },
        csrfToken: Craft.csrfTokenValue,
      },
      undefined,
      {
        queued: () => {
          if (++this._inProgressCounter === 1) {
            this.$element.trigger('fileuploadstart');
          }
        },
        progress: (loaded, total) =>
          this.$element.trigger('fileuploadprogressall', {loaded, total}),
        done: (result) => {
          data.result = result;
          this.$element.trigger('fileuploaddone', data);
        },
        fail: (error, canceled) => {
          if (this.destroyed) {
            return;
          }
          data.errorThrown = canceled ? 'abort' : 'error';
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
        },
        settled: () => {
          this._inProgressCounter--;
          if (!this.destroyed) {
            this.$element.trigger('fileuploadalways', data);
            if (this._inProgressCounter === 0) {
              this.$element.trigger('fileuploadstop');
            }
          }
        },
      }
    );
    data.abort = () => this.uploads.cancel(job);
    data.submit = () => this.uploads.retry(job);
  }

  override destroy(): void {
    this.destroyed = true;
    void this.uploads.cancelAll();

    if (this.uploader.fileupload('instance')) {
      this.uploader.fileupload('destroy');
    }

    this.$element.off('fileuploadadd', this._onFileAdd);

    Object.entries(this.events).forEach(([name, handler]) => {
      this.$element.off(name, handler);
    });
  }
}
