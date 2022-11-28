import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Multi-Function Button
 */
export default Base.extend(
  {
    $btn: null,
    $liveRegion: null,

    busyMessage: null,
    failureMessage: null,
    successMessage: null,

    init: function (button, settings) {
      this.$btn = $(button);

      this.setSettings(settings, Garnish.MultiFunctionBtn.defaults);

      if (this.$btn.prev().attr('role') === 'status') {
        this.$liveRegion = this.$btn.prev();
      }

      this.busyMessage = this.$btn.data('busy-message');
      this.failureMessage = this.$btn.data('failure-message');
      this.successMessage = this.$btn.data('success-message');
    },

    busyEvent: function () {
      this.$btn.addClass(this.settings.busyClass);

      if (this.busyMessage) {
        this.$liveRegion.html(this.busyMessage);
      }
    },

    failureEvent: function () {
      this.endBusyState();

      if (this.failureMessage) {
        this.$liveRegion.html(this.failureMessage);
      }
    },

    successEvent: function () {
      this.endBusyState();

      if (this.successMessage) {
        this.$liveRegion.html(this.successMessage);
      }
    },

    endBusyState: function () {
      this.$btn.removeClass(this.settings.busyClass);

      // Empty live region so a SR user navigating with virtual cursor doesn't find outdated message
      setTimeout(() => {
        this.$liveRegion.empty();
      }, 2500);
    },
  },
  {
    defaults: {
      busyClass: 'loading',
    },
  }
);
