/** global: Craft */
/** global: Garnish */
/**
 * Preview File Modal
 */
Craft.PreviewFileModal = Garnish.Modal.extend(
    {
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
                Craft.PreviewFileModal.openInstance.loadAsset(assetId, settings.startingWidth, settings.startingHeight);
                Craft.PreviewFileModal.openInstance.elementSelect = elementSelect;
                return this.destroy();
            }

            Craft.PreviewFileModal.openInstance = this;
            this.elementSelect = elementSelect;

            this.$container = $('<div id="previewmodal" class="modal loading"/>').appendTo(Garnish.$bod);

            this.base(this.$container, $.extend({
                resizable: true
            }, settings));

            this.loadAsset(assetId, settings.startingWidth, settings.startingHeight);
        },

        /**
         * When hiding, remove all traces and focus last focused element.
         * @private
         */
        _onHide: function () {
            Craft.PreviewFileModal.openInstance = null;
            this.elementSelect.focusItem(this.elementSelect.$focusedItem);

            return this.destroy();
        },

        /**
         * Load an asset, using starting width and height, if applicable
         * @param assetId
         * @param startingWidth
         * @param startingHeight
         */
        loadAsset: function (assetId, startingWidth, startingHeight) {
            this.$container.empty();
            this.loaded = false;

            this.desiredHeight = null;
            this.desiredWidth = null;

            var containerHeight = this.updateSizeAndPosition._windowHeight * 0.66;
            var containerWidth = Math.min(containerHeight / 3 * 4, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2);
            containerHeight = containerWidth / 4 * 3;

            if (startingWidth && startingHeight) {
                var ratio = startingWidth / startingHeight;
                containerWidth =  Math.min(startingWidth, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2);
                containerHeight = Math.min(containerWidth / ratio, this.updateSizeAndPosition._windowHeight - this.settings.minGutter * 2);
                containerWidth = containerHeight * ratio;

                // This might actually have put width over the viewport limits, so doublecheck
                if (containerWidth > Math.min(startingWidth, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2)) {
                    containerWidth =  Math.min(startingWidth, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2);
                    containerHeight = containerWidth / ratio;
                }
            }

            this._resizeContainer(containerWidth, containerHeight);

            this.$spinner = $('<div class="spinner big centeralign"></div>').appendTo(this.$container);
            var top = (this.$container.height() / 2 - this.$spinner.height() / 2) + 'px',
                left = (this.$container.width() / 2 - this.$spinner.width() / 2) + 'px';

            this.$spinner.css({left: left, top: top, position: 'absolute'});
            this.requestId++;

            Craft.postActionRequest('assets/preview-file', {assetId: assetId, requestId: this.requestId}, function(response, textStatus) {
                if (textStatus === 'success') {
                    if (response.success) {
                        if (response.requestId != this.requestId) {
                            return;
                        }

                        this.$container.removeClass('loading');
                        this.$spinner.remove();

                        this.loaded = true;
                        this.$container.append(response.modalHtml);

                        var $highlight = this.$container.find('.highlight');

                        if ($highlight.length && $highlight.hasClass('json')) {
                            var $target = $highlight.find('code');

                            $target.html(JSON.stringify(JSON.parse($target.html()), undefined, 4));
                        }

                        if ($highlight.length) {
                            Prism.highlightAll();
                        } else {
                            this.$container.find('img').css({
                                width: containerWidth,
                                height: containerHeight
                            });
                        }

                        this.updateSizeAndPosition();
                    } else {
                        alert(response.error);

                        this.hide();
                    }
                }
            }.bind(this));
        },

        /**
         * Override default logic with some extra shenanigans
         */
        updateSizeAndPosition: function() {
            if (!this.loaded) {
                return;
            }

            var $img = this.$container.find('img');

            if (this.loaded && $img.length) {
                // Make sure we maintain the ratio

                var maxWidth = $img.data('maxwidth'),
                    maxHeight = $img.data('maxheight'),
                    imageRatio = maxWidth / maxHeight,
                    desiredWidth = this.desiredWidth ? this.desiredWidth : this.$container.width(),
                    desiredHeight = this.desiredHeight ? this.desiredHeight : this.$container.height(),
                    width = Math.min(desiredWidth, maxWidth),
                    height = Math.round(Math.min(maxHeight, width / imageRatio));

                width = Math.round(height * imageRatio);

                $img.css({'width': width, 'height': height});
                this._resizeContainer(width, height);

                this.desiredWidth = width;
                this.desiredHeight = height;

            }

            this.base();

            if (this.loaded && $img.length) {
                // Correct anomalities
                var containerWidth = Math.round(Math.min(Math.max($img.height() * imageRatio), this.updateSizeAndPosition._windowWidth - (this.settings.minGutter * 2))),
                    containerHeight = Math.round(Math.min(Math.max(containerWidth / imageRatio), this.updateSizeAndPosition._windowHeight - (this.settings.minGutter * 2)));
                    containerWidth = Math.round(containerHeight * imageRatio);

                // This might actually have put width over the viewport limits, so doublecheck that
                if (containerWidth > Math.min(containerWidth, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2)) {
                    containerWidth =  Math.min(containerWidth, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2);
                    containerHeight = containerWidth / imageRatio;
                }

                this._resizeContainer(containerWidth, containerHeight);

                $img.css({'width': containerWidth, 'height': containerHeight});
            } else if (this.loaded) {
                this.$container.find('.highlight')
                    .height(this.$container.height())
                    .width(this.$container.width())
                    .css({'overflow': 'auto'});
            }
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
                'top': (this.updateSizeAndPosition._windowHeight - containerHeight) / 2,
                'left': (this.updateSizeAndPosition._windowWidth - containerWidth) / 2
            });
        }
    },
    {
        defaultSettings: {
            startingWidth: null,
            startingHeight: null,
        }
    }
);
