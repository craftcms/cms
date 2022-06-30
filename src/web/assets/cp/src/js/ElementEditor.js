/** global: Craft */
/** global: Garnish */
import Garnish from '../../../garnish/src';

/**
 * Element Editor
 */
Craft.ElementEditor = Garnish.Base.extend(
  {
    isFullPage: null,
    $container: null,
    $tabContainer: null,
    $contentContainer: null,
    $revisionBtn: null,
    $revisionLabel: null,
    $spinner: null,
    $expandSiteStatusesBtn: null,
    $statusIcon: null,
    $previewBtn: null,

    $editMetaBtn: null,
    metaHud: null,
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
    listeningForChanges: false,
    pauseLevel: 0,
    timeout: null,
    cancelToken: null,
    ignoreFailedRequest: false,
    queue: null,
    submittingForm: false,

    duplicatedElements: null,
    failed: false,
    httpStatus: null,
    httpError: null,

    openingPreview: false,
    preview: null,
    activatedPreviewToken: false,
    previewTokenQueue: null,
    previewLinks: null,
    scrollY: null,

    get slideout() {
      return this.$container.data('slideout');
    },

    init: function (container, settings) {
      this.$container = $(container);

      if (this.$container.prop('tagName') !== 'FORM') {
        throw 'Element editors may only be used with forms.';
      }

      if (this.$container.data('elementEditor')) {
        console.warn('Double-instantiating an element editor on an element.');
        this.$container.data('elementEditor').destroy();
      }

      this.$container.data('elementEditor', this);
      this.$container.attr('data-element-editor', '');

      this.setSettings(settings, Craft.ElementEditor.defaults);

      this.isFullPage = this.$container[0] === Craft.cp.$primaryForm[0];

      if (this.isFullPage) {
        this.$tabContainer = $('#tabs');
        this.$contentContainer = $('#content');
      } else {
        this.$tabContainer = this.slideout.$tabContainer;
        this.$contentContainer = this.slideout.$content;
      }

      this.queue = this._createQueue();
      this.previewTokenQueue = this._createQueue();

      this.duplicatedElements = {};
      this.enableAutosave = Craft.autosaveDrafts;
      this.previewLinks = [];

      this.siteIds = Object.keys(this.settings.siteStatuses).map((siteId) => {
        return parseInt(siteId);
      });

      this.$revisionBtn = this.$container.find('.context-btn');
      this.$revisionLabel = this.$container.find('.revision-label');
      this.$previewBtn = this.$container.find('.preview-btn');

      const $spinnerContainer = this.isFullPage
        ? $('#page-title')
        : this.slideout.$toolbar;
      this.$spinner = $('<div/>', {
        class: 'revision-spinner spinner hidden',
        title: Craft.t('app', 'Saving'),
      }).appendTo($spinnerContainer);
      this.$statusIcon = $('<div/>', {
        class: `revision-status ${this.isFullPage ? 'invisible' : 'hidden'}`,
      }).appendTo($spinnerContainer);
      this.$statusMessage = $('<div/>', {
        class: 'revision-status-message visually-hidden',
        'aria-live': 'polite',
      }).appendTo($spinnerContainer);

      this.$expandSiteStatusesBtn = $('.expand-status-btn');

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
      this.$container.data('serializer', () => this.serializeForm(true));
      this.$container.data('initialSerializedValue', this.serializeForm(true));

      if (this.isFullPage) {
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
            this.createDraft();
            this.removeListener(this.$container, 'submit.saveShortcut');
          }
        });
      }

      this.listenForChanges();

      this.addListener(this.$statusIcon, 'click', () => {
        this.showStatusHud(this.$statusIcon);
      });

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
            window.location.reload();
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
              window.location.reload();
            }
          }
        });
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
      if (this.isFullPage) {
        return null;
      }

      return this.slideout.namespace;
    },

    namespaceInputName(name) {
      return Craft.namespaceInputName(name, this.namespace);
    },

    namespaceId(id) {
      return Craft.namespaceId(id, this.namespace);
    },

    listenForChanges: function () {
      if (
        this.listeningForChanges ||
        this.pauseLevel > 0 ||
        !this.enableAutosave ||
        !this.settings.canCreateDrafts
      ) {
        return;
      }

      this.listeningForChanges = true;

      // Listen for events on the body when editing a full page form, so we don’t miss events from Live Preview
      const $target = this.isFullPage ? Garnish.$bod : this.$container;

      this.addListener(
        $target,
        'keypress,keyup,change,focus,blur,click,mousedown,mouseup',
        (ev) => {
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
        throw 'Craft.ElementEditor::resume() should only be called after pause().';
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
      let $discardButton = this.$container.find('.discard-changes-btn');

      if (!$discardButton.length) {
        let initialHeight;

        let $noticeContainer;
        if (this.isFullPage) {
          $noticeContainer = Craft.cp.$noticeContainer;
          initialHeight = $noticeContainer.height();
        } else {
          $noticeContainer = this.$container.find('.so-notice');
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
          // Disable pointer events until half a second after the animation is complete
          Craft.cp.$contentContainer.css('pointer-events', 'none');

          $('#content-header').css('min-height', 'auto');
          const height = $noticeContainer.height();
          $noticeContainer
            .css({height: initialHeight, overflow: 'hidden'})
            .velocity({height: height}, 'fast', () => {
              $('#content-header').css('min-height', '');
              $noticeContainer.css({height: '', overflow: ''});

              setTimeout(() => {
                Craft.cp.$contentContainer.css('pointer-events', '');
              }, 300);
            });
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
                      provisional: 1,
                    },
                  });
                } else {
                  Craft.sendActionRequest('POST', 'elements/delete-draft', {
                    data: {
                      elementId: this.settings.canonicalId,
                      draftId: this.settings.draftId,
                      provisional: 1,
                    },
                  })
                    .then((response) => {
                      Craft.cp.displayNotice(response.data.message);
                      this.slideout.close();
                    })
                    .catch(reject);
                }
              })
          );
        }
      });
    },

    initForDraft: function () {
      // Create the edit draft button
      this.createEditMetaBtn();

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
        this.namespaceInputName('enabled') + `=${originalEnabledValue}`;
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
        name: `enabledForSite[${site.id}]`,
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

      const $addlSiteSelectContainer = Craft.ui
        .createSelect({
          options: [
            {label: Craft.t('app', 'Add a site…')},
            ...additionalSites.map((s) => {
              return {label: s.name, value: s.id};
            }),
          ],
        })
        .addClass('fullwidth');

      this.$additionalSiteField = Craft.ui
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

    showSpinner: function () {
      this.spinners().removeClass('hidden');
    },

    hideSpinner: function () {
      this.spinners().addClass('hidden');
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
      const $btnGroup = this.$container.find('.context-btngroup');
      this.$editMetaBtn = $('<button/>', {
        type: 'button',
        class: 'btn edit icon',
        'aria-expanded': 'false',
        'aria-label': Craft.t('app', 'Edit draft settings'),
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
      createTokenParams.redirect = previewUrl;
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
              this.$container.data('initialSerializedValue')
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
      let data = this.$container.serialize();

      if (this.isPreviewActive()) {
        // Replace the temp input with the preview form data
        data = data.replace(
          '__PREVIEW_FIELDS__=1',
          this.preview.$editor.serialize()
        );
      }

      if (removeActionParams && !this.settings.isUnpublishedDraft) {
        // Remove action and redirect params
        const actionName = this.namespaceInputName('action');
        const redirectName = this.namespaceInputName('redirect');
        data = data.replace(
          new RegExp(`&${Craft.escapeRegex(actionName)}=[^&]*`),
          ''
        );
        data = data.replace(
          new RegExp(`&${Craft.escapeRegex(redirectName)}=[^&]*`),
          ''
        );
      }

      return data;
    },

    /**
     * @param {boolean} [force=false]
     * @returns {Promise}
     */
    checkForm: function (force) {
      return this.queue.push(
        () =>
          new Promise((resolve, reject) => {
            // If this isn't a draft and there's no active preview, then there's nothing to check
            if (
              this.settings.revisionId ||
              this.pauseLevel > 0 ||
              !this.enableAutosave ||
              !this.settings.canCreateDrafts
            ) {
              resolve();
              return;
            }

            clearTimeout(this.timeout);
            this.timeout = null;

            // If we haven't had a chance to fetch the initial data yet, try again in a bit
            if (
              typeof this.$container.data('initialSerializedValue') ===
              'undefined'
            ) {
              this.timeout = setTimeout(this.checkForm.bind(this), 500);
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

            this.saveDraft(data)
              .then(resolve)
              .catch((e) => {
                console.warn('Couldn’t save draft:', e);
                reject(e);
              });
          })
      );
    },

    isPreviewActive: function () {
      return this.preview && this.preview.isActive;
    },

    createDraft: function () {
      return this.queue.push(
        () =>
          new Promise((resolve, reject) => {
            this.saveDraft(this.serializeForm(true))
              .then(resolve)
              .catch(reject);
          })
      );
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

        this.lastSerializedValue = data;
        this.failed = false;
        this.httpStatus = null;
        this.httpError = null;
        this.cancelToken = axios.CancelToken.source();

        this.statusIcons()
          .velocity('stop')
          .css('opacity', '')
          .removeClass('hidden invisible checkmark-icon alert-icon fade-out')
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
            : null
        );

        const extraData = {
          [this.namespaceInputName('visibleLayoutElements')]:
            this.settings.visibleLayoutElements,
        };

        // Are we saving a provisional draft?
        if (this.settings.isProvisionalDraft || !this.settings.draftId) {
          extraData[this.namespaceInputName('provisional')] = 1;
        }

        const selectedTabId = this.$contentContainer
          .children('[data-layout-tab]:not(.hidden)')
          .data('id');
        if (selectedTabId) {
          extraData[this.namespaceInputName('selectedTab')] = selectedTabId;
        }

        preparedData += `&${$.param(extraData)}`;

        Craft.sendActionRequest('POST', 'elements/save-draft', {
          cancelToken: this.cancelToken.token,
          headers: this._saveHeaders,
          data: preparedData,
        })
          .then((response) => {
            this._afterSaveDraft();

            const createdProvisionalDraft = !this.settings.draftId;

            if (createdProvisionalDraft) {
              this.settings.isProvisionalDraft = true;
              this.$revisionLabel.append(
                $('<span/>', {
                  text: ` — ${Craft.t('app', 'Edited')}`,
                })
              );
            }

            if (this.isFullPage) {
              if (response.data.title) {
                this.$container.find('.screen-title').text(response.data.title);
              }

              if (response.data.docTitle) {
                document.title = response.data.docTitle;
              }
            }

            if (!this.settings.isProvisionalDraft) {
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
                const siteSettings = this.settings.additionalSites.find(
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
                this.$container
                  .find('input.action-input')
                  .attr('value', 'elements/apply-draft');

                // Update the editor settings
                this.settings.draftId = response.data.draftId;
                this.settings.isLive = false;
                this.previewToken = null;

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
                oldId != this.settings.canonicalId &&
                response.data.duplicatedElements.hasOwnProperty(oldId)
              ) {
                this.duplicatedElements[oldId] =
                  response.data.duplicatedElements[oldId];
              }
            }

            // Add missing field modified indicators
            const selectors = response.data.modifiedAttributes
              .map((attr) => {
                attr = this.namespaceInputName(attr);
                return `[name="${attr}"],[name^="${attr}["]`;
              })
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

            // Keep track of whether anything changed while we were waiting.
            // If not, we can safely update lastSerializedValue after swapping out the fields
            const noChanges = this.serializeForm(true) === data;

            // Update the visible elements
            let $allTabContainers = $();
            const visibleLayoutElements = {};
            let changedElements = false;

            for (let i = 0; i < response.data.missingElements.length; i++) {
              const tabInfo = response.data.missingElements[i];
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

              for (let j = 0; j < tabInfo.elements.length; j++) {
                const elementInfo = tabInfo.elements[j];

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
                    !Garnish.hasAttr(
                      $oldElement,
                      'data-layout-element-placeholder'
                    )
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
            if (this.isFullPage) {
              Craft.cp.updateTabs(response.data.tabs);
            } else {
              this.slideout.updateTabs(response.data.tabs);
            }

            Craft.appendHeadHtml(response.data.headHtml);
            Craft.appendBodyHtml(response.data.bodyHtml);

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

            this.afterUpdate(data);

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
          });
      });
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
     * @param {string} data
     * @param {function|null} [deltaCallback] Callback function that should be passed to `Craft.findDeltaData()`
     * @returns {string}
     */
    prepareData: function (data, deltaCallback) {
      // Filter out anything that hasn't changed since the last time the form was submitted
      data = Craft.findDeltaData(
        this.$container.data('initialSerializedValue'),
        data,
        this.$container.data('delta-names'),
        deltaCallback,
        this.$container.data('initial-delta-values')
      );

      // Swap out element IDs with their duplicated ones
      data = this.swapDuplicatedElementIds(data);

      const extraData = {};

      // Add the draft info
      if (this.settings.draftId) {
        extraData[this.namespaceInputName('draftId')] = this.settings.draftId;

        if (this.settings.isProvisionalDraft) {
          extraData[this.namespaceInputName('provisional')] = 1;
        }
      }

      if (this.settings.draftName !== null) {
        extraData[this.namespaceInputName('draftName')] =
          this.settings.draftName;
      }

      if (!$.isEmptyObject(extraData)) {
        data += `&${$.param(extraData)}`;
      }

      return data;
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

    /**
     * @param {string} data
     * @returns {string}
     */
    swapDuplicatedElementIds: function (data) {
      const idsRE = Object.keys(this.duplicatedElements).join('|');
      if (idsRE === '') {
        return data;
      }
      const lb = encodeURIComponent('[');
      const rb = encodeURIComponent(']');
      const namespacedFields = Craft.escapeRegex(
        this.namespaceInputName('fields')
      );

      // Keep replacing field IDs until data stops changing
      while (true) {
        if (
          data ===
          (data = data
            // &fields[...][X]
            .replace(
              new RegExp(
                `(&${namespacedFields}${lb}[^=]+${rb}${lb})(${idsRE})(${rb})`,
                'g'
              ),
              (m, pre, id, post) => {
                return pre + this.duplicatedElements[id] + post;
              }
            )
            // &fields[...=X
            .replace(
              new RegExp(`&(${namespacedFields}${lb}[^=]+)=(${idsRE})\\b`, 'g'),
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
      this.$container.data('initialSerializedValue', data);
      this.$container.data('initial-delta-values', {});

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
        'aria-disabled': 'true',
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
      this.$editMetaBtn.attr('aria-expanded', 'true');
    },

    onMetaHudHide: function () {
      this.$editMetaBtn.removeClass('active');
      this.$editMetaBtn.attr('aria-expanded', 'false');

      if (Garnish.focusIsInside(this.metaHud.$body)) {
        this.$editMetaBtn.trigger('focus');
      }
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
        this.$saveMetaBtn.removeAttr('aria-disabled');
        return true;
      }

      this.$saveMetaBtn.addClass('disabled');
      this.$saveMetaBtn.attr('aria-disabled', 'true');
      return false;
    },

    shakeMetaHud: function () {
      Garnish.shake(this.metaHud.$hud);
    },

    saveMeta: function () {
      return new Promise((resolve, reject) => {
        if (!this.checkMetaValues()) {
          this.shakeMetaHud();
          reject();
          return;
        }

        this.settings.draftName = this.$nameTextInput.val();
        this.metaHud.hide();
        this.checkForm(true).then(resolve).catch(reject);
      });
    },

    handleSubmit: function (ev) {
      ev.preventDefault();
      ev.stopPropagation();

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
        this.slideout.showSubmitSpinner();
        Craft.sendActionRequest('POST', null, {
          headers: this._saveHeaders,
          data,
        })
          .then((response) => {
            this.slideout.handleSubmitResponse(response);
          })
          .catch((error) => {
            this.slideout.handleSubmitError(error);
          })
          .finally(() => {
            this.submittingForm = false;
            this.slideout.hideSubmitSpinner();
          });
      }
    },
  },
  {
    defaults: {
      additionalSites: [],
      canCreateDrafts: false,
      canEditMultipleSites: false,
      canSaveCanonical: false,
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
      revisionId: null,
      siteId: null,
      siteStatuses: null,
      siteToken: null,
      visibleLayoutElements: {},
    },
  }
);
