import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Multi-Purpose Button
 */
export default Base.extend(
  {
    $btn: null,
    $liveRegion: null,

    busyMessage: null,
    failureMessage: null,

    init: function (button, settings) {
      this.$btn = $(button);

      this.setSettings(settings, Garnish.MultiPurposeBtn.defaults);

      if (this.$btn.prev().attr('role') === 'status') {
        this.$liveRegion = this.$btn.prev();
      }

      this.busyMessage = this.$btn.dataset.busyMessage;
      this.failureMessage = this.$btn.dataset.failureMessage;
    },

    busyEvent: function () {
      this.$btn.addClass(this.settings.busyClass);

      if (this.busyMessage) {
        this.$liveRegion.html(this.busyMessage);
      }
    },

    failureEvent: function () {
      if (this.failureMessage) {
        this.$liveRegion.html(this.failureMessage);
      }
    },
  },
  {
    defaults: {
      onShow: $.noop,
      onHide: $.noop,
      onFadeIn: $.noop,
      onFadeOut: $.noop,
      busyClass: 'loading',
    },
  }
);
