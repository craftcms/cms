import './Money.scss';

(function($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Money = Garnish.Base.extend({
    settings: null,

    $field: null,
    $currencyField: null,

    init: function(fieldId, currencyFieldId, settings) {
      this.setSettings(settings, this.defaultSettings);

      this.$field = $('#' + fieldId);
      this.$currencyField = $('#' + currencyFieldId);

      this.updateInputMask();

      this.addListener(this.$currencyField, 'change', 'currencyChangeHandler');
    },

    currencyChangeHandler: function() {
      this.updateInputMask();
    },

    updateInputMask: function() {
      const opts = {
        digits: this.getSubUnits(),
        groupSeparator: this.settings.groupSeparator,
        radixPoint: this.settings.decimalSeparator,
      };

      this.$field.inputmask($.extend(this.settings.maskOptions, opts));
    },

    getCurrencyCode: function() {
      return this.$currencyField.val();
    },

    getSubUnits: function() {
      return window.Craft.CurrencySubUnits[this.getCurrencyCode()];
    },

    defaultSettings: {
      decimalSeparator: '.',
      groupSeparator: ',',
      maskOptions: {
        alias: 'currency',
        autoGroup: false,
        clearMaskOnLostFocus: false,
        digits: 2,
        digitsOptional: false,
        groupSeparator: ',',
        placeholder: '0',
        prefix: '',
        radixPoint: '.',
      },
    },
  });
})(jQuery);
