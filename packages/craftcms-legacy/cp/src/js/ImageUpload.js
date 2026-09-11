/** global: Craft */
/** global: Garnish */

/**
 * Image upload class for user photos, site icon and logo.
 */
Craft.ImageUpload = Garnish.Base.extend(
  {
    $container: null,
    progressBar: null,
    uploader: null,

    init: function (settings) {
      this.setSettings(settings, Craft.ImageUpload.defaults);
      this.$container = $(this.settings.containerSelector);
      this.initImageUpload();
    },

    initImageUpload: function () {
      this.progressBar = new Craft.ProgressBar(
        $('<div class="progress-shade"></div>').appendTo(this.$container)
      );

      const options = {
        url: Craft.getActionUrl(this.settings.uploadAction),
        formData: this.settings.postParameters,
        fileInput: this.$container.find(this.settings.fileInputSelector).toArray(),
      };

      options.on = {};
      options.on.start = this._onUploadStart.bind(this);
      options.on.progress = this._onUploadProgress.bind(this);
      options.on.done = this._onUploadComplete.bind(this);
      options.on.fail = this._onUploadFailure.bind(this);

      this.uploader = new Craft.Uploaders.Uploader(this.$container[0], options);

      this.initButtons();
    },

    initButtons: function () {
      this.$container
        .find(this.settings.uploadButtonSelector)
        .on('click', (ev) => {
          this.$container
            .find(this.settings.fileInputSelector)
            .trigger('click');
        });

      this.$container
        .find(this.settings.deleteButtonSelector)
        .on('click', (ev) => {
          if (
            confirm(
              Craft.t('app', 'Are you sure you want to delete this image?')
            )
          ) {
            $(ev.currentTarget)
              .parent()
              .append('<div class="blocking-modal"></div>');

            Craft.sendActionRequest('POST', this.settings.deleteAction, {
              data: this.settings.postParameters,
            }).then(({data}) => {
              this.refreshImage(data);
            });
          }
        });
    },

    refreshImage: function (response) {
      this.uploader.destroy();
      this.$container.replaceWith((this.$container = $(response.html)));
      this.settings.onAfterRefreshImage(response);
      Craft.cp.elementThumbLoader.load(this.$container);
      this.initImageUpload();
    },

    /**
     * On upload start.
     */
    _onUploadStart: function (event) {
      this.progressBar.$progressBar.css({
        top: Math.round(this.$container.outerHeight() / 2) - 6,
      });

      this.$container.addClass('uploading');
      this.progressBar.resetProgressBar();
      this.progressBar.showProgressBar();
    },

    /**
     * On upload progress.
     */
    _onUploadProgress: function (data) {
      var progress = parseInt((data.loaded / data.total) * 100, 10);
      this.progressBar.setProgressPercentage(progress);
    },

    /**
     * On a file being uploaded.
     */
    _onUploadComplete: function ({result}) {
      this.refreshImage(result);

      this.progressBar.hideProgressBar();
      this.$container.removeClass('uploading');
    },

    /**
     * On Upload Failure.
     */
    _onUploadFailure: function ({error, canceled, file}) {
      if (canceled) {
        this.progressBar.hideProgressBar();
        this.$container.removeClass('uploading');
        return;
      }

      let message = error instanceof Error ? error.message : undefined;
      const {errors = {}} = error?.data || {};
      const filename = error?.data?.filename || file?.name;
      let errorMessages = errors ? Object.values(errors).flat() : [];

      if (!message) {
        if (errorMessages.length) {
          message = errorMessages.join('\n');
        } else if (filename) {
          message = Craft.t('app', 'Upload failed for “{filename}”.', {
            filename,
          });
        } else {
          message = Craft.t('app', 'Upload failed.');
        }
      }

      Craft.cp.displayError(message);
      this.progressBar.hideProgressBar();
      this.$container.removeClass('uploading');
    },
  },
  {
    defaults: {
      postParameters: {},
      uploadAction: '',
      deleteAction: '',
      fileInputSelector: '',

      onAfterRefreshImage: $.noop,
      containerSelector: null,

      uploadButtonSelector: null,
      deleteButtonSelector: null,
    },
  }
);
