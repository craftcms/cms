/** global: Craft */
/** global: Garnish */
/**
 * Link Field
 */
Craft.LinkField = Garnish.Base.extend({
  /** @type {jQuery} */
  $container: null,
  /** @type {jQuery} */
  $typeSelect: null,
  /** @type {jQuery} */
  $labelInput: null,
  /** @type {jQuery} */
  $filenameInput: null,

  init: function (container, settings) {
    this.$container = $(container);
    this.$container.data('linkField', this);

    this.$typeSelect = this.$container.find('select:first');
    this.$labelInput = this.$container
      .children('[data-label-field]:first')
      .find('.text:first');
    this.$filenameInput = this.$container.find(
      '[data-filename-field]:first .text:first'
    );

    if (this.$typeSelect.length) {
      this.$typeSelect
        .fieldtoggle()
        .data('fieldtoggle')
        .on('toggleChange', () => {
          this.updateLabel();
          this.updateFilename();
        });
    }
  },

  getActiveLinkTypeContainer: function () {
    return this.$container.find('[data-link-type]:not(.hidden):first');
  },

  updateLabel: function (label = null) {
    const $container = this.getActiveLinkTypeContainer();
    if (label === null) {
      label = $container.data('linkLabel') || '';
    } else {
      $container.data('linkLabel', label);
    }
    this.$labelInput.prop('placeholder', label);
  },

  updateFilename: function (filename = null) {
    const $container = this.getActiveLinkTypeContainer();
    if (filename === null) {
      filename = $container.data('filename') || '';
    } else {
      $container.data('filename', filename);
    }
    this.$filenameInput.prop('placeholder', filename);
  },
});
