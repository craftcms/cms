/** global: Craft */
/** global: Garnish */

import {FileUpload} from '../../../uploads/src/FileUpload.js';

/**
 * Uploads files through upload sessions, which send them straight to storage
 * when the volume’s filesystem allows it.
 *
 * It dispatches the same events as Craft.Uploader, as DOM events whose
 * `detail` holds the data.
 */
Craft.UploadSessionUploader = Craft.BaseUploader.extend(
  {
    element: null,
    $dropZone: null,
    _totalBytes: 0,
    _loaded: null,

    init: function ($element, settings) {
      settings = $.extend({}, Craft.UploadSessionUploader.defaults, settings);
      this.base($element, settings);
      this.element = this.$element[0];
      this._loaded = new Map();

      Object.entries(this.events).forEach(([name, handler]) => {
        this.element.addEventListener(name, handler);
      });

      if (this.allowedKinds) {
        this._createExtensionList();
      }

      this.addListener(this.$fileInput, 'change', (event) => {
        this.addFiles(event.target.files);
        this.$fileInput.val('');
      });

      if (this.settings.dropZone) {
        this.$dropZone = $(this.settings.dropZone);
        this.addListener(this.$dropZone, 'dragover', (event) => {
          if (this._hasFiles(event)) {
            event.preventDefault();
            event.originalEvent.dataTransfer.dropEffect = 'copy';
          }
        });
        this.addListener(this.$dropZone, 'drop', (event) => {
          if (this._hasFiles(event)) {
            event.preventDefault();
            this.addFiles(event.originalEvent.dataTransfer.files);
          }
        });
      }
    },

    addFiles: async function (files) {
      let validFileCount = 0;
      const validFiles = Array.from(files).filter((file) => {
        let valid = true;

        if (this._extensionList) {
          const extension = file.name.match(/\.([a-z0-4_]+)$/i)?.[1];
          if (
            !extension ||
            !this._extensionList.includes(extension.toLowerCase())
          ) {
            this._rejectedFiles.type.push('“' + file.name + '”');
            valid = false;
          }
        }

        if (
          this.settings.maxFileSize &&
          file.size > this.settings.maxFileSize
        ) {
          this._rejectedFiles.size.push('“' + file.name + '”');
          valid = false;
        }

        if (
          valid &&
          typeof this.settings.canAddMoreFiles === 'function' &&
          !this.settings.canAddMoreFiles(validFileCount)
        ) {
          this._rejectedFiles.limit.push('“' + file.name + '”');
          valid = false;
        }

        if (valid) {
          validFileCount++;
        }

        return valid;
      });

      this.processErrorMessages();

      if (!validFiles.length) {
        return;
      }

      if (!this._inProgressCounter) {
        this._totalBytes = 0;
        this._loaded.clear();
        this._dispatch('fileuploadstart');
      }

      this._inProgressCounter += validFiles.length;
      this._totalBytes += validFiles.reduce((sum, file) => sum + file.size, 0);

      // The destination can change while the batch uploads, such as when another folder is selected
      const parameters = this._parameters();

      for (const file of validFiles) {
        await this._upload(file, parameters);
      }
    },

    _upload: async function (file, parameters) {
      const upload = new FileUpload(file, {
        url: Craft.getActionUrl('uploads/start'),
        csrfToken: Craft.csrfTokenValue,
        parameters,
        onProgress: (loaded) => {
          this._loaded.set(file, loaded);
          this._dispatch('fileuploadprogressall', {
            loaded: [...this._loaded.values()].reduce((a, b) => a + b, 0),
            total: this._totalBytes,
          });
        },
      });

      try {
        this._dispatch('fileuploaddone', await upload.upload());
      } catch (error) {
        // Nothing retries it, so don't leave its file staged until it expires
        upload.cancel().catch(() => {});
        this._dispatch('fileuploadfail', {
          message: error.message,
          filename: file.name,
        });
      } finally {
        // Handlers check isLastUpload() before the count drops, as with Craft.Uploader
        this._dispatch('fileuploadalways');
        this._inProgressCounter--;
      }
    },

    _parameters: function () {
      const params = Object.assign({}, this.formData);
      delete params[Craft.csrfTokenName];

      if (this.settings.replace) {
        return {operation: 'replace', assetId: params.assetId};
      }

      return params;
    },

    _hasFiles: function (event) {
      return Array.from(
        event.originalEvent?.dataTransfer?.types || []
      ).includes('Files');
    },

    _dispatch: function (name, detail = {}) {
      this.element.dispatchEvent(new CustomEvent(name, {detail}));
    },

    destroy: function () {
      this.base();
      this.removeAllListeners();

      Object.entries(this.events).forEach(([name, handler]) => {
        this.element.removeEventListener(name, handler);
      });
    },
  },
  {
    defaults: {
      maxFileSize: Craft.maxUploadSessionSize,
      // Used for conflict follow-ups, which don't upload anything
      replaceAction: 'assets/replace-file',
      deleteAction: 'assets/delete-asset',
    },
  }
);
