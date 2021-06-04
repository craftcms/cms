(function($) {
    /** global: Craft */
    /** global: Garnish */
    /**
     * Slide Picker
     */
    Craft.Slideout = Garnish.Base.extend({
        $container: null,
        $shade: null,
        isOpen: false,

        init: function(contents, settings) {
            this.setSettings(settings, Craft.Slideout.defaults);

            if (!Craft.Slideout.isMobile()) {
                this.$shade = $('<div class="slideout-shade"/>')
                    .appendTo(Garnish.$bod);

                if (this.settings.closeOnShadeClick) {
                    this.addListener(this.$shade, 'click', ev => {
                        ev.stopPropagation();
                        this.close();
                    });
                }
            }

            this.$container = $(`<${this.settings.containerElement}/>`, this.settings.containerAttributes)
                .addClass('slideout hidden')
                .append(contents)
                .data('slideout', this);

            if (Craft.Slideout.isMobile()) {
                this.$container.addClass('so-mobile');
            }

            Craft.trapFocusWithin(this.$container);

            if (this.settings.autoOpen) {
                this.open();
            }
        },

        open: function() {
            if (this.isOpen) {
                return;
            }

            this._cancelTransitionListeners();

            // Move the shade + container to the end of <body> so they get the highest sub-z-indexes
            if (this.$shade) {
                this.$shade
                    .appendTo(Garnish.$bod)
                    .show();
            }

            this.$container
                .appendTo(Garnish.$bod)
                .removeClass('hidden');

            if (Craft.Slideout.isMobile()) {
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
            Garnish.shortcutManager.addLayer();

            if (this.settings.closeOnEsc) {
                Garnish.shortcutManager.registerShortcut(Garnish.ESC_KEY, () => {
                    this.close();
                });
            }

            this.isOpen = true;
            this.trigger('open');
        },

        close: function() {
            if (!this.isOpen) {
                return;
            }

            this.trigger('beforeClose');
            this.disable();
            this.isOpen = false;

            this._cancelTransitionListeners();

            if (this.$shade) {
                this.removeListener(this.$shade, 'click');
                this.$shade
                    .removeClass('so-visible')
                    .one('transitionend.slideout', () => {
                        this.$shade.hide();
                    });
            }

            Craft.Slideout.removePanel(this);
            Garnish.shortcutManager.removeLayer();
            this.$container.one('transitionend.slideout', () => {
                this.$container.addClass('hidden');
                this.trigger('close');
            });
        },

        _cancelTransitionListeners: function() {
            if (this.$shade) {
                this.$shade.off('transitionend.slideout');
            }

            this.$container.off('transitionend.slideout');
        },

        /**
         * Destroy
         */
        destroy: function() {
            if (this.$shade) {
                this.$shade.remove();
                this.$shade = null;
            }

            this.$container.remove();
            this.$container = null;

            this.base();
        },
    }, {
        defaults: {
            containerElement: 'div',
            containerAttributes: {},
            autoOpen: true,
            closeOnEsc: true,
            closeOnShadeClick: true,
        },
        openPanels: [],
        addPanel: function(panel) {
            Craft.Slideout.openPanels.unshift(panel);
            if (Craft.Slideout.isMobile()) {
                panel.$container.css('top', 0);
            } else {
                Craft.Slideout.updateStyles();
            }
        },
        removePanel: function(panel) {
            Craft.Slideout.openPanels = Craft.Slideout.openPanels.filter(m => m !== panel);
            if (Craft.Slideout.isMobile()) {
                panel.$container.css('top', '100vh');
            } else {
                panel.$container.css(Garnish.ltr ? 'left' : 'right', '100vw');
                Craft.Slideout.updateStyles();
            }
        },
        updateStyles: function() {
            const totalPanels = Craft.Slideout.openPanels.length;
            Craft.Slideout.openPanels.forEach((panel, i) => {
                panel.$container.css(Garnish.ltr ? 'left' : 'right', `${50 * ((totalPanels - i) / totalPanels)}vw`);
            });

            if (totalPanels !== 0) {
                Garnish.$bod.addClass('no-scroll');
            } else {
                Garnish.$bod.removeClass('no-scroll');
            }
        },
        isMobile: function() {
            return Garnish.isMobileBrowser(true);
        },
    });
})(jQuery);
