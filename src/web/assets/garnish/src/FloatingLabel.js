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

      if (this.$input.val()) {
        this.activateFloat();
      }
    },

    activateFloat: function () {
      this.$inputWrapper.addClass(this.settings.activeWrapperClass);
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
