import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Floating Label
 */
export default Base.extend(
  {
    $label: null,
    $inputWrapper: null,
    $input: null,

    init: function (label, settings) {
      this.setSettings(settings, Garnish.FloatingLabel.defaults);
      this.$label = $(label);

      // Is this already a floating label?
      if (this.$label.data('floating-label')) {
        console.warn('Double-instantiating a floating label on an element');
        this.$label.data('floating-label').destroy();
      }

      this.$inputWrapper = this.$label.closest('.input');
      this.$input = this.$inputWrapper.find('input').first();

      // If there's already a value on init, activate float
      if (this.$input.val()) {
        this.activateFloat();
      } else {
        const isFocused = this.$input.is(':focus');

        if (isFocused) {
          this.activateFloat();
        }
      }

      this.addListener(this.$input, 'blur', () => {
        if (!this.$input.val()) {
          this.deactivateFloat();
        }
      });

      this.addListener(this.$input, 'focus', () => {
        this.activateFloat();
      });
    },

    activateFloat: function () {
      this.$inputWrapper.addClass(this.settings.activeWrapperClass);
    },

    deactivateFloat: function () {
      this.$inputWrapper.removeClass(this.settings.activeWrapperClass);
    },

    destroy: function () {
      this.$label.removeData('floating-label');
      this.base();
    },
  },
  {
    defaults: {
      activeWrapperClass: 'has-float',
    },
  }
);
