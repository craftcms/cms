/** global: Craft */
/** global: Garnish */
/**
 * Preview File Modal
 */
Craft.PreviewFileModal = Garnish.Modal.extend(
    {
        id: null,
        $spinner: null,
        type: null,
        loaded: null,

        init: function(assetId, settings) {
            this.id = assetId;

            this.$container = $('<div id="previewmodal" class="modal loading"/>').appendTo(Garnish.$bod);

            settings = $.extend(this.defaultSettings, settings);
            this.base(this.$container, $.extend({
                resizable: true
            }, settings));

            var containerHeight = this.updateSizeAndPosition._windowHeight * 0.66;
            var containerWidth = Math.min(containerHeight / 3 * 4, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2);
            containerHeight = containerWidth / 4 * 3;

            if (settings.startingWidth && settings.startingHeight) {
                containerWidth =  Math.min(settings.startingWidth, this.updateSizeAndPosition._windowWidth - this.settings.minGutter * 2);
                var ratio = settings.startingWidth / settings.startingHeight;
                containerHeight = Math.min(containerWidth / ratio, this.updateSizeAndPosition._windowHeight - this.settings.minGutter * 2);
                containerWidth = containerHeight * ratio;
            }

            this._resizeContainer(containerWidth, containerHeight);

            this.$spinner = $('<div class="spinner big centeralign"></div>').appendTo(this.$container);
            var top = (this.$container.height() / 2 - this.$spinner.height() / 2) + 'px',
                left = (this.$container.width() / 2 - this.$spinner.width() / 2) + 'px';

            this.$spinner.css({left: left, top: top, position: 'absolute'});

            Craft.postActionRequest('assets/preview-file', {assetId: this.id}, function(response, textStatus) {
                this.$container.removeClass('loading');
                this.$spinner.remove();

                if (textStatus === 'success') {
                    if (response.success) {
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

        updateSizeAndPosition: function() {
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
                var containerWidth = Math.round(Math.min(Math.max(200, $img.height() * imageRatio), this.updateSizeAndPosition._windowWidth - (this.settings.minGutter * 2))),
                    containerHeight = Math.round(Math.min(Math.max(200, containerWidth / imageRatio), this.updateSizeAndPosition._windowHeight - (this.settings.minGutter * 2)));

                containerWidth = Math.round(containerHeight * imageRatio);
                this._resizeContainer(containerWidth, containerHeight);

                $img.css({'width': containerWidth, 'height': containerHeight});
            } else if (this.loaded) {
                this.$container.find('.highlight')
                    .height(this.$container.height())
                    .width(this.$container.width())
                    .css({'overflow': 'auto'});
            }
        },

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
            startingHeight: null
        }
    }
);
