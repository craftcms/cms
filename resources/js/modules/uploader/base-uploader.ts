import {Base} from '@craftcms/garnish';

// jQuery is only the transport here: the blueimp jQuery File Upload plugin
// (`$element.fileupload(...)`) has no modern equivalent, so the uploader keeps
// the `$` seam. Craft.cp/Craft.fileKinds are runtime CP globals.
declare const $: any;
declare const Craft: any;

const DEFAULTS = {
  dropZone: null,
  pasteZone: null,
  fileInput: null,
  // Resolved from `Craft.maxUploadSize` at init (see the static `defaults`
  // getter) so module load order can't capture an undefined value.
  // SAFETY: Upload size is nullable until the runtime Craft config is available.
  maxFileSize: null as number | null,
  allowedKinds: null,
  events: {},
  formData: {},
  canAddMoreFiles: null,
  headers: {Accept: 'application/json;q=0.9,*/*;q=0.8'},
  paramName: 'assets-upload',
  url: null,
  createAction: null,
  replaceAction: null,
  deleteAction: null,
  replace: false,
};

/**
 * BaseUploader — a port of `Craft.BaseUploader` onto `@craftcms/garnish` `Base`.
 * The shared uploader plumbing (settings, rejected-file bookkeeping, error
 * messaging, extension lists); {@link Uploader} adds the blueimp integration.
 * Instantiated via the `Craft.createUploader` seam, so exposed on `window.Craft`.
 */
export class BaseUploader extends Base {
  declare settings: any;

  static get defaults(): any {
    return {...DEFAULTS, maxFileSize: Craft.maxUploadSize};
  }

  allowedKinds: any = null;
  $element: any = null;
  $fileInput: any = null;
  fsType: any = null;
  formData: any = {};
  events: any = {};
  _rejectedFiles: any = {};
  _extensionList: any = null;
  _inProgressCounter = 0;

  constructor($element?: any, settings?: any) {
    super();
    if (new.target === BaseUploader) {
      this.init($element, settings);
    }
  }

  init($element: any, settings: any): void {
    this._rejectedFiles = {size: [], type: [], limit: []};
    this.$element = $element;
    this.settings = $.extend({}, BaseUploader.defaults, settings);
    this.formData = this.settings.formData;
    this.$fileInput = this.settings.fileInput || $element;
    this.events = this.settings.events;

    if (!this.settings.url) {
      this.settings.url = this.settings.replace
        ? Craft.getActionUrl(this.settings.replaceAction)
        : Craft.getActionUrl(this.settings.createAction);
    }

    if (this.settings.allowedKinds && this.settings.allowedKinds.length) {
      if (Object(this.settings.allowedKinds).constructor === String) {
        this.settings.allowedKinds = [String(this.settings.allowedKinds)];
      }

      this.allowedKinds = this.settings.allowedKinds;
      delete this.settings.allowedKinds;
    }
  }

  /**
   * Set uploader parameters.
   */
  setParams(paramObject: any): void {
    // If CSRF protection isn't enabled, these won't be defined.
    if (
      Craft.csrfTokenName !== undefined &&
      Craft.csrfTokenValue !== undefined
    ) {
      // Add the CSRF token
      paramObject[Craft.csrfTokenName] = Craft.csrfTokenValue;
    }

    this.formData = paramObject;
  }

  /**
   * Get the number of uploads in progress.
   */
  getInProgress(): number {
    return this._inProgressCounter;
  }

  /**
   * Return true, if this is the last upload.
   */
  isLastUpload(): boolean {
    // Processing the last file or not processing at all.
    return this.getInProgress() < 2;
  }

  /**
   * Process error messages.
   */
  processErrorMessages(): void {
    let str;

    if (this._rejectedFiles.type.length) {
      if (this._rejectedFiles.type.length === 1) {
        str =
          'The file {files} could not be uploaded. The allowed file kinds are: {kinds}.';
      } else {
        str =
          'The files {files} could not be uploaded. The allowed file kinds are: {kinds}.';
      }

      str = Craft.t('app', str, {
        files: this._rejectedFiles.type.join(', '),
        kinds: this.allowedKinds.join(', '),
      });
      this._rejectedFiles.type = [];
      Craft.cp.displayError(str);
    }

    if (this._rejectedFiles.size.length) {
      if (this._rejectedFiles.size.length === 1) {
        str =
          'The file {files} could not be uploaded, because it exceeds the maximum upload size of {size}.';
      } else {
        str =
          'The files {files} could not be uploaded, because they exceeded the maximum upload size of {size}.';
      }

      str = Craft.t('app', str, {
        files: this._rejectedFiles.size.join(', '),
        size: this.humanFileSize(this.settings.maxFileSize),
      });
      this._rejectedFiles.size = [];
      Craft.cp.displayError(str);
    }

    if (this._rejectedFiles.limit.length) {
      if (this._rejectedFiles.limit.length === 1) {
        str =
          'The file {files} could not be uploaded, because the field limit has been reached.';
      } else {
        str =
          'The files {files} could not be uploaded, because the field limit has been reached.';
      }

      str = Craft.t('app', str, {
        files: this._rejectedFiles.limit.join(', '),
      });
      this._rejectedFiles.limit = [];
      Craft.cp.displayError(str);
    }
  }

  humanFileSize(bytes: number): string {
    const threshold = 1024;

    if (bytes < threshold) {
      return bytes + ' B';
    }

    const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    let u = -1;

    do {
      bytes = bytes / threshold;
      ++u;
    } while (bytes >= threshold);

    return bytes.toFixed(1) + ' ' + units[u];
  }

  _createExtensionList(): void {
    this._extensionList = [];

    for (let i = 0; i < this.allowedKinds.length; i++) {
      const allowedKind = this.allowedKinds[i];

      if (Craft.fileKinds[allowedKind] !== undefined) {
        for (
          let j = 0;
          j < Craft.fileKinds[allowedKind].extensions.length;
          j++
        ) {
          const ext = Craft.fileKinds[allowedKind].extensions[j];
          this._extensionList.push(ext);
        }
      }
    }
  }

  override destroy(): void {
    // Legacy `Craft.BaseUploader.destroy` was a no-op ($.noop).
  }
}
