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
      this.initImageUpload();
    },

    initImageUpload: function () {
      this.$container = $(this.settings.containerSelector);
      this.progressBar = new Craft.ProgressBar(
        $('<div class="progress-shade"></div>').appendTo(this.$container)
      );

      var options = {
        url: Craft.getActionUrl(this.settings.uploadAction),
        formData: this.settings.postParameters,
        fileInput: this.$container.find(this.settings.fileInputSelector),
        paramName: this.settings.uploadParamName,
      };

      // If CSRF protection isn't enabled, these won't be defined.
      if (
        typeof Craft.csrfTokenName !== 'undefined' &&
        typeof Craft.csrfTokenValue !== 'undefined'
      ) {
        // Add the CSRF token
        options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;
      }

      options.events = {};
      options.events.fileuploadstart = this._onUploadStart.bind(this);
      options.events.fileuploadprogressall = this._onUploadProgress.bind(this);
      options.events.fileuploaddone = this._onUploadComplete.bind(this);
      options.events.fileuploadfail = this._onUploadFailure.bind(this);

      this.uploader = new Craft.Uploader(this.$container, options);

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
      $(this.settings.containerSelector).replaceWith(response.html);
      this.settings.onAfterRefreshImage(response);
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
    _onUploadProgress: function (event, data) {
      var progress = parseInt((data.loaded / data.total) * 100, 10);
      this.progressBar.setProgressPercentage(progress);
    },

    /**
     * On a file being uploaded.
     */
    _onUploadComplete: function (event, data) {
      if (data.result.error) {
        alert(data.result.error);
      } else {
        var html = $(data.result.html);
        this.refreshImage(data.result);
      }

      // Last file
      if (this.uploader.isLastUpload()) {
        this.progressBar.hideProgressBar();
        this.$container.removeClass('uploading');
      }
    },

    /**
     * On Upload Failure.
     */
    _onUploadFailure: function (event, data) {
      const response = data.response();
      let {message, filename} = response?.jqXHR?.responseJSON || {};

      if (!message) {
        message = filename
          ? Craft.t('app', 'Upload failed for “{filename}”.', {filename})
          : Craft.t('app', 'Upload failed.');
      }

      alert(message);
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

      uploadParamName: 'files',
    },
  }
);
