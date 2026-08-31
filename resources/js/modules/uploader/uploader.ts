import {BaseUploader} from './base-uploader';

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
    return this.uploader.fileupload('active');
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
        const fileExtension = matches[1];
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
        data.submit();
      }

      if (++this._totalFileCounter === data.originalFiles.length) {
        this._totalFileCounter = 0;
        this._validFileCounter = 0;
        this.processErrorMessages();
      }
    });

    return true;
  }

  override destroy(): void {
    if (this.uploader.fileupload('instance')) {
      this.uploader.fileupload('destroy');
    }

    this.$element.off('fileuploadadd', this._onFileAdd);

    Object.entries(this.events).forEach(([name, handler]) => {
      this.$element.off(name, handler);
    });
  }
}
