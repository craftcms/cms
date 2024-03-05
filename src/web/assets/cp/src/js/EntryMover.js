/** global: Craft */
/** global: Garnish */
/**
 * Entry Mover
 */
Craft.EntryMover = Garnish.Base.extend({
  modal: null,
  cancelToken: null,

  entryIds: null,
  currentSectionUid: null,
  elementIndex: null,

  $sectionsListContainer: null,
  $sectionsList: null,
  $cancelBtn: null,
  $selectBtn: null,

  init(entryIds, elementIndex) {
    this.entryIds = entryIds;
    this.elementIndex = elementIndex;

    // get uid of the section from which we're triggering this move;
    let sourceKey = elementIndex.$source.data('key');
    let sourceUid = null;
    if (sourceKey.indexOf('section:') === 0) {
      sourceUid = sourceKey.substring(8);
    }
    this.currentSectionUid = sourceUid;
    this.createModal();
  },

  createModal() {
    const $container = $('<div class="modal entry-mover-modal"/>');
    const $header = $('<div class="header"/>').appendTo($container);
    const $h1 = $('<h1>' + Craft.t('app', 'Move to') + '</h1>').appendTo(
      $header
    );
    const $body = $('<div class="body"/>').appendTo($container);
    const $footer = $('<div/>', {
      class: 'footer',
    }).appendTo($container);

    this.$sectionsListContainer = $(
      '<div class="entry-mover-modal--list"/>'
    ).appendTo($body);
    this.$sectionsList = $('<ul/>').appendTo(this.$sectionsListContainer);

    const $secondaryButtons = $(
      '<div class="buttons left secondary-buttons"/>'
    ).appendTo($footer);
    const $primaryButtons = $('<div class="buttons right"/>').appendTo($footer);
    this.$cancelBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: Craft.t('app', 'Cancel'),
    }).appendTo($primaryButtons);
    this.$selectBtn = Craft.ui
      .createSubmitButton({
        class: 'disabled',
        label: Craft.t('app', 'Move'),
        spinner: true,
      })
      .attr('aria-disabled', 'true')
      .appendTo($primaryButtons);

    const $spinner = $('<div class="spinner spinner-absolute"/>').appendTo(
      this.$sectionsListContainer
    );
    $('<span class="visually-hidden"/>')
      .text(Craft.t('app', 'Loading'))
      .appendTo($spinner);

    this.addListener(this.$sectionsList, 'click', (ev) => {
      let $button;
      if (ev.target.nodeName === 'A') {
        $button = $(ev.target);
      } else {
        $button = $(ev.target).closest('a');
      }

      if ($button.length) {
        this.$sectionsList.find('a').removeClass('sel');
        $button.addClass('sel');
        this.$selectBtn.removeClass('disabled');
      }
    });

    this.addListener(this.$cancelBtn, 'activate', 'cancel');
    this.addListener(this.$selectBtn, 'activate', 'selectSection');

    this.modal = new Garnish.Modal($container);
    this.getCompatibleSections();
  },

  async getCompatibleSections() {
    console.log('getCompatibleSections');
    const listHtml = await this.loadSections();
    this.$sectionsList.html(listHtml);
  },

  async loadSections() {
    console.log('loadSections');
    if (this.cancelToken) {
      this.cancelToken.cancel();
    }

    this.$sectionsListContainer.addClass('loading');
    this.cancelToken = axios.CancelToken.source();

    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'entries/move-to-structure-modal-data',
        {
          data: {
            entryIds: this.entryIds,
            siteId: this.elementIndex.siteId,
            currentSectionUid: this.currentSectionUid,
          },
          cancelToken: this.cancelToken.token,
        }
      );
      const listHtml = response.data.listHtml;
      return listHtml;
    } finally {
      this.$sectionsListContainer.removeClass('loading');
      this.cancelToken = null;
    }
  },

  selectSection() {
    let $button = this.$sectionsList.find('.sel');
    this.$sectionsListContainer.addClass('loading');
    const sectionUid = $button.data('uid');

    let data = {
      sectionUid: sectionUid,
      entryIds: this.entryIds,
    };

    Craft.sendActionRequest('POST', 'entries/move-to-structure', {
      data: data,
      //cancelToken: this.cancelToken.token,
    })
      .then((response) => {
        Craft.cp.displaySuccess(response.data.message);
        this.elementIndex.updateElements();
        this.$sectionsListContainer.removeClass('loading');
        this.modal.hide();
      })
      .catch(({e}) => {
        Craft.cp.displayError(e?.response?.data?.error);
      })
      .finally(() => {
        this.$sectionsListContainer.removeClass('loading');
      });
  },

  cancel: function () {
    this.modal.hide();
  },
});
