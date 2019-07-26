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
        $targetBtn: null,
        $targetMenu: null,
        $iframe: null,
        $tempInput: null,
        $fieldPlaceholder: null,

        isActive: false,
        activeTarget: 0,
        url: null,
        fields: null,

        scrollLeft: 0,
        scrollTop: 0,

        dragger: null,
        dragStartEditorWidth: null,

        _slideInOnIframeLoad: false,
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
                this.$editorContainer = $('<div/>', {'class': 'lp-editor-container'}).appendTo(Garnish.$bod);
                this.$previewContainer = $('<div/>', {'class': 'lp-preview-container'}).appendTo(Garnish.$bod);

                var $editorHeader = $('<header/>', {'class': 'flex'}).appendTo(this.$editorContainer);
                this.$editor = $('<form/>', {'class': 'lp-editor'}).appendTo(this.$editorContainer);
                this.$dragHandle = $('<div/>', {'class': 'lp-draghandle'}).appendTo(this.$editorContainer);
                var $closeBtn = $('<div/>', {'class': 'btn', text: Craft.t('app', 'Close Preview')}).appendTo($editorHeader);
                $('<div/>', {'class': 'flex-grow'}).appendTo($editorHeader);
                this.$spinner = $('<div/>', {'class': 'spinner hidden', title: Craft.t('app', 'Saving')}).appendTo($editorHeader);
                this.$statusIcon = $('<div/>', {'class': 'invisible'}).appendTo($editorHeader);

                if (this.draftEditor.settings.previewTargets.length > 1) {
                    var $previewHeader = $('<header/>', {'class': 'flex'}).appendTo(this.$previewContainer);
                    this.$targetBtn = $('<div/>', {
                        'class': 'btn menubtn',
                        text: this.draftEditor.settings.previewTargets[0].label,
                        role: 'btn',
                    }).appendTo($previewHeader);
                    this.$targetMenu = $('<div/>', {'class': 'menu lp-target-menu'}).insertAfter(this.$targetBtn);
                    var $ul = $('<ul/>', {'class': 'padded'}).appendTo(this.$targetMenu);
                    var $li, $a;
                    for (var i = 0; i < this.draftEditor.settings.previewTargets.length; i++) {
                        $li = $('<li/>').appendTo($ul)
                        $a = $('<a/>', {
                            data: {target: i},
                            text: this.draftEditor.settings.previewTargets[i].label,
                            'class': i === 0 ? 'sel' : null,
                        }).appendTo($li);
                    }
                    new Garnish.MenuBtn(this.$targetBtn, {
                        onOptionSelect: $.proxy(function(option) {
                            this.switchTarget($(option).data('target'));
                        }, this)
                    });
                }

                this.dragger = new Garnish.BaseDrag(this.$dragHandle, {
                    axis: Garnish.X_AXIS,
                    onDragStart: $.proxy(this, '_onDragStart'),
                    onDrag: $.proxy(this, '_onDrag'),
                    onDragStop: $.proxy(this, '_onDragStop')
                });

                this.addListener($closeBtn, 'click', 'close');
                this.addListener(this.$statusIcon, 'click', function() {
                    this.draftEditor.showStatusHud(this.$statusIcon);
                }.bind(this));
            }

            // Set the sizes
            this.handleWindowResize();
            this.addListener(Garnish.$win, 'resize', 'handleWindowResize');

            this.$editorContainer.css(Craft.left, -(this.editorWidthInPx + Craft.Preview.dragHandleWidth) + 'px');
            this.$previewContainer.css(Craft.right, -this.getIframeWidth());

            // Find the fields, excluding nested fields
            this.fields = [];
            var $fields = $('#content .field').not($('#content .field .field'));

            if ($fields.length) {
                // Insert our temporary input before the first field so we know where to swap in the serialized form values
                this.$tempInput.insertBefore($fields.get(0));

                // Move all the fields into the editor rather than copying them
                // so any JS that's referencing the elements won't break.
                for (var i = 0; i < $fields.length; i++) {
                    var $field = $($fields[i]),
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

            this._slideInOnIframeLoad = true;
            this.updateIframe();

            this.draftEditor.on('update', this._updateIframeProxy);
            Garnish.on(Craft.BaseElementEditor, 'saveElement', this._updateIframeProxy);
            Garnish.on(Craft.AssetImageEditor, 'save', this._updateIframeProxy);

            this.trigger('open');
        },

        switchTarget: function(i) {
            this.activeTarget = i;
            this.$targetBtn.text(this.draftEditor.settings.previewTargets[i].label);
            this.$targetMenu.find('a.sel').removeClass('sel');
            this.$targetMenu.find('a').eq(i).addClass('sel');
            this.updateIframe(true);
        },

        handleWindowResize: function() {
            // Reset the width so the min width is enforced
            this.editorWidth = this.editorWidth;

            // Update the editor/iframe sizes
            this.updateWidths();
        },

        slideIn: function() {
            $('html').addClass('noscroll');
            this.$shade.velocity('fadeIn');

            this.$editorContainer.show().velocity('stop').animateLeft(0, 'slow', $.proxy(function() {
                this.trigger('slideIn');
                Garnish.$win.trigger('resize');
            }, this));

            this.$previewContainer.show().velocity('stop').animateRight(0, 'slow', $.proxy(function() {
                this.addListener(Garnish.$bod, 'keyup', function(ev) {
                    if (ev.keyCode === Garnish.ESC_KEY) {
                        this.close();
                    }
                });
            }, this));
        },

        close: function() {
            if (!this.isActive) {
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

            this.$editorContainer.velocity('stop').animateLeft(-(this.editorWidthInPx + Craft.Preview.dragHandleWidth), 'slow', $.proxy(function() {
                for (var i = 0; i < this.fields.length; i++) {
                    this.fields[i].$newClone.remove();
                }
                this.$editorContainer.hide();
                this.trigger('slideOut');
            }, this));

            this.$previewContainer.velocity('stop').animateRight(-this.getIframeWidth(), 'slow', $.proxy(function() {
                this.$previewContainer.hide();
            }, this));

            this.draftEditor.off('update', this._updateIframeProxy);
            Garnish.off(Craft.BaseElementEditor, 'saveElement', this._updateIframeProxy);
            Garnish.off(Craft.AssetImageEditor, 'save', this._updateIframeProxy);

            this.isActive = false;
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
            return Garnish.$win.width() - (this.editorWidthInPx + Craft.Preview.dragHandleWidth);
        },

        updateWidths: function() {
            this.$editorContainer.css('width', this.editorWidthInPx + 'px');
            this.$previewContainer.width(this.getIframeWidth());
        },

        updateIframe: function(resetScroll) {
            if (!this.isActive) {
                return false;
            }

            // Ignore non-boolean resetScroll values
            resetScroll = resetScroll === true;

            var url = this.draftEditor.settings.previewTargets[this.activeTarget].url;

            this.draftEditor.getTokenizedPreviewUrl(url, true).then(function(url) {
                // Capture the current scroll position?
                var sameHost;
                if (resetScroll) {
                    this.scrollLeft = 0;
                    this.scrolllTop = 0;
                } else {
                    sameHost = Craft.isSameHost(url);
                    if (sameHost && this.$iframe && this.$iframe[0].contentWindow) {
                        var $doc = $(this.$iframe[0].contentWindow.document);
                        this.scrollLeft = $doc.scrollLeft();
                        this.scrollTop = $doc.scrollTop();
                    }
                }

                var $iframe = $('<iframe/>', {
                    'class': 'lp-preview',
                    frameborder: 0,
                    src: url,
                });

                if (!resetScroll && sameHost) {
                    $iframe.on('load', function() {
                        var $doc = $($iframe[0].contentWindow.document);
                        $doc.scrollLeft(this.scrollLeft);
                        $doc.scrollTop(this.scrollTop);
                    }.bind(this));
                }

                if (this.$iframe) {
                    this.$iframe.replaceWith($iframe);
                } else {
                    $iframe.appendTo(this.$previewContainer);
                }

                this.url = url;
                this.$iframe = $iframe;
                this.afterUpdateIframe();
            }.bind(this));
        },

        afterUpdateIframe: function() {
            this.trigger('afterUpdateIframe');

            if (this._slideInOnIframeLoad) {
                this.slideIn();
                this._slideInOnIframeLoad = false;
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
    },
    {
        defaultEditorWidth: 0.33,
        minEditorWidthInPx: 320,
        dragHandleWidth: 2,
    });
