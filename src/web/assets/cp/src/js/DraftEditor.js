/** global: Craft */
/** global: Garnish */
/**
 * Element Monitor
 */
Craft.DraftEditor = Garnish.Base.extend(
    {
        $revisionBtn: null,
        $revisionLabel: null,
        $spinner: null,
        $savedIcon: null,

        $editMetaBtn: null,
        metaHud: null,
        $nameTextInput: null,
        $notesTextInput: null,
        $saveMetaBtn: null,

        timeout: null,
        saving: false,
        checkFormAfterUpdate: false,

        duplicatedElements: null,
        applying: false,

        preview: null,
        previewToken: null,

        init: function(settings) {
            this.setSettings(settings, Craft.DraftEditor.defaults);

            this.duplicatedElements = {};

            this.$revisionBtn = $('#revision-btn');
            this.$revisionLabel = $('#revision-label');
            this.$spinner = $('#revision-spinner');
            this.$savedIcon = $('#revision-saved');

            if (this.settings.draftId) {
                this.createEditMetaBtn();
            }

            if (this.settings.previewTargets.length) {
                if (this.settings.enablePreview) {
                    this.addListener($('#preview-btn'), 'click', 'openPreview');
                }

                var $shareBtn = $('#share-btn');

                if (this.settings.previewTargets.length === 1) {
                    this.addListener($shareBtn, 'click', function() {
                        this.openShareLink(this.settings.previewTargets[0].url);
                    });
                } else {
                    this.createShareMenu($shareBtn);
                }
            }

            // If this is a revision, then we're done here
            if (this.settings.revisionId) {
                return;
            }

            // Just to be safe
            Craft.cp.$primaryForm.data('initialSerializedValue', this.serializeForm());

            // Override the serializer to use our own
            Craft.cp.$primaryForm.data('serializer', $.proxy(this, 'serializeForm'));

            this.addListener(Garnish.$bod, 'keypress keyup change focus blur click mousedown mouseup', function(ev) {
                clearTimeout(this.timeout);
                this.timeout = setTimeout($.proxy(this, 'checkForm'), 500);
            });

            this.addListener(Craft.cp.$primaryForm, 'submit', 'handleFormSubmit');
        },

        spinners: function() {
            return this.isPreviewActive()
                ? this.$spinner.add(this.preview.$spinner)
                : this.$spinner;
        },

        savedIcons: function() {
            return this.isPreviewActive()
                ? this.$savedIcon.add(this.preview.$savedIcon)
                : this.$savedIcon;
        },

        createEditMetaBtn: function() {
            this.$editMetaBtn = $('<a/>', {
                'class': 'btn edit icon',
                title: Craft.t('app', 'Edit draft settings'),
            }).appendTo($('#revision-btngroup'));
            this.addListener(this.$editMetaBtn, 'click', 'showMetaHud');
        },

        createShareMenu: function($shareBtn) {
            $shareBtn.addClass('menubtn');

            var $menu = $('<div/>', {'class': 'menu'}).insertAfter($shareBtn);
            var $ul = $('<ul/>').appendTo($menu);
            var $li, $a;
            var $a;

            for (var i = 0; i < this.settings.previewTargets.length; i++) {
                $li = $('<li/>').appendTo($ul);
                $a = $('<a/>', {
                    text: this.settings.previewTargets[i].label,
                    data: {
                        url: this.settings.previewTargets[i].url,
                    }
                }).appendTo($li);
                this.addListener($a, 'click', {
                    url: this.settings.previewTargets[i].url,
                }, function(ev) {
                    this.openShareLink(ev.data.url);
                });
            }
        },

        getPreviewToken: function() {
            return new Promise(function(resolve, reject) {
                if (this.previewToken) {
                    resolve(this.previewToken);
                    return;
                }

                Craft.postActionRequest('preview/create-token', {
                    elementType: this.settings.elementType,
                    sourceId: this.settings.sourceId,
                    siteId: this.settings.siteId,
                    draftId: this.settings.draftId,
                    revisionId: this.settings.revisionId,
                }, function(response, textStatus) {
                    if (textStatus === 'success') {
                        this.previewToken = response.token;
                        resolve(this.previewToken);
                    } else {
                        reject();
                    }
                }.bind(this));
            }.bind(this));
        },

        getTokenizedPreviewUrl: function(url, forceRandomParam) {
            return new Promise(function(resolve, reject) {
                var params = {};

                if (forceRandomParam || !this.settings.isLive) {
                    // Randomize the URL so CDNs don't return cached pages
                    params.v = Craft.randomString(10);
                }

                // No need for a token if we're looking at a live element
                if (this.settings.isLive) {
                    resolve(Craft.getUrl(url, params));
                    return;
                }

                this.getPreviewToken().then(function(token) {
                    params[Craft.tokenParam] = token;
                    resolve(Craft.getUrl(url, params));
                }).catch(reject);
            }.bind(this));
        },

        openShareLink: function(url) {
            this.getTokenizedPreviewUrl(url).then(function(url) {
                window.open(url);
            });
        },

        getPreview: function() {
            if (!this.preview) {
                this.preview = new Craft.Preview(this);
            }
            return this.preview;
        },

        openPreview: function() {
            this.getPreview().open();
        },

        serializeForm: function() {
            var data = Craft.cp.$primaryForm.serialize();

            if (this.isPreviewActive()) {
                // Replace the temp input with the preview form data
                data = data.replace('__PREVIEW_FIELDS__=1', this.preview.$editor.serialize());
            }

            return data;
        },

        checkForm: function(force) {
            this.timeout = null;

            // Has anything changed?
            var data = this.serializeForm();
            if (force || data !== Craft.cp.$primaryForm.data('initialSerializedValue')) {
                this.saveDraft(data);
            }
        },

        isPreviewActive: function() {
            return this.preview && this.preview.isActive;
        },

        saveDraft: function(data) {
            if (this.applying) {
                return;
            }

            if (this.saving) {
                this.checkFormAfterUpdate = true;
                return;
            }

            this.saving = true;
            this.spinners().removeClass('hidden');
            this.savedIcons().removeClass('invisible').addClass('hidden');
            if (this.$saveMetaBtn) {
                this.$saveMetaBtn.addClass('active');
            }

            var url = Craft.getActionUrl(this.settings.saveDraftAction);

            Craft.postActionRequest(url, this.prepareData(data), $.proxy(function(response, textStatus) {
                this.spinners().addClass('hidden');
                if (this.$saveMetaBtn) {
                    this.$saveMetaBtn.removeClass('active');
                }
                this.saving = false;

                if (textStatus === 'success') {
                    if (response.title) {
                        $('#header h1').text(response.title);
                    }

                    if (response.docTitle) {
                        document.title = response.docTitle;
                    }

                    this.$revisionLabel.text(response.draftName);

                    this.settings.draftName = response.draftName;
                    this.settings.draftNotes = response.draftNotes;

                    var revisionMenu = this.$revisionBtn.data('menubtn') ? this.$revisionBtn.data('menubtn').menu : null;

                    // Did we just create a draft?
                    var draftCreated = !this.settings.draftId;
                    if (draftCreated) {
                        var newHref;
                        var anchorPos = document.location.href.search('#');
                        if (anchorPos !== -1) {
                            newHref = document.location.href.substr(0, anchorPos);
                        } else {
                            newHref = document.location.href;
                        }
                        newHref += (newHref.match(/\?/) ? '&' : '?') + 'draftId=' + response.draftId;
                        if (anchorPos !== -1) {
                            newHref += document.location.href.substr(anchorPos);
                        }
                        history.replaceState({}, '', newHref);
                        this.settings.draftId = response.draftId;
                        this.settings.isLive = false;
                        this.settings.canDeleteDraft = true;
                        this.previewToken = null;
                        this.createEditMetaBtn();
                        $('#apply-btn').removeClass('disabled');

                        // Add it to the revision menu
                        if (revisionMenu) {
                            revisionMenu.$options.filter(':not(.site-option)').removeClass('sel');
                            var $draftsUl = revisionMenu.$container.find('.revision-group-drafts');
                            if (!$draftsUl.length) {
                                var $draftHeading = $('<h6/>', {
                                    text: Craft.t('app', 'Drafts'),
                                }).insertAfter(revisionMenu.$container.find('.revision-group-current'));
                                $draftsUl = $('<ul/>', {
                                    'class': 'padded revision-group-drafts',
                                }).insertAfter($draftHeading);
                            }
                            var $draftLi = $('<li/>').appendTo($draftsUl);
                            var $draftA = $('<a/>', {
                                'class': 'sel',
                                html: '<span class="draft-name"></span> <span class="draft-creator light"></span>',
                            }).appendTo($draftLi);
                            revisionMenu.addOptions($draftA);
                            revisionMenu.selectOption($draftA);

                            // Update the site URLs
                            var $siteOptions = revisionMenu.$options.filter('.site-option[href]');
                            for (var i = 0; i < $siteOptions.length; i++) {
                                var $siteOption = $siteOptions.eq(i);
                                $siteOption.attr('href', Craft.getUrl($siteOption.attr('href'), {draftId: response.draftId}));
                            }
                        }
                    }

                    if (revisionMenu) {
                        revisionMenu.$options.filter('.sel').find('.draft-name').text(response.draftName);
                        revisionMenu.$options.filter('.sel').find('.draft-creator').text(Craft.t('app', 'by {creator}', {
                            creator: response.creator
                        }));
                    }

                    this.afterUpdate(data);

                    if (draftCreated) {
                        this.trigger('createDraft');
                    }

                    if (this.$nameTextInput) {
                        this.checkMetaValues();
                    }

                    $.extend(this.duplicatedElements, response.duplicatedElements);
                }
            }, this));
        },

        prepareData: function(data) {
            // Swap out element IDs with their duplicated ones
            for (var oldId in this.duplicatedElements) {
                if (this.duplicatedElements.hasOwnProperty(oldId)) {
                    data = data.replace(new RegExp(Craft.escapeRegex(encodeURIComponent('][' + oldId + ']')), 'g'),
                        '][' + this.duplicatedElements[oldId] + ']');
                }
            }

            // Add the draft info
            if (this.settings.draftId) {
                data += '&draftId=' + this.settings.draftId
                    + '&draftName=' + encodeURIComponent(this.settings.draftName)
                    + '&draftNotes=' + encodeURIComponent(this.settings.draftNotes || '');

                if (this.settings.propagateAll) {
                    data += '&propagateAll=1';
                }
            }

            return data;
        },

        afterUpdate: function(data) {
            Craft.cp.$primaryForm.data('initialSerializedValue', data);
            this.savedIcons().removeClass('hidden');

            this.trigger('update');

            if (this.checkFormAfterUpdate) {
                this.checkFormAfterUpdate = false;

                // Only actually check the form if there's no active timeout for it
                if (!this.timeout) {
                    this.checkForm();
                }
            }
        },

        showMetaHud: function() {
            if (!this.metaHud) {
                this.createMetaHud();
                this.onMetaHudShow();
            } else {
                this.metaHud.show();
            }

            if (!Garnish.isMobileBrowser(true)) {
                this.$nameTextInput.trigger('focus');
            }
        },

        createMetaHud: function() {
            var $hudBody = $('<div/>');
            var $field, $inputContainer;

            // Add the Name field
            $field = $('<div class="field"><div class="heading"><label for="draft-name">' + Craft.t('app', 'Draft Name') + '</label></div></div>').appendTo($hudBody);
            $inputContainer = $('<div class="input"/>').appendTo($field);
            this.$nameTextInput = $('<input type="text" class="text fullwidth" id="draft-name"/>').appendTo($inputContainer).val(this.settings.draftName);

            // Add the Notes field
            $field = $('<div class="field"><div class="heading"><label for="draft-notes">' + Craft.t('app', 'Notes') + '</label></div></div>').appendTo($hudBody);
            $inputContainer = $('<div class="input"/>').appendTo($field);
            this.$notesTextInput = $('<textarea class="text fullwidth" id="draft-notes" rows="2"/>').appendTo($inputContainer).val(this.settings.draftNotes);

            // HUD footer
            var $footer = $('<div class="hud-footer flex flex-center"/>').appendTo($hudBody);

            // Delete button
            if (this.settings.canDeleteDraft) {
                var $deleteLink = $('<a class="error" role="button">' + Craft.t('app', 'Delete') + '</a>').appendTo($footer);
            }

            $('<div class="flex-grow"></div>').appendTo($footer);
            this.$saveMetaBtn = $('<input type="submit" class="btn submit disabled" value="' + Craft.t('app', 'Save') + '"/>').appendTo($footer);

            this.metaHud = new Garnish.HUD(this.$editMetaBtn, $hudBody, {
                onSubmit: $.proxy(this, 'saveMeta')
            });

            new Garnish.NiceText(this.$notesTextInput);

            this.addListener(this.$notesTextInput, 'keydown', 'onNotesKeydown');

            this.addListener(this.$nameTextInput, 'textchange', 'checkMetaValues');
            this.addListener(this.$notesTextInput, 'textchange', 'checkMetaValues');

            this.metaHud.on('show', $.proxy(this, 'onMetaHudShow'));
            this.metaHud.on('hide', $.proxy(this, 'onMetaHudHide'));
            this.metaHud.on('escape', $.proxy(this, 'onMetaHudEscape'));

            if ($deleteLink) {
                this.addListener($deleteLink, 'click', 'deleteDraft');
            }
        },

        onMetaHudShow: function() {
            this.$editMetaBtn.addClass('active');
        },

        onMetaHudHide: function() {
            this.$editMetaBtn.removeClass('active');
        },

        onMetaHudEscape: function() {
            this.$nameTextInput.val(this.settings.draftName);
            this.$notesTextInput.val(this.settings.draftNotes);
        },

        onNotesKeydown: function(ev) {
            if (ev.keyCode === Garnish.RETURN_KEY) {
                ev.preventDefault();
                this.metaHud.submit();
            }
        },

        checkMetaValues: function() {
            if (
                this.$nameTextInput.val() && (
                    this.$nameTextInput.val() !== this.settings.draftName ||
                    this.$notesTextInput.val() !== this.settings.draftNotes
                )
            ) {
                this.$saveMetaBtn.removeClass('disabled');
                return true;
            }

            this.$saveMetaBtn.addClass('disabled');
            return false;
        },

        shakeMetaHud: function() {
            Garnish.shake(this.metaHud.$hud);
        },

        saveMeta: function() {
            if (!this.checkMetaValues()) {
                this.shakeMetaHud();
                return;
            }

            this.settings.draftName = this.$nameTextInput.val();
            this.settings.draftNotes = this.$notesTextInput.val();

            this.metaHud.hide();
            this.checkForm(true);
        },

        deleteDraft: function() {
            if (!confirm(Craft.t('app', 'Are you sure you want to delete this draft?'))) {
                return;
            }

            Craft.postActionRequest(this.settings.deleteDraftAction, {draftId: this.settings.draftId}, $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    window.location.href = this.settings.cpEditUrl;
                }
            }, this))
        },

        handleFormSubmit: function(ev) {
            ev.preventDefault();

            // Don't allow a form submit under any circumstances if we’re currently applying a draft
            if (this.applying) {
                return;
            }

            if (!ev.customTrigger) {
                // return;
                if (!this.settings.draftId) {
                    return;
                }
                $('#apply-btn').addClass('disabled');
            }

            this.applying = true;

            // Duplicate the form with normalized data
            var $form = $('<form/>', {
                attr: {
                    'accept-charset': Craft.cp.$primaryForm.attr('accept-charset'),
                    'action': Craft.cp.$primaryForm.attr('action'),
                    'enctype': Craft.cp.$primaryForm.attr('enctype'),
                    'method': Craft.cp.$primaryForm.attr('method'),
                    'target': Craft.cp.$primaryForm.attr('target'),
                }
            });
            var data = this.prepareData(this.serializeForm());
            var values = data.split('&');
            var chunks;
            for (var i = 0; i < values.length; i++) {
                chunks = values[i].split('=', 2);
                $('<input/>', {
                    type: 'hidden',
                    name: decodeURIComponent(chunks[0]),
                    value: decodeURIComponent(chunks[1] || '')
                }).appendTo($form);
            }

            if (!ev.customTrigger) {
                $('<input/>', {
                    type: 'hidden',
                    name: 'action',
                    value: this.settings.applyDraftAction
                }).appendTo($form);
            }

            $form.appendTo(Garnish.$bod);
            $form.submit();
        },
    },
    {
        defaults: {
            elementType: null,
            sourceId: null,
            siteId: null,
            isLive: false,
            cpEditUrl: null,
            draftId: null,
            revisionId: null,
            draftName: null,
            draftNotes: null,
            propagateAll: false,
            canDeleteDraft: false,
            canUpdateSource: false,
            saveDraftAction: null,
            deleteDraftAction: null,
            applyDraftAction: null,
            enablePreview: false,
            previewTargets: [],
        }
    }
);
