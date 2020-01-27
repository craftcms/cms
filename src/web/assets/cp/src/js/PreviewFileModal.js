/** global: Craft */
/** global: Garnish */
/**
 * Preview File Modal
 */
Craft.PreviewFileModal = Garnish.Modal.extend(
    {
        assetId: null,
        $spinner: null,
        elementSelect: null,
        type: null,
        loaded: null,
        requestId: 0,

        /**
         * Initialize the preview file modal.
         * @returns {*|void}
         */
        init: function(assetId, elementSelect, settings) {
            settings = $.extend(this.defaultSettings, settings);

            settings.onHide = this._onHide.bind(this);

            if (Craft.PreviewFileModal.openInstance) {
                var instance = Craft.PreviewFileModal.openInstance;

                if (instance.assetId !== assetId) {
                    instance.loadAsset(assetId, settings.startingWidth, settings.startingHeight);
                    instance.elementSelect = elementSelect;
                }

                return this.destroy();
            }

            Craft.PreviewFileModal.openInstance = this;
            this.elementSelect = elementSelect;

            this.$container = $('<div class="modal previewmodal loading"/>').appendTo(Garnish.$bod);

            this.base(this.$container, $.extend({
                resizable: true
            }, settings));

            // Cut the flicker, just show the nice person the preview.
            if (this.$container) {
                this.$container.velocity('stop');
                this.$container.show().css('opacity', 1);

                this.$shade.velocity('stop');
                this.$shade.show().css('opacity', 1);
            }

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
            }

            this.$shade.remove();

            return this.destroy();
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

            return true;
        },

        /**
         * Load an asset, using starting width and height, if applicable
         * @param assetId
         * @param startingWidth
         * @param startingHeight
         */
        loadAsset: function (assetId, startingWidth, startingHeight) {
            this.assetId = assetId;

            this.$container.empty();
            this.loaded = false;

            this.desiredHeight = null;
            this.desiredWidth = null;

            var containerHeight = Garnish.$win.height() * 0.66;
            var containerWidth = Math.min(containerHeight / 3 * 4, Garnish.$win.width() - this.settings.minGutter * 2);
            containerHeight = containerWidth / 4 * 3;

            if (startingWidth && startingHeight) {
                var ratio = startingWidth / startingHeight;
                containerWidth =  Math.min(startingWidth, Garnish.$win.width() - this.settings.minGutter * 2);
                containerHeight = Math.min(containerWidth / ratio, Garnish.$win.height() - this.settings.minGutter * 2);
                containerWidth = containerHeight * ratio;

                // This might actually have put width over the viewport limits, so doublecheck
                if (containerWidth > Math.min(startingWidth, Garnish.$win.width() - this.settings.minGutter * 2)) {
                    containerWidth =  Math.min(startingWidth, Garnish.$win.width() - this.settings.minGutter * 2);
                    containerHeight = containerWidth / ratio;
                }
            }

            this._resizeContainer(containerWidth, containerHeight);

            this.$spinner = $('<div class="spinner centeralign"></div>').appendTo(this.$container);
            var top = (this.$container.height() / 2 - this.$spinner.height() / 2) + 'px',
                left = (this.$container.width() / 2 - this.$spinner.width() / 2) + 'px';

            this.$spinner.css({left: left, top: top, position: 'absolute'});
            this.requestId++;

            Craft.postActionRequest('assets/preview-file', {assetId: assetId, requestId: this.requestId}, function(response, textStatus) {
                this.$container.removeClass('loading');
                this.$spinner.remove();
                this.loaded = true;

                if (textStatus === 'success') {
                    if (response.success) {
                        if (response.requestId != this.requestId) {
                            return;
                        }

                        if (!response.previewHtml) {
                            this.$container.addClass('zilch');
                            this.$container.append($('<p/>', {text: Craft.t('app', 'No preview available.')}));
                            return;
                        }

                        this.$container.removeClass('zilch');
                        this.$container.append(response.previewHtml);
                        Craft.appendHeadHtml(response.headHtml);
                        Craft.appendFootHtml(response.footHtml);
                    } else {
                        alert(response.error);

                        this.hide();
                    }
                }
            }.bind(this));
        },

        /**
         * Resize the container to specified dimensions
         * @param containerWidth
         * @param containerHeight
         * @private
         */
        _resizeContainer: function (containerWidth, containerHeight) {
            this.$container.css({
                'width': containerWidth,
                'min-width': containerWidth,
                'max-width': containerWidth,
                'height': containerHeight,
                'min-height': containerHeight,
                'max-height': containerHeight,
                'top': (Garnish.$win.height() - containerHeight) / 2,
                'left': (Garnish.$win.width() - containerWidth) / 2
            });
        }
    },
    {
        defaultSettings: {
            startingWidth: null,
            startingHeight: null
        }
    }
);
