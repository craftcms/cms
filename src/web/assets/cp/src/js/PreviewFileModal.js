/** global: Craft */
/** global: Garnish */
/**
 * Preview File Modal
 */
Craft.PreviewFileModal = Garnish.Modal.extend(
  {
    assetId: null,
    $spinner: null,
    $triggerElement: null,
    $bumperButtonStart: null,
    $bumperButtonEnd: null,
    elementSelect: null,
    type: null,
    loaded: null,
    requestId: 0,

    /**
     * Initialize the preview file modal.
     * @returns {*|void}
     */
    init: function (assetId, elementSelect, settings) {
      settings = $.extend(this.defaultSettings, settings);
      this.$triggerElement = Garnish.getFocusedElement();

      settings.onHide = this._onHide.bind(this);

      if (Craft.PreviewFileModal.openInstance) {
        var instance = Craft.PreviewFileModal.openInstance;

        if (instance.assetId !== assetId) {
          instance.loadAsset(
            assetId,
            settings.startingWidth,
            settings.startingHeight
          );
          instance.elementSelect = elementSelect;
        }

        return this.destroy();
      }

      Craft.PreviewFileModal.openInstance = this;
      this.elementSelect = elementSelect;

      this.$container = $('<div class="modal previewmodal loading"/>').appendTo(
        Garnish.$bod
      );

      this.base(
        this.$container,
        $.extend(
          {
            resizable: true,
          },
          settings
        )
      );

      // Cut the flicker, just show the nice person the preview.
      if (this.$container) {
        this.$container.velocity('stop');
        this.$container.show().css('opacity', 1);

        this.$shade.velocity('stop');
        this.$shade.show().css('opacity', 1);

        Garnish.setFocusWithin(this.$container);
      }

      // Add bumper elements to maintain focus trap
      this.$bumperButtonStart = Craft.ui.createButton({
        html: Craft.t('app', 'Close Preview'),
        class: 'skip-link',
      });

      this.addListener(this.$bumperButtonStart, 'click', () => {
        this.hide();
      });
      this.$bumperButtonEnd = this.$bumperButtonStart.clone(true);

      this.loadAsset(assetId, settings.startingWidth, settings.startingHeight);
    },

    /**
     * When hiding, remove all traces and focus last focused element.
     * @private
     */
    _onHide: function () {
      Craft.PreviewFileModal.openInstance = null;
      if (this.elementSelect) {
        this.elementSelect.focusItem(this.elementSelect.$focusedItem);
      } else if (this.$triggerElement && this.$triggerElement.length) {
        this.$triggerElement.trigger('focus');
      }

      this.$shade.remove();

      return this.destroy();
    },

    _addBumperButtons: function () {
      this.$container
        .prepend(this.$bumperButtonStart)
        .append(this.$bumperButtonEnd);
    },

    _addModalName: function () {
      const headingId = 'preview-heading';

      $('<h1/>', {
        class: 'visually-hidden',
        id: headingId,
        text: Craft.t('app', 'Preview file'),
      }).prependTo(this.$container);

      this.$container.attr('aria-labelledby', headingId);
    },

    /**
     * Disappear immediately forever.
     * @returns {boolean}
     */
    selfDestruct: function () {
      var instance = Craft.PreviewFileModal.openInstance;

      instance.hide();
      instance.$shade.remove();
      instance.destroy();

      Craft.PreviewFileModal.openInstance = null;
      Craft.focalPoint.destruct();
      Craft.focalPoint = null;

      return true;
    },

    /**
     * Load an asset, using starting width and height, if applicable
     * @param {number} assetId
     * @param {number} [startingWidth]
     * @param {number} [startingHeight]
     */
    loadAsset: function (assetId, startingWidth, startingHeight) {
      this.assetId = assetId;

      this.$container.empty();
      this.loaded = false;

      this.desiredHeight = null;
      this.desiredWidth = null;

      var containerHeight = Garnish.$win.height() * 0.66;
      var containerWidth = Math.min(
        (containerHeight / 3) * 4,
        Garnish.$win.width() - this.settings.minGutter * 2
      );
      containerHeight = (containerWidth / 4) * 3;

      if (startingWidth && startingHeight) {
        var ratio = startingWidth / startingHeight;
        containerWidth = Math.min(
          startingWidth,
          Garnish.$win.width() - this.settings.minGutter * 2
        );
        containerHeight = Math.min(
          containerWidth / ratio,
          Garnish.$win.height() - this.settings.minGutter * 2
        );
        containerWidth = containerHeight * ratio;

        // This might actually have put width over the viewport limits, so doublecheck
        if (
          containerWidth >
          Math.min(
            startingWidth,
            Garnish.$win.width() - this.settings.minGutter * 2
          )
        ) {
          containerWidth = Math.min(
            startingWidth,
            Garnish.$win.width() - this.settings.minGutter * 2
          );
          containerHeight = containerWidth / ratio;
        }
      }

      this._resizeContainer(containerWidth, containerHeight);

      this.$spinner = $('<div class="spinner centeralign"></div>').appendTo(
        this.$container
      );
      var top =
          this.$container.height() / 2 - this.$spinner.height() / 2 + 'px',
        left = this.$container.width() / 2 - this.$spinner.width() / 2 + 'px';

      this.$spinner.css({left: left, top: top, position: 'absolute'});
      this.requestId++;

      let data = {assetId: assetId, requestId: this.requestId};
      let onResponse = () => {
        this.$container.removeClass('loading');
        this.$spinner.remove();
        this.loaded = true;
      };
      Craft.sendActionRequest('POST', 'assets/preview-file', {data})
        .then((response) => {
          onResponse();

          if (response.data.requestId != this.requestId) {
            return;
          }

          if (!response.data.previewHtml) {
            this.$container.addClass('zilch');
            this.$container.append(
              $('<p/>', {text: Craft.t('app', 'No preview available.')})
            );
            this._addBumperButtons();
            return;
          }

          this.$container.removeClass('zilch');
          this.$container.attr('data-asset-id', this.assetId);
          this.$container.append(response.data.previewHtml);
          this._addBumperButtons();
          this._addModalName();
          Craft.appendHeadHtml(response.data.headHtml);
          Craft.appendBodyHtml(response.data.bodyHtml);
        })
        .catch(({response}) => {
          onResponse();
          alert(response.data.message);
          this.hide();
        });
    },

    /**
     * Resize the container to specified dimensions
     * @param {number} containerWidth
     * @param {number} containerHeight
     * @private
     */
    _resizeContainer: function (containerWidth, containerHeight) {
      this.$container.css({
        width: containerWidth,
        'min-width': containerWidth,
        'max-width': containerWidth,
        height: containerHeight,
        'min-height': containerHeight,
        'max-height': containerHeight,
        top: (Garnish.$win.height() - containerHeight) / 2,
        left: (Garnish.$win.width() - containerWidth) / 2,
      });
    },
  },
  {
    defaultSettings: {
      startingWidth: null,
      startingHeight: null,
    },
  }
);
