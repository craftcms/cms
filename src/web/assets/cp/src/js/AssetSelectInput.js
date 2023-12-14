/** global: Craft */
/** global: Garnish */
/**
 * Asset select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend({
  $uploadBtn: null,
  uploader: null,
  progressBar: null,
  openPreviewTimeout: null,

  init: function () {
    this.base.apply(this, arguments);

    if (this.settings.canUpload) {
      this._attachUploader();
    }

    this.updateAddElementsBtn();

    this.addListener(
      this.$elementsContainer,
      'keydown',
      this._onKeyDown.bind(this)
    );
    this.elementSelect.on('focusItem', this._onElementFocus.bind(this));
  },

  /**
   * Handle a keypress
   * @private
   */
  _onKeyDown: function (ev) {
    if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
      this.openPreview();
      ev.stopPropagation();
      return false;
    }
  },

  onAddElements: function () {
    this.$elements
      .find('.elementthumb')
      .addClass('open-preview')
      .on('click', (ev) => {
        this.clearOpenPreviewTimeout();
        this.openPreviewTimeout = setTimeout(() => {
          this.openPreview();
          this.openPreviewTimeout = null;
        }, 500);
      })
      .on('dblclick', (ev) => {
        this.clearOpenPreviewTimeout();
      });
    this.base();
  },

  clearOpenPreviewTimeout: function () {
    if (this.openPreviewTimeout) {
      clearTimeout(this.openPreviewTimeout);
      this.openPreviewTimeout = null;
    }
  },

  openPreview: function () {
    if (Craft.PreviewFileModal.openInstance) {
      Craft.PreviewFileModal.openInstance.selfDestruct();
    } else {
      var $element = this.elementSelect.$focusedItem;

      if ($element.length) {
        this._loadPreview($element);
      }
    }
  },

  /**
   * Handle element being focused
   * @private
   */
  _onElementFocus: function (ev) {
    var $element = $(ev.item);

    if (Craft.PreviewFileModal.openInstance && $element.length) {
      this._loadPreview($element);
    }
  },

  /**
   * Load the preview for an asset
   * @private
   */
  _loadPreview: function ($element) {
    var settings = {
      minGutter: 50,
    };

    if ($element.data('image-width')) {
      settings.startingWidth = $element.data('image-width');
      settings.startingHeight = $element.data('image-height');
    }

    new Craft.PreviewFileModal(
      $element.data('id'),
      this.elementSelect,
      settings
    );
  },

  /**
   * Attach the uploader with drag event handler
   */
  _attachUploader: function () {
    this.progressBar = new Craft.ProgressBar(
      $('<div class="progress-shade"></div>').appendTo(this.$container)
    );

    if (this.$addElementBtn) {
      this.$uploadBtn = $('<button/>', {
        type: 'button',
        class: 'btn dashed',
        'data-icon': 'upload',
        'aria-label':
          this.settings.limit == 1
            ? Craft.t('app', 'Upload a file')
            : Craft.t('app', 'Upload files'),
        'aria-describedby': this.settings.describedBy,
        text:
          this.settings.limit == 1
            ? Craft.t('app', 'Upload a file')
            : Craft.t('app', 'Upload files'),
      }).insertAfter(this.$addElementBtn);
      this.$fileInput = $('<input/>', {
        type: 'file',
        class: 'hidden',
        multiple: this.settings.limit != 1,
      }).insertAfter(this.$uploadBtn);

      // Trigger a window resize in case the field is inside an element editor HUD
      Garnish.$win.trigger('resize');
    }

    var options = {
      dropZone: this.$container,
      fileInput: this.$fileInput,
    };

    if (typeof this.settings.criteria.kind !== 'undefined') {
      options.allowedKinds = this.settings.criteria.kind;
    }

    options.canAddMoreFiles = this.canAddMoreFiles.bind(this);

    options.events = {};
    options.events.fileuploadstart = this._onUploadStart.bind(this);
    options.events.fileuploadprogressall = this._onUploadProgress.bind(this);
    options.events.fileuploaddone = this._onUploadComplete.bind(this);
    options.events.fileuploadfail = this._onUploadFailure.bind(this);

    this.uploader = Craft.createUploader(
      this.settings.fsType,
      this.$container,
      options
    );

    const params = {
      fieldId: this.settings.fieldId,
    };
    if (this.settings.sourceElementId) {
      params.elementId = this.settings.sourceElementId;
    }
    if (this.settings.criteria.siteId) {
      params.siteId = this.settings.criteria.siteId;
    }
    this.uploader.setParams(params);

    if (this.$uploadBtn) {
      this.$uploadBtn.on('click', (ev) => {
        // We can't store a reference to the file input, because it gets replaced with a new input
        // each time a new file is uploaded - see https://stackoverflow.com/a/25034721/1688568
        this.$uploadBtn.next('input[type=file]').trigger('click');
      });
    }
  },

  enableAddElementsBtn: function () {
    if (this.$uploadBtn) {
      this.$uploadBtn.removeClass('hidden');
    }

    this.base();
  },

  disableAddElementsBtn: function () {
    if (this.$uploadBtn) {
      this.$uploadBtn.addClass('hidden');
    }

    this.base();
  },

  /**
   * Add the freshly uploaded file to the input field.
   */
  selectUploadedFile: function (element) {
    // Check if we're able to add new elements
    if (!this.canAddMoreElements()) {
      return;
    }

    var $newElement = element.$element;

    // Make a couple tweaks
    $newElement.addClass('removable');
    $newElement.prepend(
      '<input type="hidden" name="' +
        this.settings.name +
        '[]" value="' +
        element.id +
        '">' +
        '<a class="delete icon" title="' +
        Craft.t('app', 'Remove') +
        '"></a>'
    );

    $newElement.appendTo(this.$elementsContainer);

    var margin = -($newElement.outerWidth() + 10);

    this.$addElementBtn.css('margin-' + Craft.left, margin + 'px');

    var animateCss = {};
    animateCss['margin-' + Craft.left] = 0;
    this.$addElementBtn.velocity(animateCss, 'fast');

    this.addElements($newElement);

    delete this.modal;
  },

  /**
   * On upload start.
   */
  _onUploadStart: function () {
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
    data = event instanceof CustomEvent ? event.detail : data;

    var progress = parseInt(Math.min(data.loaded / data.total, 1) * 100, 10);
    this.progressBar.setProgressPercentage(progress);
  },

  /**
   * On a file being uploaded.
   */
  _onUploadComplete: function (event, data) {
    const result = event instanceof CustomEvent ? event.detail : data.result;

    const parameters = {
      elementId: result.assetId,
      siteId: this.settings.criteria.siteId,
      thumbSize: this.settings.viewMode,
    };

    Craft.sendActionRequest('POST', 'elements/get-element-html', {
      data: parameters,
    })
      .then((response) => {
        var html = $(response.data.html);
        Craft.appendHeadHtml(response.data.headHtml);
        this.selectUploadedFile(Craft.getElementInfo(html));

        // Last file
        if (this.uploader.isLastUpload()) {
          this.progressBar.hideProgressBar();
          this.$container.removeClass('uploading');
          this.$container.trigger('change');
        }
      })
      .catch(({response}) => {
        Craft.cp.displayError(response.data.message);
      });

    Craft.cp.runQueue();
  },

  /**
   * On Upload Failure.
   */
  _onUploadFailure: function (event, data) {
    const file = data.data.getAll('assets-upload');
    const backupFilename = file[0].name;
    const response =
      event instanceof CustomEvent ? event.detail : data?.jqXHR?.responseJSON;

    let {message, filename} = response || {};

    if (!message) {
      if (!filename) {
        filename = backupFilename;
      }
      message = filename
        ? Craft.t('app', 'Upload failed for “{filename}”.', {filename})
        : Craft.t('app', 'Upload failed.');
    }

    Craft.cp.displayError(message);
    this.progressBar.hideProgressBar();
    this.$container.removeClass('uploading');
  },

  /**
   * We have to take into account files about to be added as well
   */
  canAddMoreFiles: function (slotsTaken) {
    return (
      !this.settings.limit ||
      this.$elements.length + slotsTaken < this.settings.limit
    );
  },
});
