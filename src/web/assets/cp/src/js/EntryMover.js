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

    this.addListener(this.$cancelBtn, 'activate', 'cancel');
    this.addListener(this.$selectBtn, 'activate', 'selectSection');

    this.modal = new Garnish.Modal($container);
    this.getCompatibleSections();
  },

  async getCompatibleSections() {
    const listHtml = await this.loadSections();
    this.$sectionsList.html(listHtml);
    this.addListener(
      this.$sectionsList.find('.entry-mover-modal--item'),
      'activate',
      (ev) => {
        let $button = $(ev.target);
        this.$sectionsList.find('a').removeClass('sel');
        $button.addClass('sel');
        this.$selectBtn.removeClass('disabled');
      }
    );
  },

  async loadSections() {
    if (this.cancelToken) {
      this.cancelToken.cancel();
    }

    this.$sectionsListContainer.addClass('loading');
    this.cancelToken = axios.CancelToken.source();

    try {
      const response = await Craft.sendActionRequest(
        'POST',
        'entries/move-to-section-modal-data',
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

    Craft.sendActionRequest('POST', 'entries/move-to-section', {
      data: data,
    })
      .then((response) => {
        Craft.cp.displaySuccess(response.data.message);
        this.elementIndex.updateElements();
        this.modal.hide();
      })
      .catch((e) => {
        Craft.cp.displayError(e?.response?.data?.message);
      })
      .finally(() => {
        this.$sectionsListContainer.removeClass('loading');
      });
  },

  cancel: function () {
    this.modal.hide();
  },
});
