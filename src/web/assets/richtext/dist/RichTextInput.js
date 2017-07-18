(function($) {
    /** global: Craft */
    /** global: Garnish */
    /**
     * Rich Text input class
     */
    Craft.RichTextInput = Garnish.Base.extend(
        {
            id: null,
            linkOptions: null,
            volumes: null,
            elementSiteId: null,
            redactorConfig: null,

            $textarea: null,
            redactor: null,
            linkOptionModals: null,

            init: function(settings) {
                this.id = settings.id;
                this.linkOptions = settings.linkOptions;
                this.volumes = settings.volumes;
                this.transforms = settings.transforms;
                this.elementSiteId = settings.elementSiteId;
                this.redactorConfig = settings.redactorConfig;

                this.linkOptionModals = [];

                if (!this.redactorConfig.lang) {
                    this.redactorConfig.lang = settings.redactorLang;
                }

                if (!this.redactorConfig.direction) {
                    this.redactorConfig.direction = (settings.direction || Craft.orientation);
                }

                this.redactorConfig.imageUpload = true;
                this.redactorConfig.fileUpload = true;
                this.redactorConfig.dragImageUpload = false;
                this.redactorConfig.dragFileUpload = false;
                this.redactorConfig.toolbarFixedTopOffset = 72;

                // Prevent a JS error when calling core.destroy() when opts.plugins == false
                if (typeof this.redactorConfig.plugins !== typeof []) {
                    this.redactorConfig.plugins = [];
                }

                // Redactor I config setting normalization
                if (this.redactorConfig.buttons) {
                    var index;

                    // buttons.html => plugins.source
                    if ((index = $.inArray('html', this.redactorConfig.buttons)) !== -1) {
                        this.redactorConfig.buttons.splice(index, 1);
                        this.redactorConfig.plugins.unshift('source');
                    }

                    // buttons.formatting => buttons.format
                    if ((index = $.inArray('formatting', this.redactorConfig.buttons)) !== -1) {
                        this.redactorConfig.buttons.splice(index, 1, 'format');
                    }

                    // buttons.unorderedlist/orderedlist/undent/indent => buttons.lists
                    var oldListButtons = ['unorderedlist', 'orderedlist', 'undent', 'indent'],
                        lowestListButtonIndex;

                    for (var i = 0; i < oldListButtons.length; i++) {
                        if ((index = $.inArray(oldListButtons[i], this.redactorConfig.buttons)) !== -1) {
                            this.redactorConfig.buttons.splice(index, 1);

                            if (typeof lowestListButtonIndex === 'undefined' || index < lowestListButtonIndex) {
                                lowestListButtonIndex = index;
                            }
                        }
                    }

                    if (typeof lowestListButtonIndex !== 'undefined') {
                        this.redactorConfig.buttons.splice(lowestListButtonIndex, 0, 'lists');
                    }
                }

                this.redactorConfig.callbacks = {
                    init: Craft.RichTextInput.handleRedactorInit
                };

                // Initialize Redactor
                this.$textarea = $('#' + this.id);

                this.initRedactor();

                if (typeof Craft.livePreview !== 'undefined') {
                    // There's a UI glitch if Redactor is in Code view when Live Preview is shown/hidden
                    Craft.livePreview.on('beforeEnter beforeExit', $.proxy(function() {
                        this.redactor.core.destroy();
                    }, this));

                    Craft.livePreview.on('enter slideOut', $.proxy(function() {
                        this.initRedactor();
                    }, this));
                }
            },

            mergeCallbacks: function(callback1, callback2) {
                return function() {
                    callback1.apply(this, arguments);
                    callback2.apply(this, arguments);
                };
            },

            initRedactor: function() {
                Craft.RichTextInput.currentInstance = this;
                this.$textarea.redactor(this.redactorConfig);
                delete Craft.RichTextInput.currentInstance;
            },

            onInitRedactor: function(redactor) {
                this.redactor = redactor;

                // Only customize the toolbar if there is one,
                // otherwise we get a JS error due to redactor.$toolbar being undefined
                if (this.redactor.opts.toolbar) {
                    this.customizeToolbar();
                }

                this.leaveFullscreetOnSaveShortcut();

                this.redactor.core.editor()
                    .on('focus', $.proxy(this, 'onEditorFocus'))
                    .on('blur', $.proxy(this, 'onEditorBlur'));

                if (this.redactor.opts.toolbarFixed && !Craft.RichTextInput.scrollPageOnReady) {
                    Garnish.$doc.on('ready', function() {
                        Garnish.$doc.trigger('scroll');
                    });

                    Craft.RichTextInput.scrollPageOnReady = true;
                }
            },

            customizeToolbar: function() {
                // Override the Image and File buttons?
                if (this.volumes.length) {
                    var $imageBtn = this.replaceRedactorButton('image', this.redactor.lang.get('image')),
                        $fileBtn = this.replaceRedactorButton('file', this.redactor.lang.get('file'));

                    if ($imageBtn) {
                        this.redactor.button.addCallback($imageBtn, $.proxy(this, 'onImageButtonClick'));
                    }

                    if ($fileBtn) {
                        this.redactor.button.addCallback($fileBtn, $.proxy(this, 'onFileButtonClick'));
                    }
                }
                else {
                    // Image and File buttons aren't supported
                    this.redactor.button.remove('image');
                    this.redactor.button.remove('file');
                }

                // Override the Link button?
                if (this.linkOptions.length) {
                    var $linkBtn = this.replaceRedactorButton('link', this.redactor.lang.get('link'));

                    if ($linkBtn) {
                        var dropdownOptions = {};

                        for (var i = 0; i < this.linkOptions.length; i++) {
                            dropdownOptions['link_option' + i] = {
                                title: this.linkOptions[i].optionTitle,
                                func: $.proxy(this, 'onLinkOptionClick', i)
                            };
                        }

                        // Add the default Link options
                        $.extend(dropdownOptions, {
                            link: {
                                title: this.redactor.lang.get('link-insert'),
                                func: 'link.show',
                                observe: {
                                    element: 'a',
                                    in: {
                                        title: this.redactor.lang.get('link-edit')
                                    },
                                    out: {
                                        title: this.redactor.lang.get('link-insert')
                                    }
                                }
                            },
                            unlink: {
                                title: this.redactor.lang.get('unlink'),
                                func: 'link.unlink',
                                observe: {
                                    element: 'a',
                                    out: {
                                        attr: {
                                            'class': 'redactor-dropdown-link-inactive',
                                            'aria-disabled': true
                                        }
                                    }
                                }
                            }
                        });

                        this.redactor.button.addDropdown($linkBtn, dropdownOptions);
                    }
                }
            },

            onImageButtonClick: function() {
                this.redactor.selection.save();

                if (typeof this.assetSelectionModal === 'undefined') {
                    this.assetSelectionModal = Craft.createElementSelectorModal('craft\\elements\\Asset', {
                        storageKey: 'RichTextFieldType.ChooseImage',
                        multiSelect: true,
                        sources: this.volumes,
                        criteria: {siteId: this.elementSiteId, kind: 'image'},
                        onSelect: $.proxy(function(assets, transform) {
                            if (assets.length) {
                                this.redactor.selection.restore();
                                for (var i = 0; i < assets.length; i++) {
                                    var asset = assets[i],
                                        url = asset.url + '#asset:' + asset.id;

                                    if (transform) {
                                        url += ':transform:' + transform;
                                    }

                                    this.redactor.insert.node($('<figure><img src="' + url + '" /></figure>')[0]);
                                    this.redactor.code.sync();
                                }
                                this.redactor.observe.images();
                            }
                        }, this),
                        closeOtherModals: false,
                        transforms: this.transforms
                    });
                }
                else {
                    this.assetSelectionModal.show();
                }
            },

            onFileButtonClick: function() {
                this.redactor.selection.save();

                if (typeof this.assetLinkSelectionModal === 'undefined') {
                    this.assetLinkSelectionModal = Craft.createElementSelectorModal('craft\\elements\\Asset', {
                        storageKey: 'RichTextFieldType.LinkToAsset',
                        sources: this.volumes,
                        criteria: {siteId: this.elementSiteId},
                        onSelect: $.proxy(function(assets) {
                            if (assets.length) {
                                this.redactor.selection.restore();
                                var asset = assets[0],
                                    url = asset.url + '#asset:' + asset.id,
                                    selection = this.redactor.selection.text(),
                                    title = selection.length > 0 ? selection : asset.label;
                                this.redactor.insert.node($('<a href="' + url + '">' + title + '</a>')[0]);
                                this.redactor.code.sync();
                            }
                        }, this),
                        closeOtherModals: false,
                        transforms: this.transforms
                    });
                }
                else {
                    this.assetLinkSelectionModal.show();
                }
            },

            onLinkOptionClick: function(key) {
                this.redactor.selection.save();

                if (typeof this.linkOptionModals[key] === 'undefined') {
                    var settings = this.linkOptions[key];

                    this.linkOptionModals[key] = Craft.createElementSelectorModal(settings.elementType, {
                        storageKey: (settings.storageKey || 'RichTextFieldType.LinkTo.' + settings.elementType),
                        sources: settings.sources,
                        criteria: $.extend({siteId: this.elementSiteId}, settings.criteria),
                        onSelect: $.proxy(function(elements) {
                            if (elements.length) {
                                this.redactor.selection.restore();
                                var element = elements[0],
                                    url = element.url + '#' + settings.refHandle + ':' + element.id,
                                    selection = this.redactor.selection.text(),
                                    title = selection.length > 0 ? selection : element.label;
                                this.redactor.insert.node($('<a href="' + url + '">' + title + '</a>')[0]);
                                this.redactor.code.sync();
                            }
                        }, this),
                        closeOtherModals: false
                    });
                }
                else {
                    this.linkOptionModals[key].show();
                }
            },

            onEditorFocus: function() {
                this.redactor.core.box().addClass('focus');
                this.redactor.core.box().trigger('focus');
            },

            onEditorBlur: function() {
                this.redactor.core.box().removeClass('focus');
                this.redactor.core.box().trigger('blur');
            },

            leaveFullscreetOnSaveShortcut: function() {
                if (typeof this.redactor.fullscreen !== 'undefined' && typeof this.redactor.fullscreen.disable === 'function') {
                    Craft.cp.on('beforeSaveShortcut', $.proxy(function() {
                        if (this.redactor.fullscreen.isOpen) {
                            this.redactor.fullscreen.disable();
                        }
                    }, this));
                }
            },

            replaceRedactorButton: function(key, title) {
                // Ignore if the button isn't in use
                if (!this.redactor.button.get(key).length) {
                    return;
                }

                // Create a placeholder button
                var $placeholder = this.redactor.button.addAfter(key, key+'_placeholder');

                // Remove the original
                this.redactor.button.remove(key);

                // Add the new one
                // (Can't just use button.addAfter() here because it doesn't let us specify
                // full button properties (e.g. icon); just title)
                var $btn = this.redactor.button.build(key, {
                    title: title,
                    icon: true
                });
                $placeholder.parent().after($('<li>').append($btn));

                // Remove the placeholder
                $placeholder.remove();

                return $btn;
            }
        },
        {
            handleRedactorInit: function() {
                // `this` is the current Redactor instance.
                // `Craft.RichTextInput.currentInstance` is the current RichTextInput instance
                Craft.RichTextInput.currentInstance.onInitRedactor(this);
            }
        });
})(jQuery);
