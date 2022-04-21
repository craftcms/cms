import './Money.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Money = Garnish.Base.extend(
    {
      settings: null,

      $field: null,
      $clearBtn: null,

      init: function (fieldId, settings) {
        this.setSettings(settings, Craft.Money.defaults);

        this.$field = $('#' + fieldId);
        this.$clearBtn = this.$field
          .closest('.money-container')
          .find('.clear-btn');

        this.$field.on('focus', $.proxy(this, 'onFocus'));
        this.$clearBtn.on('click', $.proxy(this, 'onClearBtnClick'));

        if (this.$field.val() != '') {
          this.updateInputMask();
        }
      },

      showClearBtn: function () {
        this.$clearBtn.removeClass('hidden');
      },

      hideClearBtn: function () {
        this.$clearBtn.addClass('hidden');
      },

      onClearBtnClick: function (ev) {
        ev.preventDefault();
        this.hideClearBtn();
        this.removeInputMask();

        this.$field.removeAttr('placeholder');
        this.$field.val('');
        this.$field.trigger('keyup');
      },

      onFocus: function () {
        this.updateInputMask();
      },

      removeInputMask: function () {
        this.$field.inputmask('remove');
      },

      updateInputMask: function () {
        this.showClearBtn();
        const opts = {
          digits: this.settings.decimals,
          groupSeparator: this.settings.groupSeparator,
          radixPoint: this.settings.decimalSeparator,
        };

        this.$field.inputmask($.extend(this.settings.maskOptions, opts));
      },
    },
    {
      defaults: {
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
    }
  );
})(jQuery);
