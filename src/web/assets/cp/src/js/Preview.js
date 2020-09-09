/** global: Craft */
/** global: Garnish */
/**
 * Preview
 */
Craft.Preview = Garnish.Base.extend(
    {
        draftEditor: null,

        $shade: null,
        $editorContainer: null,
        $editor: null,
        $spinner: null,
        $statusIcon: null,
        $dragHandle: null,
        $previewContainer: null,
        $iframeContainer: null,
        $targetBtn: null,
        $targetMenu: null,
        $iframe: null,
        iframeLoaded: false,
        $tempInput: null,
        $fieldPlaceholder: null,

        isActive: false,
        isVisible: false,
        activeTarget: 0,
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

            this._updateIframeProxy = $.proxy(this,'updateIframe');

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

                if (this.draftEditor.settings.previewTargets.length > 1) {
                    var $previewHeader = $('<header/>', {'class': 'lp-preview-header flex'}).appendTo(this.$previewContainer);
                    this.$targetBtn = $('<button/>', {
                        type: 'button',
                        'class': 'btn menubtn',
                        text: this.draftEditor.settings.previewTargets[0].label,
                    }).appendTo($previewHeader);
                    this.$targetMenu = $('<div/>', {'class': 'menu lp-target-menu'}).insertAfter(this.$targetBtn);
                    var $ul = $('<ul/>', {'class': 'padded'}).appendTo(this.$targetMenu);
                    var $li, $a;
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

                this.$iframeContainer = $('<div/>', {'class': 'lp-iframe-container'}).appendTo(this.$previewContainer);

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
                target: this.draftEditor.settings.previewTargets[i],
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
                target: target,
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

                this.trigger('afterUpdateIframe', {
                    target: this.draftEditor.settings.previewTargets[this.activeTarget],
                    $iframe: this.$iframe,
                });

                this.slideIn();
            });
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
    },
    {
        defaultEditorWidth: 0.33,
        minEditorWidthInPx: 320,
    });
