/** global: Craft */
/** global: Garnish */

/**
 * File Manager.
 */
Craft.Uploader = Craft.BaseUploader.extend(
  {
    uploader: null,
    _totalFileCounter: 0,
    _validFileCounter: 0,
    _onFileAdd: null,

    init: function ($element, settings) {
      settings = $.extend({}, Craft.Uploader.defaults, settings);
      this.base($element, settings);
      delete this.settings.events;

      this.uploader = this.$element.fileupload(this.settings);

      Object.entries(this.events).forEach(([name, handler]) => {
        this.$element.on(name, handler);
      });

      this._onFileAdd = this.onFileAdd.bind(this);
      this.$element.on('fileuploadadd', this._onFileAdd);
    },

    /**
     * Set uploader parameters.
     */
    setParams: function (paramObject) {
      this.base(paramObject);

      // Only set params if the uploader has been initialized
      // It won't be if the input is disabled
      if (this.uploader.data('blueimpFileupload')) {
        this.uploader.fileupload('option', {formData: this.formData});
      }
    },

    /**
     * Get the number of uploads in progress.
     */
    getInProgress: function () {
      return this.uploader.fileupload('active');
    },

    /**
     * Called on file add.
     */
    onFileAdd: function (e, data) {
      e.stopPropagation();

      var validateExtension = false;

      if (this.allowedKinds) {
        if (!this._extensionList) {
          this._createExtensionList();
        }

        validateExtension = true;
      }

      // Make sure that file API is there before relying on it
      data.process().done(() => {
        var file = data.files[0];
        var pass = true;
        if (validateExtension) {
          var matches = file.name.match(/\.([a-z0-4_]+)$/i);
          var fileExtension = matches[1];
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
          typeof this.settings.canAddMoreFiles === 'function' &&
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
    },

    destroy: function () {
      if (this.uploader.fileupload('instance')) {
        this.uploader.fileupload('destroy');
      }

      this.$element.off('fileuploadadd', this._onFileAdd);

      Object.entries(this.events).forEach(([name, handler]) => {
        this.$element.off(name, handler);
      });
    },
  },
  {
    defaults: {
      autoUpload: false,
      sequentialUploads: true,
      maxFileSize: Craft.maxUploadSize,
      replaceFileInput: false,
      createAction: 'assets/upload',
      replaceAction: 'assets/replace-file',
      deleteAction: 'assets/delete-asset',
    },
  }
);
