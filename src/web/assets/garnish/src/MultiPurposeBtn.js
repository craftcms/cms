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

    init: function (button, settings) {
      this.$btn = $(button);
      console.log(this.$btn);
    },
  },
  {
    defaults: {
      onShow: $.noop,
      onHide: $.noop,
      onFadeIn: $.noop,
      onFadeOut: $.noop,
    },
  }
);
