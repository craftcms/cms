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

  init: function (container, settings) {
    this.$container = $(container);
    this.$container.data('linkField', this);

    this.$typeSelect = this.$container.find('select:first');
    this.$labelInput = this.$container
      .children('[data-label-field]:first')
      .find('.text:first');

    if (this.$typeSelect.length) {
      this.$typeSelect.data('fieldtoggle').on('toggleChange', () => {
        this.updateLabel();
      });
    }

    this.addListener(this.$container, 'labelChanged', () => {
      this.updateLabel();
    });
  },

  updateLabel: function (label = null) {
    const $container = this.$container.find(
      '[data-link-type]:not(.hidden):first'
    );
    if (label === null) {
      label = $container.data('linkLabel') || '';
    } else {
      $container.data('linkLabel', label);
    }
    this.$labelInput.prop('placeholder', label);
  },
});
