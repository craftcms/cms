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
        $expandSiteStatusesBtn: null,
        $statusIcon: null,

        $editMetaBtn: null,
        metaHud: null,
        $nameTextInput: null,
        $notesTextInput: null,
        $saveMetaBtn: null,

        $siteStatusPane: null,
        $globalLightswitch: null,
        $siteLightswitches: null,
        $addlSiteField: null,
        newSites: null,

        enableAutosave: null,
        lastSerializedValue: null,
        listeningForChanges: false,
        pauseLevel: 0,
        timeout: null,
        saving: false,
        saveXhr: null,
        queue: null,
        submittingForm: false,

        duplicatedElements: null,
        errors: null,

        preview: null,
        previewToken: null,

        init: function(settings) {
            this.setSettings(settings, Craft.DraftEditor.defaults);

            this.queue = [];

            this.duplicatedElements = {};

            this.enableAutosave = Craft.autosaveDrafts;

            this.$revisionBtn = $('#context-btn');
            this.$revisionLabel = $('#revision-label');
            this.$spinner = $('#revision-spinner');
            this.$expandSiteStatusesBtn = $('#expand-status-btn');
            this.$statusIcon = $('#revision-status');

            if (this.settings.canEditMultipleSites) {
                this.addListener(this.$expandSiteStatusesBtn, 'click', 'expandSiteStatuses');
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

            // If this is a revision, we're done here
            if (this.settings.revisionId) {
                return;
            }

            // Override the serializer to use our own
            Craft.cp.$primaryForm.data('serializer', function() {
                return this.serializeForm(true)
            }.bind(this));

            this.addListener(Craft.cp.$primaryForm, 'submit', 'handleFormSubmit');

            if (this.settings.draftId) {
                this.initForDraft();
            } else {
                // If the "Save as a Draft" button is a secondary button, then add special handling for it
                this.addListener($('#save-draft-btn'), 'click', function(ev) {
                    ev.preventDefault();
                    this.createDraft();
                    this.removeListener(Craft.cp.$primaryForm, 'submit.saveShortcut');
                }.bind(this));

                // If they're not allowed to update the source element, override the save shortcut to create a draft too
                if (!this.settings.canUpdateSource) {
                    this.addListener(Craft.cp.$primaryForm, 'submit.saveShortcut', function(ev) {
                        if (ev.saveShortcut) {
                            ev.preventDefault();
                            this.createDraft();
                            this.removeListener(Craft.cp.$primaryForm, 'submit.saveShortcut');
                        }
                    }.bind(this));
                }
            }
        },

        listenForChanges: function() {
            if (this.listeningForChanges || this.pauseLevel > 0 || !this.enableAutosave) {
                return;
            }

            this.listeningForChanges = true;

            this.addListener(Garnish.$bod, 'keypress,keyup,change,focus,blur,click,mousedown,mouseup', function(ev) {
                if ($(ev.target).is(this.statusIcons())) {
                    return;
                }
                clearTimeout(this.timeout);
                // If they are typing, wait half a second before checking the form
                if (Craft.inArray(ev.type, ['keypress', 'keyup', 'change'])) {
                    this.timeout = setTimeout(this.checkForm.bind(this), 500);
                } else {
                    this.checkForm();
                }
            });
        },

        stopListeningForChanges: function() {
            if (!this.listeningForChanges) {
                return;
            }

            this.removeListener(Garnish.$bod, 'keypress,keyup,change,focus,blur,click,mousedown,mouseup');
            clearTimeout(this.timeout);
            this.listeningForChanges = false;
        },

        pause: function() {
            this.pauseLevel++;
            this.stopListeningForChanges();
        },

        resume: function() {
            if (this.pauseLevel === 0) {
                throw 'Craft.DraftEditor::resume() should only be called after pause().';
            }

            // Only actually resume operation if this has been called the same
            // number of times that pause() was called
            this.pauseLevel--;
            if (this.pauseLevel === 0) {
                this.checkForm();
                this.listenForChanges();
            }
        },

        initForDraft: function() {
            // Create the edit draft button
            this.createEditMetaBtn();

            this.addListener(this.$statusIcon, 'click', function() {
                this.showStatusHud(this.$statusIcon);
            }.bind(this));

            this.addListener($('#merge-changes-btn'), 'click', this.mergeChanges);

            if (Craft.autosaveDrafts) {
                this.listenForChanges();
            }
        },

        mergeChanges: function() {
            // Make sure there aren't any unsaved changes
            this.checkForm();

            // Make sure we aren't currently saving something
            if (this.saving) {
                this.queue.push(this.mergeChanges.bind(this));
                return;
            }

            this.saving = true;
            $('#merge-changes-spinner').removeClass('hidden');

            Craft.postActionRequest('drafts/merge-source-changes', {
                elementType: this.settings.elementType,
                draftId: this.settings.draftId,
                siteId: this.settings.siteId,
            }, function(response, textStatus) {
                if (textStatus === 'success') {
                    window.location.reload();
                } else {
                    $('#merge-changes-spinner').addClass('hidden');
                }
            });
        },

        expandSiteStatuses: function() {
            this.removeListener(this.$expandSiteStatusesBtn, 'click');
            this.$expandSiteStatusesBtn.velocity({opacity: 0}, 'fast', function() {
                this.$expandSiteStatusesBtn.remove();
            }.bind(this));

            var $enabledForSiteField = $(`#enabledForSite-${this.settings.siteId}-field`);
            this.$siteStatusPane = $enabledForSiteField.parent();

            // If this is a revision, just show the site statuses statically and be done
            if (this.settings.revisionId) {
                for (let i = 0; i < Craft.sites.length; i++) {
                    let site = Craft.sites[i];
                    if (site.id == this.settings.siteId) {
                        continue;
                    }
                    if (this.settings.siteStatuses.hasOwnProperty(site.id)) {
                        this._createSiteStatusField(site);
                    }
                }
                return;
            }

            $enabledForSiteField.addClass('nested');
            var $globalField = Craft.ui.createLightswitchField({
                id: 'enabled',
                label: Craft.t('app', 'Enabled'),
                name: 'enabled',
            }).insertBefore($enabledForSiteField);
            $globalField.find('label').css('font-weight', 'bold');
            this.$globalLightswitch = $globalField.find('.lightswitch');

            if (!this.settings.revisionId) {
                this._showField($globalField);
            }

            // Figure out what the "Enabled everywhere" lightswitch would have been set to when the page first loaded
            var originalEnabledValue = (this.settings.enabled && !Craft.inArray(false, this.settings.siteStatuses))
              ? '1'
              : (this.settings.enabledForSite ? '-' : '');
            var originalSerializedStatus = encodeURIComponent(`enabledForSite[${this.settings.siteId}]`) +
              '=' + (this.settings.enabledForSite ? '1' : '');

            this.$siteLightswitches = $enabledForSiteField.find('.lightswitch')
                .on('change', this._updateGlobalStatus.bind(this));
            let addlSiteOptions = [];

            for (let i = 0; i < Craft.sites.length; i++) {
                let site = Craft.sites[i];
                if (site.id == this.settings.siteId) {
                    continue;
                }
                if (this.settings.siteStatuses.hasOwnProperty(site.id)) {
                    this._createSiteStatusField(site);
                } else if (Craft.inArray(site.id, this.settings.addlSiteIds)) {
                    addlSiteOptions.push({label: site.name, value: site.id});
                }
            }

            var serializedStatuses = `enabled=${originalEnabledValue}`;
            for (let i = 0; i < this.$siteLightswitches.length; i++) {
                let $input = this.$siteLightswitches.eq(i).data('lightswitch').$input;
                serializedStatuses += '&' + encodeURIComponent($input.attr('name')) + '=' + $input.val();
            }

            Craft.cp.$primaryForm.data('initialSerializedValue',
                Craft.cp.$primaryForm.data('initialSerializedValue').replace(originalSerializedStatus, serializedStatuses));

            // Are there additional sites that can be added?
            if (this.settings.addlSiteIds && this.settings.addlSiteIds.length) {
                addlSiteOptions.unshift({label: Craft.t('app', 'Add a site…')});
                let $addlSiteSelectContainer = Craft.ui.createSelect({
                    options: addlSiteOptions,
                }).addClass('fullwidth');
                this.$addlSiteField = Craft.ui.createField($addlSiteSelectContainer, {})
                    .addClass('nested add')
                    .appendTo(this.$siteStatusPane);
                let $addlSiteSelect = $addlSiteSelectContainer.find('select');
                $addlSiteSelect.on('change', () => {
                    let siteId = $addlSiteSelect.val();
                    let site;
                    for (let i = 0; i < Craft.sites.length; i++) {
                        if (Craft.sites[i].id == siteId) {
                            site = Craft.sites[i];
                            break;
                        }
                    }
                    if (site) {
                        this._createSiteStatusField(site);
                        $addlSiteSelect
                            .val('')
                            .find(`option[value="${siteId}"]`).remove();
                        if (this.newSites === null) {
                            this.newSites = [];
                        }
                        this.newSites.push(siteId);
                        // Was that the last site?
                        if ($addlSiteSelect.find('option').length === 1) {
                            this._removeField(this.$addlSiteField);
                        }
                    }
                });
                this._showField(this.$addlSiteField);
            }

            this.$globalLightswitch.on('change', this._updateSiteStatuses.bind(this));
            this._updateGlobalStatus();
        },

        _showField: function($field) {
            let height = $field.height();
            $field
              .css('overflow', 'hidden')
              .height(0)
              .velocity({height: height}, 'fast', () => {
                  $field.css({
                      overflow: '',
                      height: '',
                  });
              });
        },

        _removeField: function($field) {
            let height = $field.height();
            $field
              .css('overflow', 'hidden')
              .velocity({height: 0}, 'fast', () => {
                  $field.remove();
              });
        },

        _updateGlobalStatus: function() {
            var allEnabled = true, allDisabled = true;
            this.$siteLightswitches.each(function() {
                var enabled = $(this).data('lightswitch').on;
                if (enabled) {
                    allDisabled = false;
                } else {
                    allEnabled = false;
                }
                if (!allEnabled && !allDisabled) {
                    return false;
                }
            });
            if (allEnabled) {
                this.$globalLightswitch.data('lightswitch').turnOn(true);
            } else if (allDisabled) {
                this.$globalLightswitch.data('lightswitch').turnOff(true);
            } else {
                this.$globalLightswitch.data('lightswitch').turnIndeterminate(true);
            }
        },

        _updateSiteStatuses: function() {
            var enabled = this.$globalLightswitch.data('lightswitch').on;
            this.$siteLightswitches.each(function() {
                if (enabled) {
                    $(this).data('lightswitch').turnOn(true);
                } else {
                    $(this).data('lightswitch').turnOff(true);
                }
            });
        },

        _createSiteStatusField: function(site) {
            let $field = Craft.ui.createLightswitchField({
                id: `enabledForSite-${site.id}`,
                label: Craft.t('app', 'Enabled for {site}', {site: site.name}),
                name: `enabledForSite[${site.id}]`,
                on: typeof this.settings.siteStatuses[site.id] !== 'undefined'
                    ? this.settings.siteStatuses[site.id]
                    : true,
                disabled: !!this.settings.revisionId,
            });
            if (this.$addlSiteField) {
                $field.insertBefore(this.$addlSiteField);
            } else {
                $field.appendTo(this.$siteStatusPane);
            }

            if (!this.settings.revisionId) {
                $field.addClass('nested');
                let $lightswitch = $field.find('.lightswitch')
                  .on('change', this._updateGlobalStatus.bind(this));
                this.$siteLightswitches = this.$siteLightswitches.add($lightswitch);
            }

            this._showField($field);

            return $field;
        },

        showStatusHud: function(target) {
            var bodyHtml;

            if (this.errors === null) {
                bodyHtml = '<p>' + Craft.t('app', 'The draft has been saved.') + '</p>';
            } else {
                bodyHtml = '<p class="error">' + Craft.t('app', 'The draft could not be saved.') + '</p>';

                if (this.errors.length) {
                    bodyHtml += '<ul class="errors">';
                    for (i = 0; i < this.errors.length; i++) {
                        bodyHtml += '<li>' + Craft.escapeHtml(this.errors[i]) + '</li>';
                    }
                    bodyHtml += '</ul>';
                }
            }

            var hud = new Garnish.HUD(target, bodyHtml, {
                onHide: function() {
                    hud.destroy();
                }
            });
        },

        spinners: function() {
            return this.preview
                ? this.$spinner.add(this.preview.$spinner)
                : this.$spinner;
        },

        statusIcons: function() {
            return this.preview
                ? this.$statusIcon.add(this.preview.$statusIcon)
                : this.$statusIcon;
        },

        createEditMetaBtn: function() {
            this.$editMetaBtn = $('<button/>', {
                type: 'button',
                'class': 'btn edit icon',
                title: Craft.t('app', 'Edit draft settings'),
            }).appendTo($('#context-btngroup'));
            this.addListener(this.$editMetaBtn, 'click', 'showMetaHud');
        },

        createShareMenu: function($shareBtn) {
            $shareBtn.addClass('menubtn');

            var $menu = $('<div/>', {'class': 'menu'}).insertAfter($shareBtn);
            var $ul = $('<ul/>').appendTo($menu);
            var $li, $a;

            for (var i = 0; i < this.settings.previewTargets.length; i++) {
                $li = $('<li/>').appendTo($ul);
                $a = $('<a/>', {
                    text: this.settings.previewTargets[i].label,
                }).appendTo($li);
                this.addListener($a, 'click', {
                    target: i,
                }, function(ev) {
                    this.openShareLink(this.settings.previewTargets[ev.data.target].url);
                }.bind(this));
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

        getTokenizedPreviewUrl: function(url, randoParam) {
            return new Promise(function(resolve, reject) {
                var params = {};

                if (randoParam || !this.settings.isLive) {
                    // Randomize the URL so CDNs don't return cached pages
                    params[randoParam || 'x-craft-preview'] = Craft.randomString(10);
                }

                if (this.settings.siteToken) {
                    params[Craft.siteToken] = this.settings.siteToken;
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
                this.preview.on('open', function() {
                    if (!this.settings.draftId || !Craft.autosaveDrafts) {
                        if (!Craft.autosaveDrafts) {
                            this.enableAutosave = true;
                        }
                        this.listenForChanges();
                    }
                }.bind(this));
                this.preview.on('close', function() {
                    if (!this.settings.draftId || !Craft.autosaveDrafts) {
                        if (!Craft.autosaveDrafts) {
                            this.enableAutosave = false;
                            let $statusIcons = this.statusIcons();
                            if ($statusIcons.hasClass('checkmark-icon')) {
                                $statusIcons.addClass('hidden');
                            }
                        }
                        this.stopListeningForChanges();
                    }
                }.bind(this));
            }
            return this.preview;
        },

        openPreview: function() {
            return new Promise(function(resolve, reject) {
                this.ensureIsDraftOrRevision(true)
                    .then(function() {
                        this.getPreview().open();
                        resolve();
                    }.bind(this))
                    .catch(reject);
            }.bind(this))
        },

        ensureIsDraftOrRevision: function(onlyIfChanged) {
            return new Promise(function(resolve, reject) {
                if (!this.settings.draftId && !this.settings.revisionId) {
                    if (
                        onlyIfChanged &&
                        this.serializeForm(true) === Craft.cp.$primaryForm.data('initialSerializedValue')
                    ) {
                        resolve();
                        return;
                    }

                    this.createDraft()
                        .then(resolve)
                        .catch(reject);
                } else {
                    resolve();
                }
            }.bind(this));
        },

        serializeForm: function(removeActionParams) {
            var data = Craft.cp.$primaryForm.serialize();

            if (this.isPreviewActive()) {
                // Replace the temp input with the preview form data
                data = data.replace('__PREVIEW_FIELDS__=1', this.preview.$editor.serialize());
            }

            if (removeActionParams && !this.settings.isUnsavedDraft) {
                // Remove action and redirect params
                data = data.replace(/&action=[^&]*/, '');
                data = data.replace(/&redirect=[^&]*/, '');
            }

            return data;
        },

        checkForm: function(force) {
            // If this isn't a draft and there's no active preview, then there's nothing to check
            if (
                this.settings.revisionId ||
                (!this.settings.draftId && !this.isPreviewActive()) ||
                this.pauseLevel > 0
            ) {
                return;
            }
            clearTimeout(this.timeout);
            this.timeout = null;

            // Has anything changed?
            var data = this.serializeForm(true);
            if (force || data !== (this.lastSerializedValue || Craft.cp.$primaryForm.data('initialSerializedValue'))) {
                this.saveDraft(data);
            }
        },

        isPreviewActive: function() {
            return this.preview && this.preview.isActive;
        },

        createDraft: function() {
            return new Promise(function(resolve, reject) {
                this.saveDraft(this.serializeForm(true))
                    .then(resolve)
                    .catch(reject);
            }.bind(this));
        },

        saveDraft: function(data) {
            return new Promise(function(resolve, reject) {
                // Ignore if we're already submitting the main form
                if (this.submittingForm) {
                    reject();
                    return;
                }

                if (this.saving) {
                    this.queue.push(function() {
                        this.checkForm()
                    }.bind(this));
                    return;
                }

                this.lastSerializedValue = data;
                this.saving = true;
                var $spinners = this.spinners().removeClass('hidden');
                var $statusIcons = this.statusIcons()
                    .velocity('stop')
                    .css('opacity', '')
                    .removeClass('invisible checkmark-icon alert-icon fade-out')
                    .addClass('hidden');
                if (this.$saveMetaBtn) {
                    this.$saveMetaBtn.addClass('active');
                }
                this.errors = null;

                var url = Craft.getActionUrl(this.settings.saveDraftAction);
                var i;

                this.saveXhr = Craft.postActionRequest(url, this.prepareData(data), function(response, textStatus) {
                    $spinners.addClass('hidden');
                    if (this.$saveMetaBtn) {
                        this.$saveMetaBtn.removeClass('active');
                    }
                    this.saving = false;

                    if (textStatus === 'abort') {
                        return;
                    }

                    if (textStatus !== 'success' || response.errors) {
                        this.errors = (response ? response.errors : null) || [];
                        $statusIcons
                            .velocity('stop')
                            .css('opacity', '')
                            .removeClass('hidden checkmark-icon')
                            .addClass('alert-icon')
                            .attr('title', Craft.t('app', 'The draft could not be saved.'));
                        reject();
                        return;
                    }

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

                    // Did we just add a site?
                    if (this.newSites) {
                        // Do we need to create the revision menu?
                        if (!revisionMenu) {
                            this.$revisionBtn.removeClass('disabled').addClass('menubtn');
                            new Garnish.MenuBtn(this.$revisionBtn);
                            revisionMenu = this.$revisionBtn.data('menubtn').menu;
                            revisionMenu.$container.removeClass('hidden');
                        }
                        for (let i = 0; i < this.newSites.length; i++) {
                            let $option = revisionMenu.$options.filter(`[data-site-id=${this.newSites[i]}]`);
                            $option.find('.status').removeClass('disabled').addClass('enabled');
                            let $li = $option.parent().removeClass('hidden');
                            $li.closest('.site-group').removeClass('hidden');
                        }
                        revisionMenu.$container.find('.revision-hr').removeClass('hidden');
                        this.newSites = null;
                    }

                    // Did we just create a draft?
                    var draftCreated = !this.settings.draftId;
                    if (draftCreated) {
                        // Update the document location HREF
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

                        // Remove the "Save as a Draft" and "Save" buttons
                        $('#save-draft-btn-container').remove();
                        $('#save-btn-container').remove();

                        let $actionButtonContainer = $('#action-buttons');

                        // If they're allowed to update the source, add a "Publish changes" button
                        if (this.settings.canUpdateSource) {
                            $('<button/>', {
                                type: 'button',
                                class: 'btn secondary formsubmit',
                                text: Craft.t('app', 'Publish changes'),
                                data: {
                                    action: this.settings.applyDraftAction,
                                },
                            }).appendTo($actionButtonContainer).formsubmit();
                        }

                        // If autosaving is disabled, add a "Save draft" button
                        if (!Craft.autosaveDrafts) {
                            $('<button/>', {
                                type: 'submit',
                                class: 'btn submit',
                                text: Craft.t('app', 'Save draft'),
                            }).appendTo($actionButtonContainer);
                        }

                        // Update the editor settings
                        this.settings.draftId = response.draftId;
                        this.settings.isLive = false;
                        this.settings.canDeleteDraft = true;
                        this.previewToken = null;
                        this.initForDraft();

                        // Add the draft to the revision menu
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
                            var $draftLi = $('<li/>').prependTo($draftsUl);
                            var $draftA = $('<a/>', {
                                'class': 'sel',
                                html: '<span class="draft-name"></span> <span class="draft-meta light"></span>',
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
                        revisionMenu.$options.filter('.sel').find('.draft-meta').text('– ' + (response.creator
                            ? Craft.t('app', 'saved {timestamp} by {creator}', {
                                timestamp: response.timestamp,
                                creator: response.creator
                            })
                            : Craft.t('app', 'updated {timestamp}', {
                                timestamp: response.timestamp,
                            })
                        ));
                    }

                    // Did the controller send us updated preview targets?
                    if (
                        response.previewTargets &&
                        JSON.stringify(response.previewTargets) !== JSON.stringify(this.settings.previewTargets)
                    ) {
                        this.updatePreviewTargets(response.previewTargets);
                    }

                    this.afterUpdate(data);

                    if (draftCreated) {
                        this.trigger('createDraft');
                    }

                    if (this.$nameTextInput) {
                        this.checkMetaValues();
                    }

                    for (let oldId in response.duplicatedElements) {
                        if (oldId != this.settings.sourceId && response.duplicatedElements.hasOwnProperty(oldId)) {
                            this.duplicatedElements[oldId] = response.duplicatedElements[oldId];
                        }
                    }

                    resolve();
                }.bind(this));
            }.bind(this));
        },

        prepareData: function(data) {
            // Swap out element IDs with their duplicated ones
            data = this.swapDuplicatedElementIds(data);

            // Add the draft info
            if (this.settings.draftId) {
                data += '&draftId=' + this.settings.draftId
                    + '&draftName=' + encodeURIComponent(this.settings.draftName)
                    + '&draftNotes=' + encodeURIComponent(this.settings.draftNotes || '');
            }


            // Filter out anything that hasn't changed
            var initialData = this.swapDuplicatedElementIds(Craft.cp.$primaryForm.data('initialSerializedValue'));
            return Craft.findDeltaData(initialData, data, this.getDeltaNames());
        },

        swapDuplicatedElementIds: function(data) {
            let idsRE = Object.keys(this.duplicatedElements).join('|');
            if (idsRE === '') {
                return data;
            }
            let lb = encodeURIComponent('[');
            let rb = encodeURIComponent(']');
            // Keep replacing field IDs until data stops changing
            while (true) {
                if (data === (
                    data = data
                        // &fields[...][X]
                        .replace(new RegExp(`(&fields${lb}[^=]+${rb}${lb})(${idsRE})(${rb})`, 'g'), (m, pre, id, post) => {
                            return pre + this.duplicatedElements[id] + post;
                        })
                        // &fields[...=X
                        .replace(new RegExp(`(&fields${lb}[^=]+=)(${idsRE})\\b`, 'g'), (m, pre, id) => {
                            return pre + this.duplicatedElements[id];
                        })
                )) {
                    break;
                }
            }
            return data;
        },

        getDeltaNames: function() {
            var deltaNames = Craft.deltaNames.slice(0);
            for (var i = 0; i < deltaNames.length; i++) {
                for (var oldId in this.duplicatedElements) {
                    if (this.duplicatedElements.hasOwnProperty(oldId)) {
                        deltaNames[i] = deltaNames[i].replace('][' + oldId + ']', '][' + this.duplicatedElements[oldId] + ']');
                    }
                }
            }
            return deltaNames;
        },

        updatePreviewTargets: function(previewTargets) {
            // index the current preview targets by label
            var currentTargets = {};
            for (var i = 0; i < this.settings.previewTargets.length; i++) {
                currentTargets[this.settings.previewTargets[i].label] = this.settings.previewTargets[i];
            }
            for (i = 0; i < previewTargets.length; i++) {
                if (currentTargets[previewTargets[i].label]) {
                    currentTargets[previewTargets[i].label].url = previewTargets[i].url;
                }
            }
        },

        afterUpdate: function(data) {
            Craft.cp.$primaryForm.data('initialSerializedValue', data);
            Craft.initialDeltaValues = {};
            let $statusIcons = this.statusIcons()
                .velocity('stop')
                .css('opacity', '')
                .removeClass('hidden')
                .addClass('checkmark-icon')
                .attr('title', Craft.t('app', 'The draft has been saved.'));

            if (!this.enableAutosave) {
                // Fade the icon out after a couple seconds, since it won't be accurate as content continues to change
                $statusIcons
                    .velocity('stop')
                    .velocity({
                        opacity: 0,
                    }, {
                        delay: 2000,
                        complete: () => {
                            $statusIcons.addClass('hidden');
                        },
                    });
            }

            this.trigger('update');

            this.nextInQueue();
        },

        nextInQueue: function() {
            if (this.queue.length) {
                this.queue.shift()();
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
            let $deleteLink;
            if (this.settings.canDeleteDraft) {
                $deleteLink = $('<a class="error" role="button">' + Craft.t('app', 'Delete') + '</a>').appendTo($footer);
            }

            $('<div class="flex-grow"></div>').appendTo($footer);
            this.$saveMetaBtn = $('<button/>', {
                type: 'submit',
                class: 'btn submit disabled',
                text: Craft.t('app', 'Save'),
            }).appendTo($footer);

            this.metaHud = new Garnish.HUD(this.$editMetaBtn, $hudBody, {
                onSubmit: this.saveMeta.bind(this)
            });

            new Garnish.NiceText(this.$notesTextInput);

            this.addListener(this.$notesTextInput, 'keydown', 'onNotesKeydown');

            this.addListener(this.$nameTextInput, 'input', 'checkMetaValues');
            this.addListener(this.$notesTextInput, 'input', 'checkMetaValues');

            this.metaHud.on('show', this.onMetaHudShow.bind(this));
            this.metaHud.on('hide', this.onMetaHudHide.bind(this));
            this.metaHud.on('escape', this.onMetaHudEscape.bind(this));

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

            Craft.postActionRequest(this.settings.deleteDraftAction, {draftId: this.settings.draftId}, function(response, textStatus) {
                if (textStatus === 'success') {
                    window.location.href = this.settings.cpEditUrl;
                }
            }.bind(this))
        },

        handleFormSubmit: function(ev) {
            ev.preventDefault();

            // Prevent double form submits
            if (this.submittingForm) {
                return;
            }

            // Is this a normal draft, and was this a normal save (either via submit button or save shortcut)?
            if (this.settings.draftId && !this.settings.isUnsavedDraft && !ev.customTrigger) {
                this.checkForm(true);
                return;
            }

            // Prevent the normal unload confirmation dialog
            Craft.cp.$confirmUnloadForms = Craft.cp.$confirmUnloadForms.not(Craft.cp.$primaryForm);

            // Abort the current save request if there is one
            if (this.saving) {
                this.saveXhr.abort();
            }

            // Duplicate the form with normalized data
            var data = this.prepareData(this.serializeForm(false));
            var $form = Craft.createForm(data);

            if (this.settings.draftId) {
                if (
                    this.settings.isUnsavedDraft &&
                    (!ev.customTrigger || !ev.customTrigger.data('action'))
                ) {
                    $('<input/>', {
                        type: 'hidden',
                        name: 'action',
                        value: this.settings.applyDraftAction
                    }).appendTo($form);
                }

                if (
                    (!ev.saveShortcut || !Craft.cp.$primaryForm.data('saveshortcut-redirect')) &&
                    (!ev.customTrigger || !ev.customTrigger.data('redirect'))
                ) {
                    $('<input/>', {
                        type: 'hidden',
                        name: 'redirect',
                        value: this.settings.hashedRedirectUrl
                    }).appendTo($form);
                }
            }

            $form.appendTo(Garnish.$bod);
            $form.submit();
            this.submittingForm = true;
        },
    },
    {
        defaults: {
            elementType: null,
            sourceId: null,
            siteId: null,
            isLive: false,
            siteStatuses: null,
            addlSiteIds: [],
            enabledGlobally: null,
            cpEditUrl: null,
            draftId: null,
            revisionId: null,
            draftName: null,
            draftNotes: null,
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
