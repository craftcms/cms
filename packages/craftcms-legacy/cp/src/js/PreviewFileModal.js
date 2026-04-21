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
    $liveRegion: $('<span class="visually-hidden" role="status"></span>'),
    elementSelect: null,
    type: null,
    loaded: null,
    requestId: 0,

    /**
     * Initialize the preview file modal.
     * @returns {*|void}
     */
    init: function (assetId, elementSelect, settings) {
      // (assetId, settings)
      if (
        typeof settings === 'undefined' &&
        jQuery.isPlainObject(elementSelect)
      ) {
        settings = elementSelect;
        elementSelect = null;
      }

      settings = $.extend(this.defaultSettings, settings);
      this.$triggerElement = Garnish.getFocusedElement();

      if (Craft.PreviewFileModal.openInstance) {
        Craft.PreviewFileModal.openInstance.quickHide();
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

      Craft.cp.announce(Craft.t('app', 'Loading'));

      // Cut the flicker, just show the nice person the preview.
      this.$container.velocity('stop');
      this.$container.show().css('opacity', 1);

      this.$shade.velocity('stop');
      this.$shade.show().css('opacity', 1);

      Garnish.setFocusWithin(this.$container);

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

      this.addListener(this.$container, 'keydown', (ev) => {
        switch (ev.keyCode) {
          case Garnish.LEFT_KEY:
          case Garnish.UP_KEY:
            ev.preventDefault();
            this.previewPreviousAsset();
            break;
          case Garnish.RIGHT_KEY:
          case Garnish.DOWN_KEY:
            ev.preventDefault();
            this.previewNextAsset();
            break;
          case Garnish.SPACE_KEY:
            ev.preventDefault();
            if (ev.shiftKey) {
              this.hide();
            }
        }
      });
    },

    getSelectItem: function () {
      const $item = this.elementSelect?.$items.filter(
        `[data-id=${this.assetId}]`
      );
      return $item?.length ? $item : null;
    },

    previewPreviousAsset: function () {
      const $element = this.getSelectItem();
      if ($element) {
        const index = this.elementSelect.getItemIndex($element);
        let $prev = this.elementSelect.getPreviousItem(index);
        if ($prev?.length) {
          if (Craft.PreviewFileModal.showForAsset($prev, this.elementSelect)) {
            this.elementSelect.deselectAll();
            this.elementSelect.selectItem($prev, false, false);
          }
        }
      }
    },

    previewNextAsset: function () {
      const $element = this.getSelectItem();
      if ($element) {
        const index = this.elementSelect.getItemIndex($element);
        let $next = this.elementSelect.getNextItem(index);
        if ($next?.length) {
          if (Craft.PreviewFileModal.showForAsset($next, this.elementSelect)) {
            this.elementSelect.deselectAll();
            this.elementSelect.selectItem($next, false, false);
          }
        }
      }
    },

    /**
     * When hiding, remove all traces and focus last focused element.
     */
    onFadeOut: function () {
      this.base();

      const $element = this.getSelectItem();
      if ($element) {
        this.elementSelect.focusItem($element);
      } else if (this.$triggerElement && this.$triggerElement.length) {
        this.$triggerElement.focus();
      }

      this.destroy();
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

    _addLiveRegion: function () {
      this.$container.append(this.$liveRegion);
    },

    /**
     * @deprecated
     */
    selfDestruct: function () {
      this.quickHide();
      return true;
    },

    destroy: function () {
      this.base();

      if (Craft.PreviewFileModal.openInstance === this) {
        Craft.PreviewFileModal.openInstance = null;
        Craft.focalPoint?.destruct();
        Craft.focalPoint = null;
      }
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
        .then(async (response) => {
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
          this._addLiveRegion();
          this._addModalName();
          await Craft.appendHeadHtml(response.data.headHtml);
          await Craft.appendBodyHtml(response.data.bodyHtml);
        })
        .catch(({response}) => {
          onResponse();
          Craft.cp.displayError(response.data.message);
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
    openInstance: null,

    defaultSettings: {
      minGutter: 50,
      startingWidth: null,
      startingHeight: null,
    },

    resizePreviewImage() {
      const instance = Craft.PreviewFileModal.openInstance;
      if (!instance) {
        return;
      }

      let containerHeight = Garnish.$win.height() * 0.66;
      let containerWidth = Math.min(
        (containerHeight / 3) * 4,
        Garnish.$win.width() - instance.settings.minGutter * 2
      );
      containerHeight = (containerWidth / 4) * 3;

      const $img = instance.$container.find('img');

      $img.css({
        width: containerWidth,
        height: containerHeight,
      });

      let imageRatio;

      if (instance.loaded && $img.length) {
        // Make sure we maintain the ratio

        const maxWidth = $img.data('maxwidth');
        const maxHeight = $img.data('maxheight');
        imageRatio = maxWidth / maxHeight;
        const desiredWidth = instance.desiredWidth
          ? instance.desiredWidth
          : instance.getWidth();
        const desiredHeight = instance.desiredHeight
          ? instance.desiredHeight
          : instance.getHeight();
        let width = Math.min(desiredWidth, maxWidth);
        let height = Math.round(Math.min(maxHeight, width / imageRatio));

        if (height > desiredHeight) {
          height = desiredHeight;
        }

        width = Math.round(height * imageRatio);

        $img.css({width: width, height: height});
        instance._resizeContainer(width, height);

        instance.desiredWidth = width;
        instance.desiredHeight = height;
      }

      instance.base();

      if (instance.loaded && $img.length) {
        // Correct anomalies
        containerWidth = Math.round(
          Math.min(
            Math.max($img.height() * imageRatio),
            Garnish.$win.width() - instance.settings.minGutter * 2
          )
        );
        containerHeight = Math.round(
          Math.min(
            Math.max(containerWidth / imageRatio),
            Garnish.$win.height() - instance.settings.minGutter * 2
          )
        );
        containerWidth = Math.round(containerHeight * imageRatio);

        // This might actually have put width over the viewport limits, so double-check that
        if (
          containerWidth >
          Math.min(
            containerWidth,
            Garnish.$win.width() - instance.settings.minGutter * 2
          )
        ) {
          containerWidth = Math.min(
            containerWidth,
            Garnish.$win.width() - instance.settings.minGutter * 2
          );
          containerHeight = containerWidth / imageRatio;
        }

        instance._resizeContainer(containerWidth, containerHeight);
        $img.css({width: containerWidth, height: containerHeight});

        if (window.imageFocalPoint) {
          window.imageFocalPoint.renderFocal();
        }
      }
    },

    showForAsset: function ($element, elementSelect) {
      if (!$element.hasClass('element')) {
        $element = $element.find('.element:first');
      }
      if (
        !$element.hasClass('element') ||
        Garnish.hasAttr($element, 'data-folder-id')
      ) {
        return false;
      }

      const settings = {};
      if ($element.data('image-width')) {
        settings.startingWidth = $element.data('image-width');
        settings.startingHeight = $element.data('image-height');
      }
      new Craft.PreviewFileModal($element.data('id'), elementSelect, settings);
      return true;
    },
  }
);
