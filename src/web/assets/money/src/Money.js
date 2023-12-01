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
        this.$field.on('keyup', $.proxy(this, 'onKeyUp'));
        if (this.$clearBtn) {
          this.$clearBtn.on('click', $.proxy(this, 'onClearBtnClick'));
        }

        if (this.$field.val() != '') {
          this.updateInputMask();
        }

        this.$field.data('money-input', this);
      },

      showClearBtn: function () {
        if (!this.$clearBtn) {
          return;
        }

        this.$clearBtn.removeClass('hidden');
      },

      hideClearBtn: function () {
        if (!this.$clearBtn) {
          return;
        }
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

      onKeyUp: function () {
        if (this.$field.val() !== '') {
          this.$field.removeClass('money-placeholder');
        }
      },

      removeInputMask: function () {
        this.$field.inputmask('remove');
      },

      updateInputMask: function () {
        this.showClearBtn();
        const opts = {
          digits: this.settings.decimals,
          placeholder: this.settings.placeholder,
          groupSeparator: this.settings.groupSeparator,
          radixPoint: this.settings.decimalSeparator,
        };

        this.$field.inputmask($.extend(this.settings.maskOptions, opts));

        if (this.$field.val() === '') {
          this.$field.addClass('money-placeholder');
        }
      },
    },
    {
      defaults: {
        decimalSeparator: '.',
        groupSeparator: ',',
        decimals: 2,
        placeholder: '0',
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
