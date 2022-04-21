import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Checkbox select class
 */
export default Base.extend({
  $container: null,
  $all: null,
  $options: null,

  init: function (container) {
    this.$container = $(container);

    // Is this already a checkbox select?
    if (this.$container.data('checkboxSelect')) {
      console.warn('Double-instantiating a checkbox select on an element');
      this.$container.data('checkboxSelect').destroy();
    }

    this.$container.data('checkboxSelect', this);

    var $checkboxes = this.$container.find('input');
    this.$all = $checkboxes.filter('.all:first');
    this.$options = $checkboxes.not(this.$all);

    this.addListener(this.$all, 'change', 'onAllChange');
  },

  onAllChange: function () {
    var isAllChecked = this.$all.prop('checked');

    this.$options.prop({
      checked: isAllChecked,
      disabled: isAllChecked,
    });
  },

  /**
   * Destroy
   */
  destroy: function () {
    this.$container.removeData('checkboxSelect');
    this.base();
  },
});
