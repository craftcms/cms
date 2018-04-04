/** global: Craft */
/** global: Garnish */
/**
 * Asset Select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend(
    {
        requestId: 0,
        hud: null,
        uploader: null,
        progressBar: null,

        originalFilename: '',
        originalExtension: '',

        init: function() {
            if (arguments.length > 0 && typeof arguments[0] === 'object') {
                arguments[0].editorSettings = {
                    onShowHud: $.proxy(this.resetOriginalFilename, this),
                    onCreateForm: $.proxy(this._renameHelper, this),
                    validators: [$.proxy(this.validateElementForm, this)]
                };
            }

            this.base.apply(this, arguments);
            this._attachUploader();

            this.addListener(this.$elementsContainer, 'keydown', this._onKeyDown.bind(this));
            this.elementSelect.on('focusItem', this._onElementFocus.bind(this));
        },

        /**
         * Handle a keypress
         * @private
         */
        _onKeyDown: function(ev) {
            if (ev.keyCode === Garnish.SPACE_KEY && ev.shiftKey) {
                if (Craft.PreviewFileModal.openInstance) {
                    Craft.PreviewFileModal.openInstance.selfDestruct();
                } else {
                    var $element = this.elementSelect.$focusedItem;

                    if ($element.length) {
                        this._loadPreview($element);
                    }
                }

                ev.stopPropagation();

                return false;
            }
        },

        /**
         * Handle element being focused
         * @private
         */
        _onElementFocus: function (ev) {
            var $element = $(ev.item);

            if (Craft.PreviewFileModal.openInstance && $element.length) {
                this._loadPreview($element);
            }
        },

        /**
         * Load the preview for an Asset element
         * @private
         */
        _loadPreview: function($element) {
            var settings = {};

            if ($element.data('image-width')) {
                settings.startingWidth = $element.data('image-width');
                settings.startingHeight = $element.data('image-height');
            }

            new Craft.PreviewFileModal($element.data('id'), this.elementSelect, settings);
        },

        /**
         * Create the element editor
         */
        createElementEditor: function($element) {
            return Craft.createElementEditor(this.settings.elementType, $element, {
                params: {
                    defaultFieldLayoutId: this.settings.defaultFieldLayoutId
                }
            });
        },

        /**
         * Attach the uploader with drag event handler
         */
        _attachUploader: function() {
            this.progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').appendTo(this.$container));

            var options = {
                url: Craft.getActionUrl('assets/save-asset'),
                dropZone: this.$container,
                formData: {
                    fieldId: this.settings.fieldId,
                    elementId: this.settings.sourceElementId
                }
            };

            // If CSRF protection isn't enabled, these won't be defined.
            if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined') {
                // Add the CSRF token
                options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;
            }

            if (typeof this.settings.criteria.kind !== 'undefined') {
                options.allowedKinds = this.settings.criteria.kind;
            }

            options.canAddMoreFiles = $.proxy(this, 'canAddMoreFiles');

            options.events = {};
            options.events.fileuploadstart = $.proxy(this, '_onUploadStart');
            options.events.fileuploadprogressall = $.proxy(this, '_onUploadProgress');
            options.events.fileuploaddone = $.proxy(this, '_onUploadComplete');

            this.uploader = new Craft.Uploader(this.$container, options);
        },

        /**
         * Add the freshly uploaded file to the input field.
         */
        selectUploadedFile: function(element) {
            // Check if we're able to add new elements
            if (!this.canAddMoreElements()) {
                return;
            }

            var $newElement = element.$element;

            // Make a couple tweaks
            $newElement.addClass('removable');
            $newElement.prepend('<input type="hidden" name="' + this.settings.name + '[]" value="' + element.id + '">' +
                '<a class="delete icon" title="' + Craft.t('app', 'Remove') + '"></a>');

            $newElement.appendTo(this.$elementsContainer);

            var margin = -($newElement.outerWidth() + 10);

            this.$addElementBtn.css('margin-' + Craft.left, margin + 'px');

            var animateCss = {};
            animateCss['margin-' + Craft.left] = 0;
            this.$addElementBtn.velocity(animateCss, 'fast');

            this.addElements($newElement);

            delete this.modal;
        },

        /**
         * On upload start.
         */
        _onUploadStart: function() {
            this.progressBar.$progressBar.css({
                top: Math.round(this.$container.outerHeight() / 2) - 6
            });

            this.$container.addClass('uploading');
            this.progressBar.resetProgressBar();
            this.progressBar.showProgressBar();
        },

        /**
         * On upload progress.
         */
        _onUploadProgress: function(event, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            this.progressBar.setProgressPercentage(progress);
        },

        /**
         * On a file being uploaded.
         */
        _onUploadComplete: function(event, data) {
            if (data.result.error) {
                alert(data.result.error);
            } else {
                var elementId = data.result.assetId;

                Craft.postActionRequest('elements/get-element-html', {elementId: elementId, siteId: this.settings.criteria.siteId}, function (data) {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        var html = $(data.html);
                        Craft.appendHeadHtml(data.headHtml);
                        this.selectUploadedFile(Craft.getElementInfo(html));
                    }

                    // Last file
                    if (this.uploader.isLastUpload()) {
                        this.progressBar.hideProgressBar();
                        this.$container.removeClass('uploading');
                    }
                }.bind(this));

                Craft.cp.runQueue();
            }
        },

        /**
         * We have to take into account files about to be added as well
         */
        canAddMoreFiles: function(slotsTaken) {
            return (!this.settings.limit || this.$elements.length + slotsTaken < this.settings.limit);
        },

        /**
         * Parse the passed filename into the base filename and extension.
         *
         * @param filename
         * @returns {{extension: string, baseFileName: string}}
         */
        _parseFilename: function(filename) {
            var parts = filename.split('.'),
                extension = '';

            if (parts.length > 1) {
                extension = parts.pop();
            }
            var baseFileName = parts.join('.');
            return {extension: extension, baseFileName: baseFileName};
        },

        /**
         * A helper function or the filename field.
         * @private
         */
        _renameHelper: function($form) {
            $('.renameHelper', $form).on('focus', $.proxy(function(e) {
                var input = e.currentTarget,
                    filename = this._parseFilename(input.value);

                if (this.originalFilename === '' && this.originalExtension === '') {
                    this.originalFilename = filename.baseFileName;
                    this.originalExtension = filename.extension;
                }

                var startPos = 0,
                    endPos = filename.baseFileName.length;

                if (typeof input.selectionStart !== 'undefined') {
                    input.selectionStart = startPos;
                    input.selectionEnd = endPos;
                } else if (document.selection && document.selection.createRange) {
                    // IE branch
                    input.select();
                    var range = document.selection.createRange();
                    range.collapse(true);
                    range.moveEnd("character", endPos);
                    range.moveStart("character", startPos);
                    range.select();
                }

            }, this));
        },

        resetOriginalFilename: function() {
            this.originalFilename = "";
            this.originalExtension = "";
        },

        validateElementForm: function() {
            var $filenameField = $('.renameHelper', this.elementEditor.hud.$hud.data('elementEditor').$form);
            var filename = this._parseFilename($filenameField.val());

            if (filename.extension !== this.originalExtension) {
                // Blank extension
                if (filename.extension === '') {
                    // If filename changed as well, assume removal of extension a mistake
                    if (this.originalFilename !== filename.baseFileName) {
                        $filenameField.val(filename.baseFileName + '.' + this.originalExtension);
                        return true;
                    } else {
                        // If filename hasn't changed, make sure they want to remove extension
                        return confirm(Craft.t('app', "Are you sure you want to remove the extension “.{ext}”?", {ext: this.originalExtension}));
                    }
                } else {
                    // If the extension has changed, make sure it s intentional
                    return confirm(Craft.t('app', "Are you sure you want to change the extension from “.{oldExt}” to “.{newExt}”?",
                        {
                            oldExt: this.originalExtension,
                            newExt: filename.extension
                        }));
                }
            }
            return true;
        }
    });
