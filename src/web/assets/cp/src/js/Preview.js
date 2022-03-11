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
    $deviceTypeContainer: null,
    $orientationBtn: null,
    $refreshBtn: null,
    $deviceMask: null,
    $devicePreviewContainer: null,
    $iframe: null,
    iframeLoaded: false,
    $tempInput: null,
    $fieldPlaceholder: null,

    isActive: false,
    isVisible: false,
    activeTarget: 0,

    currentDeviceType: 'desktop',
    deviceOrientation: null,
    deviceWidth: '',
    deviceHeight: '',
    deviceMaskDimensions: {
        phone: {
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

        this._updateIframeProxy = this.updateIframe.bind(this);

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
            this.$statusMessage = $('<span/>', {'class': 'visually-hidden', 'aria-live': 'polite'}).appendTo($editorHeader);

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

                    $('<div class="flex-grow"/>').appendTo(this.$previewHeader);
                }

                this._buildDeviceTypeFieldset();

                $('<div class="flex-grow"/>').appendTo(this.$previewHeader);
                const $buttonContainer = $('<div class="buttons"/>').appendTo(this.$previewHeader);

                // Orientation toggle
                this.$orientationBtn = $('<button/>', {
                    type: 'button',
                    'class': 'btn disabled',
                    'data-icon': 'rotate',
                    disabled: '',
                    'text': Craft.t('app', 'Rotate'),
                    'aria-label': Craft.t('app', 'Rotate'),
                }).appendTo($buttonContainer);
                this.addListener(this.$orientationBtn, 'click', 'switchOrientation');

                // Refresh button
                this.$refreshBtn = $('<button/>', {
                    type: 'button',
                    class: 'btn hidden',
                    text: Craft.t('app', 'Refresh'),
                    'data-icon': 'refresh',
                }).appendTo($buttonContainer);
                this._updateRefreshBtn();
                this.addListener(this.$refreshBtn, 'click', () => {
                    this.updateIframe(false, true);
                });

                // Get the last stored orientation
                this.deviceOrientation = Craft.getLocalStorage('LivePreview.orientation');

                // Device type input change handler
                this.addListener($('input', this.$deviceTypeContainer), 'change', 'switchDeviceType');

            }

            this.$iframeContainer = $('<div/>', {'class': 'lp-iframe-container'}).appendTo(this.$previewContainer);
            this.$devicePreviewContainer = $('<div/>', {
                'class': 'lp-device-preview-container',
            }).appendTo(this.$iframeContainer);
            this.$deviceMask = $('<div/>', {
                'class': 'lp-device-mask',
            }).appendTo(this.$iframeContainer);

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

    _buildDeviceTypeFieldset: function() {
        this.$deviceTypeContainer = $('<fieldset/>', {
            class: 'lp-device-type',
        }).appendTo(this.$previewHeader);

        $('<legend/>', {
            text: Craft.t('app', 'Device type'),
            class: 'visually-hidden',
        }).appendTo(this.$deviceTypeContainer);

        const $radioGroup = $('<div/>', {
            class: 'lp-device-type__radio-group',
        }).appendTo(this.$deviceTypeContainer);

        // Desktop
        const $desktopWrapper = $('<div/>', {
            class: 'lp-device-type__item',
        }).appendTo($radioGroup);

        $('<input/>', {
            class: 'lp-device-type__input visually-hidden',
            type: 'radio',
            name: 'device',
            value: 'desktop',
            id: 'device-desktop',
            checked: true,
            data: {
                width: '',
                height: '',
            },
        }).appendTo($desktopWrapper);

        const $desktopLabel = $('<label/>', {
            for: 'device-desktop',
            class: 'btn lp-device-type__label lp-device-type__label--desktop active',
            title: Craft.t('app', 'Desktop'),
        }).appendTo($desktopWrapper);

        $('<span/>', {
            class: 'visually-hidden',
            text: Craft.t('app', 'Desktop'),
        }).appendTo($desktopLabel);

        // Tablet
        const $tabletWrapper = $('<div/>', {
            class: 'lp-device-type__item',
        }).appendTo($radioGroup);

        $('<input/>', {
            class: 'lp-device-type__input visually-hidden',
            type: 'radio',
            name: 'device',
            value: 'tablet',
            id: 'device-tablet',
            data: {
                width: 768,
                height: 1024,
            },
        }).appendTo($tabletWrapper);

        const $tabletLabel = $('<label/>', {
            for: 'device-tablet',
            class: 'btn lp-device-type__label lp-device-type__label--tablet',
            title: Craft.t('app', 'Tablet'),
        }).appendTo($tabletWrapper);

        $('<span/>', {
            class: 'visually-hidden',
            text: Craft.t('app', 'Tablet'),
        }).appendTo($tabletLabel);

        // Mobile
        const $mobileWrapper = $('<div/>', {
            class: 'lp-device-type__item',
        }).appendTo($radioGroup);

        $('<input/>', {
            class: 'lp-device-type__input visually-hidden',
            type: 'radio',
            name: 'device',
            value: 'phone',
            id: 'device-phone',
            data: {
                width: 375,
                height: 667,
            },
        }).appendTo($mobileWrapper);

        const $mobileLabel = $('<label/>', {
            for: 'device-phone',
            class: 'btn lp-device-type__label lp-device-type__label--phone',
            title: Craft.t('app', 'Mobile'),
        }).appendTo($mobileWrapper);

        $('<span/>', {
            class: 'visually-hidden',
            text: Craft.t('app', 'Mobile'),
        }).appendTo($mobileLabel);
    },

    _activeTarget: function() {
        return this.draftEditor.settings.previewTargets[this.activeTarget];
    },

    /**
     * @returns {boolean}
     * @private
     */
    _autoRefresh: function() {
        const target = this._activeTarget();
        return typeof typeof target.refresh === 'undefined' || !!target.refresh;
    },

    _updateRefreshBtn: function() {
        if (!this._autoRefresh()) {
            this.$refreshBtn.removeClass('hidden');
        } else {
            this.$refreshBtn.addClass('hidden');
        }
    },

    switchTarget: function(i) {
        this.activeTarget = i;
        this.$targetBtn.text(this.draftEditor.settings.previewTargets[i].label);
        this.$targetMenu.find('a.sel').removeClass('sel');
        this.$targetMenu.find('a').eq(i).addClass('sel');
        this.updateIframe(true);
        this._updateRefreshBtn();
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

        this.$previewContainer.show().velocity('stop').animateRight(0, 'slow');

        this.isVisible = true;

        Garnish.uiLayerManager.addLayer(this.$sidebar);
        Garnish.uiLayerManager.registerShortcut(Garnish.ESC_KEY, () => {
            this.close();
        });
    },

    close: function() {
        if (!this.isActive || !this.isVisible) {
            return;
        }

        this.trigger('beforeClose');

        $('html').removeClass('noscroll');

        this.removeListener(Garnish.$win, 'resize');
        Garnish.uiLayerManager.removeLayer();

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
            this.$iframeContainer.removeClass('lp-iframe-container--rotating');
            this.$previewContainer.hide();
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
        Garnish.$doc.trigger('scroll');
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

    /**
     * @param {boolean} [resetScroll=false]
     * @param {boolean} [refresh]
     */
    updateIframe: function(resetScroll, refresh) {
        if (!this.isActive) {
            return false;
        }

        // Ignore non-boolean resetScroll values
        resetScroll = resetScroll === true;

        // If the draft ID has changed or there's no iframe, we definitely need to refresh
        if (this.draftId !== (this.draftId = this.draftEditor.settings.draftId) || !this.$iframe) {
            refresh = true;
        }

        const target = this._activeTarget();
        if (typeof refresh === 'undefined') {
            refresh = resetScroll || this._autoRefresh();
        }

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
                'title': Craft.t('app', 'Preview'),
            });

            if (this.$iframe) {
                this.$iframe.replaceWith($iframe);
            } else {
                $iframe.appendTo(this.$devicePreviewContainer);
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
        return this.currentDeviceType !== 'desktop';
    },

    switchDeviceType: function(ev) {
        this.$iframeContainer.removeClass('lp-iframe-container--rotating');

        const $input = $(ev.target);
        const $inputWrapper = $input.closest('.lp-device-type__item');
        const newDeviceType = $input.val();

        // Bail if we’re just smashing the same button
        if (newDeviceType === this.currentDeviceType) {
            return false;
        }

        // Store new device type data
        this.currentDeviceType = newDeviceType;
        this.deviceWidth = $input.data('width');
        this.deviceHeight = $input.data('height');

        // Set the active state on the label
        this.$deviceTypeContainer.find('.btn')
            .removeClass('active');

        $inputWrapper.find('.btn').addClass('active');

        if (this.currentDeviceType === 'desktop') {
            // Disable the orientation button
            this.$orientationBtn
                .addClass('disabled')
                .attr('disabled', '');

            this.$iframeContainer.removeClass('lp-iframe-container--has-device-preview');
        } else {
            // Enable the orientation button
            this.$orientationBtn
                .removeClass('disabled')
                .removeAttr('disabled');

            this.$iframeContainer.addClass('lp-iframe-container--has-device-preview');
        }

        // Add the tablet class if needed
        if (this.currentDeviceType === 'tablet') {
            this.$iframeContainer.addClass('lp-iframe-container--tablet');
        } else {
            this.$iframeContainer.removeClass('lp-iframe-container--tablet');
        }

        if (this.currentDeviceType !== 'desktop') {
            this.updateDevicePreview();
        }
    },

    switchOrientation: function()
    {
        if (!this._devicePreviewIsActive()) {
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

        // Allow the animation to take place
        this.$iframeContainer.addClass('lp-iframe-container--rotating');

        // Update the device preview
        this.updateDevicePreview();

        setTimeout(() => {
            this.$iframeContainer.removeClass('lp-iframe-container--rotating');
        }, 300);
    },

    updateDevicePreview: function()
    {
        // Figure out the best zoom
        let hZoom = 1;
        let wZoom = 1;
        let zoom = 1;
        let previewHeight = (this.$previewContainer.height() - 50) - 48; // 50px for the header bar and 24px clearance
        let previewWidth = this.$previewContainer.width() - 48;
        let maskHeight = this.deviceMaskDimensions[this.currentDeviceType].height;
        let maskWidth = this.deviceMaskDimensions[this.currentDeviceType].width;

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
            width: this.deviceMaskDimensions[this.currentDeviceType].width + 'px',
            height: this.deviceMaskDimensions[this.currentDeviceType].height + 'px',
            transform: 'scale('+zoom+') translate('+translate+'%, '+translate+'%) rotate('+rotationDeg+')'
        });

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
