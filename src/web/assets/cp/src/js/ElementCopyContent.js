Craft.ElementCopyContent = Garnish.Base.extend(
  {
    $sitesMenuCopyBtn: null,
    $copyAllFromSiteBtn: null,
    $translationBtn: null,
    tooltips: {},
    copySitesMenuId: null,
    hud: null,

    init: function (settings) {
      // this.$trigger = $(trigger);
      this.setSettings(settings, Craft.ElementCopyContent.defaults);

      this.$copyAllFromSiteBtn = $('.copy-all-from-site');
      this.$translationBtn = $('.t9n-indicator');
      this.copySitesMenuId = `copy-sites-menu-${Math.floor(
        Math.random() * 1000000000
      )}`;

      this.addListener(
        this.$copyAllFromSiteBtn,
        'click',
        'showElementCopyDialogue'
      );

      this.addListener(this.$translationBtn, 'click', 'showFieldCopyDialogue');
      this.addListener(this.$translationBtn, 'mouseover', 'fetchCopyTooltip');
    },

    fetchCopyTooltip: async function (ev) {
      const $btn = $(ev.target);
      const handle = $btn.data('handle');

      if (!Object.hasOwn(this.tooltips, handle)) {
        this.tooltips[handle] = await this._getCopyBetweenSitesForm(handle);
      }

      return this.tooltips[handle];
    },

    /**
     * Fetch copy element markup
     *
     * @param {string} viewMode View mode of the markup. Can be 'modal' or 'tooltip'
     * @returns {Promise<string>} Markup for modal
     * @private
     */
    _fetchMarkup: async function (viewMode = 'modal', copyFieldHandle = null) {
      const {data} = await Craft.sendActionRequest(
        'POST',
        'elements/copy-from-site-form',
        {
          data: {
            [Craft.csrfTokenName]: Craft.csrfTokenValue,
            viewMode,
            copyFieldHandle,
            siteId: this.settings.siteId,
            elementId: this.settings.canonicalId,
            draftId: this.settings.draftId,
            provisional: this.settings.isProvisionalDraft,
          },
        }
      );

      return data.html;
    },

    _getCopyBetweenSitesForm: async function (copyFieldHandle = null) {
      const html = await this._fetchMarkup('tooltip', copyFieldHandle);
      const $form = $('<form/>', {
        method: 'POST',
        class: 'fitted copy flex flex-nowrap flex-end tooltip-copy-content',
      });

      // Put server HTML into our form
      $form.append(html);

      // Add submit button
      Craft.ui
        .createSubmitButton({
          spinner: true,
          label: Craft.t('app', 'Copy'),
        })
        .appendTo($form);

      return $form;
    },

    showElementCopyDialogue: async function (ev) {
      const html = await this._fetchMarkup('modal');
      const $container = $(html);
      const $cancelBtn = $container.find('[data-cancel]');

      const modal = new Garnish.Modal($container);
      this.addListener($cancelBtn, 'activate', () => modal.hide());
    },

    showFieldCopyDialogue: async function (ev) {
      ev.preventDefault();

      const $btn = $(ev.target);
      const handle = $btn.data('handle');

      const $hudContent = $('<div/>', {
        class: 'copy-translation-dialogue',
      });
      $hudContent.append('<span/>', $btn.attr('title'));

      // only allow the copy field value of a copyable field
      // only if drafts can be created for this element (both user has permissions and element supports them)
      // only if this element exists on other sites too
      if ($btn.hasClass('copyable')) {
        $hudContent.append('<hr/>');

        // If we haven't loaded this tooltip, do that and show a loading spinner along the way
        if (!Object.hasOwn(this.tooltips, handle)) {
          const $loadSpinner = $('<div/>', {
            class: 'spinner',
            title: Craft.t('app', 'Loading'),
            'aria-label': Craft.t('app', 'Loading'),
          });

          $hudContent.append($loadSpinner);

          this.fetchCopyTooltip(ev).then((html) => {
            $loadSpinner.remove();
            $hudContent.append(html);
          });
        } else {
          $hudContent.append($(this.tooltips[handle]));
        }
      }

      this.hud = new Garnish.HUD($btn, $hudContent);

      this.addListener(
        $hudContent.find('form'),
        'submit',
        'copyValuesFromSite'
      );
    },

    copyValuesFromSite: async function (ev) {
      ev.preventDefault();

      const $submitBtn = $(ev.target).find('[type=submit]');
      $submitBtn.addClass('loading');

      try {
        const response = await Craft.sendActionRequest(
          'POST',
          'elements/copy-field-values-from-site',
          {
            data: new FormData(ev.target),
          }
        );
        const element = response.data.element;

        if (Craft.broadcaster) {
          Craft.broadcaster.postMessage({
            pageId: Craft.pageId,
            event: 'saveDraft',
            canonicalId: element.canonicalId,
            draftId: element.draftId,
            isProvisionalDraft: element.isProvisionalDraft,
          });
        }

        if (typeof this.settings.onSuccess === 'function') {
          this.settings.onSuccess(response);
        }

        if (this.hud) {
          this.hud.hide();
        }

        $submitBtn.removeClass('loading');
        Craft.cp.displayNotice(response.data.message);
      } catch (e) {
        if (typeof this.settings.onError === 'function') {
          this.settings.onError(e);
        }
        Craft.cp.displayError(e.response.data.message);
      }
    },
  },
  {
    defaults: {
      supportedSites: [],
      canonicalId: null,
      draftId: null,
      provisional: false,
      isFullPage: false,
      onSuccess: () => {},
      onError: () => {},
    },
  }
);
