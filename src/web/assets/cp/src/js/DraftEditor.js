/** global: Craft */
/** global: Garnish */
/**
 * Element draft editor
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
    cancelToken: null,
    ignoreFailedRequest: false,
    queue: null,
    submittingForm: false,

    duplicatedElements: null,
    errors: null,
    httpStatus: null,
    httpError: null,

    openingPreview: false,
    preview: null,
    activatingPreviewToken: false,
    activatedPreviewToken: false,
    previewTokenQueue: null,
    previewLinks: null,
    scrollY: null,
    createdProvisionalDraft: false,

    bc: null,

    init: function (settings) {
      this.setSettings(settings, Craft.DraftEditor.defaults);

      this.queue = [];
      this.previewTokenQueue = [];
      this.duplicatedElements = {};
      this.enableAutosave = Craft.autosaveDrafts;
      this.previewLinks = [];

      this.siteIds = Object.keys(this.settings.siteStatuses).map((siteId) => {
        return parseInt(siteId);
      });

      this.$revisionBtn = $('#context-btn');
      this.$revisionLabel = $('#revision-label');
      this.$spinner = $('#revision-spinner');
      this.$expandSiteStatusesBtn = $('#expand-status-btn');
      this.$statusIcon = $('#revision-status');
      this.$statusMessage = $('#revision-status-message');

      if (this.settings.canEditMultipleSites) {
        this.addListener(
          this.$expandSiteStatusesBtn,
          'click',
          'expandSiteStatuses'
        );
      }

      if (this.settings.previewTargets.length) {
        if (this.settings.enablePreview) {
          this.addListener($('#preview-btn'), 'click', 'openPreview');
        }

        const $previewBtnContainer = $('#preview-btn-container');

        if (this.settings.previewTargets.length === 1) {
          const [target] = this.settings.previewTargets;
          this.createPreviewLink(target)
            .attr('id', 'share-btn')
            .addClass('btn')
            .appendTo($previewBtnContainer);
        } else {
          this.createShareMenu($previewBtnContainer);
        }
      }

      // If this is a revision, we're done here
      if (this.settings.revisionId) {
        return;
      }

      // Override the serializer to use our own
      Craft.cp.$primaryForm.data('serializer', () => this.serializeForm(true));

      this.addListener(Craft.cp.$primaryForm, 'submit', 'handleFormSubmit');

      if (this.settings.isProvisionalDraft) {
        this.initForProvisionalDraft();
      } else if (this.settings.draftId && !this.settings.isUnpublishedDraft) {
        this.initForDraft();
      } else if (!this.settings.canUpdateSource) {
        // Override the save shortcut to create a draft too
        this.addListener(Craft.cp.$primaryForm, 'submit.saveShortcut', (ev) => {
          if (ev.saveShortcut) {
            ev.preventDefault();
            this.createDraft();
            this.removeListener(Craft.cp.$primaryForm, 'submit.saveShortcut');
          }
        });
      }

      this.listenForChanges();

      this.addListener(this.$statusIcon, 'click', () => {
        this.showStatusHud(this.$statusIcon);
      });

      if (
        typeof BroadcastChannel !== 'undefined' &&
        !this.settings.revisionId
      ) {
        this.bc = new BroadcastChannel('DraftEditor');
        this.bc.onmessage = (ev) => {
          if (
            ev.data.event === 'saveDraft' &&
            ev.data.canonicalId === this.settings.sourceId &&
            (ev.data.draftId === this.settings.draftId ||
              (ev.data.isProvisionalDraft && !this.settings.draftId))
          ) {
            window.location.reload();
          }
        };
      }
    },

    listenForChanges: function () {
      if (
        this.listeningForChanges ||
        this.pauseLevel > 0 ||
        !this.enableAutosave ||
        !this.settings.saveDraftAction
      ) {
        return;
      }

      this.listeningForChanges = true;

      this.addListener(
        Garnish.$bod,
        'keypress,keyup,change,focus,blur,click,mousedown,mouseup',
        function (ev) {
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
        }
      );
    },

    stopListeningForChanges: function () {
      if (!this.listeningForChanges) {
        return;
      }

      this.removeListener(
        Garnish.$bod,
        'keypress,keyup,change,focus,blur,click,mousedown,mouseup'
      );
      clearTimeout(this.timeout);
      this.listeningForChanges = false;
    },

    pause: function () {
      this.pauseLevel++;
      this.stopListeningForChanges();
    },

    resume: function () {
      if (this.pauseLevel === 0) {
        throw 'Craft.DraftEditor::resume() should only be called after pause().';
      }

      // Only actually resume operation if this has been called the same
      // number of times that pause() was called
      this.pauseLevel--;
      if (this.pauseLevel === 0) {
        if (this.enableAutosave) {
          this.checkForm();
          this.listenForChanges();
        }
      }
    },

    initForProvisionalDraft: function () {
      let $button = $('#discard-changes');
      if (!$button.length) {
        $button = this.$revisionBtn.data('menubtn')
          ? this.$revisionBtn
              .data('menubtn')
              .menu.$container.find('#discard-changes')
          : null;
      }
      if ($button && $button.length) {
        this.addListener($button, 'click', () => {
          if (
            confirm(
              Craft.t('app', 'Are you sure you want to discard your changes?')
            )
          ) {
            Craft.submitForm(Craft.cp.$primaryForm, {
              action: this.settings.deleteDraftAction,
              redirect: this.settings.hashedCpEditUrl,
              params: {
                draftId: this.settings.draftId,
                provisional: this.settings.isProvisionalDraft,
              },
            });
          }
        });
      }
    },

    initForDraft: function () {
      // Create the edit draft button
      this.createEditMetaBtn();

      if (this.settings.canUpdateSource) {
        Garnish.uiLayerManager.registerShortcut(
          {
            keyCode: Garnish.S_KEY,
            ctrl: true,
            alt: true,
          },
          () => {
            Craft.submitForm(Craft.cp.$primaryForm, {
              action: this.settings.publishDraftAction,
              redirect: this.settings.hashedCpEditUrl,
            });
          },
          0
        );
      }
    },

    expandSiteStatuses: function () {
      this.removeListener(this.$expandSiteStatusesBtn, 'click');
      this.$expandSiteStatusesBtn.velocity({opacity: 0}, 'fast', () => {
        this.$expandSiteStatusesBtn.remove();
      });

      const $enabledForSiteField = $(
        `#enabledForSite-${this.settings.siteId}-field`
      );
      this.$siteStatusPane = $enabledForSiteField.parent();

      // If this is a revision, just show the site statuses statically and be done
      if (this.settings.revisionId) {
        this._getOtherSupportedSites().forEach((s) =>
          this._createSiteStatusField(s)
        );
        return;
      }

      $enabledForSiteField.addClass('nested');
      const $globalField = Craft.ui
        .createLightswitchField({
          id: 'enabled',
          label: Craft.t('app', 'Enabled'),
          name: 'enabled',
        })
        .insertBefore($enabledForSiteField);
      $globalField.find('label').css('font-weight', 'bold');
      this.$globalLightswitch = $globalField.find('.lightswitch');

      if (!this.settings.revisionId) {
        this._showField($globalField);
      }

      // Figure out what the "Enabled everywhere" lightswitch would have been set to when the page first loaded
      const siteStatusValues = Object.values(this.settings.siteStatuses);
      const hasEnabled = siteStatusValues.includes(true);
      const hasDisabled = siteStatusValues.includes(false);
      const originalEnabledValue =
        hasEnabled && hasDisabled ? '-' : hasEnabled ? '1' : '';
      const originalSerializedStatus =
        encodeURIComponent(`enabledForSite[${this.settings.siteId}]`) +
        '=' +
        (this.settings.enabledForSite ? '1' : '');

      this.$siteLightswitches = $enabledForSiteField
        .find('.lightswitch')
        .on('change', this._updateGlobalStatus.bind(this));

      this._getOtherSupportedSites().forEach((s) =>
        this._createSiteStatusField(s)
      );

      let serializedStatuses = `enabled=${originalEnabledValue}`;
      for (let i = 0; i < this.$siteLightswitches.length; i++) {
        const $input = this.$siteLightswitches.eq(i).data('lightswitch').$input;
        serializedStatuses +=
          '&' + encodeURIComponent($input.attr('name')) + '=' + $input.val();
      }

      Craft.cp.$primaryForm.data(
        'initialSerializedValue',
        Craft.cp.$primaryForm
          .data('initialSerializedValue')
          .replace(originalSerializedStatus, serializedStatuses)
      );

      if (this.lastSerializedValue) {
        this.lastSerializedValue = this.lastSerializedValue.replace(
          originalSerializedStatus,
          serializedStatuses
        );
      }

      // Are there additional sites that can be added?
      if (this.settings.addlSites && this.settings.addlSites.length) {
        this._createAddlSiteField();
      }

      this.$globalLightswitch.on('change', this._updateSiteStatuses.bind(this));
      this._updateGlobalStatus();
    },

    /**
     * @returns {Array}
     */
    _getOtherSupportedSites: function () {
      return Craft.sites.filter(
        (s) => s.id != this.settings.siteId && this.siteIds.includes(s.id)
      );
    },

    _showField: function ($field) {
      const height = $field.height();
      $field
        .css('overflow', 'hidden')
        .height(0)
        .velocity({height}, 'fast', () => {
          $field.css({
            overflow: '',
            height: '',
          });
        });
    },

    _removeField: function ($field) {
      const height = $field.height();
      $field.css('overflow', 'hidden').velocity({height: 0}, 'fast', () => {
        $field.remove();
      });
    },

    _updateGlobalStatus: function () {
      let allEnabled = true,
        allDisabled = true;
      this.$siteLightswitches.each(function () {
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

    _updateSiteStatuses: function () {
      const enabled = this.$globalLightswitch.data('lightswitch').on;
      this.$siteLightswitches.each(function () {
        if (enabled) {
          $(this).data('lightswitch').turnOn(true);
        } else {
          $(this).data('lightswitch').turnOff(true);
        }
      });
    },

    _createSiteStatusField: function (site, status) {
      const $field = Craft.ui.createLightswitchField({
        id: `enabledForSite-${site.id}`,
        label: Craft.t('app', 'Enabled for {site}', {site: site.name}),
        name: `enabledForSite[${site.id}]`,
        on:
          typeof status != 'undefined'
            ? status
            : this.settings.siteStatuses.hasOwnProperty(site.id)
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
        const $lightswitch = $field
          .find('.lightswitch')
          .on('change', this._updateGlobalStatus.bind(this));
        this.$siteLightswitches = this.$siteLightswitches.add($lightswitch);
      }

      this._showField($field);

      return $field;
    },

    _createAddlSiteField: function () {
      const addlSites = Craft.sites.filter((site) => {
        return (
          !this.siteIds.includes(site.id) &&
          this.settings.addlSites.some((s) => s.siteId == site.id)
        );
      });

      if (!addlSites.length) {
        return;
      }

      const $addlSiteSelectContainer = Craft.ui
        .createSelect({
          options: [
            {label: Craft.t('app', 'Add a site…')},
            ...addlSites.map((s) => {
              return {label: s.name, value: s.id};
            }),
          ],
        })
        .addClass('fullwidth');

      this.$addlSiteField = Craft.ui
        .createField($addlSiteSelectContainer, {})
        .addClass('nested add')
        .appendTo(this.$siteStatusPane);

      const $addlSiteSelect = $addlSiteSelectContainer.find('select');

      $addlSiteSelect.on('change', () => {
        const siteId = parseInt($addlSiteSelect.val());
        const site = Craft.sites.find((s) => s.id === siteId);

        if (!site) {
          return;
        }

        const addlSiteInfo = this.settings.addlSites.find(
          (s) => s.siteId == site.id
        );
        this._createSiteStatusField(site, addlSiteInfo.enabledByDefault);
        this._updateGlobalStatus();

        $addlSiteSelect.val('').find(`option[value="${siteId}"]`).remove();

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

    showStatusHud: function (target) {
      let bodyHtml;

      if (this.errors === null) {
        bodyHtml = `<p>${this._saveSuccessMessage()}</p>`;
      } else {
        bodyHtml = `<p class="error"><strong>${this._saveFailMessage()}</strong></p>`;

        if (this.errors.length) {
          bodyHtml +=
            '<ul class="errors">' +
            this.errors.map((e) => `<li>${Craft.escapeHtml(e)}</li>`).join('') +
            '</ul>';
        }

        if (this.httpError) {
          bodyHtml += `<p class="http-error code">${Craft.escapeHtml(
            this.httpError
          )}</p>`;
        }

        if (this.httpStatus === 400) {
          bodyHtml += `<button class="btn refresh-btn">${Craft.t(
            'app',
            'Refresh'
          )}</button>`;
        }
      }

      const hud = new Garnish.HUD(target, bodyHtml, {
        hudClass: 'hud revision-status-hud',
        onHide: function () {
          hud.destroy();
        },
      });

      hud.$mainContainer.find('.refresh-btn').on('click', () => {
        window.location.reload();
      });
    },

    /**
     * @return {string}
     */
    _saveSuccessMessage: function () {
      return this.settings.isProvisionalDraft ||
        this.settings.isUnpublishedDraft
        ? Craft.t('app', 'Your changes have been stored.')
        : Craft.t('app', 'The draft has been saved.');
    },

    /**
     * @return {string}
     */
    _saveFailMessage: function () {
      return this.settings.isProvisionalDraft ||
        this.settings.isUnpublishedDraft
        ? Craft.t('app', 'Your changes could not be stored.')
        : Craft.t('app', 'The draft could not be saved.');
    },

    spinners: function () {
      return this.preview
        ? this.$spinner.add(this.preview.$spinner)
        : this.$spinner;
    },

    statusIcons: function () {
      return this.preview
        ? this.$statusIcon.add(this.preview.$statusIcon)
        : this.$statusIcon;
    },

    statusMessage: function () {
      return this.preview
        ? this.$statusMessage.add(this.preview.$statusMessage)
        : this.$statusMessage;
    },

    createEditMetaBtn: function () {
      const $btnGroup = $('#context-btngroup');
      this.$editMetaBtn = $('<button/>', {
        type: 'button',
        class: 'btn edit icon',
        title: Craft.t('app', 'Edit draft settings'),
      }).appendTo($btnGroup);
      $btnGroup.find('.btngroup-btn-last').removeClass('btngroup-btn-last');
      this.addListener(this.$editMetaBtn, 'click', 'showMetaHud');
    },

    createPreviewLink: function (target, label) {
      const $a = $('<a/>', {
        href: this.getTokenizedPreviewUrl(target.url, null, false),
        text: label || Craft.t('app', 'View'),
        target: '_blank',
        data: {
          targetUrl: target.url,
          targetLabel: target.label,
        },
      });

      this.addListener($a, 'click', () => {
        setTimeout(() => {
          this.activatePreviewToken();
        }, 1);
      });

      this.previewLinks.push($a);
      return $a;
    },

    updatePreviewLinks: function () {
      this.previewLinks.forEach(($a) => {
        this.updatePreviewLinkHref($a);
        if (this.activatedPreviewToken) {
          this.removeListener($a, 'click');
        }
      });
    },

    updatePreviewLinkHref: function ($a) {
      $a.attr(
        'href',
        this.getTokenizedPreviewUrl($a.data('targetUrl'), null, false)
      );
    },

    activatePreviewToken: function () {
      if (this.settings.isLive) {
        // don't do anything yet, but leave the event in case we need it later
        return;
      }

      this.activatedPreviewToken = true;
      this.updatePreviewLinks();
    },

    createShareMenu: function ($container) {
      $('<button/>', {
        id: 'share-btn',
        type: 'button',
        class: 'btn menubtn',
        text: Craft.t('app', 'View'),
      }).appendTo($container);

      const $menu = $('<div/>', {class: 'menu'}).appendTo($container);
      const $ul = $('<ul/>').appendTo($menu);

      this.settings.previewTargets.forEach((target) => {
        $('<li/>')
          .append(this.createPreviewLink(target, target.label))
          .appendTo($ul);
      });
    },

    getPreviewTokenParams: function () {
      const params = {
        elementType: this.settings.elementType,
        sourceId: this.settings.sourceId,
        siteId: this.settings.siteId,
        revisionId: this.settings.revisionId,
        previewToken: this.settings.previewToken,
      };

      if (this.settings.draftId && !this.settings.isProvisionalDraft) {
        params.draftId = this.settings.draftId;
      }

      return params;
    },

    getPreviewToken: function () {
      return new Promise((resolve, reject) => {
        if (this.activatedPreviewToken) {
          resolve(this.settings.previewToken);
          return;
        }

        if (this.activatingPreviewToken) {
          this.previewTokenQueue.push(resolve);
          return;
        }

        this.activatingPreviewToken = true;

        Craft.sendActionRequest('POST', 'preview/create-token', {
          data: this.getPreviewTokenParams(),
        })
          .then(() => {
            this.activatingPreviewToken = false;
            this.activatePreviewToken();
            resolve(this.settings.previewToken);

            while (this.previewTokenQueue.length) {
              this.previewTokenQueue.shift()(this.settings.previewToken);
            }
          })
          .catch(reject);
      });
    },

    /**
     * @param {string} url
     * @param {string|null} [randoParam]
     * @param {boolean} [asPromise=false]
     * @return Promise|string
     */
    getTokenizedPreviewUrl: function (url, randoParam, asPromise) {
      if (typeof asPromise === 'undefined') {
        asPromise = true;
      }

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
        const previewUrl = Craft.getUrl(url, params);

        if (asPromise) {
          return new Promise((resolve) => {
            resolve(previewUrl);
          });
        }

        return previewUrl;
      }

      if (!this.settings.previewToken) {
        throw 'Missing preview token';
      }

      params[Craft.tokenParam] = this.settings.previewToken;
      const previewUrl = Craft.getUrl(url, params);

      if (this.activatedPreviewToken) {
        if (asPromise) {
          return new Promise((resolve) => {
            resolve(previewUrl);
          });
        }

        return previewUrl;
      }

      if (asPromise) {
        return new Promise((resolve, reject) => {
          this.getPreviewToken()
            .then(() => {
              resolve(previewUrl);
            })
            .catch(reject);
        });
      }

      const createTokenParams = this.getPreviewTokenParams();
      createTokenParams.redirect = encodeURIComponent(previewUrl);
      return Craft.getActionUrl('preview/create-token', createTokenParams);
    },

    getPreview: function () {
      if (!this.preview) {
        this.preview = new Craft.Preview(this);
        if (!this.enableAutosave) {
          this.preview.on('open', () => {
            this.enableAutosave = true;
            this.listenForChanges();
          });
          this.preview.on('close', () => {
            this.enableAutosave = false;
            this.stopListeningForChanges();

            // Hide the status icon if the save was successful
            const $statusIcons = this.statusIcons();
            if ($statusIcons.hasClass('checkmark-icon')) {
              $statusIcons.addClass('hidden');
            }
          });
        }
        this.preview.on('close', () => {
          if (this.scrollY) {
            window.scrollTo(0, this.scrollY);
            this.scrollY = null;
          }
        });
      }
      return this.preview;
    },

    openPreview: function () {
      return new Promise((resolve, reject) => {
        this.openingPreview = true;
        this.ensureIsDraftOrRevision(true)
          .then(() => {
            this.scrollY = window.scrollY;
            this.getPreview().open();
            this.openingPreview = false;
            resolve();
          })
          .catch(reject);
      });
    },

    ensureIsDraftOrRevision: function (onlyIfChanged) {
      return new Promise((resolve, reject) => {
        if (!this.settings.draftId && !this.settings.revisionId) {
          if (
            onlyIfChanged &&
            this.serializeForm(true) ===
              Craft.cp.$primaryForm.data('initialSerializedValue')
          ) {
            resolve();
            return;
          }

          this.createDraft().then(resolve).catch(reject);
        } else {
          resolve();
        }
      });
    },

    serializeForm: function (removeActionParams) {
      let data = Craft.cp.$primaryForm.serialize();

      if (this.isPreviewActive()) {
        // Replace the temp input with the preview form data
        data = data.replace(
          '__PREVIEW_FIELDS__=1',
          this.preview.$editor.serialize()
        );
      }

      if (removeActionParams && !this.settings.isUnpublishedDraft) {
        // Remove action and redirect params
        data = data.replace(/&action=[^&]*/, '');
        data = data.replace(/&redirect=[^&]*/, '');
      }

      return data;
    },

    checkForm: function (force) {
      // If this isn't a draft and there's no active preview, then there's nothing to check
      if (
        this.settings.revisionId ||
        this.pauseLevel > 0 ||
        !this.enableAutosave ||
        !this.settings.saveDraftAction
      ) {
        return;
      }

      clearTimeout(this.timeout);
      this.timeout = null;

      // If we haven't had a chance to fetch the initial data yet, try again in a bit
      if (
        typeof Craft.cp.$primaryForm.data('initialSerializedValue') ===
        'undefined'
      ) {
        this.timeout = setTimeout(this.checkForm.bind(this), 500);
        return;
      }

      // Has anything changed?
      const data = this.serializeForm(true);
      if (
        force ||
        data !==
          (this.lastSerializedValue ||
            Craft.cp.$primaryForm.data('initialSerializedValue'))
      ) {
        const provisional =
          (!this.settings.draftId || this.settings.isProvisionalDraft) &&
          !this.settings.revisionId;
        this.saveDraft(data, provisional).catch((e) => {
          console.warn('Couldn’t save draft:', e);
        });
      }
    },

    isPreviewActive: function () {
      return this.preview && this.preview.isActive;
    },

    createDraft: function () {
      return new Promise((resolve, reject) => {
        this.saveDraft(this.serializeForm(true)).then(resolve).catch(reject);
      });
    },

    /**
     * @param {object} data
     * @returns {Promise<unknown>}
     */
    saveDraft: function (data) {
      return new Promise((resolve, reject) => {
        // Ignore if we're already submitting the main form
        if (this.submittingForm) {
          reject('Form already being submitted.');
          return;
        }

        if (this.saving) {
          this.queue.push(() => {
            this.checkForm();
          });
          return;
        }

        this.lastSerializedValue = data;
        this.saving = true;
        this.errors = null;
        this.httpStatus = null;
        this.httpError = null;
        this.cancelToken = axios.CancelToken.source();
        this.spinners().removeClass('hidden');

        this.statusIcons()
          .velocity('stop')
          .css('opacity', '')
          .removeClass('invisible checkmark-icon alert-icon fade-out')
          .addClass('hidden');

        // Clear previous status message
        this.statusMessage().empty();

        if (this.$saveMetaBtn) {
          this.$saveMetaBtn.addClass('active');
        }

        // Prep the data to be saved, keeping track of the first input name for each delta group
        let modifiedFieldNames = [];
        let preparedData = this.prepareData(
          data,
          !this.settings.isUnpublishedDraft
            ? (deltaName, params) => {
                if (params.length) {
                  modifiedFieldNames.push(
                    decodeURIComponent(params[0].split('=')[0])
                  );
                }
              }
            : undefined
        );

        // Are we saving a provisional draft?
        if (this.settings.isProvisionalDraft || !this.settings.draftId) {
          preparedData += '&provisional=1';
        }

        Craft.sendActionRequest('POST', this.settings.saveDraftAction, {
          cancelToken: this.cancelToken.token,
          headers: {
            'content-type': 'application/x-www-form-urlencoded',
          },
          data: preparedData,
        })
          .then((response) => {
            this._afterSaveRequest();

            if (response.data.errors) {
              this.errors = response.data.errors;
              this._showFailStatus();
              reject(response.data.errors);
            }

            const createdProvisionalDraft = !this.settings.draftId;

            if (createdProvisionalDraft) {
              this.settings.isProvisionalDraft = true;
              this.createdProvisionalDraft = true;
            }

            if (response.data.title) {
              $('#header h1').text(response.data.title);
            }

            if (response.data.docTitle) {
              document.title = response.data.docTitle;
            }

            if (this.settings.isProvisionalDraft) {
              if (createdProvisionalDraft) {
                this.$revisionLabel.append(
                  $('<span/>', {
                    text: ` — ${Craft.t('app', 'Edited')}`,
                  })
                );
              }
            } else {
              this.$revisionLabel.text(response.data.draftName);
              this.settings.draftName = response.data.draftName;
            }

            let revisionMenu = this.$revisionBtn.data('menubtn')
              ? this.$revisionBtn.data('menubtn').menu
              : null;

            // Did we just add a site?
            if (this.newSiteIds) {
              // Do we need to create the revision menu?
              if (!revisionMenu) {
                this.$revisionBtn.removeClass('disabled').addClass('menubtn');
                new Garnish.MenuBtn(this.$revisionBtn);
                revisionMenu = this.$revisionBtn.data('menubtn').menu;
                revisionMenu.$container.removeClass('hidden');
              }
              this.newSiteIds.forEach((siteId) => {
                const $option = revisionMenu.$options.filter(
                  `[data-site-id=${siteId}]`
                );
                const siteSettings = this.settings.addlSites.find(
                  (s) => s.siteId == siteId
                );
                if (
                  !siteSettings ||
                  typeof siteSettings.enabledByDefault === 'undefined' ||
                  siteSettings.enabledByDefault
                ) {
                  $option
                    .find('.status')
                    .removeClass('disabled')
                    .addClass('enabled');
                }
                const $li = $option.parent().removeClass('hidden');
                $li.closest('.site-group').removeClass('hidden');
              });
              revisionMenu.$container
                .find('.revision-hr')
                .removeClass('hidden');
              this.newSiteIds = null;
            }

            if (this.settings.isProvisionalDraft) {
              if (createdProvisionalDraft) {
                // Replace the action
                const $actionInput = $('#action').attr(
                  'value',
                  this.settings.publishDraftAction
                );

                // Update the editor settings
                this.settings.draftId = response.data.draftId;
                this.settings.isLive = false;
                this.previewToken = null;

                if (revisionMenu) {
                  // Add edited description to the “Current” option
                  revisionMenu.$container.find('#current-revision').append(
                    $('<div/>', {
                      class: 'edited-desc',
                    })
                      .append(
                        $('<p/>', {
                          text: Craft.t('app', 'Showing your unsaved changes.'),
                        })
                      )
                      .append(
                        $('<button/>', {
                          id: 'discard-changes',
                          class: 'btn',
                          text: Craft.t('app', 'Discard changes'),
                        })
                      )
                  );
                }

                this.initForProvisionalDraft();
              }
            } else if (revisionMenu) {
              revisionMenu.$options
                .filter('.sel')
                .find('.draft-name')
                .text(response.data.draftName);
              revisionMenu.$options
                .filter('.sel')
                .find('.draft-meta')
                .text(
                  response.data.creator
                    ? Craft.t('app', 'Saved {timestamp} by {creator}', {
                        timestamp: response.data.timestamp,
                        creator: response.data.creator,
                      })
                    : Craft.t('app', 'Saved {timestamp}', {
                        timestamp: response.data.timestamp,
                      })
                );
            }

            // Did the controller send us updated preview targets?
            if (
              response.data.previewTargets &&
              JSON.stringify(response.data.previewTargets) !==
                JSON.stringify(this.settings.previewTargets)
            ) {
              this.updatePreviewTargets(response.data.previewTargets);
            }

            if (createdProvisionalDraft) {
              this.updatePreviewLinks();
              this.trigger('createProvisionalDraft');
            }

            if (this.$nameTextInput) {
              this.checkMetaValues();
            }

            for (const oldId in response.data.duplicatedElements) {
              if (
                oldId != this.settings.sourceId &&
                response.data.duplicatedElements.hasOwnProperty(oldId)
              ) {
                this.duplicatedElements[oldId] =
                  response.data.duplicatedElements[oldId];
              }
            }

            // Add missing field modified indicators
            const selectors = response.data.modifiedAttributes
              .map((attr) => `[name="${attr}"],[name^="${attr}["]`)
              .concat(modifiedFieldNames.map((name) => `[name="${name}"]`));

            const $fields = $(selectors.join(','))
              .parents()
              .filter('.field:not(:has(> .status-badge))');
            for (let i = 0; i < $fields.length; i++) {
              $fields.eq(i).prepend(
                $('<div/>', {
                  class: 'status-badge modified',
                  title: Craft.t('app', 'This field has been modified.'),
                }).append(
                  $('<span/>', {
                    class: 'visually-hidden',
                    html: Craft.t('app', 'This field has been modified.'),
                  })
                )
              );
            }

            this.afterUpdate(data);

            if (this.bc) {
              this.bc.postMessage({
                event: 'saveDraft',
                canonicalId: this.settings.sourceId,
                draftId: this.settings.draftId,
                isProvisionalDraft: this.settings.isProvisionalDraft,
              });
            }

            resolve();
          })
          .catch((e) => {
            this._afterSaveRequest();

            if (!this.ignoreFailedRequest) {
              this.errors = [];
              if (e && e.response) {
                this.httpStatus = e.response.status;
                this.httpError = e.response.data ? e.response.data.error : null;
              }
              this._showFailStatus();
              reject(e);
            }

            this.ignoreFailedRequest = false;
          });
      });
    },

    _afterSaveRequest: function () {
      this.spinners().addClass('hidden');
      if (this.$saveMetaBtn) {
        this.$saveMetaBtn.removeClass('active');
      }
      this.saving = false;
    },

    _showFailStatus: function () {
      this.statusIcons()
        .velocity('stop')
        .css('opacity', '')
        .removeClass('hidden checkmark-icon')
        .addClass('alert-icon');

      this.setStatusMessage(this._saveFailMessage());
    },

    /**
     * @param {string} data
     * @param {function} [deltaCallback] Callback function that should be passed to `Craft.findDeltaData()`
     * @returns {string}
     */
    prepareData: function (data, deltaCallback) {
      // Filter out anything that hasn't changed since the last time the form was submitted
      data = Craft.findDeltaData(
        Craft.cp.$primaryForm.data('initialSerializedValue'),
        data,
        Craft.deltaNames,
        deltaCallback
      );

      // Swap out element IDs with their duplicated ones
      data = this.swapDuplicatedElementIds(data);

      // Add the draft info
      if (this.settings.draftId) {
        data += `&draftId=${this.settings.draftId}`;
        if (this.settings.isProvisionalDraft) {
          data += '&provisional=1';
        }
      }

      if (this.settings.draftName !== null) {
        data += `&draftName=${this.settings.draftName}`;
      }

      return data;
    },

    /**
     * @param {string} data
     * @returns {string}
     */
    swapDuplicatedElementIds: function (data) {
      if (!Craft.fieldsWithoutContent.length) {
        return data;
      }
      const idsRE = Object.keys(this.duplicatedElements).join('|');
      if (idsRE === '') {
        return data;
      }
      const lb = encodeURIComponent('[');
      const rb = encodeURIComponent(']');
      const namePrefixRE = `fields${lb}(?:${Craft.fieldsWithoutContent.join(
        '|'
      )})${rb}`;
      // Keep replacing field IDs until data stops changing
      while (true) {
        if (
          data ===
          (data = data
            // &fields[handle]([...])?[X]
            .replace(
              new RegExp(
                `(&${namePrefixRE}(?:${lb}[^=]+${rb})?${lb})(${idsRE})(${rb})`,
                'g'
              ),
              (m, pre, id, post) => {
                return pre + this.duplicatedElements[id] + post;
              }
            )
            // &fields[handle]([...)?=X
            .replace(
              new RegExp(
                `&(${namePrefixRE}(?:${lb}[^=]+)?)=(${idsRE})\\b`,
                'g'
              ),
              (m, name, id) => {
                // Ignore param names that end in `[enabled]`, `[type]`, etc.
                // (`[sortOrder]` should pass here, which could be set to a specific order index, but *not* `[sortOrder][]`!)
                if (
                  name.match(
                    new RegExp(`${lb}(enabled|sortOrder|type|typeId)${rb}$`)
                  )
                ) {
                  return m;
                }
                return `&${name}=${this.duplicatedElements[id]}`;
              }
            ))
        ) {
          break;
        }
      }
      return data;
    },

    updatePreviewTargets: function (previewTargets) {
      previewTargets.forEach((newTarget) => {
        const currentTarget = this.settings.previewTargets.find(
          (t) => t.label === newTarget.label
        );
        if (currentTarget) {
          currentTarget.url = newTarget.url;
        }

        const $previewLink = this.previewLinks.find(
          ($a) => $a.data('targetLabel') === newTarget.label
        );
        if ($previewLink) {
          $previewLink.data('targetUrl', newTarget.url);
          this.updatePreviewLinkHref($previewLink);
        }
      });
    },

    afterUpdate: function (data) {
      Craft.cp.$primaryForm.data('initialSerializedValue', data);
      Craft.initialDeltaValues = {};

      const $statusIcons = this.statusIcons()
        .velocity('stop')
        .css('opacity', '')
        .removeClass('hidden')
        .addClass('checkmark-icon');

      this.setStatusMessage(this._saveSuccessMessage());

      if (!Craft.autosaveDrafts) {
        // Fade the icon out after a couple seconds, since it won't be accurate as content continues to change
        $statusIcons.velocity('stop').velocity(
          {
            opacity: 0,
          },
          {
            delay: 2000,
            complete: () => {
              $statusIcons.addClass('hidden');
            },
          }
        );
      }

      this.trigger('update');

      this.nextInQueue();
    },

    setStatusMessage: function (message) {
      this.statusIcons().attr('title', message);
      this.statusMessage()
        .empty()
        .append(
          $('<span/>', {
            class: 'visually-hidden',
            text: message,
          })
        );
    },

    nextInQueue: function () {
      if (this.queue.length) {
        this.queue.shift()();
      }
    },

    showMetaHud: function () {
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

    createMetaHud: function () {
      const $hudBody = $('<div/>');

      // Add the Name field
      const $nameField = $(
        '<div class="field"><div class="heading"><label for="draft-name">' +
          Craft.t('app', 'Draft Name') +
          '</label></div></div>'
      ).appendTo($hudBody);
      const $nameInputContainer = $('<div class="input"/>').appendTo(
        $nameField
      );
      this.$nameTextInput = $(
        '<input type="text" class="text fullwidth" id="draft-name"/>'
      )
        .appendTo($nameInputContainer)
        .val(this.settings.draftName);

      // HUD footer
      const $footer = $('<div class="hud-footer flex flex-center"/>').appendTo(
        $hudBody
      );

      $('<div class="flex-grow"></div>').appendTo($footer);
      this.$saveMetaBtn = $('<button/>', {
        type: 'submit',
        class: 'btn submit disabled',
        text: Craft.t('app', 'Save'),
      }).appendTo($footer);

      this.metaHud = new Garnish.HUD(this.$editMetaBtn, $hudBody, {
        onSubmit: this.saveMeta.bind(this),
      });

      this.addListener(this.$nameTextInput, 'input', 'checkMetaValues');

      this.metaHud.on('show', this.onMetaHudShow.bind(this));
      this.metaHud.on('hide', this.onMetaHudHide.bind(this));
      this.metaHud.on('escape', this.onMetaHudEscape.bind(this));
    },

    onMetaHudShow: function () {
      this.$editMetaBtn.addClass('active');
    },

    onMetaHudHide: function () {
      this.$editMetaBtn.removeClass('active');
    },

    onMetaHudEscape: function () {
      this.$nameTextInput.val(this.settings.draftName);
    },

    checkMetaValues: function () {
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

    shakeMetaHud: function () {
      Garnish.shake(this.metaHud.$hud);
    },

    saveMeta: function () {
      if (!this.checkMetaValues()) {
        this.shakeMetaHud();
        return;
      }

      this.settings.draftName = this.$nameTextInput.val();

      this.metaHud.hide();
      this.checkForm(true);
    },

    handleFormSubmit: function (ev) {
      ev.preventDefault();

      // Prevent double form submits
      if (this.submittingForm) {
        return;
      }

      // If this a draft and was this a normal save (either via submit button or save shortcut),
      // then trigger an autosave
      if (
        this.settings.draftId &&
        !this.settings.isUnpublishedDraft &&
        !this.settings.isProvisionalDraft &&
        (typeof ev.autosave === 'undefined' || ev.autosave) &&
        (ev.saveShortcut ||
          (ev.customTrigger &&
            ev.customTrigger.data('action') ===
              this.settings.saveDraftAction)) &&
        this.enableAutosave
      ) {
        this.checkForm(true);
        return;
      }

      // Prevent the normal unload confirmation dialog
      Craft.cp.$confirmUnloadForms = Craft.cp.$confirmUnloadForms.not(
        Craft.cp.$primaryForm
      );

      // Abort the current save request if there is one
      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }

      // Duplicate the form with normalized data
      const data = this.prepareData(this.serializeForm(false));
      const $form = Craft.createForm(data);

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
      isUnpublishedDraft: false,
      enabled: false,
      enabledForSite: false,
      isLive: false,
      isProvisionalDraft: false,
      siteStatuses: null,
      addlSites: [],
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
      previewToken: null,
      siteToken: null,
    },
  }
);
