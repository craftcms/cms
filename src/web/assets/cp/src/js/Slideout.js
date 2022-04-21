(function ($) {
  /** global: Craft */
  /** global: Garnish */
  /**
   * Slide Picker
   */
  Craft.Slideout = Garnish.Base.extend(
    {
      $outerContainer: null,
      $container: null,
      $shade: null,
      isOpen: false,

      init: function (contents, settings) {
        this.setSettings(settings, Craft.Slideout.defaults);

        if (!Garnish.isMobileBrowser()) {
          this.$shade = $('<div class="slideout-shade"/>').appendTo(
            Garnish.$bod
          );

          if (this.settings.closeOnShadeClick) {
            this.addListener(this.$shade, 'click', (ev) => {
              ev.stopPropagation();
              this.close();
            });
          }
        }

        this.$outerContainer = $('<div/>', {
          class: 'slideout-container hidden',
        });
        this.$container = $(
          `<${this.settings.containerElement}/>`,
          this.settings.containerAttributes
        )
          .attr('data-slideout', '')
          .addClass('slideout')
          .append(contents)
          .data('slideout', this)
          .appendTo(this.$outerContainer);

        Garnish.addModalAttributes(this.$outerContainer);

        if (Garnish.isMobileBrowser()) {
          this.$container.addClass('so-mobile');
        }

        Craft.trapFocusWithin(this.$container);

        if (this.settings.autoOpen) {
          this.open();
        }
      },

      open: function () {
        if (this.isOpen) {
          return;
        }

        this.setTriggerElement(document.activeElement);

        this._cancelTransitionListeners();

        // Move the shade + container to the end of <body> so they get the highest sub-z-indexes
        if (this.$shade) {
          this.$shade.appendTo(Garnish.$bod).show();
        }

        this.$outerContainer.appendTo(Garnish.$bod).removeClass('hidden');

        if (Garnish.isMobileBrowser()) {
          this.$container.css('top', '100vh');
        } else {
          this.$container.css(Garnish.ltr ? 'left' : 'right', '100vw');
        }

        this.$container.one('transitionend.slideout', () => {
          Craft.setFocusWithin(this.$container);
        });

        if (this.$shade) {
          this.$shade[0].offsetWidth;
          this.$shade.addClass('so-visible');
        }

        this.$container[0].offsetWidth;
        Craft.Slideout.addPanel(this);

        this.enable();
        Garnish.uiLayerManager.addLayer(this.$outerContainer);
        Garnish.hideModalBackgroundLayers();

        if (this.settings.closeOnEsc) {
          Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
            this.close();
          });
        }

        this.isOpen = true;
        this.trigger('open');
      },

      setTriggerElement: function (trigger) {
        this.settings.triggerElement = trigger;
      },

      close: function () {
        if (!this.isOpen) {
          return;
        }

        this.trigger('beforeClose');
        this.disable();
        this.isOpen = false;

        this._cancelTransitionListeners();

        if (this.$shade) {
          this.$shade
            .removeClass('so-visible')
            .one('transitionend.slideout', () => {
              this.$shade.hide();
            });
        }

        Craft.Slideout.removePanel(this);
        Garnish.uiLayerManager.removeLayer();
        Garnish.resetModalBackgroundLayerVisibility();
        this.$container.one('transitionend.slideout', () => {
          this.$outerContainer.addClass('hidden');
          this.trigger('close');
        });

        if (this.settings.triggerElement) {
          this.settings.triggerElement.focus();
        }
      },

      _cancelTransitionListeners: function () {
        if (this.$shade) {
          this.$shade.off('transitionend.slideout');
        }

        this.$container.off('transitionend.slideout');
      },

      /**
       * Destroy
       */
      destroy: function () {
        if (this.$shade) {
          this.$shade.remove();
          this.$shade = null;
        }

        this.$outerContainer.remove();
        this.$outerContainer = null;
        this.$container = null;

        this.base();
      },
    },
    {
      defaults: {
        containerElement: 'div',
        containerAttributes: {},
        autoOpen: true,
        closeOnEsc: true,
        closeOnShadeClick: true,
        triggerElement: null,
      },
      openPanels: [],
      addPanel: function (panel) {
        Craft.Slideout.openPanels.unshift(panel);
        if (Garnish.isMobileBrowser()) {
          panel.$container.css('top', 0);
        } else {
          Craft.Slideout.updateStyles();
        }
      },
      removePanel: function (panel) {
        Craft.Slideout.openPanels = Craft.Slideout.openPanels.filter(
          (m) => m !== panel
        );
        if (Garnish.isMobileBrowser()) {
          panel.$container.css('top', '100vh');
        } else {
          panel.$container.css(Garnish.ltr ? 'left' : 'right', '100vw');
          Craft.Slideout.updateStyles();
        }
      },
      updateStyles: function () {
        const totalPanels = Craft.Slideout.openPanels.length;
        Craft.Slideout.openPanels.forEach((panel, i) => {
          panel.$container.css(
            Garnish.ltr ? 'left' : 'right',
            `${50 * ((totalPanels - i) / totalPanels)}vw`
          );
        });

        if (totalPanels !== 0) {
          Garnish.$bod.addClass('no-scroll');
        } else {
          Garnish.$bod.removeClass('no-scroll');
        }
      },
    }
  );
})(jQuery);
