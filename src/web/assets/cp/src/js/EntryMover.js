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
        data: {
          'busy-message': 'Moving entries to a different section.',
          'failure-message': 'Failed moving entries to a different section.',
          'retry-message': 'Try again.',
          'success-message': 'Entries moved to a different section.',
        },
      })
      .attr('aria-disabled', 'true')
      .appendTo($primaryButtons);

    this.submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
      changeButtonText: true,
    });

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

  getCompatibleSections() {
    if (this.cancelToken) {
      this.cancelToken.cancel();
    }

    this.$sectionsListContainer.addClass('loading');
    this.submitBtn.busyEvent();
    this.cancelToken = axios.CancelToken.source();

    Craft.sendActionRequest('POST', 'entries/move-to-section-modal-data', {
      data: {
        entryIds: this.entryIds,
        siteId: this.elementIndex.siteId,
        currentSectionUid: this.currentSectionUid,
      },
      cancelToken: this.cancelToken.token,
    })
      .then(({data}) => {
        const listHtml = data?.listHtml;
        if (listHtml) {
          this.$sectionsList.html(listHtml);
          this.submitBtn.successEvent();

          this.addListener(
            this.$sectionsList.find('.entry-mover-modal--item'),
            'activate',
            (ev) => {
              let $button = $(ev.target);

              // reset all the links
              this.$sectionsList
                .find('a')
                .removeClass('sel')
                .attr('aria-pressed', 'false');

              // mark as selected
              $button.addClass('sel').attr('aria-pressed', 'true');

              // enable submit btn
              if (this.$selectBtn.hasClass('disabled')) {
                this.$selectBtn
                  .removeClass('disabled')
                  .attr('aria-disabled', 'false');
              }
            }
          );
        }
      })
      .catch(({response}) => {
        Craft.cp.displayError(response?.data?.message);
        this.submitBtn.failureEvent();
        this.modal.hide();
      })
      .finally(() => {
        this.$sectionsListContainer.removeClass('loading');
        this.cancelToken = null;
      });
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
