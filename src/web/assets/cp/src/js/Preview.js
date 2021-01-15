/** global: Craft */
/** global: Garnish */
/**
 * Preview
 */
Craft.Preview = Garnish.Base.extend({
    draftEditor: null,

    $shade: null,
    $editorContainer: null,
    $editor: null,
    $spinner: null,
    $statusIcon: null,
    $dragHandle: null,
    $previewContainer: null,
    $iframeContainer: null,
    $previewHeader: null,
    $targetBtn: null,
    $targetMenu: null,
    $breakpointButtons: null,
    $orientationBtn: null,
    $deviceMask: null,
    $devicePreviewContainer: null,
    $iframe: null,
    iframeLoaded: false,
    $tempInput: null,
    $fieldPlaceholder: null,

    isActive: false,
    isVisible: false,
    activeTarget: 0,

    isDeviceUpdating: false,
    deviceAnimationTimeout: null,
    currentBreakpoint: 'desktop',
    deviceOrientation: null,
    deviceWidth: '',
    deviceHeight: '',
    deviceMaskDimensions: {
        mobile: {
            width: 375,
            height: 753
        },
        tablet: {
            width: 768,
            height: 1110
        }
    },

    draftId: null,
    url: null,
    fields: null,

    iframeHeight: null,
    scrollTop: null,

    dragger: null,
    dragStartEditorWidth: null,

    _updateIframeProxy: null,

    _editorWidth: null,
    _editorWidthInPx: null,

    init: function(draftEditor) {
        this.draftEditor = draftEditor;

        this._updateIframeProxy = $.proxy(this, 'updateIframe');

        this.$tempInput = $('<input/>', {type: 'hidden', name: '__PREVIEW_FIELDS__', value: '1'});
        this.$fieldPlaceholder = $('<div/>');

        // Set the initial editor width
        this.editorWidth = Craft.getLocalStorage('LivePreview.editorWidth', Craft.Preview.defaultEditorWidth);
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
        if (inPx < Craft.Preview.minEditorWidthInPx) {
            inPx = Craft.Preview.minEditorWidthInPx;
            width = inPx / Garnish.$win.width();
        }

        this._editorWidth = width;
        this._editorWidthInPx = inPx;
    },

    open: function() {
        if (this.isActive) {
            return;
        }

        this.isActive = true;
        this.trigger('beforeOpen');

        $(document.activeElement).trigger('blur');

        if (!this.$editor) {
            this.$shade = $('<div/>', {'class': 'modal-shade dark'}).appendTo(Garnish.$bod);
            this.$previewContainer = $('<div/>', {'class': 'lp-preview-container'}).appendTo(Garnish.$bod);
            this.$editorContainer = $('<div/>', {'class': 'lp-editor-container'}).appendTo(Garnish.$bod);

            var $editorHeader = $('<header/>', {'class': 'flex'}).appendTo(this.$editorContainer);
            this.$editor = $('<form/>', {'class': 'lp-editor'}).appendTo(this.$editorContainer);
            this.$dragHandle = $('<div/>', {'class': 'lp-draghandle'}).appendTo(this.$editorContainer);
            var $closeBtn = $('<button/>', {
                type: 'button',
                class: 'btn',
                text: Craft.t('app', 'Close Preview'),
            }).appendTo($editorHeader);
            $('<div/>', {'class': 'flex-grow'}).appendTo($editorHeader);
            this.$spinner = $('<div/>', {'class': 'spinner hidden', title: Craft.t('app', 'Saving')}).appendTo($editorHeader);
            this.$statusIcon = $('<div/>', {'class': 'invisible'}).appendTo($editorHeader);

            if (Craft.Pro) {
                this.$previewHeader = $('<header/>', {'class': 'lp-preview-header'}).appendTo(this.$previewContainer);

                // Preview targets
                if (this.draftEditor.settings.previewTargets.length > 1) {
                    this.$targetBtn = $('<button/>', {
                        type: 'button',
                        'class': 'btn menubtn',
                        text: this.draftEditor.settings.previewTargets[0].label,
                    }).appendTo(this.$previewHeader);
                    this.$targetMenu = $('<div/>', {'class': 'menu lp-target-menu'}).insertAfter(this.$targetBtn);
                    const $ul = $('<ul/>', {'class': 'padded'}).appendTo(this.$targetMenu);
                    let $li, $a;
                    for (let i = 0; i < this.draftEditor.settings.previewTargets.length; i++) {
                        $li = $('<li/>').appendTo($ul)
                        $a = $('<a/>', {
                            data: {target: i},
                            text: this.draftEditor.settings.previewTargets[i].label,
                            'class': i === 0 ? 'sel' : null,
                        }).appendTo($li);
                    }
                    new Garnish.MenuBtn(this.$targetBtn, {
                        onOptionSelect: option => {
                            this.switchTarget($(option).data('target'));
                        },
                    });
                }

                // Breakpoint buttons
                this.$breakpointButtons = $('<div/>', {'class': 'btngroup lp-breakpoints'}).appendTo(this.$previewHeader);
                $('<div/>', {
                    'class': 'lp-breakpoint-btn lp-breakpoint-btn--desktop lp-breakpoint-btn--active',
                    title: Craft.t('app', 'Desktop'),
                    data: {
                        width: '',
                        height: '',
                        breakpoint: 'desktop'
                    }
                }).appendTo(this.$breakpointButtons);
                $('<div/>', {
                    'class': 'lp-breakpoint-btn lp-breakpoint-btn--tablet',
                    title: Craft.t('app', 'Tablet'),
                    data: {
                        width: 768,
                        height: 1024,
                        breakpoint: 'tablet'
                    }
                }).appendTo(this.$breakpointButtons);
                $('<div/>', {
                    'class': 'lp-breakpoint-btn lp-breakpoint-btn--mobile',
                    title: Craft.t('app', 'Mobile'),
                    data: {
                        width: 375,
                        height: 667,
                        breakpoint: 'mobile'
                    }
                }).appendTo(this.$breakpointButtons);

                // Orientation toggle
                this.$orientationBtn = $('<button/>', {
                    'class': 'btn',
                    'data-icon': 'refresh',
                    'text': Craft.t('app', 'Rotate')
                });
                this.addListener(this.$orientationBtn, 'click', 'switchOrientation');

                // Get the last stored orientation
                this.deviceOrientation = Craft.getLocalStorage('LivePreview.orientation');

                // Breakpoint button click handlers
                this.addListener($('.lp-breakpoint-btn', this.$breakpointButtons), 'click', 'switchBreakpoint');

                // Device mask
                this.$deviceMask = $('<div/>', {
                    'class': 'lp-device-mask'
                });
            }

            this.$iframeContainer = $('<div/>', {'class': 'lp-iframe-container'}).appendTo(this.$previewContainer);

            if (this.$deviceMask) {
                this.$iframeContainer.append(this.$deviceMask);
            }

            this.dragger = new Garnish.BaseDrag(this.$dragHandle, {
                axis: Garnish.X_AXIS,
                onDragStart: this._onDragStart.bind(this),
                onDrag: this._onDrag.bind(this),
                onDragStop: this._onDragStop.bind(this),
            });

            this.addListener($closeBtn, 'click', 'close');
            this.addListener(this.$statusIcon, 'click', () => {
                this.draftEditor.showStatusHud(this.$statusIcon);
            });
        }

        // Set the sizes
        this.handleWindowResize();
        this.addListener(Garnish.$win, 'resize', 'handleWindowResize');

        this.$editorContainer.css(Craft.left, -this.editorWidthInPx + 'px');
        this.$previewContainer.css(Craft.right, -this.getIframeWidth());

        // Find the fields, excluding nested fields
        this.fields = [];
        var $fields = $('#content .field').not($('#content .field .field'));

        if ($fields.length) {
            // Insert our temporary input before the first field so we know where to swap in the serialized form values
            this.$tempInput.insertBefore($fields.get(0));

            // Move all the fields into the editor rather than copying them
            // so any JS that's referencing the elements won't break.
            for (let i = 0; i < $fields.length; i++) {
                let $field = $($fields[i]),
                    $clone = this._getClone($field);

                // It's important that the actual field is added to the DOM *after* the clone,
                // so any radio buttons in the field get deselected from the clone rather than the actual field.
                this.$fieldPlaceholder.insertAfter($field);
                $field.detach();
                this.$fieldPlaceholder.replaceWith($clone);
                $field.appendTo(this.$editor);

                this.fields.push({
                    $field: $field,
                    $clone: $clone
                });
            }
        }

        this.updateIframe();

        this.draftEditor.on('update', this._updateIframeProxy);
        Garnish.on(Craft.BaseElementEditor, 'saveElement', this._updateIframeProxy);
        Garnish.on(Craft.AssetImageEditor, 'save', this._updateIframeProxy);

        Craft.ElementThumbLoader.retryAll();

        this.trigger('open');
    },

    switchTarget: function(i) {
        this.activeTarget = i;
        this.$targetBtn.text(this.draftEditor.settings.previewTargets[i].label);
        this.$targetMenu.find('a.sel').removeClass('sel');
        this.$targetMenu.find('a').eq(i).addClass('sel');
        this.updateIframe(true);
        this.trigger('switchTarget', {
            previewTarget: this.draftEditor.settings.previewTargets[i],
        });
    },

    handleWindowResize: function() {
        // Reset the width so the min width is enforced
        this.editorWidth = this.editorWidth;

        // Update the editor/iframe sizes
        this.updateWidths();
    },

    slideIn: function() {
        if (!this.isActive || this.isVisible) {
            return;
        }

        $('html').addClass('noscroll');
        this.$shade.velocity('fadeIn');

        this.$editorContainer.show().velocity('stop').animateLeft(0, 'slow', () => {
            this.trigger('slideIn');
            Garnish.$win.trigger('resize');
        });

        this.$previewContainer.show().velocity('stop').animateRight(0, 'slow', () => {
            this.addListener(Garnish.$bod, 'keyup', function(ev) {
                if (ev.keyCode === Garnish.ESC_KEY) {
                    this.close();
                }
            });
        });

        this.isVisible = true;
    },

    close: function() {
        if (!this.isActive || !this.isVisible) {
            return;
        }

        this.trigger('beforeClose');

        $('html').removeClass('noscroll');

        this.removeListener(Garnish.$win, 'resize');
        this.removeListener(Garnish.$bod, 'keyup');

        // Remove our temporary input and move the preview fields back into place
        this.$tempInput.detach();
        this.moveFieldsBack();

        this.$shade.delay(200).velocity('fadeOut');

        this.$editorContainer.velocity('stop').animateLeft(-this.editorWidthInPx, 'slow', () => {
            for (var i = 0; i < this.fields.length; i++) {
                this.fields[i].$newClone.remove();
            }
            this.$editorContainer.hide();
            this.trigger('slideOut');
        });

        this.$previewContainer.velocity('stop').animateRight(-this.getIframeWidth(), 'slow', () => {
            this.$previewContainer.hide();
            this.resetDevicePreview();
        });

        this.draftEditor.off('update', this._updateIframeProxy);
        Garnish.off(Craft.BaseElementEditor, 'saveElement', this._updateIframeProxy);
        Garnish.off(Craft.AssetImageEditor, 'save', this._updateIframeProxy);

        Craft.ElementThumbLoader.retryAll();

        this.isActive = false;
        this.isVisible = false;
        this.trigger('close');
    },

    moveFieldsBack: function() {
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

    getIframeWidth: function() {
        return Garnish.$win.width() - this.editorWidthInPx;
    },

    updateWidths: function() {
        this.$editorContainer.css('width', this.editorWidthInPx + 'px');
        this.$previewContainer.width(this.getIframeWidth());
        if (this._devicePreviewIsActive()) {
            this.updateDevicePreview();
        }
    },

    _useIframeResizer: function() {
        return Craft.previewIframeResizerOptions !== false;
    },

    updateIframe: function(resetScroll) {
        if (!this.isActive) {
            return false;
        }

        // Ignore non-boolean resetScroll values
        resetScroll = resetScroll === true;

        var target = this.draftEditor.settings.previewTargets[this.activeTarget];
        var refresh = !!(
            this.draftId !== (this.draftId = this.draftEditor.settings.draftId) ||
            !this.$iframe ||
            resetScroll ||
            typeof target.refresh === 'undefined' ||
            target.refresh
        );

        this.trigger('beforeUpdateIframe', {
            previewTarget: target,
            resetScroll: resetScroll,
            refresh: refresh,
        });

        // If this is an existing preview target, make sure it wants to be refreshed automatically
        if (!refresh) {
            this.slideIn();
            return;
        }

        this.draftEditor.getTokenizedPreviewUrl(target.url, 'x-craft-live-preview').then(url => {
            // Maintain the current scroll position?
            let sameHost;
            if (resetScroll) {
                this.scrollTop = null;
            } else if (this.iframeLoaded && this.$iframe) {
                if (this._useIframeResizer()) {
                    this.iframeHeight = this.$iframe.height();
                    this.scrollTop = this.$iframeContainer.scrollTop();
                } else {
                    sameHost = Craft.isSameHost(url);
                    if (sameHost && this.$iframe[0].contentWindow) {
                        this.scrollTop = $(this.$iframe[0].contentWindow.document).scrollTop();
                    }
                }
            }

            this.iframeLoaded = false;

            var $iframe = $('<iframe/>', {
                'class': 'lp-preview',
                frameborder: 0,
                src: url,
            });

            if (this.$iframe) {
                this.$iframe.replaceWith($iframe);
            } else {
                $iframe.appendTo(this.$iframeContainer);
            }

            // If we’re on a device breakpoint then wrap the iframe in our own container
            // so we can keep all the iFrameResizer() stuff working
            if (this._devicePreviewIsActive()) {
                if (!this.$devicePreviewContainer) {
                    this.$devicePreviewContainer = $('<div/>', {
                        'class': 'lp-device-preview-container'
                    });
                    $iframe.wrap('<div class="lp-device-preview-container"></div>');
                    this.$devicePreviewContainer = this.$iframeContainer.find('.lp-device-preview-container');
                }
            }

            // Keep the iframe height consistent with its content
            if (this._useIframeResizer()) {
                if (!resetScroll && this.iframeHeight !== null) {
                    $iframe.height(this.iframeHeight);
                    this.$iframeContainer.scrollTop(this.scrollTop);
                }

                iFrameResize($.extend({
                    checkOrigin: false,
                    // Allow iframe scrolling until we've successfully initialized the resizer
                    scrolling: true,
                    onInit: iframe => {
                        this.iframeLoaded = true;
                        this.iframeHeight = null;
                        this.scrollTop = null;
                        iframe.scrolling = 'no';
                    },
                }, Craft.previewIframeResizerOptions || {}), $iframe[0]);
            } else {
                $iframe.on('load', () => {
                    this.iframeLoaded = true;
                    if (!resetScroll && sameHost && this.scrollTop !== null) {
                        $($iframe[0].contentWindow.document).scrollTop(this.scrollTop);
                    }
                });
            }

            this.url = url;
            this.$iframe = $iframe;

            if (this._devicePreviewIsActive()) {
                this.updateDevicePreview();
            }

            this.trigger('afterUpdateIframe', {
                previewTarget: this.draftEditor.settings.previewTargets[this.activeTarget],
                $iframe: this.$iframe,
            });

            this.slideIn();
        });
    },

    _devicePreviewIsActive: function() {
        return this.currentBreakpoint !== 'desktop';
    },

    switchBreakpoint: function(ev) {
        if (this.isDeviceUpdating) {
            return false;
        }

        const $btn = $(ev.target);
        const newBreakpoint = $btn.data('breakpoint');

        // Bail if we’re just smashing the same button
        if (newBreakpoint === this.currentBreakpoint) {
            return false;
        }

        // Store breakpoint data
        this.currentBreakpoint = newBreakpoint;
        this.deviceWidth = $btn.data('width');
        this.deviceHeight = $btn.data('height');

        // Set the active state on the button
        $('.lp-breakpoint-btn', this.$breakpointButtons).removeClass('lp-breakpoint-btn--active');
        $btn.addClass('lp-breakpoint-btn--active');

        // Update or reset
        if (this.currentBreakpoint === 'desktop') {
            this.resetDevicePreview();
        } else {
            this.updateDevicePreview();
        }
    },

    switchOrientation: function()
    {
        if (this.isDeviceUpdating) {
            return false;
        }

        // Switch to whichever orientation is currently not stored
        if (!this.deviceOrientation || this.deviceOrientation === 'portrait') {
            this.deviceOrientation = 'landscape';
        } else {
            this.deviceOrientation = 'portrait';
        }

        // Store the new one
        Craft.setLocalStorage('LivePreview.orientation', this.deviceOrientation);

        // Update the device preview
        this.updateDevicePreview();
    },

    updateDevicePreview: function()
    {
        if (this.isDeviceUpdating) {
            return false;
        }

        this.isDeviceUpdating = true;
        this.$iframeContainer.addClass('lp-iframe-container--animating');

        // Add the orientation button to the header bar
        this.$previewHeader.append(this.$orientationBtn);

        // Trigger the resized css mods
        this.$iframeContainer.addClass('lp-iframe-container--resized');

        // Add the tablet class if needed
        if (this.currentBreakpoint === 'tablet') {
            this.$iframeContainer.addClass('lp-iframe-container--tablet');
        } else {
            this.$iframeContainer.removeClass('lp-iframe-container--tablet');
        }

        // Figure out the best zoom
        let hZoom = 1;
        let wZoom = 1;
        let zoom = 1;
        let previewHeight = (this.$previewContainer.height() - 50) - 48; // 50px for the header bar and 24px clearance
        let previewWidth = this.$previewContainer.width() - 48;
        let maskHeight = this.deviceMaskDimensions[this.currentBreakpoint].height;
        let maskWidth = this.deviceMaskDimensions[this.currentBreakpoint].width;

        if (this.deviceOrientation === 'landscape') {
            if (previewWidth < maskHeight) {
                hZoom = previewWidth / maskHeight;
            }
            if (previewHeight < maskWidth) {
                wZoom = previewHeight / maskWidth;
            }
        } else {
            if (previewHeight < maskHeight) {
                hZoom = previewHeight / maskHeight;
            }
            if (previewWidth < maskWidth) {
                wZoom = previewWidth / maskWidth;
            }
        }

        zoom = hZoom;
        if (wZoom < hZoom) {
            zoom = wZoom;
        }

        // Figure out the css values
        const translate = -((100/zoom)/2);
        const rotationDeg = this.deviceOrientation === 'landscape' ? '-90deg' : '0deg';

        // Apply first to the device mask
        this.$deviceMask.css({
            width: this.deviceMaskDimensions[this.currentBreakpoint].width + 'px',
            height: this.deviceMaskDimensions[this.currentBreakpoint].height + 'px',
            transform: 'scale('+zoom+') translate('+translate+'%, '+translate+'%) rotate('+rotationDeg+')'
        });

        // Ping the iframe
        // TODO: if we just pass true here we lose the scroll position ... should only pass it if coming from a device click
        // TODO: also, in this method we should not run iFrameResizer if the device preview is active
        this.updateIframe();

        // After the animation duration we can update the iframe sizes and show it
        if (this.deviceAnimationTimeout) {
            clearTimeout(this.deviceAnimationTimeout);
        }
        this.deviceAnimationTimeout = setTimeout($.proxy(function() {

            // Then make the size change to the preview container
            if (this.deviceOrientation === 'landscape') {
                this.$devicePreviewContainer.css({
                    width: this.deviceHeight + 'px',
                    height: this.deviceWidth + 'px',
                    transform: 'scale('+zoom+') translate('+translate+'%, '+translate+'%)',
                    marginTop: 0,
                    marginLeft: '-' + (12*zoom) + 'px'
                });
            } else {
                this.$devicePreviewContainer.css({
                    width: this.deviceWidth + 'px',
                    height: this.deviceHeight + 'px',
                    transform: 'scale('+zoom+') translate('+translate+'%, '+translate+'%)',
                    marginTop: '-' + (12*zoom) + 'px',
                    marginLeft: 0
                });
            }

            // Remove the animating class and show the iframe
            this.$iframeContainer.removeClass('lp-iframe-container--animating');
            this.isDeviceUpdating = false;

        }, this), 300);
    },

    resetDevicePreview: function()
    {
        if (this.deviceAnimationTimeout) {
            clearTimeout(this.deviceAnimationTimeout);
        }
        this.currentBreakpoint = 'desktop';
        this.$breakpointButtons.find('.lp-breakpoint-btn').removeClass('lp-breakpoint-btn--active');
        this.$breakpointButtons.find('.lp-breakpoint-btn--desktop').addClass('lp-breakpoint-btn--active');
        this.$orientationBtn.detach();
        this.$iframeContainer.removeClass('lp-iframe-container--animating');
        this.$iframeContainer.removeClass('lp-iframe-container--resized');
        this.$iframeContainer.removeClass('lp-iframe-container--tablet');

        // Flat out remove the iframe and let it get regenerated
        if (this.$devicePreviewContainer) {
            // If using iFrameResizer then remove the listeners first so we don’t get zombie instances
            if (this._useIframeResizer()) {
                this.$iframe[0].iFrameResizer.removeListeners();
            }
            this.$devicePreviewContainer.detach();
            this.$devicePreviewContainer = null;
            this.$iframe = null;
            this.updateIframe();
        }

        this.isDeviceUpdating = false;
    },

    _getClone: function($field) {
        var $clone = $field.clone();

        // clone() won't account for input values that have changed since the original HTML set them
        Garnish.copyInputValues($field, $clone);

        // Remove any id= attributes
        $clone.attr('id', '');
        $clone.find('[id]').attr('id', '');

        // Disable anything with a name attribute
        $clone.find('[name]').prop('disabled', true);

        return $clone;
    },

    _onDragStart: function() {
        this.dragStartEditorWidth = this.editorWidthInPx;
        this.$previewContainer.addClass('dragging');
    },

    _onDrag: function() {
        if (Craft.orientation === 'ltr') {
            this.editorWidth = this.dragStartEditorWidth + this.dragger.mouseDistX;
        } else {
            this.editorWidth = this.dragStartEditorWidth - this.dragger.mouseDistX;
        }

        this.updateWidths();
    },

    _onDragStop: function() {
        this.$previewContainer.removeClass('dragging');
        Craft.setLocalStorage('LivePreview.editorWidth', this.editorWidth);
    }
}, {
    defaultEditorWidth: 0.33,
    minEditorWidthInPx: 320,
});
