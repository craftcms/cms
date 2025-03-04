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
  sectionSelect: null,

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
    const headingId =
      'sectionSelectorModalHeading-' + Math.floor(Math.random() * 1000000);
    $('<h1/>', {
      text: Craft.t('app', 'Move to'),
      id: headingId,
    }).appendTo($header);
    const $body = $('<div class="body"/>').appendTo($container);
    const $footer = $('<div/>', {
      class: 'footer',
    }).appendTo($container);

    this.$sectionsListContainer = $(
      '<div class="entry-mover-modal--list"/>'
    ).appendTo($body);
    this.$sectionsList = $('<fieldset/>', {
      class: 'chips',
      'aria-labelledby': headingId,
    }).appendTo(this.$sectionsListContainer);

    $('<div class="buttons left secondary-buttons"/>').appendTo($footer);
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

    this.addListener(this.$cancelBtn, 'activate', 'cancel');
    this.addListener(this.$selectBtn, 'activate', 'selectSection');

    this.modal = new Garnish.Modal($container);
    this.getCompatibleSections();
  },

  getCompatibleSections() {
    if (this.cancelToken) {
      this.cancelToken.cancel();
    }

    this.$selectBtn.addClass('loading');
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

          this.$sectionsList.find('.checkbox').attr('role', 'radio');

          this.sectionSelect = new Garnish.Select(
            this.$sectionsList,
            this.$sectionsList.find('.chip'),
            {
              vertical: true,
              filter: (target) => {
                return !$(target).closest('a[href],.toggle,.btn,[role=button]')
                  .length;
              },
              checkboxMode: true,
              onSelectionChange: () => {
                if (this.sectionSelect.$selectedItems.length) {
                  this.$selectBtn.removeClass('disabled');
                } else {
                  this.$selectBtn.addClass('disabled');
                }
              },
            }
          );
        }
      })
      .catch(({response}) => {
        Craft.cp.displayError(response?.data?.message);
        this.modal.hide();
      })
      .finally(() => {
        this.$selectBtn.removeClass('loading');
        this.cancelToken = null;
      });
  },

  selectSection() {
    if (this.$selectBtn.hasClass('loading')) {
      return;
    }

    this.$selectBtn.addClass('loading');
    Craft.cp.announce(Craft.t('app', 'Loading'));

    let data = {
      sectionId: this.sectionSelect.$selectedItems.data('id'),
      entryIds: this.entryIds,
    };

    Craft.sendActionRequest('POST', 'entries/move-to-section', {
      data: data,
    })
      .then((response) => {
        Craft.cp.displaySuccess(response.data.message);
        Craft.cp.announce(response.data.message);

        this.elementIndex.updateElements();
        this.elementIndex.$elements.attr('tabindex', '-1').focus();
        this.modal.hide();
      })
      .catch((e) => {
        Craft.cp.displayError(e?.response?.data?.message);
        Craft.cp.announce(e?.response?.data?.message);
      })
      .finally(() => {
        this.$selectBtn.removeClass('loading');
      });
  },

  cancel: function () {
    this.modal.hide();
  },
});
