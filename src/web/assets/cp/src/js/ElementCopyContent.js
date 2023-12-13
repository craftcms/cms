Craft.ElementCopyContent = Garnish.Base.extend(
  {
    $sitesMenuCopyBtn: null,
    $copyAllFromSiteBtn: null,
    $translationBtn: null,
    sitesForCopyFieldAction: null,
    copySitesMenuId: null,

    init: function (settings) {
      // this.$trigger = $(trigger);
      this.setSettings(settings, Craft.ElementCopyContent.defaults);

      this.$copyAllFromSiteBtn = $('.copy-all-from-site');
      this.$translationBtn = $('.t9n-indicator');
      this.copySitesMenuId = `copy-sites-menu-${Math.floor(
        Math.random() * 1000000000
      )}`;

      this.sitesForCopyFieldAction = this._getSitesForCopyFieldAction();

      this.addListener(
        this.$copyAllFromSiteBtn,
        'click',
        'showElementCopyDialogue'
      );

      this.addListener(this.$translationBtn, 'click', 'showFieldCopyDialogue');
    },

    _getSitesForCopyFieldAction: function () {
      return this.settings.supportedSites.map((site) => {
        return {
          label: site.name,
          value: site.id,
        };
      });
    },

    _getCopyBetweenSitesForm: function (fieldHandle = null) {
      let form = '';

      form +=
        '<form class="fitted copyBetweenSites" method="post" accept-charset="UTF-8" data-action="elements/copy-field-values-from-site">' +
        Craft.getCsrfInput();

      if (fieldHandle !== null) {
        form +=
          '<input type="hidden" id="copyFieldHandle" name="copyFieldHandle" value="' +
          $btn.data('handle') +
          '"/>';
      }

      form +=
        '<label>' +
        Craft.t('app', 'From') +
        '</label>' +
        '<input type="hidden" name="copyFromSiteId" id="copyFromSiteId" value="" />' +
        '<button type="submit" class="btn submit">' +
        Craft.t('app', 'Copy') +
        '</button>' +
        '</form>';

      return form;
    },

    showElementCopyDialogue: async function (ev) {
      const {data} = await Craft.sendActionRequest(
        'POST',
        'elements/copy-from-site-modal',
        {
          data: {
            siteId: this.settings.siteId,
            elementId: this.settings.canonicalId,
            draftId: this.settings.draftId,
            provisional: this.settings.isProvisionalDraft,
          },
        }
      );
      const $container = $(data.html);

      new Garnish.Modal($container);
    },

    showFieldCopyDialogue: function (ev) {
      ev.preventDefault();

      $btn = $(ev.target);

      let hudContent =
        `<div class="copy-translation-dialogue">` +
        `<span>` +
        $btn.attr('title') +
        `</span>`;

      // only allow the copy field value of a copyable field
      // only if drafts can be created for this element (both user has permissions and element supports them)
      // only if this element exists on other sites too
      if (
        $btn.hasClass('copyable') &&
        this.sitesForCopyFieldAction.length > 0
      ) {
        hudContent +=
          `<hr />` + this._getCopyBetweenSitesForm($btn.data('handle'));
      }

      hudContent += `</div>`;

      let hud = new Garnish.HUD($btn, hudContent);
      this.sitesDisclosureMenu(hud);

      this.addListener(
        $('.copyBetweenSites'),
        'submit',
        {
          hud: hud,
        },
        'copyValuesFromSite'
      );
    },

    sitesDisclosureMenu: function (hud) {
      let submitBtn = hud.$body.find('.copyBetweenSites button.submit');

      if (
        this.$sitesMenuCopyBtn &&
        this.$sitesMenuCopyBtn.data('trigger') !== undefined
      ) {
        this.$sitesMenuCopyBtn.data('trigger').destroy();
        $(`#${this.copySitesMenuId}`).remove();
        this.$sitesMenuCopyBtn = null;
      }

      this.$sitesMenuCopyBtn = $('<button />', {
        type: 'button',
        class: 'btn copy-sites-menu-btn menubtn',
        text: Craft.t('app', 'Select a site'),
        'aria-controls': this.copySitesMenuId,
        'aria-expanded': false,
      }).insertBefore(submitBtn);

      const $menu = $('<div/>', {
        id: this.copySitesMenuId,
        class: 'menu menu--disclosure',
      }).insertBefore(submitBtn);

      this._buildSitesList(this.sitesForCopyFieldAction, hud).appendTo($menu);

      this.$sitesMenuCopyBtn.disclosureMenu();
    },

    _buildSitesList: function (sites, hud) {
      const $ul = $('<ul/>');

      sites.forEach((site) => {
        const $button = $('<button/>', {
          type: 'button',
          class: 'menu-option',
          text: site.label,
          'data-siteId': site.value,
        }).on('click', (ev) => {
          let $option = $(ev.target);

          hud.$body.find('#copyFromSiteId').val($option.data('siteid'));
          this.$sitesMenuCopyBtn.text($option.text());
          this.$sitesMenuCopyBtn.data('trigger').hide();
        });

        $('<li/>').append($button).appendTo($ul);
      });

      return $ul;
    },

    copyValuesFromSite: async function (ev) {
      ev.preventDefault();
      // hide the HUD
      ev.data.hud.$hud.hide();

      let $form = $(ev.target);
      let params = {
        copyFromSiteId: $form.find('[name="copyFromSiteId"]').val(),
        elementId: this.settings.canonicalId,
        draftId: this.settings.draftId,
        provisional: this.settings.isProvisionalDraft,
        isFullPage: this.settings.isFullPage,
      };

      if ($form.find('[name="copyFieldHandle"]').length > 0) {
        params['fieldHandle'] = $form.find('[name="copyFieldHandle"]').val();
      }

      if (Craft.csrfTokenName) {
        params[Craft.csrfTokenName] = Craft.csrfTokenValue;
      }

      try {
        const response = await Craft.sendActionRequest(
          'POST',
          $form.data('action'),
          {
            data: params,
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
