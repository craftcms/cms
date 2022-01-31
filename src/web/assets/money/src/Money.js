import './Money.scss';

(function($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Money = Garnish.Base.extend({
    settings: null,

    $field: null,

    init: function(fieldId, settings) {
      this.setSettings(settings, this.defaultSettings);

      this.$field = $('#' + fieldId);

      this.updateInputMask();
    },

    updateInputMask: function() {
      const opts = {
        digits: this.settings.decimals,
        groupSeparator: this.settings.groupSeparator,
        radixPoint: this.settings.decimalSeparator,
      };

      this.$field.inputmask($.extend(this.settings.maskOptions, opts));
    },

    defaultSettings: {
      decimalSeparator: '.',
      groupSeparator: ',',
      decimals: 2,
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
