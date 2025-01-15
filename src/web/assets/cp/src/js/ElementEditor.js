/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * Element Editor
 */
Craft.ElementEditor = Garnish.Base.extend(
  {
    isFullPage: null,
    $container: null,
    $activityContainer: null,
    $contentContainer: null,
    $sidebar: null,
    $spinner: null,
    $expandSiteStatusesBtn: null,
    $statusIcon: null,
    $previewBtn: null,

    metaModal: null,
    $nameTextInput: null,
    $saveMetaBtn: null,

    $siteStatusPane: null,
    $globalLightswitch: null,
    $siteLightswitches: null,
    $additionalSiteField: null,

    siteIds: null,
    newSiteIds: null,

    enableAutosave: null,
    lastSerializedValue: null,
    /**
     * @type {?Craft.FormObserver}
     */
    formObserver: null,
    cancelToken: null,
    ignoreFailedRequest: false,
    queue: null,
    savingDraft: false,
    saveDraftCallbacks: null,
    submittingForm: false,

    draftElementIds: null,
    draftElementUids: null,
    failed: false,
    httpStatus: null,
    httpError: null,

    preview: null,
    activatedPreviewToken: false,
    previewTokenQueue: null,
    previewLinks: null,

    hiddenTipsStorageKey: 'Craft-' + Craft.systemUid + '.TipField.hiddenTips',

    activityTooltips: null,

    get tipDismissBtn() {
      return this.$container.find('.tip-dismiss-btn');
    },

    get slideout() {
      return this.$container.data('slideout');
    },

    get tabManager() {
      const fn =
        this.settings.getTabManager ??
        (() => (this.isFullPage ? Craft.cp.tabManager : null));
      return fn();
    },

    init: function (container, settings) {
      this.$container = $(container);

      if (this.$container.data('elementEditor')) {
        console.warn('Double-instantiating an element editor on an element.');
        this.$container.data('elementEditor').destroy();
      }

      this.$container.data('elementEditor', this);
      this.$container.attr('data-element-editor', '');

      this.setSettings(settings, Craft.ElementEditor.defaults);

      this.isFullPage = [Craft.cp.$primaryForm[0], Craft.cp.$main[0]].includes(
        this.$container[0]
      );

      this.$contentContainer =
        this.settings.$contentContainer ??
        (this.isFullPage ? $('#content') : $());
      this.$sidebar =
        this.settings.$sidebar ??
        (this.isFullPage ? $('#details .details') : $());

      this.queue = this._createQueue();
      this.previewTokenQueue = this._createQueue();

      this.draftElementIds = {};
      this.draftElementUids = {};
      this.enableAutosave =
        this.settings.autosaveDrafts ?? Craft.autosaveDrafts;
      this.previewLinks = [];

      if (this.settings.previewTargets?.length) {
        const $actionBtn =
          this.settings.$actionBtn ??
          (this.isFullPage ? $('#action-btn') : $());
        const idPrefix = this.namespaceId('action-view');
        const $viewAction = $actionBtn
          .data('disclosureMenu')
          ?.$container.find(`a[id^="${idPrefix}-"]`);
        if ($viewAction?.length) {
          const href = $viewAction.attr('href');
          $viewAction
            .data('targetUrl', href)
            .attr('href', this.getTokenizedPreviewUrl(href, null, false));
          this.previewLinks.push($viewAction);
        }
      }

      this.siteIds = Object.keys(this.settings.siteStatuses).map((siteId) => {
        return parseInt(siteId);
      });

      this.$previewBtn = this.$container.find('.preview-btn');

      const $spinnerContainer =
        this.settings.$spinnerContainer ??
        (this.isFullPage ? $('#revision-indicators') : $());

      this.$spinner = $('<div/>', {
        class: 'revision-spinner spinner hidden',
        title: Craft.t('app', 'Saving'),
      }).appendTo($spinnerContainer);
      this.$statusIcon = $('<div/>', {
        class: `revision-status ${this.isFullPage ? 'invisible' : 'hidden'}`,
      }).appendTo($spinnerContainer);

      this.$expandSiteStatusesBtn = this.$container.find('.expand-status-btn');

      if (this.settings.canEditMultipleSites) {
        this.addListener(
          this.$expandSiteStatusesBtn,
          'click',
          'expandSiteStatuses'
        );
      }

      if (this.settings.previewTargets.length && this.isFullPage) {
        if (this.settings.enablePreview) {
          this.addListener(this.$previewBtn, 'click', 'openPreview');
        }

        const $previewBtnContainer = this.$container.find(
          '.preview-btn-container'
        );

        if (this.settings.previewTargets.length === 1) {
          const [target] = this.settings.previewTargets;
          this.createPreviewLink(target)
            .addClass('view-btn btn')
            .attr('aria-label', Craft.t('app', 'View'))
            .appendTo($previewBtnContainer);
        } else {
          this.createShareMenu($previewBtnContainer);
        }
      }

      // If the user can't save the element, we're done here
      if (!this.settings.canSave) {
        return;
      }

      if (this.$container.prop('tagName') !== 'FORM') {
        throw 'Element editors may only be used with forms.';
      }

      if (this.isFullPage && Craft.edition !== Craft.Solo) {
        this.$activityContainer = this.$container.find('.activity-container');
        this._checkActivity();
      }

      // Override the serializer to use our own
      this.$container.data('serializer', () => this.serializeForm(true));
      this.$container.data('initialSerializedValue', this.serializeForm(true));

      // Re-record the initial values once the fields have had a chance to initialize
      Garnish.requestAnimationFrame(() => {
        this.$container.data(
          'initialSerializedValue',
          this.serializeForm(true)
        );
      });

      if (!this.slideout) {
        this.addListener(this.$container, 'submit', 'handleSubmit');
      }

      if (this.settings.isProvisionalDraft) {
        this.initForProvisionalDraft();
      } else if (this.settings.draftId && !this.settings.isUnpublishedDraft) {
        this.initForDraft();
      } else if (!this.settings.canSaveCanonical) {
        // Override the save shortcut to create a draft too
        this.addListener(this.$container, 'submit.saveShortcut', (ev) => {
          if (ev.saveShortcut) {
            ev.preventDefault();
            this.saveDraft();
            this.removeListener(this.$container, 'submit.saveShortcut');
          }
        });
      }

      this.listenForChanges();

      this.addListener(this.$statusIcon, 'click', () => {
        this.showStatusHud(this.$statusIcon);
      });

      // handle closing tips
      this.handleDismissibleTips();

      if (this.isFullPage && Craft.messageReceiver) {
        // Listen on Craft.broadcaster to ignore any messages sent by this very page
        Craft.broadcaster.addEventListener('message', (ev) => {
          if (
            (ev.data.event === 'saveDraft' &&
              ev.data.canonicalId === this.settings.canonicalId &&
              (ev.data.draftId === this.settings.draftId ||
                (ev.data.isProvisionalDraft && !this.settings.draftId))) ||
            (ev.data.event === 'saveElement' &&
              ev.data.id === this.settings.canonicalId &&
              !this.settings.draftId)
          ) {
            // Reload unless reloadOnBroadcastSave is disabled (unless the
            // draftId is different, in which case we really need to reload)
            if (
              this.settings.reloadOnBroadcastSave ||
              ev.data.draftId !== this.settings.draftId
            ) {
              Craft.setUrl(
                Craft.getUrl(document.location.href, {
                  scrollY: window.scrollY,
                })
              );
              window.location.reload();
            }
          } else if (
            ev.data.event === 'deleteDraft' &&
            ev.data.canonicalId === this.settings.canonicalId &&
            ev.data.draftId === this.settings.draftId
          ) {
            const url = new URL(window.location.href);
            url.searchParams.delete('draftId');
            if (url.href !== document.location.href) {
              window.location.href = url;
            } else {
              Craft.setUrl(
                Craft.getUrl(document.location.href, {
                  scrollY: window.scrollY,
                })
              );
              window.location.reload();
            }
          }
        });
      }

      this.activityTooltips = {};

      if (this.isFullPage) {
        Craft.ui.setFocusOnErrorSummary(this.$container);
      }
    },

    _createQueue: function () {
      const queue = new Craft.Queue();
      queue.on('beforeRun', () => {
        this.showSpinner();
      });
      queue.on('afterRun', () => {
        this.hideSpinner();
      });
      return queue;
    },

    get namespace() {
      return this.settings.namespace;
    },

    namespaceInputName(name) {
      return Craft.namespaceInputName(name, this.namespace);
    },

    namespaceId(id) {
      return Craft.namespaceId(id, this.namespace);
    },

    get listeningForChanges() {
      return !!this.formObserver;
    },

    /**
     * @deprecated
     */
    get pauseLevel() {
      return this.formObserver?._pauseLevel ?? 0;
    },

    listenForChanges: function () {
      if (this.formObserver) {
        return;
      }

      this.formObserver = new Craft.FormObserver(this.$container, () => {
        this.checkForm();
      });
    },

    stopListeningForChanges: function () {
      if (this.formObserver) {
        this.formObserver.destroy();
        this.formObserver = null;
      }
    },

    pause: function () {
      this.formObserver?.pause();
      return new Promise((resolve) => {
        if (this.savingDraft) {
          // wait until that's done before we give the go-ahead
          this.saveDraftCallbacks.push(() => {
            resolve();
          });
        } else {
          resolve();
        }
      });
    },

    resume: function (checkBeforeListening = true) {
      this.formObserver?.resume();
    },

    initForProvisionalDraft: function () {
      let $discardButton = this.$container.find('.discard-changes-btn');

      if (!$discardButton.length) {
        const $noticeContainer =
          this.settings.$noticeContainer ??
          (this.isFullPage
            ? Craft.cp.$noticeContainer
            : this.$container.find('.so-notice'));
        let initialHeight, scrollTop;

        if (this.isFullPage) {
          initialHeight = $('#content').height();
          scrollTop = Garnish.$win.scrollTop();
        }

        const $notice = $('<div/>', {
          class: 'draft-notice',
        })
          .append(
            $('<div/>', {
              class: 'draft-icon',
              'aria-hidden': 'true',
              'data-icon': 'edit',
            })
          )
          .append(
            $('<p/>', {
              text: Craft.t('app', 'Showing your unsaved changes.'),
            })
          )
          .appendTo($noticeContainer);

        $discardButton = $('<button/>', {
          type: 'button',
          class: 'discard-changes-btn btn',
          text: Craft.t('app', 'Discard'),
        }).appendTo($notice);

        if (this.isFullPage) {
          const heightDiff = $('#content').height() - initialHeight;
          Garnish.$win.scrollTop(scrollTop + heightDiff);

          // If there isn’t enough content to simulate the same scroll position, slide it down instead
          if (Garnish.$win.scrollTop() === scrollTop) {
            // Disable pointer events until half a second after the animation is complete
            Craft.cp.$contentContainer.css('pointer-events', 'none');

            $('#content-header').css('min-height', 'auto');
            const height = $noticeContainer.height();
            $noticeContainer
              .css({height: height - heightDiff, overflow: 'hidden'})
              .velocity({height: height}, 'fast', () => {
                $('#content-header').css('min-height', '');
                $noticeContainer.css({height: '', overflow: ''});

                setTimeout(() => {
                  Craft.cp.$contentContainer.css('pointer-events', '');
                }, 300);
              });
          }
        }
      }

      this.addListener(
        $discardButton,
        'keypress,keyup,change,focus,blur,click,mousedown,mouseup',
        (ev) => {
          ev.stopPropagation();
        }
      );

      this.addListener($discardButton, 'click', () => {
        if (
          confirm(
            Craft.t('app', 'Are you sure you want to discard your changes?')
          )
        ) {
          this.queue.unshift(
            () =>
              new Promise((resolve, reject) => {
                if (this.isFullPage) {
                  Craft.submitForm(this.$container, {
                    action: 'elements/delete-draft',
                    redirect: this.settings.hashedCpEditUrl,
                    params: {
                      draftId: this.settings.draftId,
                      fieldId: this.settings.fieldId,
                      ownerId: this.settings.ownerId,
                      provisional: 1,
                    },
                  });
                } else {
                  Craft.sendActionRequest('POST', 'elements/delete-draft', {
                    data: {
                      elementId: this.settings.canonicalId,
                      draftId: this.settings.draftId,
                      fieldId: this.settings.fieldId,
                      ownerId: this.settings.ownerId,
                      siteId: this.settings.siteId,
                      provisional: 1,
                    },
                  })
                    .then((response) => {
                      Craft.cp.displaySuccess(response.data.message);

                      // Broadcast a saveMessage event, in case any chips/cards should be
                      // updated to stop showing the provisional changes
                      Craft.broadcaster.postMessage({
                        event: 'saveElement',
                        id: this.settings.canonicalId,
                      });

                      this.slideout?.close();
                    })
                    .catch(reject);
                }
              })
          );
        }
      });

      if (!this.isFullPage) {
        this.slideout?.$cancelBtn.text(Craft.t('app', 'Close'));
      }
    },

    initForDraft: function () {
      // Create the edit draft button
      this.createEditMetaAction();

      if (this.settings.canSaveCanonical) {
        Garnish.uiLayerManager.registerShortcut(
          {
            keyCode: Garnish.S_KEY,
            ctrl: true,
            alt: true,
          },
          () => {
            Craft.submitForm(this.$container, {
              action: 'elements/apply-draft',
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

      const $enabledForSiteField = this.$container.find(
        `.enabled-for-site-${this.settings.siteId}-field`
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
          label: Craft.t('app', 'Enabled for all sites'),
          name: this.namespaceInputName('enabled'),
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
        encodeURIComponent(
          this.namespaceInputName(`enabledForSite[${this.settings.siteId}]`)
        ) +
        '=' +
        (this.settings.enabledForSite ? '1' : '');

      this.$siteLightswitches = $enabledForSiteField
        .find('.lightswitch')
        .on('change', this._updateGlobalStatus.bind(this));

      this._getOtherSupportedSites().forEach((s) =>
        this._createSiteStatusField(s)
      );

      let serializedStatuses =
        encodeURIComponent(this.namespaceInputName('enabled')) +
        `=${originalEnabledValue}`;
      for (let i = 0; i < this.$siteLightswitches.length; i++) {
        const $input = this.$siteLightswitches.eq(i).data('lightswitch').$input;
        serializedStatuses +=
          '&' + encodeURIComponent($input.attr('name')) + '=' + $input.val();
      }

      this.$container.data(
        'initialSerializedValue',
        this.$container
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
      if (
        this.settings.additionalSites &&
        this.settings.additionalSites.length &&
        this.isFullPage
      ) {
        this._createAddlSiteField();
      }

      // Focus on first lightswitch
      this.$globalLightswitch.focus();

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
        .css({
          overflow: 'hidden',
          'min-height': 'auto',
        })
        .height(0)
        .velocity({height}, 'fast', () => {
          $field.css({
            overflow: '',
            height: '',
            'min-height': '',
          });
        });
    },

    _removeField: function ($field) {
      $field
        .css({
          overflow: 'hidden',
          'min-height': 'auto',
        })
        .velocity({height: 0}, 'fast', () => {
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
        fieldClass: `enabled-for-site-${site.id}-field`,
        label: site.name,
        name: this.namespaceInputName(`enabledForSite[${site.id}]`),
        on:
          typeof status != 'undefined'
            ? status
            : this.settings.siteStatuses.hasOwnProperty(site.id)
              ? this.settings.siteStatuses[site.id]
              : true,
        disabled: !!this.settings.revisionId,
      });

      if (this.$additionalSiteField) {
        $field.insertBefore(this.$additionalSiteField);
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
      const additionalSites = Craft.sites.filter((site) => {
        return (
          !this.siteIds.includes(site.id) &&
          this.settings.additionalSites.some((s) => s.siteId == site.id)
        );
      });

      if (!additionalSites.length) {
        return;
      }

      const selectLabelId = 'add-site-label';

      const $addlSiteSelectLabel = $('<span/>', {
        text: Craft.t('app', 'Add a site...'),
        class: 'visually-hidden',
        id: selectLabelId,
      });

      const $addlSiteSelectContainer = Craft.ui
        .createSelect({
          options: [
            {label: Craft.t('app', 'Add a site…')},
            ...additionalSites.map((s) => {
              return {label: s.name, value: s.id};
            }),
          ],
          labelledBy: selectLabelId,
        })
        .addClass('fullwidth');

      this.$additionalSiteField = Craft.ui
        .createField($addlSiteSelectContainer, {})
        .addClass('nested add')
        .appendTo(this.$siteStatusPane);

      $addlSiteSelectLabel.prependTo(this.$additionalSiteField);

      const $addlSiteSelect = $addlSiteSelectContainer.find('select');

      $addlSiteSelect.on('change', () => {
        const siteId = parseInt($addlSiteSelect.val());
        const site = Craft.sites.find((s) => s.id === siteId);

        if (!site) {
          return;
        }

        const addlSiteInfo = this.settings.additionalSites.find(
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
          this._removeField(this.$additionalSiteField);
        }
      });

      this._showField(this.$additionalSiteField);
    },

    showStatusHud: function (target) {
      let bodyHtml;

      if (!this.failed) {
        bodyHtml = `<p>${this._saveSuccessMessage()}</p>`;
      } else {
        bodyHtml = `<p class="error"><strong>${this._saveFailMessage()}</strong></p>`;

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
     * @returns {string}
     */
    _saveSuccessMessage: function () {
      return this.settings.isProvisionalDraft ||
        this.settings.isUnpublishedDraft
        ? Craft.t('app', 'Your changes have been stored.')
        : Craft.t('app', 'The draft has been saved.');
    },

    /**
     * @returns {string}
     */
    _saveFailMessage: function () {
      return this.settings.isProvisionalDraft ||
        this.settings.isUnpublishedDraft
        ? Craft.t('app', 'Your changes could not be stored.')
        : Craft.t('app', 'The draft could not be saved.');
    },

    spinners: function () {
      return this.$spinner;
    },

    showSpinner: function () {
      this.spinners().removeClass('hidden');
    },

    hideSpinner: function () {
      this.spinners().addClass('hidden');
    },

    statusIcons: function () {
      return this.$statusIcon;
    },

    createEditMetaAction: function () {
      if (!this.isFullPage) {
        return;
      }

      const menu = $('#action-menu').disclosureMenu().data('disclosureMenu');
      const destructiveGroup = menu.getFirstDestructiveGroup();
      const group = menu.addGroup(null, true, destructiveGroup);
      const button = menu.addItem(
        {
          type: 'button',
          icon: 'edit',
          label: Craft.t('app', 'Edit draft settings'),
        },
        group
      );
      this.addListener(button, 'click', 'showMetaModal');
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
      const $btn = $('<button/>', {
        type: 'button',
        class: 'view-btn btn menubtn',
        text: Craft.t('app', 'View'),
      }).appendTo($container);

      const $menu = $('<div/>', {class: 'menu'}).appendTo($container);
      const $ul = $('<ul/>').appendTo($menu);

      this.settings.previewTargets.forEach((target) => {
        $('<li/>')
          .append(this.createPreviewLink(target, target.label))
          .appendTo($ul);
      });

      new Garnish.MenuBtn($btn);
    },

    getPreviewTokenParams: function () {
      const params = {
        elementType: this.settings.elementType,
        canonicalId: this.settings.canonicalId,
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
      return this.previewTokenQueue.push(() => {
        return new Promise((resolve, reject) => {
          if (this.activatedPreviewToken) {
            resolve(this.settings.previewToken);
            return;
          }

          Craft.sendActionRequest('POST', 'preview/create-token', {
            data: this.getPreviewTokenParams(),
          })
            .then(() => {
              this.activatePreviewToken();
              resolve(this.settings.previewToken);
            })
            .catch(reject);
        });
      });
    },

    /**
     * @param {string} url
     * @param {?string} [previewParam]
     * @param {boolean} [asPromise=true]
     * @returns {(Promise|string)}
     */
    getTokenizedPreviewUrl: function (url, previewParam, asPromise = true) {
      const params = {};

      if (
        this.settings.previewParamValue &&
        (previewParam || !this.settings.isLive)
      ) {
        // Randomize the URL so CDNs don't return cached pages
        params[previewParam || 'x-craft-preview'] =
          this.settings.previewParamValue;
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
      createTokenParams.redirect = previewUrl;
      return Craft.getActionUrl('preview/create-token', createTokenParams);
    },

    getPreview: function () {
      // If we already have a preview instance, but the element has been edited
      // since the last time it was open, discard it
      if (
        this.preview &&
        !this.preview.isVisible &&
        this.preview.elementEditor?.settings.updatedTimestamp !==
          this.settings.updatedTimestamp
      ) {
        this.preview.destroy();
        delete this.preview;
      }

      if (!this.preview) {
        this.preview = new Craft.Preview({
          elementType: this.settings.elementType,
          elementId: this.settings.isProvisionalDraft
            ? this.settings.canonicalId
            : this.settings.elementId,
          draftId: !this.settings.isProvisionalDraft
            ? this.settings.draftId
            : null,
          revisionId: this.settings.revisionId,
          siteId: this.settings.siteId,
          onBeforeLoad: async () => {
            // Autosave any last-minute changes before the preview loads its editor
            await this.checkForm(false, true);
          },
        });
        let updatedTimestamp;
        this.preview.on('open', () => {
          updatedTimestamp = this.settings.updatedTimestamp;
          this.pause();
        });
        this.preview.on('close', async () => {
          if (this.$previewBtn) {
            this.$previewBtn.focus();
          }
          if (this.settings.updatedTimestamp !== updatedTimestamp) {
            await this.refreshContent();
          }
          const tabIndex =
            this.preview.tabManager?.getSelectedTabIndex() ?? null;
          if (tabIndex !== null) {
            this.tabManager?.selectTab(tabIndex);
          }
          this.resume();
        });
        this.preview.on('afterSaveDraft', ({response}) => {
          this._handleSaveDraftResponse(response);
        });
      }
      return this.preview;
    },

    openPreview: async function () {
      if (Garnish.hasAttr(this.$previewBtn, 'aria-disabled')) {
        return;
      }

      this.$previewBtn.attr('aria-disabled', true);

      try {
        const preview = this.getPreview();
        await preview.open();

        const tabIndex = this.tabManager?.getSelectedTabIndex() ?? null;
        if (tabIndex !== null) {
          preview.tabManager?.selectTab(tabIndex);
        }
      } finally {
        this.$previewBtn.removeAttr('aria-disabled');
      }
    },

    ensureIsDraftOrRevision: function (onlyIfChanged) {
      return new Promise((resolve, reject) => {
        if (!this.settings.draftId && !this.settings.revisionId) {
          if (
            onlyIfChanged &&
            this.serializeForm(true) ===
              this.$container.data('initialSerializedValue')
          ) {
            resolve();
            return;
          }

          this.saveDraft().then(resolve).catch(reject);
        } else {
          resolve();
        }
      });
    },

    async setFormValue(name, value) {
      if (this.settings.revisionId) {
        throw 'Unable to set form values on a revision.';
      }

      // Make sure any existing changes have already been dealt with
      // (https://github.com/craftcms/cms/issues/15069)
      await this.checkForm();

      // See if the value is already set
      const params = this.$container.serialize().split('&');
      if (
        params.includes(
          `${encodeURIComponent(name)}=${encodeURIComponent(value)}`
        )
      ) {
        return false;
      }

      $('<input/>', {
        type: 'hidden',
        name,
        value,
      }).prependTo(this.$container);
      if (this.settings.canCreateDrafts) {
        await this.saveDraft();
      }

      return true;
    },

    async markDeltaNameAsModified(name) {
      let names = this.$container.data('modified-delta-names') || [];
      if (!names.includes(name)) {
        names.push(name);
        this.$container.data('modified-delta-names', names);
        await this.saveDraft();
      }
    },

    serializeForm: function (removeActionParams) {
      let data = this.$container.serialize();

      if (removeActionParams && !this.settings.isUnpublishedDraft) {
        // Remove action and redirect params
        const actionName = encodeURIComponent(
          this.namespaceInputName('action')
        );
        const redirectName = encodeURIComponent(
          this.namespaceInputName('redirect')
        );
        data = data.replace(
          new RegExp(`&${Craft.escapeRegex(actionName)}=[^&]*`),
          ''
        );
        data = data.replace(
          new RegExp(`&${Craft.escapeRegex(redirectName)}=[^&]*`),
          ''
        );
      }

      // remove embedded element index names
      data = data.replace(/&elementindex-[^&]*/g, '');

      // Give other things the ability to customize the serialized data
      // (need to be passed via a nested object so changes persist upstream)
      const eventData = {
        serialized: data,
      };
      this.trigger('serializeForm', {
        data: eventData,
      });

      return eventData.serialized;
    },

    /**
     * @param {boolean} [force=false]
     * @param {?boolean} [saveDraft]
     * @returns {Promise}
     */
    checkForm: function (force, saveDraft = null) {
      return this.queue.push(
        () =>
          new Promise((resolve, reject) => {
            // If this is a revision, there's nothing to check
            if (this.settings.revisionId) {
              resolve();
              return;
            }

            // If we're already saving a draft, try again later
            if (this.savingDraft) {
              this.saveDraftCallbacks.push(async () => {
                try {
                  await this.checkForm(force, saveDraft);
                } catch (e) {
                  reject(e);
                  return;
                }
                resolve();
              });
            }

            // If we haven't had a chance to fetch the initial data yet, try again in a bit
            if (
              typeof this.$container.data('initialSerializedValue') ===
              'undefined'
            ) {
              setTimeout(async () => {
                try {
                  await this.checkForm(force, saveDraft);
                } catch (e) {
                  reject(e);
                  return;
                }
                resolve();
              }, 500);
              return;
            }

            // Has anything changed?
            const data = this.serializeForm(true);
            if (
              !force &&
              data ===
                (this.lastSerializedValue ||
                  this.$container.data('initialSerializedValue'))
            ) {
              resolve();
              return;
            }

            if (
              (saveDraft ?? this.enableAutosave) &&
              this.settings.canCreateDrafts
            ) {
              this._saveDraftInternal(data)
                .then(resolve)
                .catch((e) => {
                  console.warn('Couldn’t save draft:', e);
                  reject(e);
                });
            } else {
              this.updateFieldLayout(data)
                .then(resolve)
                .catch((e) => {
                  console.warn('Couldn’t update field layout:', e);
                  reject(e);
                });
            }
          })
      );
    },

    /**
     * @param {Object} [params]
     * @returns {Promise<void>}
     */
    async refreshContent(params) {
      this.settings.visibleLayoutElements = [];
      const data = [this.serializeForm(true)];
      data.push(
        $.param({
          [this.namespaceInputName('applyParams')]: 0,
        })
      );
      if (params && !$.isEmptyObject(params)) {
        data.push($.param(params));
      }
      await this.updateFieldLayout(data.join('&'));
    },

    isPreviewActive: function () {
      return this.preview && this.preview.isActive;
    },

    /**
     * @returns {Promise}
     */
    saveDraft: function () {
      return this.queue.push(
        () =>
          new Promise((resolve, reject) => {
            this._saveDraftInternal(this.serializeForm(true))
              .then(resolve)
              .catch(reject);
          })
      );
    },

    /**
     * @param {Object} data
     * @returns {Promise}
     */
    _saveDraftInternal: function (data) {
      return new Promise((resolve, reject) => {
        // Ignore if we're already submitting the main form
        if (this.submittingForm) {
          reject('Form already being submitted.');
          return;
        }

        // Ignore if we're already saving the draft
        if (this.savingDraft) {
          reject('Draft already being saved.');
        }

        this.lastSerializedValue = data;
        this.savingDraft = true;
        this.saveDraftCallbacks = [];
        this.failed = false;
        this.httpStatus = null;
        this.httpError = null;
        this.cancelToken = axios.CancelToken.source();

        this.statusIcons()
          .velocity('stop')
          .css('opacity', '')
          .removeClass('hidden invisible checkmark-icon alert-icon fade-out')
          .addClass('hidden');

        if (this.$saveMetaBtn) {
          this.$saveMetaBtn.addClass('active');
        }

        // Prep the data to be saved, keeping track of the first input name for each delta group
        const [modifiedDeltaNames] = Craft.findModifiedDeltaNames(
          this.$container.data('initialSerializedValue'),
          data,
          this.$container.data('delta-names'),
          this.$container.data('initial-delta-values'),
          this.$container.data('modified-delta-names'),
          true
        );

        const params = this.prepareData(data, null, true);

        let $modifiedFields = $();
        for (const name of modifiedDeltaNames) {
          const $field = ($modifiedFields = $modifiedFields.add(
            this.$container.find(
              `.field[data-base-input-name="${$.escapeSelector(name)}"]`
            )
          ));
          $modifiedFields = $modifiedFields
            .add($field)
            .add($field.parentsUntil(this.$container, '.field'));
        }

        params.push(
          $.param({
            [this.namespaceInputName('visibleLayoutElements')]:
              this.settings.visibleLayoutElements,
          })
        );

        // Are we saving a provisional draft?
        if (this.settings.isProvisionalDraft || !this.settings.draftId) {
          params.push(`${this.namespaceInputName('provisional')}=1`);
        }

        const selectedTabId = this.$contentContainer
          .children('[data-layout-tab]:not(.hidden)')
          .data('id');
        if (selectedTabId) {
          params.push(
            `${this.namespaceInputName('selectedTab')}=${selectedTabId}`
          );
        }

        Craft.sendActionRequest('POST', 'elements/save-draft', {
          cancelToken: this.cancelToken.token,
          headers: this._saveHeaders,
          data: params.join('&'),
        })
          .then((response) => {
            this._afterSaveDraft();
            this.settings.previewParamValue = response.data.previewParamValue;
            this._afterUpdateFieldLayout(data, selectedTabId, response);
            this._handleSaveDraftResponse(response);

            if ($.isPlainObject(response.data.draftElementUids)) {
              this.draftElementUids = {
                ...this.draftElementUids,
                ...response.data.draftElementUids,
              };
            }

            // Add missing field modified indicators
            const selector = response.data.modifiedAttributes
              .map((attr) => {
                attr = this.namespaceInputName(attr);
                return [`[name="${attr}"]`, `[name^="${attr}["]`];
              })
              .flat()
              .join(',');

            $modifiedFields = $modifiedFields
              .add(
                this.$contentContainer
                  .find(selector)
                  .parentsUntil(this.$container, '.flex-fields > .field')
              )
              .add(this.$sidebar?.find(selector).closest('.field'))
              .not(':has(> .status-badge)');

            for (let i = 0; i < $modifiedFields.length; i++) {
              $modifiedFields.eq(i).prepend(
                $('<div/>', {
                  class: 'status-badge modified',
                  'aria-hidden': 'true',
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
            this.trigger('afterSaveDraft', {response});

            if (Craft.broadcaster) {
              Craft.broadcaster.postMessage({
                pageId: Craft.pageId,
                event: 'saveDraft',
                canonicalId: this.settings.canonicalId,
                draftId: this.settings.draftId,
                isProvisionalDraft: this.settings.isProvisionalDraft,
              });
            }

            resolve();
          })
          .catch((e) => {
            this._afterSaveDraft();

            if (!this.ignoreFailedRequest) {
              this.failed = true;
              if (e && e.response) {
                this.httpStatus = e.response.status;
                this.httpError = e.response.data
                  ? e.response.data.message
                  : null;
              }
              this._showFailStatus();
              reject(e);
            }

            this.ignoreFailedRequest = false;
          })
          .finally(async () => {
            this.savingDraft = false;
            const callbacks = [...this.saveDraftCallbacks];
            this.saveDraftCallbacks = [];

            for (const callback of callbacks) {
              await callback();
            }
          });
      });
    },

    _handleSaveDraftResponse(response) {
      const createdProvisionalDraft = !this.settings.draftId;

      if (createdProvisionalDraft) {
        this.settings.isProvisionalDraft = true;
      }

      if (this.isFullPage) {
        if (response.data.title) {
          this.$container.find('.screen-title').text(response.data.title);
        }

        if (response.data.docTitle) {
          document.title = response.data.docTitle;
        }
      }

      // Did we just add a site?
      if (this.newSiteIds) {
        this.newSiteIds.forEach((siteId) => {
          const siteSettings = this.settings.additionalSites.find(
            (s) => s.siteId == siteId
          );
          const enabled =
            !siteSettings ||
            typeof siteSettings.enabledByDefault === 'undefined' ||
            siteSettings.enabledByDefault;
          Craft.cp.showSiteCrumbMenuItem(siteId);
          Craft.cp.setSiteCrumbMenuItemStatus(
            siteId,
            enabled ? 'enabled' : 'disabled'
          );
        });
        this.newSiteIds = null;
      }

      if (this.settings.isProvisionalDraft) {
        if (createdProvisionalDraft) {
          // Replace the action
          this.$container
            .find('input.action-input')
            .attr('value', 'elements/apply-draft');

          // Update the editor settings
          this.settings.elementId = response.data.elementId;
          this.settings.draftId = response.data.draftId;
          this.settings.isLive = false;
          this.previewToken = null;

          this.initForProvisionalDraft();
        }
      } else {
        this.settings.draftName = response.data.draftName;
        if (this.isFullPage) {
          Craft.cp.updateContext(
            response.data.draftName,
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

      if ($.isPlainObject(response.data.draftElementIds)) {
        this.draftElementIds = {
          ...this.draftElementIds,
          ...response.data.draftElementIds,
        };
      }

      // updated the updatedTimestamp values
      this.settings.updatedTimestamp = response.data.updatedTimestamp;
      this.settings.canonicalUpdatedTimestamp =
        response.data.canonicalUpdatedTimestamp;
    },

    _afterSaveDraft: function () {
      if (this.$saveMetaBtn) {
        this.$saveMetaBtn.removeClass('active');
      }
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
     * @param {Object} data
     * @returns {Promise}
     */
    updateFieldLayout: function (data) {
      return new Promise((resolve, reject) => {
        // Ignore if we're already submitting the main form
        if (this.submittingForm) {
          reject('Form already being submitted.');
          return;
        }

        this.lastSerializedValue = data;
        this.cancelToken = axios.CancelToken.source();

        // Prep the data to be saved, keeping track of the first input name for each delta group
        let preparedData = this.prepareData(data);

        const extraData = {
          [this.namespaceInputName('visibleLayoutElements')]:
            this.settings.visibleLayoutElements,
        };

        // Are we editing a provisional draft?
        if (this.settings.isProvisionalDraft) {
          extraData[this.namespaceInputName('provisional')] = 1;
        }

        const selectedTabId = this.$contentContainer
          .children('[data-layout-tab]:not(.hidden)')
          .data('id');
        if (selectedTabId) {
          extraData[this.namespaceInputName('selectedTab')] = selectedTabId;
        }

        preparedData += `&${$.param(extraData)}`;

        Craft.sendActionRequest('POST', 'elements/update-field-layout', {
          cancelToken: this.cancelToken.token,
          headers: this._saveHeaders,
          data: preparedData,
        })
          .then((response) => {
            this._afterUpdateFieldLayout(data, selectedTabId, response);
            resolve();
          })
          .catch((e) => {
            this._afterSaveDraft();

            if (!this.ignoreFailedRequest) {
              this.failed = true;
              if (e && e.response) {
                this.httpStatus = e.response.status;
                this.httpError = e.response.data
                  ? e.response.data.message
                  : null;
              }
              this._showFailStatus();
              reject(e);
            }

            this.ignoreFailedRequest = false;
          });
      });
    },

    /**
     * @param {string} data
     * @param {findDeltaDataCallback|null} [deltaCallback] Callback function that should be passed to `Craft.findDeltaData()`
     * @param {boolean} [asArray]
     * @returns {string|Array}
     */
    prepareData: function (data, deltaCallback = () => {}, asArray = false) {
      // Filter out anything that hasn't changed since the last time the form was submitted
      const params = Craft.findDeltaData(
        this.$container.data('initialSerializedValue'),
        data,
        this.$container.data('delta-names'),
        deltaCallback,
        this.$container.data('initial-delta-values'),
        this.$container.data('modified-delta-names'),
        true
      );

      // Add the draft info
      if (this.settings.draftId) {
        params.push(
          `${this.namespaceInputName('draftId')}=${this.settings.draftId}`
        );

        if (this.settings.isProvisionalDraft) {
          params.push(`${this.namespaceInputName('provisional')}=1`);
        }
      }

      if (this.settings.draftName !== null) {
        params.push(
          `${this.namespaceInputName('draftName')}=${this.settings.draftName}`
        );
      }

      if (this.settings.fieldId !== null) {
        params.push(
          `${this.namespaceInputName('fieldId')}=${this.settings.fieldId}`
        );
      }

      if (this.settings.ownerId !== null) {
        params.push(
          `${this.namespaceInputName('ownerId')}=${this.settings.ownerId}`
        );
      }

      if (this.settings.saveParams) {
        for (const [name, value] of Object.entries(this.settings.saveParams)) {
          params.push(`${this.namespaceInputName(name)}=${value}`);
        }
      }

      return asArray ? params : params.join('&');
    },

    get _saveHeaders() {
      const headers = {
        'content-type': 'application/x-www-form-urlencoded',
      };

      if (this.namespace) {
        headers['X-Craft-Namespace'] = this.namespace;
      }

      return headers;
    },

    getDraftElementId(elementId) {
      return this.draftElementIds[elementId] || elementId;
    },

    getDraftElementUid(elementUid) {
      return this.draftElementUids[elementUid] || elementUid;
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

    async _afterUpdateFieldLayout(data, selectedTabId, response) {
      // Keep track of whether anything changed while we were waiting.
      // If not, we can safely update lastSerializedValue after swapping out the fields
      const noChanges = this.serializeForm(true) === data;

      // capture the new selected tab ID, in case it just changed
      const newSelectedTabId = this.$contentContainer
        .children('[data-layout-tab]:not(.hidden)')
        .data('id');

      // Update the visible elements
      let $allTabContainers = $();
      const visibleLayoutElements = {};
      let changedElements = false;

      for (const tabInfo of response.data.missingElements) {
        let $tabContainer = this.$contentContainer.children(
          `[data-layout-tab="${tabInfo.uid}"]`
        );

        if (!$tabContainer.length) {
          $tabContainer = $('<div/>', {
            id: this.namespaceId(tabInfo.id),
            class: 'flex-fields',
            'data-id': tabInfo.id,
            'data-layout-tab': tabInfo.uid,
          });
          if (tabInfo.id !== selectedTabId) {
            $tabContainer.addClass('hidden');
          }
          $tabContainer.appendTo(this.$contentContainer);
        }

        $allTabContainers = $allTabContainers.add($tabContainer);

        for (const elementInfo of tabInfo.elements) {
          if (elementInfo.html !== false) {
            if (!visibleLayoutElements[tabInfo.uid]) {
              visibleLayoutElements[tabInfo.uid] = [];
            }
            visibleLayoutElements[tabInfo.uid].push(elementInfo.uid);

            if (typeof elementInfo.html === 'string') {
              const $oldElement = $tabContainer.children(
                `[data-layout-element="${elementInfo.uid}"]`
              );
              const $newElement = $(elementInfo.html);
              if ($oldElement.length) {
                $oldElement.replaceWith($newElement);
              } else {
                $newElement.appendTo($tabContainer);
              }
              Craft.initUiElements($newElement);
              changedElements = true;
            }
          } else {
            const $oldElement = $tabContainer.children(
              `[data-layout-element="${elementInfo.uid}"]`
            );
            if (
              !$oldElement.length ||
              !Garnish.hasAttr($oldElement, 'data-layout-element-placeholder')
            ) {
              const $placeholder = $('<div/>', {
                class: 'hidden',
                'data-layout-element': elementInfo.uid,
                'data-layout-element-placeholder': '',
              });

              if ($oldElement.length) {
                $oldElement.replaceWith($placeholder);
              } else {
                $placeholder.appendTo($tabContainer);
              }

              changedElements = true;
            }
          }
        }
      }

      // Remove any unused tab content containers
      // (`[data-layout-tab=""]` == unconditional containers, so ignore those)
      const $unusedTabContainers = this.$contentContainer
        .children('[data-layout-tab]')
        .not($allTabContainers)
        .not('[data-layout-tab=""]');
      if ($unusedTabContainers.length) {
        $unusedTabContainers.remove();
        changedElements = true;
      }

      // Make the first tab visible if no others are
      if (!$allTabContainers.filter(':not(.hidden)').length) {
        $allTabContainers.first().removeClass('hidden');
      }

      this.settings.visibleLayoutElements = visibleLayoutElements;

      // Update the tabs
      const updateTabs =
        this.settings.updateTabs ??
        (this.isFullPage ? (tabs) => Craft.cp.updateTabs(tabs) : () => {});
      updateTabs(response.data.tabs);

      // was a new tab selected after the request was kicked off?
      if (
        selectedTabId &&
        newSelectedTabId &&
        selectedTabId !== newSelectedTabId
      ) {
        const tabManager = this.tabManager;
        if (tabManager) {
          const $newSelectedTab = tabManager.$tabs.filter(
            `[data-id="${newSelectedTabId}"]`
          );
          if ($newSelectedTab.length) {
            // if the new tab is visible - switch to it
            tabManager.selectTab($newSelectedTab);
          } else {
            // if the new tab is not visible (e.g. hidden by a condition)
            // switch to the first tab
            tabManager.selectTab(tabManager.$tabs.first());
          }
        }
      }

      await Craft.appendHeadHtml(response.data.headHtml);
      await Craft.appendBodyHtml(response.data.bodyHtml);

      // Did any layout elements get added or removed?
      if (changedElements) {
        if (response.data.initialDeltaValues) {
          Object.assign(
            this.$container.data('initial-delta-values'),
            response.data.initialDeltaValues
          );
        }

        if (noChanges) {
          // Update our record of the last serialized value to avoid a pointless resave
          this.lastSerializedValue = this.serializeForm(true);
        }
      }

      // re-grab dismissible tips, re-attach listener, hide on re-load
      this.handleDismissibleTips();
    },

    afterUpdate: function (data) {
      this.$container.data('initialSerializedValue', data);
      this.$container.data('initial-delta-values', {});

      const $statusIcons = this.statusIcons()
        .velocity('stop')
        .css('opacity', '')
        .removeClass('hidden')
        .addClass('checkmark-icon');

      this.setStatusMessage(this._saveSuccessMessage());

      if (!this.settings.autosaveDrafts) {
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

      if (
        (this.settings.isProvisionalDraft ||
          this.settings.isUnpublishedDraft) &&
        Craft.broadcaster
      ) {
        // Broadcast a saveMessage event, in case any chips/cards should be
        // updated to show the provisional changes
        Craft.broadcaster.postMessage({
          event: 'saveElement',
          id: this.settings.canonicalId,
        });
      }
    },

    setStatusMessage: function (message) {
      this.statusIcons().attr('title', message);
      Craft.cp.announce(message);
    },

    showMetaModal: function () {
      if (!this.metaModal) {
        this.createMetaModal();
      } else {
        this.metaModal.show();
      }

      if (!Garnish.isMobileBrowser(true)) {
        setTimeout(() => {
          this.$nameTextInput.focus();
        }, 100);
      }
    },

    createMetaModal: function () {
      const $modal = $('<form/>', {
        class: 'modal fitted',
      });
      const $hudBody = $('<div/>', {
        class: 'body',
      }).appendTo($modal);

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
        $modal
      );

      $('<div class="flex-grow"></div>').appendTo($footer);
      this.$saveMetaBtn = $('<button/>', {
        type: 'submit',
        class: 'btn submit disabled',
        'aria-disabled': 'true',
        text: Craft.t('app', 'Save'),
      }).appendTo($footer);

      this.metaModal = new Garnish.Modal($modal);

      this.addListener(this.$nameTextInput, 'input', 'checkMetaValues');
      this.addListener($modal, 'submit', (ev) => {
        ev.preventDefault();
        this.saveMeta();
      });

      this.metaModal.on('escape', () => {
        this.$nameTextInput.val(this.settings.draftName);
      });
    },

    checkMetaValues: function () {
      if (
        this.$nameTextInput.val() &&
        this.$nameTextInput.val() !== this.settings.draftName
      ) {
        this.$saveMetaBtn.removeClass('disabled');
        this.$saveMetaBtn.removeAttr('aria-disabled');
        return true;
      }

      this.$saveMetaBtn.addClass('disabled');
      this.$saveMetaBtn.attr('aria-disabled', 'true');
      return false;
    },

    shakeMetaModal: function () {
      Garnish.shake(this.metaModal.$container);
    },

    saveMeta: function () {
      return new Promise((resolve, reject) => {
        if (!this.checkMetaValues()) {
          this.shakeMetaModal();
          reject();
          return;
        }

        this.settings.draftName = this.$nameTextInput.val();
        this.metaModal.hide();
        this.checkForm(true).then(resolve).catch(reject);
      });
    },

    handleSubmit: async function (ev) {
      ev.preventDefault();
      ev.stopPropagation();

      // Prevent the beforeunload listener from getting run
      ev.stopImmediatePropagation();

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
            ev.customTrigger.data('action') === 'elements/save-draft')) &&
        this.enableAutosave
      ) {
        this.checkForm(true);
        return;
      }

      this.submittingForm = true;

      // Prevent the normal unload confirmation dialog
      Craft.cp.$confirmUnloadForms = Craft.cp.$confirmUnloadForms.not(
        this.$container
      );

      // Abort the current save request if there is one
      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }

      this.trigger('beforeSubmit');

      // Duplicate the form with normalized data
      const data = this.prepareData(this.serializeForm(false));

      if (this.isFullPage) {
        this.stopListeningForChanges();
        const $form = Craft.createForm(data);
        $form.appendTo(Garnish.$bod);
        $form.submit();
      } else {
        let response;
        try {
          response = await Craft.sendActionRequest('POST', null, {
            headers: this._saveHeaders,
            data,
          });
        } catch (e) {
          this.settings.handleSubmitError(e);
          return;
        } finally {
          this.submittingForm = false;
          this.trigger('afterSubmit');

          // after fully saving the element in a slideout, trigger checkForm but without creating a draft
          // see https://github.com/craftcms/cms/issues/15938
          this.checkForm(false, false);
        }

        if (this.settings.isUnpublishedDraft) {
          // Remove the draft-id data from existing chips/cards
          const $elements = $(
            `div.element[data-id="${this.settings.elementId}"][data-settings]`
          );
          for (let i = 0; i < $elements.length; i++) {
            $elements.eq(i).removeData('draft-id').removeAttr('data-draft-id');
          }
        }

        this.settings.handleSubmitResponse(response);
      }
    },

    handleDismissibleTips: function () {
      this.addListener(this.tipDismissBtn, 'click', (e) => {
        this.hideTip(e);
      });
    },

    getHiddenTipsUids: function () {
      return Craft.getLocalStorage('dismissedTips', []);
    },

    setHiddenTipsUids: function (uids) {
      Craft.setLocalStorage('dismissedTips', uids);
    },

    hideTip: function (ev) {
      const targetElement = ev.target;
      if (targetElement) {
        const $targetParent = $(targetElement).closest('.readable');
        if ($targetParent.length) {
          const layoutElementUid = $targetParent.data('layout-element');
          $targetParent.remove();
          // add info to local storage
          if (typeof Storage !== 'undefined') {
            const hiddenTips = this.getHiddenTipsUids();
            if (!hiddenTips.includes(layoutElementUid)) {
              hiddenTips.push(layoutElementUid);
              this.setHiddenTipsUids(hiddenTips);
            }
          }
        }
      }
    },

    _checkActivity: function () {
      if (!Craft.remainingSessionTime) {
        // Try again after login
        Garnish.once(Craft.AuthManager, 'login', () => {
          this._checkActivity();
        });
        return;
      }

      this.queue.push(
        () =>
          new Promise((resolve, reject) => {
            Craft.sendActionRequest('POST', 'elements/recent-activity', {
              params: {
                dontExtendSession: 1,
              },
              data: {
                elementType: this.settings.elementType,
                elementId: this.settings.canonicalId,
                draftId: this.settings.draftId,
                siteId: this.settings.siteId,
                provisional: this.settings.isProvisionalDraft,
              },
            })
              .then(({data}) => {
                let focusedTooltip = null;
                if (this.activityTooltips) {
                  const tooltips = Object.values(this.activityTooltips);
                  focusedTooltip = tooltips.find(
                    (t) => t.$trigger[0] === document.activeElement
                  );
                }

                this.$activityContainer
                  .html('')
                  .attr('role', 'region')
                  .attr('aria-label', Craft.t('app', 'Recent Activity'));

                if (data.activity.length) {
                  $('<h2/>', {
                    class: 'visually-hidden',
                    text: Craft.t('app', 'Recent Activity'),
                  }).appendTo(this.$activityContainer);
                  const $ul = $('<ul/>').appendTo(this.$activityContainer);
                  for (let i = 0; i < data.activity.length; i++) {
                    const activity = data.activity[i];
                    const $li = $('<li/>').appendTo($ul);
                    const $button = $('<button/>', {
                      type: 'button',
                      class: 'activity-btn',
                      'aria-label': Craft.t('app', '{name} active, more info', {
                        name: activity.userName,
                      }),
                      'aria-expanded': 'false',
                    }).appendTo($li);
                    const $thumb = $(activity.userThumb)
                      .addClass('elementthumb')
                      .css('z-index', data.activity.length - i)
                      .appendTo($button);
                    $thumb.find('img,svg').attr('role', 'presentation');
                    Craft.cp.elementThumbLoader.load($li);
                    $thumb.find('title').remove();

                    if (
                      typeof this.activityTooltips[activity.userId] ===
                      'undefined'
                    ) {
                      this.activityTooltips[activity.userId] =
                        new Craft.Tooltip($button, activity.message);
                    } else {
                      this.activityTooltips[activity.userId].$trigger = $button;
                      this.activityTooltips[activity.userId].message =
                        activity.message;

                      // maintain trigger focus
                      if (
                        this.activityTooltips[activity.userId] ===
                        focusedTooltip
                      ) {
                        this.activityTooltips[activity.userId].$trigger.focus();
                      }
                    }
                  }
                }

                // hide any tooltips that are no longer relevant
                for (let userId of Object.keys(this.activityTooltips)) {
                  if (
                    !data.activity.find((activity) => activity.userId == userId)
                  ) {
                    this.activityTooltips[userId].hide();
                  }
                }

                // if the element has been updated upstream, show a notification about it
                const elementUpdated =
                  this.settings.updatedTimestamp &&
                  this.settings.updatedTimestamp !== data.updatedTimestamp;
                const canonicalUpdated =
                  this.settings.canonicalUpdatedTimestamp &&
                  this.settings.canonicalUpdatedTimestamp !==
                    data.canonicalUpdatedTimestamp;

                if (elementUpdated || canonicalUpdated) {
                  const $reloadBtn = Craft.ui.createButton({
                    label: Craft.t('app', 'Reload'),
                    spinner: true,
                  });

                  Craft.cp.displayNotice(
                    Craft.uppercaseFirst(
                      Craft.t('app', 'This {type} has been updated.', {
                        type:
                          elementUpdated &&
                          this.settings.draftId &&
                          !this.settings.isProvisionalDraft
                            ? Craft.t('app', 'draft')
                            : Craft.elementTypeNames[this.settings.elementType]
                              ? Craft.elementTypeNames[
                                  this.settings.elementType
                                ][2]
                              : Craft.t('app', 'element'),
                      })
                    ),
                    {
                      details: $reloadBtn,
                    }
                  );
                  $reloadBtn.on('click', () => {
                    window.location.reload();
                  });
                }

                this.settings.updatedTimestamp = data.updatedTimestamp;
                this.settings.canonicalUpdatedTimestamp =
                  data.canonicalUpdatedTimestamp;

                this.trigger('checkActivity', data);

                setTimeout(() => {
                  this._checkActivity();
                }, 15000);
                resolve();
              })
              .catch((e) => {
                if (e?.response?.status === 400) {
                  // Try again after login
                  Garnish.once(Craft.AuthManager, 'login', () => {
                    this._checkActivity();
                  });
                  resolve();
                } else {
                  reject(e);
                }
              });
          })
      );
    },

    destroy: function () {
      this.queue.destroy();
      delete this.queue;
      this.formObserver?.destroy();
      delete this.formObserver;
      this.preview?.destroy();
      delete this.preview;
      this.base();
    },
  },
  {
    defaults: {
      namespace: null,
      additionalSites: [],
      canCreateDrafts: false,
      canEditMultipleSites: false,
      canSave: false,
      canSaveCanonical: false,
      elementId: null,
      canonicalId: null,
      draftId: null,
      draftName: null,
      elementType: null,
      enablePreview: false,
      enabled: false,
      enabledForSite: false,
      hashedCpEditUrl: null,
      isLive: false,
      isProvisionalDraft: false,
      isUnpublishedDraft: false,
      previewTargets: [],
      previewToken: null,
      previewParamValue: null,
      revisionId: null,
      fieldId: null,
      ownerId: null,
      siteId: null,
      siteStatuses: [],
      saveParams: null,
      siteToken: null,
      visibleLayoutElements: {},
      updatedTimestamp: null,
      canonicalUpdatedTimestamp: null,
      reloadOnBroadcastSave: true,
      $contentContainer: null,
      $sidebar: null,
      $actionBtn: null,
      $spinnerContainer: null,
      updateTabs: null,
      getTabManager: null,
      handleSubmitResponse: () => {},
      handleSubmitError: () => {},
      autosaveDrafts: null,
    },
  }
);
