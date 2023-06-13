/** global: Craft */
/** global: Garnish */

/**
 * File Manager.
 */
Craft.BaseUploader = Garnish.Base.extend(
  {
    allowedKinds: null,
    $element: null,
    settings: null,
    fsType: null,
    params: {},
    _rejectedFiles: {},
    _extensionList: null,
    _inProgressCounter: 0,

    init: function ($element, settings) {
      this._rejectedFiles = {size: [], type: [], limit: []};
      this.$element = $element;
      this.settings = $.extend({}, Craft.BaseUploader.defaults, settings);

      if (!this.settings.url) {
        this.settings.url =
          this.settings.paramName === 'replaceFile'
            ? Craft.getActionUrl(this.settings.replaceAction)
            : Craft.getActionUrl(this.settings.createAction);
      }

      if (this.settings.allowedKinds && this.settings.allowedKinds.length) {
        if (typeof this.settings.allowedKinds === 'string') {
          this.settings.allowedKinds = [this.settings.allowedKinds];
        }

        this.allowedKinds = this.settings.allowedKinds;
        delete this.settings.allowedKinds;
      }
    },

    /**
     * Set uploader parameters.
     */
    setParams: function (paramObject) {
      // If CSRF protection isn't enabled, these won't be defined.
      if (
        typeof Craft.csrfTokenName !== 'undefined' &&
        typeof Craft.csrfTokenValue !== 'undefined'
      ) {
        // Add the CSRF token
        paramObject[Craft.csrfTokenName] = Craft.csrfTokenValue;
      }

      this.params = paramObject;
    },

    /**
     * Get the number of uploads in progress.
     */
    getInProgress: function () {
      return this._inProgressCounter;
    },

    /**
     * Return true, if this is the last upload.
     */
    isLastUpload: function () {
      // Processing the last file or not processing at all.
      return this.getInProgress() < 2;
    },

    /**
     * Process error messages.
     */
    processErrorMessages: function () {
      var str;

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
    },

    humanFileSize: function (bytes) {
      var threshold = 1024;

      if (bytes < threshold) {
        return bytes + ' B';
      }

      var units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

      var u = -1;

      do {
        bytes = bytes / threshold;
        ++u;
      } while (bytes >= threshold);

      return bytes.toFixed(1) + ' ' + units[u];
    },

    _createExtensionList: function () {
      this._extensionList = [];

      for (var i = 0; i < this.allowedKinds.length; i++) {
        var allowedKind = this.allowedKinds[i];

        if (typeof Craft.fileKinds[allowedKind] !== 'undefined') {
          for (
            var j = 0;
            j < Craft.fileKinds[allowedKind].extensions.length;
            j++
          ) {
            var ext = Craft.fileKinds[allowedKind].extensions[j];
            this._extensionList.push(ext);
          }
        }
      }
    },

    destroy: function () {
      this.$element.fileupload('destroy');
      this.base();
    },
  },
  {
    defaults: {
      dropZone: null,
      pasteZone: null,
      fileInput: null,
      maxFileSize: Craft.maxUploadSize,
      allowedKinds: null,
      events: {},
      canAddMoreFiles: null,
      headers: {Accept: 'application/json;q=0.9,*/*;q=0.8'},
      paramName: 'assets-upload',
      url: null,
      createAction: null,
      replaceAction: null,
      deleteAction: null,
    },
  }
);
