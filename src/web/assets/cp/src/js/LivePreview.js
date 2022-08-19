/** global: Craft */
/** global: Garnish */
/**
 * Live Preview
 */
Craft.LivePreview = Garnish.Base.extend(
  {
    $extraFields: null,
    $trigger: null,
    $shade: null,
    $editorContainer: null,
    $editor: null,
    $dragHandle: null,
    $previewContainer: null,
    $iframeContainer: null,
    $iframe: null,
    $fieldPlaceholder: null,

    previewUrl: null,
    token: null,
    basePostData: null,
    inPreviewMode: false,
    fields: null,
    lastPostData: null,
    updateIframeInterval: null,
    loading: false,
    checkAgain: false,

    dragger: null,
    dragStartEditorWidth: null,

    _slideInOnIframeLoad: false,

    _scrollX: null,
    _scrollY: null,

    _editorWidth: null,
    _editorWidthInPx: null,

    init: function (settings) {
      this.setSettings(settings, Craft.LivePreview.defaults);

      // Should preview requests use a specific URL?
      // This won't affect how the request gets routed (the action param will override it),
      // but it will allow the templates to change behavior based on the request URI.
      if (this.settings.previewUrl) {
        this.previewUrl = this.settings.previewUrl;
      } else {
        this.previewUrl = Craft.baseSiteUrl.replace(/\/+$/, '') + '/';
      }

      // Load the preview over SSL if the current request is
      if (document.location.protocol === 'https:') {
        this.previewUrl = this.previewUrl.replace(/^http:/, 'https:');
      }

      // Set the base post data
      this.basePostData = $.extend({}, this.settings.previewParams);

      // Find the DOM elements
      this.$extraFields = $(this.settings.extraFields);
      this.$trigger = $(this.settings.trigger);
      this.$fieldPlaceholder = $('<div/>');

      // Set the initial editor width
      this.editorWidth = Craft.getLocalStorage(
        'LivePreview.editorWidth',
        Craft.LivePreview.defaultEditorWidth
      );

      // Event Listeners
      this.addListener(this.$trigger, 'activate', 'toggle');

      Craft.cp.on('beforeSaveShortcut', () => {
        if (this.inPreviewMode) {
          this.moveFieldsBack();
        }
      });

      Craft.Preview.instances.push(this);
    },

    get editorWidth() {
      return this._editorWidth;
    },

    get editorWidthInPx() {
      return this._editorWidthInPx;
    },

    set editorWidth(width) {
      var inPx;

      // Is this getting set in pixels?
      if (width >= 1) {
        inPx = width;
        width /= Garnish.$win.width();
      } else {
        inPx = Math.round(width * Garnish.$win.width());
      }

      // Make sure it's no less than the minimum
      if (inPx < Craft.LivePreview.minEditorWidthInPx) {
        inPx = Craft.LivePreview.minEditorWidthInPx;
        width = inPx / Garnish.$win.width();
      }

      this._editorWidth = width;
      this._editorWidthInPx = inPx;
    },

    toggle: function () {
      if (this.inPreviewMode) {
        this.exit();
      } else {
        this.enter();
      }
    },

    enter: function () {
      if (this.inPreviewMode) {
        return;
      }

      if (!this.token) {
        this.createToken();
        return;
      }

      this.trigger('beforeEnter');

      $(document.activeElement).trigger('blur');

      if (!this.$editor) {
        this.$shade = $('<div/>', {class: 'modal-shade dark'}).appendTo(
          Garnish.$bod
        );
        this.$previewContainer = $('<div/>', {
          class: 'lp-preview-container',
        }).appendTo(Garnish.$bod);
        this.$iframeContainer = $('<div/>', {
          class: 'lp-iframe-container',
        }).appendTo(this.$previewContainer);
        this.$editorContainer = $('<div/>', {
          class: 'lp-editor-container',
        }).appendTo(Garnish.$bod);

        var $editorHeader = $('<header/>', {class: 'flex'}).appendTo(
          this.$editorContainer
        );
        this.$editor = $('<form/>', {class: 'lp-editor'}).appendTo(
          this.$editorContainer
        );
        this.$dragHandle = $('<div/>', {class: 'lp-draghandle'}).appendTo(
          this.$editorContainer
        );
        var $closeBtn = $('<button/>', {
          type: 'button',
          class: 'btn',
          text: Craft.t('app', 'Close Preview'),
        }).appendTo($editorHeader);
        $('<div/>', {class: 'flex-grow'}).appendTo($editorHeader);
        let $saveBtn = $('<button/>', {
          type: 'button',
          class: 'btn submit',
          text: Craft.t('app', 'Save'),
        }).appendTo($editorHeader);

        this.dragger = new Garnish.BaseDrag(this.$dragHandle, {
          axis: Garnish.X_AXIS,
          onDragStart: this._onDragStart.bind(this),
          onDrag: this._onDrag.bind(this),
          onDragStop: this._onDragStop.bind(this),
        });

        this.addListener($closeBtn, 'click', 'exit');
        this.addListener($saveBtn, 'click', 'save');
      }

      // Set the sizes
      this.handleWindowResize();
      this.addListener(Garnish.$win, 'resize', 'handleWindowResize');

      this.$editorContainer.css(Craft.left, -this.editorWidthInPx + 'px');
      this.$previewContainer.css(Craft.right, -this.getIframeWidth());

      // Move all the fields into the editor rather than copying them
      // so any JS that's referencing the elements won't break.
      this.fields = [];
      var $fields = $(this.settings.fields);

      for (var i = 0; i < $fields.length; i++) {
        var $field = $($fields[i]),
          $clone = this._getClone($field);

        // It's important that the actual field is added to the DOM *after* the clone,
        // so any radio buttons in the field get deselected from the clone rather than the actual field.
        this.$fieldPlaceholder.insertAfter($field);
        $field.detach();
        this.$fieldPlaceholder.replaceWith($clone);
        $field.appendTo(this.$editor);

        this.fields.push({
          $field: $field,
          $clone: $clone,
        });
      }

      if (this.updateIframe()) {
        this._slideInOnIframeLoad = true;
      } else {
        this.slideIn();
      }

      Craft.ElementThumbLoader.retryAll();

      Garnish.uiLayerManager.addLayer(this.$sidebar);
      Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
        this.exit();
      });

      this.inPreviewMode = true;
      this.trigger('enter');
    },

    createToken: function () {
      const data = {previewAction: this.settings.previewAction};
      Craft.sendActionRequest('POST', 'live-preview/create-token', {data}).then(
        (response) => {
          this.token = response.data.token;
          this.enter();
        }
      );
    },

    save: function () {
      Craft.cp.submitPrimaryForm();
    },

    handleWindowResize: function () {
      // Reset the width so the min width is enforced
      this.editorWidth = this.editorWidth;

      // Update the editor/iframe sizes
      this.updateWidths();
    },

    slideIn: function () {
      $('html').addClass('noscroll');
      this.$shade.velocity('fadeIn');

      this.$editorContainer
        .show()
        .velocity('stop')
        .animateLeft(0, 'slow', () => {
          this.trigger('slideIn');
          Garnish.$win.trigger('resize');
        });

      this.$previewContainer
        .show()
        .velocity('stop')
        .animateRight(0, 'slow', () => {
          this.updateIframeInterval = setInterval(
            this.updateIframe.bind(this),
            1000
          );
        });
    },

    exit: function () {
      if (!this.inPreviewMode) {
        return;
      }

      this.trigger('beforeExit');

      $('html').removeClass('noscroll');

      this.removeListener(Garnish.$win, 'resize');
      Garnish.uiLayerManager.removeLayer();

      if (this.updateIframeInterval) {
        clearInterval(this.updateIframeInterval);
      }

      this.moveFieldsBack();

      this.$shade.delay(200).velocity('fadeOut');

      this.$editorContainer
        .velocity('stop')
        .animateLeft(-this.editorWidthInPx, 'slow', () => {
          for (var i = 0; i < this.fields.length; i++) {
            this.fields[i].$newClone.remove();
          }
          this.$editorContainer.hide();
          this.trigger('slideOut');
        });

      this.$previewContainer
        .velocity('stop')
        .animateRight(-this.getIframeWidth(), 'slow', () => {
          this.$previewContainer.hide();
        });

      Craft.ElementThumbLoader.retryAll();

      this.inPreviewMode = false;
      this.trigger('exit');
    },

    moveFieldsBack: function () {
      for (var i = 0; i < this.fields.length; i++) {
        var field = this.fields[i];
        field.$newClone = this._getClone(field.$field);

        // It's important that the actual field is added to the DOM *after* the clone,
        // so any radio buttons in the field get deselected from the clone rather than the actual field.
        this.$fieldPlaceholder.insertAfter(field.$field);
        field.$field.detach();
        this.$fieldPlaceholder.replaceWith(field.$newClone);
        field.$clone.replaceWith(field.$field);
      }

      Garnish.$win.trigger('resize');
    },

    getIframeWidth: function () {
      return Garnish.$win.width() - this.editorWidthInPx;
    },

    updateWidths: function () {
      this.$editorContainer.css('width', this.editorWidthInPx + 'px');
      this.$previewContainer.width(this.getIframeWidth());
    },

    updateIframe: function (force) {
      if (force) {
        this.lastPostData = null;
      }

      if (!this.inPreviewMode) {
        return false;
      }

      if (this.loading) {
        this.checkAgain = true;
        return false;
      }

      // Has the post data changed?
      var postData = $.extend(
        Garnish.getPostData(this.$editor),
        Garnish.getPostData(this.$extraFields)
      );

      if (
        !this.lastPostData ||
        !Craft.compare(postData, this.lastPostData, false)
      ) {
        this.lastPostData = postData;
        this.loading = true;

        var $doc = this.$iframe
          ? $(this.$iframe[0].contentWindow.document)
          : null;

        this._scrollX = $doc ? $doc.scrollLeft() : 0;
        this._scrollY = $doc ? $doc.scrollTop() : 0;

        $.ajax({
          url:
            this.previewUrl +
            (this.previewUrl.indexOf('?') !== -1 ? '&' : '?') +
            Craft.tokenParam +
            '=' +
            this.token,
          method: 'POST',
          data: $.extend({}, postData, this.basePostData),
          headers: {
            'X-Craft-Token': this.token,
          },
          xhrFields: {
            withCredentials: true,
          },
          crossDomain: true,
          success: this.handleSuccess.bind(this),
          error: this.handleError.bind(this),
        });

        return true;
      } else {
        return false;
      }
    },

    forceUpdateIframe: function () {
      return this.updateIframe(true);
    },

    handleSuccess: function (data) {
      var html =
        data +
        '<script type="text/javascript">window.scrollTo(' +
        this._scrollX +
        ', ' +
        this._scrollY +
        ');</script>';

      // Create a new iframe
      var $iframe = $('<iframe class="lp-preview" frameborder="0"/>');
      if (this.$iframe) {
        $iframe.insertBefore(this.$iframe);
      } else {
        $iframe.appendTo(this.$iframeContainer);
      }

      this.addListener($iframe, 'load', function () {
        if (this.$iframe) {
          this.$iframe.remove();
        }
        this.$iframe = $iframe;

        if (this._slideInOnIframeLoad) {
          this.slideIn();
          this._slideInOnIframeLoad = false;
        }

        this.removeListener($iframe, 'load');
      });

      Garnish.requestAnimationFrame(() => {
        $iframe[0].contentWindow.document.open();
        $iframe[0].contentWindow.document.write(html);
        $iframe[0].contentWindow.document.close();
        this.onResponse();
      });
    },

    handleError: function () {
      this.onResponse();
    },

    onResponse: function () {
      this.loading = false;

      if (this.checkAgain) {
        this.checkAgain = false;
        this.updateIframe();
      }
    },

    _getClone: function ($field) {
      var $clone = $field.clone();

      // clone() won't account for input values that have changed since the original HTML set them
      Garnish.copyInputValues($field, $clone);

      // Remove any id= attributes
      $clone.attr('id', '');
      $clone.find('[id]').attr('id', '');

      return $clone;
    },

    _onDragStart: function () {
      this.dragStartEditorWidth = this.editorWidthInPx;
      this.$previewContainer.addClass('dragging');
    },

    _onDrag: function () {
      if (Craft.orientation === 'ltr') {
        this.editorWidth = this.dragStartEditorWidth + this.dragger.mouseDistX;
      } else {
        this.editorWidth = this.dragStartEditorWidth - this.dragger.mouseDistX;
      }

      this.updateWidths();
    },

    _onDragStop: function () {
      this.$previewContainer.removeClass('dragging');
      Craft.setLocalStorage('LivePreview.editorWidth', this.editorWidth);
    },

    destroy: function () {
      Craft.Preview.instances = Craft.Preview.instances.filter(
        (o) => o !== this
      );
      this.base();
    },
  },
  {
    defaultEditorWidth: 0.33,
    minEditorWidthInPx: 320,
    instances: [],

    defaults: {
      trigger: '.livepreviewbtn',
      fields: null,
      extraFields: null,
      previewUrl: null,
      previewAction: null,
      previewParams: {},
    },
  }
);

Craft.LivePreview.init = function (settings) {
  Craft.livePreview = new Craft.LivePreview(settings);
};
