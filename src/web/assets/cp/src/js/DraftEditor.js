/** global: Craft */
/** global: Garnish */
/**
 * Element Monitor
 */
Craft.DraftEditor = Garnish.Base.extend(
    {
        revisionMenu: null,
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

            // Just to be safe
            Craft.cp.$primaryForm.data('initialSerializedValue', Craft.cp.$primaryForm.serialize());

            this.addListener(Garnish.$bod, 'keypress keyup change focus blur click mousedown mouseup', function(ev) {
                clearTimeout(this.timeout);
                this.timeout = setTimeout($.proxy(this, 'checkForm'), 500);
            });

            this.addListener(Craft.cp.$primaryForm, 'submit', 'handleFormSubmit');
        },

        createEditMetaBtn: function() {
            this.$editMetaBtn = $('<a/>', {
                'class': 'btn edit icon',
                title: Craft.t('app', 'Edit draft settings'),
            }).appendTo($('#revision-btngroup'));
            this.addListener(this.$editMetaBtn, 'click', 'showMetaHud');
        },

        checkForm: function(force) {
            this.timeout = null;

            // Has anything changed?
            var data = Craft.cp.$primaryForm.serialize();
            if (force || data !== Craft.cp.$primaryForm.data('initialSerializedValue')) {
                this.saveDraft(data);
            }
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
            this.$spinner.removeClass('hidden');
            this.$savedIcon.removeClass('invisible').addClass('hidden');
            if (this.$saveMetaBtn) {
                this.$saveMetaBtn.addClass('active');
            }

            var url = Craft.getActionUrl(this.settings.saveDraftAction);

            Craft.postActionRequest(url, this.prepareData(data), $.proxy(function(response, textStatus) {
                this.$spinner.addClass('hidden');
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

                    var revisionMenu = this.$revisionBtn.data('menubtn').menu;

                    // Did we just create a draft?
                    if (!this.settings.draftId) {
                        history.replaceState({}, '', document.location.href + (document.location.href.match(/\?/) ? ':' : '?') + 'draftId=' + response.draftId);
                        this.settings.draftId = response.draftId;
                        this.settings.canDeleteDraft = true;
                        this.createEditMetaBtn();
                        $('#apply-btn').removeClass('disabled');

                        // Add it to the revision menu
                        revisionMenu.$options.removeClass('sel');;
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
                        var $draftA = $('<a><span class="draft-name"></span> <span class="draft-creator light"></span></a>', {
                            'class': 'sel',
                        }).appendTo($draftLi);
                        revisionMenu.addOptions($draftA);
                        revisionMenu.selectOption($draftA);
                    }

                    revisionMenu.$options.filter('.sel').find('.draft-name').text(response.draftName);
                    revisionMenu.$options.filter('.sel').find('.draft-creator').text(Craft.t('app', 'by {creator}', {
                        creator: response.creator
                    }));

                    this.afterUpdate(data);

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
            }

            return data;
        },

        afterUpdate: function(data) {
            Craft.cp.$primaryForm.data('initialSerializedValue', data);
            this.$savedIcon.removeClass('hidden');

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
            }
            else {
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

            Craft.postActionRequest(this.settings.deleteDraftAction, { draftId: this.settings.draftId }, $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    window.location.href = this.settings.sourceEditUrl;
                }
            }, this))
        },

        handleFormSubmit: function(ev) {
            ev.preventDefault();

            if (!this.settings.draftId || this.applying) {
                return;
            }

            this.applying = true;
            $('#apply-btn').addClass('disabled');

            var data = Craft.cp.$primaryForm.serialize();

            Craft.postActionRequest(this.settings.applyDraftAction, this.prepareData(data), $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    window.location.href = this.settings.sourceEditUrl;
                }
            }, this))
        },
    },
    {
        defaults: {
            sourceId: null,
            sourceEditUrl: null,
            draftId: null,
            draftName: null,
            draftNotes: null,
            canDeleteDraft: false,
            saveDraftAction: null,
            deleteDraftAction: null,
            applyDraftAction: null,
        }
    }
);
