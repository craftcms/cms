/** global: Craft */
/** global: Garnish */
/**
 * Element Monitor
 */
Craft.DraftEditor = Garnish.Base.extend({
    $revisionBtn: null,
    $revisionLabel: null,
    $spinner: null,
    $expandSiteStatusesBtn: null,
    $statusIcon: null,

    $editMetaBtn: null,
    metaHud: null,
    $nameTextInput: null,
    $saveMetaBtn: null,

    $siteStatusPane: null,
    $globalLightswitch: null,
    $siteLightswitches: null,
    $addlSiteField: null,

    siteIds: null,
    newSiteIds: null,

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

    openingPreview: false,
    preview: null,
    previewToken: null,
    createdDraftInPreview: false,

    init: function(settings) {
        this.setSettings(settings, Craft.DraftEditor.defaults);

        this.queue = [];
        this.duplicatedElements = {};
        this.enableAutosave = Craft.autosaveDrafts;

        this.siteIds = Object.keys(this.settings.siteStatuses).map(siteId => {
            return parseInt(siteId)
        });

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

            const $shareBtn = $('#share-btn');

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
            if (['keypress', 'keyup', 'change'].includes(ev.type)) {
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
            if (this.enableAutosave) {
                this.checkForm();
            }
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

        if (this.settings.canUpdateSource) {
            Garnish.shortcutManager.registerShortcut({
                keyCode: Garnish.S_KEY,
                ctrl: true,
                alt: true
            }, () => {
                Craft.submitForm(Craft.cp.$primaryForm, {
                    action: this.settings.publishDraftAction,
                    redirect: this.settings.hashedCpEditUrl,
                });
            }, 0);
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

        const $enabledForSiteField = $(`#enabledForSite-${this.settings.siteId}-field`);
        this.$siteStatusPane = $enabledForSiteField.parent();

        // If this is a revision, just show the site statuses statically and be done
        if (this.settings.revisionId) {
            this._getOtherSupportedSites().forEach(s => this._createSiteStatusField(s));
            return;
        }

        $enabledForSiteField.addClass('nested');
        const $globalField = Craft.ui.createLightswitchField({
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
        const originalEnabledValue = (this.settings.enabled && !Craft.inArray(false, this.settings.siteStatuses))
            ? '1'
            : (this.settings.enabledForSite ? '-' : '');
        const originalSerializedStatus = encodeURIComponent(`enabledForSite[${this.settings.siteId}]`) +
            '=' + (this.settings.enabledForSite ? '1' : '');

        this.$siteLightswitches = $enabledForSiteField.find('.lightswitch')
            .on('change', this._updateGlobalStatus.bind(this));

        this._getOtherSupportedSites().forEach(s => this._createSiteStatusField(s));

        let serializedStatuses = `enabled=${originalEnabledValue}`;
        for (let i = 0; i < this.$siteLightswitches.length; i++) {
            const $input = this.$siteLightswitches.eq(i).data('lightswitch').$input;
            serializedStatuses += '&' + encodeURIComponent($input.attr('name')) + '=' + $input.val();
        }

        Craft.cp.$primaryForm.data('initialSerializedValue',
            Craft.cp.$primaryForm.data('initialSerializedValue').replace(originalSerializedStatus, serializedStatuses));

        // Are there additional sites that can be added?
        if (this.settings.addlSiteIds && this.settings.addlSiteIds.length) {
            this._createAddlSiteField();
        }

        this.$globalLightswitch.on('change', this._updateSiteStatuses.bind(this));
        this._updateGlobalStatus();
    },

    /**
     * @returns {Array}
     */
    _getOtherSupportedSites: function() {
        return Craft.sites.filter(s => s.id != this.settings.siteId && this.siteIds.includes(s.id));
    },

    _showField: function($field) {
        const height = $field.height();
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
        const height = $field.height();
        $field
            .css('overflow', 'hidden')
            .velocity({height: 0}, 'fast', () => {
                $field.remove();
            });
    },

    _updateGlobalStatus: function() {
        let allEnabled = true, allDisabled = true;
        this.$siteLightswitches.each(function() {
            const enabled = $(this).data('lightswitch').on;
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
        const enabled = this.$globalLightswitch.data('lightswitch').on;
        this.$siteLightswitches.each(function() {
            if (enabled) {
                $(this).data('lightswitch').turnOn(true);
            } else {
                $(this).data('lightswitch').turnOff(true);
            }
        });
    },

    _createSiteStatusField: function(site) {
        const $field = Craft.ui.createLightswitchField({
            id: `enabledForSite-${site.id}`,
            label: Craft.t('app', 'Enabled for {site}', {site: site.name}),
            name: `enabledForSite[${site.id}]`,
            on: this.settings.siteStatuses.hasOwnProperty(site.id)
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
            const $lightswitch = $field.find('.lightswitch')
                .on('change', this._updateGlobalStatus.bind(this));
            this.$siteLightswitches = this.$siteLightswitches.add($lightswitch);
        }

        this._showField($field);

        return $field;
    },

    _createAddlSiteField: function() {
        const addlSites = Craft.sites.filter(s => {
            return !this.siteIds.includes(s.id) && this.settings.addlSiteIds.includes(s.id);
        });

        if (!addlSites.length) {
            return;
        }

        const $addlSiteSelectContainer = Craft.ui.createSelect({
            options: [
                {label: Craft.t('app', 'Add a site…')},
                ...addlSites.map(s => {
                    return {label: s.name, value: s.id};
                }),
            ],
        }).addClass('fullwidth');

        this.$addlSiteField = Craft.ui.createField($addlSiteSelectContainer, {})
            .addClass('nested add')
            .appendTo(this.$siteStatusPane);

        const $addlSiteSelect = $addlSiteSelectContainer.find('select');

        $addlSiteSelect.on('change', () => {
            const siteId = parseInt($addlSiteSelect.val());
            const site = Craft.sites.find(s => s.id === siteId);

            if (!site) {
                return;
            }

            this._createSiteStatusField(site);

            $addlSiteSelect
                .val('')
                .find(`option[value="${siteId}"]`).remove();

            if (this.newSiteIds === null) {
                this.newSiteIds = [];
            }

            this.siteIds.push(siteId);
            this.newSiteIds.push(siteId);

            // Was that the last site?
            if ($addlSiteSelect.find('option').length === 1) {
                this._removeField(this.$addlSiteField);
            }
        });

        this._showField(this.$addlSiteField);
    },

    showStatusHud: function(target) {
        let bodyHtml;

        if (this.errors === null) {
            bodyHtml = '<p>' + Craft.t('app', 'The draft has been saved.') + '</p>';
        } else {
            bodyHtml = '<p class="error">' + Craft.t('app', 'The draft could not be saved.') + '</p>';

            if (this.errors.length) {
                bodyHtml += '<ul class="errors">' +
                    this.errors.map(e => `<li>${Craft.escapeHtml(e)}</li>`).join('') +
                    '</ul>';
            }
        }

        const hud = new Garnish.HUD(target, bodyHtml, {
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

        const $menu = $('<div/>', {'class': 'menu'}).insertAfter($shareBtn);
        const $ul = $('<ul/>').appendTo($menu);

        this.settings.previewTargets.forEach(target => {
            const $li = $('<li/>').appendTo($ul);
            const $a = $('<a/>', {
                text: target.label,
            }).appendTo($li);
            this.addListener($a, 'click', () => {
                this.openShareLink(target.url);
            });
        });
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
            const params = {};

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
                        const $statusIcons = this.statusIcons();
                        if ($statusIcons.hasClass('checkmark-icon')) {
                            $statusIcons.addClass('hidden');
                        }
                    }
                    this.stopListeningForChanges();
                }

                // did we just create a draft?
                if (this.createdDraftInPreview) {
                    setTimeout(() => {
                        this.createDraftNoticeHud();
                        this.createdDraftInPreview = false;
                    }, 750);
                }
            }.bind(this));
        }
        return this.preview;
    },

    createDraftNoticeHud: function() {
        const $closeBtn = $('<button/>', {
            class: 'btn',
            type: 'button',
            text: Craft.t('app', 'Keep it'),
        });
        const $deleteBtn = $('<button/>', {
            class: 'btn caution',
            type: 'button',
            text: Craft.t('app', 'Delete it'),
        });

        const hud = new Garnish.HUD(
            $('#context-btngroup'),
            $('<div/>', {class: 'readable centeralign'})
                .append(
                    $('<p/>', {
                        text: Craft.t('app', 'You’re now editing a draft.'),
                    })
                )
                .append(
                    $('<div/>', {class: 'flex flex-nowrap'})
                        .append($closeBtn)
                        .append($deleteBtn)
                ),
            {
                hideOnEsc: false,
                hideOnShadeClick: false,
            }
        );

        $closeBtn.on('click', () => {
            hud.hide();
            hud.destroy();
        });

        $deleteBtn.on('click', () => {
            if (confirm(Craft.t('app', 'Are you sure you want to delete this draft?'))) {
                Craft.submitForm(Craft.cp.$primaryForm, {
                    action: this.settings.deleteDraftAction,
                    redirect: this.settings.hashedCpEditUrl,
                });
            }
        })
    },

    openPreview: function() {
        return new Promise(function(resolve, reject) {
            this.openingPreview = true;
            this.ensureIsDraftOrRevision(true)
                .then(function() {
                    this.getPreview().open();
                    this.openingPreview = false;
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
        let data = Craft.cp.$primaryForm.serialize();

        if (this.isPreviewActive()) {
            // Replace the temp input with the preview form data
            data = data.replace('__PREVIEW_FIELDS__=1', this.preview.$editor.serialize());
        }

        if (removeActionParams && !this.settings.isUnpublishedDraft) {
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
        const data = this.serializeForm(true);
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
            const $spinners = this.spinners().removeClass('hidden');
            const $statusIcons = this.statusIcons()
                .velocity('stop')
                .css('opacity', '')
                .removeClass('invisible checkmark-icon alert-icon fade-out')
                .addClass('hidden');
            if (this.$saveMetaBtn) {
                this.$saveMetaBtn.addClass('active');
            }
            this.errors = null;

            const url = Craft.getActionUrl(this.settings.saveDraftAction);

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

                let revisionMenu = this.$revisionBtn.data('menubtn') ? this.$revisionBtn.data('menubtn').menu : null;

                // Did we just add a site?
                if (this.newSiteIds) {
                    // Do we need to create the revision menu?
                    if (!revisionMenu) {
                        this.$revisionBtn.removeClass('disabled').addClass('menubtn');
                        new Garnish.MenuBtn(this.$revisionBtn);
                        revisionMenu = this.$revisionBtn.data('menubtn').menu;
                        revisionMenu.$container.removeClass('hidden');
                    }
                    this.newSiteIds.forEach(siteId => {
                        const $option = revisionMenu.$options.filter(`[data-site-id=${siteId}]`);
                        $option.find('.status').removeClass('disabled').addClass('enabled');
                        const $li = $option.parent().removeClass('hidden');
                        $li.closest('.site-group').removeClass('hidden');
                    });
                    revisionMenu.$container.find('.revision-hr').removeClass('hidden');
                    this.newSiteIds = null;
                }

                // Did we just create a draft?
                const draftCreated = !this.settings.draftId;
                if (draftCreated) {
                    // Update the document location HREF
                    let newHref;
                    const anchorPos = document.location.href.search('#');
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

                    // Replace the action input
                    $('#action').remove();
                    $('<input/>', {
                        id: 'action',
                        type: 'hidden',
                        name: 'action',
                        value: this.settings.saveDraftAction,
                    }).appendTo(Craft.cp.$primaryForm);

                    // Remove the "Save as a Draft" and "Save" buttons
                    $('#save-draft-btn-container').remove();
                    $('#save-btn-container').remove();

                    const $actionButtonContainer = $('#action-buttons');

                    // If they're allowed to update the source, add a "Publish draft" button
                    if (this.settings.canUpdateSource) {
                        $('<button/>', {
                            type: 'button',
                            class: 'btn secondary formsubmit',
                            text: Craft.t('app', 'Publish draft'),
                            title: Craft.shortcutText('S', false, true),
                            data: {
                                action: this.settings.publishDraftAction,
                                redirect: this.settings.hashedCpEditUrl,
                            },
                        }).appendTo($actionButtonContainer).formsubmit();
                    }

                    // Add a "Save draft" button
                    const $saveBtnContainer = $('<div/>', {
                        id: 'save-btn-container',
                        class: 'btngroup submit',
                    }).appendTo($actionButtonContainer);

                    $('<button/>', {
                        type: 'submit',
                        class: 'btn submit',
                        text: Craft.t('app', 'Save draft'),
                    }).appendTo($saveBtnContainer);

                    if (this.settings.saveDraftAction || this.settings.deleteDraftAction) {
                        const $menuBtn = $('<button/>', {
                            type: 'button',
                            class: 'btn submit menubtn',
                        }).appendTo($saveBtnContainer);
                        const $menu = $('<div/>', {
                            class: 'menu',
                            attr: {
                                'data-align': 'right',
                            },
                        }).appendTo($saveBtnContainer);

                        if (this.settings.saveDraftAction) {
                            const $ul = $('<ul/>')
                                .appendTo($menu)
                                .append(
                                    $('<li/>')
                                        .append(
                                            $('<a/>', {
                                                class: 'formsubmit',
                                                data: {
                                                    action: this.settings.saveDraftAction,
                                                },
                                                text: Craft.t('app', 'Save and continue editing'),
                                            })
                                                .prepend(
                                                    $('<span/>', {
                                                        class: 'shortcut',
                                                        text: Craft.shortcutText('S'),
                                                    })
                                                )
                                        )
                                );
                            if (this.settings.canUpdateSource && this.settings.hashedAddAnotherRedirectUrl) {
                                $ul.append(
                                    $('<li/>')
                                        .append(
                                            $('<a/>', {
                                                class: 'formsubmit',
                                                data: {
                                                    action: this.settings.publishDraftAction,
                                                    redirect: this.settings.hashedAddAnotherRedirectUrl,
                                                },
                                                text: Craft.t('app', 'Publish and add another'),
                                            })
                                        )
                                );
                            }
                            if (this.settings.deleteDraftAction) {
                                $('<hr/>').appendTo($menu);
                            }
                        }

                        if (this.settings.deleteDraftAction) {
                            $('<ul/>')
                                .appendTo($menu)
                                .append(
                                    $('<li/>')
                                        .append(
                                            $('<a/>', {
                                                class: 'formsubmit error',
                                                data: {
                                                    action: this.settings.deleteDraftAction,
                                                    redirect: this.settings.hashedCpEditUrl,
                                                    confirm: Craft.t('app', 'Are you sure you want to delete this draft?'),
                                                },
                                                text: Craft.t('app', 'Delete draft'),
                                            })
                                        )
                                )
                        }
                    }

                    Craft.initUiElements($saveBtnContainer);

                    // Update the editor settings
                    this.settings.draftId = response.draftId;
                    this.settings.isLive = false;
                    this.previewToken = null;
                    this.initForDraft();

                    // Add the draft to the revision menu
                    if (revisionMenu) {
                        revisionMenu.$options.filter(':not(.site-option)').removeClass('sel');
                        let $draftsUl = revisionMenu.$container.find('.revision-group-drafts');
                        if (!$draftsUl.length) {
                            const $draftHeading = $('<h6/>', {
                                text: Craft.t('app', 'Drafts'),
                            }).insertAfter(revisionMenu.$container.find('.revision-group-current'));
                            $draftsUl = $('<ul/>', {
                                'class': 'padded revision-group-drafts',
                            }).insertAfter($draftHeading);
                        }
                        const $draftLi = $('<li/>').prependTo($draftsUl);
                        const $draftA = $('<a/>', {
                            'class': 'sel',
                            html: '<span class="draft-name"></span> <span class="draft-meta light"></span>',
                        }).appendTo($draftLi);
                        revisionMenu.addOptions($draftA);
                        revisionMenu.selectOption($draftA);

                        // Update the site URLs
                        const $siteOptions = revisionMenu.$options.filter('.site-option[href]');
                        for (let i = 0; i < $siteOptions.length; i++) {
                            const $siteOption = $siteOptions.eq(i);
                            $siteOption.attr('href', Craft.getUrl($siteOption.attr('href'), {draftId: response.draftId}));
                        }
                    }

                    // is Live Preview currently active?
                    if (this.openingPreview || (this.preview && this.preview.isActive)) {
                        this.createdDraftInPreview = true;
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

                for (const oldId in response.duplicatedElements) {
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
            data += `&draftId=${this.settings.draftId}`;
        }

        if (this.settings.draftName !== null) {
            data += `&draftName=${this.settings.draftName}`;
        }

        // Filter out anything that hasn't changed
        const initialData = this.swapDuplicatedElementIds(Craft.cp.$primaryForm.data('initialSerializedValue'));
        return Craft.findDeltaData(initialData, data, this.getDeltaNames());
    },

    swapDuplicatedElementIds: function(data) {
        const idsRE = Object.keys(this.duplicatedElements).join('|');
        if (idsRE === '') {
            return data;
        }
        const lb = encodeURIComponent('[');
        const rb = encodeURIComponent(']');
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
        const deltaNames = Craft.deltaNames.slice(0);
        for (let i = 0; i < deltaNames.length; i++) {
            for (const oldId in this.duplicatedElements) {
                if (this.duplicatedElements.hasOwnProperty(oldId)) {
                    deltaNames[i] = deltaNames[i].replace('][' + oldId + ']', '][' + this.duplicatedElements[oldId] + ']');
                }
            }
        }
        return deltaNames;
    },

    updatePreviewTargets: function(previewTargets) {
        previewTargets.forEach(newTarget => {
            const currentTarget = this.settings.previewTargets.find(t => t.label === newTarget.label);
            if (currentTarget) {
                currentTarget.url = newTarget.url;
            }
        });
    },

    afterUpdate: function(data) {
        Craft.cp.$primaryForm.data('initialSerializedValue', data);
        Craft.initialDeltaValues = {};
        const $statusIcons = this.statusIcons()
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
        const $hudBody = $('<div/>');

        // Add the Name field
        const $nameField = $('<div class="field"><div class="heading"><label for="draft-name">' + Craft.t('app', 'Draft Name') + '</label></div></div>').appendTo($hudBody);
        const $nameInputContainer = $('<div class="input"/>').appendTo($nameField);
        this.$nameTextInput = $('<input type="text" class="text fullwidth" id="draft-name"/>').appendTo($nameInputContainer).val(this.settings.draftName);

        // HUD footer
        const $footer = $('<div class="hud-footer flex flex-center"/>').appendTo($hudBody);

        $('<div class="flex-grow"></div>').appendTo($footer);
        this.$saveMetaBtn = $('<button/>', {
            type: 'submit',
            class: 'btn submit disabled',
            text: Craft.t('app', 'Save'),
        }).appendTo($footer);

        this.metaHud = new Garnish.HUD(this.$editMetaBtn, $hudBody, {
            onSubmit: this.saveMeta.bind(this)
        });

        this.addListener(this.$nameTextInput, 'input', 'checkMetaValues');

        this.metaHud.on('show', this.onMetaHudShow.bind(this));
        this.metaHud.on('hide', this.onMetaHudHide.bind(this));
        this.metaHud.on('escape', this.onMetaHudEscape.bind(this));
    },

    onMetaHudShow: function() {
        this.$editMetaBtn.addClass('active');
    },

    onMetaHudHide: function() {
        this.$editMetaBtn.removeClass('active');
    },

    onMetaHudEscape: function() {
        this.$nameTextInput.val(this.settings.draftName);
    },

    checkMetaValues: function() {
        if (
            this.$nameTextInput.val() &&
            this.$nameTextInput.val() !== this.settings.draftName
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

        this.metaHud.hide();
        this.checkForm(true);
    },

    handleFormSubmit: function(ev) {
        ev.preventDefault();

        // Prevent double form submits
        if (this.submittingForm) {
            return;
        }

        // If this a draft and was this a normal save (either via submit button or save shortcut),
        // then trigger an autosave
        if (
            this.settings.draftId &&
            (typeof ev.autosave === 'undefined' || ev.autosave) &&
            (ev.saveShortcut || (ev.customTrigger && ev.customTrigger.data('action') === this.settings.saveDraftAction))
        ) {
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
        const data = this.prepareData(this.serializeForm(false));
        const $form = Craft.createForm(data);

        $form.appendTo(Garnish.$bod);
        $form.submit();
        this.submittingForm = true;
    },
}, {
    defaults: {
        elementType: null,
        sourceId: null,
        siteId: null,
        isUnpublishedDraft: false,
        enabled: false,
        enabledForSite: false,
        isLive: false,
        siteStatuses: null,
        addlSiteIds: [],
        cpEditUrl: null,
        draftId: null,
        revisionId: null,
        draftName: null,
        canEditMultipleSites: false,
        canUpdateSource: false,
        saveDraftAction: null,
        deleteDraftAction: null,
        publishDraftAction: null,
        hashedCpEditUrl: null,
        hashedAddAnotherRedirectUrl: null,
        enablePreview: false,
        previewTargets: [],
        siteToken: null,
    }
});
