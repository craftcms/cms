import $ from 'jquery';
import Base from './Base.js';
import BaseDrag from './BaseDrag.js';
import CheckboxSelect from './CheckboxSelect.js';
import ContextMenu from './ContextMenu.js';
import CustomSelect from './CustomSelect.js';
import DisclosureMenu from './DisclosureMenu.js';
import Drag from './Drag.js';
import DragDrop from './DragDrop.js';
import DragMove from './DragMove.js';
import DragSort from './DragSort.js';
import EscManager from './EscManager.js';
import HUD from './HUD.js';
import MenuBtn from './MenuBtn.js';
import MixedInput from './MixedInput.js';
import Modal from './Modal.js';
import NiceText from './NiceText.js';
import Select from './Select.js';
import SelectMenu from './SelectMenu.js';
import UiLayerManager from './UiLayerManager.js';

/**
 * @namespace Garnish
 */

// Bail if Garnish is already defined
if (typeof Garnish !== 'undefined') {
  throw 'Garnish is already defined!';
}

let Garnish = {
  // jQuery objects for common elements
  $win: $(window),
  $doc: $(document),
  $bod: $(document.body),
};

Garnish.rtl = Garnish.$bod.hasClass('rtl');
Garnish.ltr = !Garnish.rtl;

Garnish = $.extend(Garnish, {
  $scrollContainer: Garnish.$win,

  // Key code constants
  BACKSPACE_KEY: 8,
  TAB_KEY: 9,
  CLEAR_KEY: 12,
  RETURN_KEY: 13,
  SHIFT_KEY: 16,
  CTRL_KEY: 17,
  ALT_KEY: 18,
  ESC_KEY: 27,
  SPACE_KEY: 32,
  PAGE_UP_KEY: 33,
  PAGE_DOWN_KEY: 34,
  END_KEY: 35,
  HOME_KEY: 36,
  LEFT_KEY: 37,
  UP_KEY: 38,
  RIGHT_KEY: 39,
  DOWN_KEY: 40,
  DELETE_KEY: 46,
  A_KEY: 65,
  S_KEY: 83,
  CMD_KEY: 91,
  META_KEY: 224,

  // ARIA hidden classes
  JS_ARIA_CLASS: 'garnish-js-aria',
  JS_ARIA_TRUE_CLASS: 'garnish-js-aria-true',
  JS_ARIA_FALSE_CLASS: 'garnish-js-aria-false',

  // Mouse button constants
  PRIMARY_CLICK: 1,
  SECONDARY_CLICK: 3,

  // Axis constants
  X_AXIS: 'x',
  Y_AXIS: 'y',

  FX_DURATION: 100,

  // Node types
  TEXT_NODE: 3,

  /**
   * Logs a message to the browser's console, if the browser has one.
   *
   * @param {string} msg
   * @deprecated
   */
  log: function (msg) {
    if (typeof console !== 'undefined' && typeof console.log === 'function') {
      console.log(msg);
    }
  },

  _isMobileBrowser: null,
  _isMobileOrTabletBrowser: null,

  /**
   * Returns whether this is a mobile browser.
   * Detection script courtesy of http://detectmobilebrowsers.com
   *
   * Last updated: 2014-11-24
   *
   * @param {boolean} detectTablets
   * @return {boolean}
   */
  isMobileBrowser: function (detectTablets) {
    var key = detectTablets ? '_isMobileOrTabletBrowser' : '_isMobileBrowser';

    if (Garnish[key] === null) {
      var a = navigator.userAgent || navigator.vendor || window.opera;
      Garnish[key] =
        new RegExp(
          '(android|bbd+|meego).+mobile|avantgo|bada/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)/|plucker|pocket|psp|series(4|6)0|symbian|treo|up.(browser|link)|vodafone|wap|windows ce|xda|xiino' +
            (detectTablets ? '|android|ipad|playbook|silk' : ''),
          'i'
        ).test(a) ||
        /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
          a.substring(0, 4)
        );
    }

    return Garnish[key];
  },

  /**
   * Returns whether user prefers reduced motion
   *
   * @return {boolean}
   */
  prefersReducedMotion: function () {
    // Grab the prefers reduced media query.
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Check if the media query matches or is not available.
    return !mediaQuery || mediaQuery.matches;
  },

  /**
   * Returns whether a variable is an array.
   *
   * @param {object} val
   * @return {boolean}
   */
  isArray: function (val) {
    return val instanceof Array;
  },

  /**
   * Returns whether a variable is a jQuery collection.
   *
   * @param {object} val
   * @return {boolean}
   */
  isJquery: function (val) {
    return val instanceof $;
  },

  /**
   * Returns whether a variable is a string.
   *
   * @param {object} val
   * @return {boolean}
   */
  isString: function (val) {
    return typeof val === 'string';
  },

  /**
   * Returns whether an element has an attribute.
   *
   * @see http://stackoverflow.com/questions/1318076/jquery-hasattr-checking-to-see-if-there-is-an-attribute-on-an-element/1318091#1318091
   */
  hasAttr: function (elem, attr) {
    var val = $(elem).attr(attr);
    return typeof val !== 'undefined' && val !== false;
  },

  /**
   * Returns whether something is a text node.
   *
   * @param {object} elem
   * @return {boolean}
   */
  isTextNode: function (elem) {
    return elem.nodeType === Garnish.TEXT_NODE;
  },

  /**
   * Returns the offset of an element within the scroll container, whether that's the window or something else
   */
  getOffset: function (elem) {
    this.getOffset._offset = $(elem).offset();

    if (Garnish.$scrollContainer[0] !== Garnish.$win[0]) {
      this.getOffset._offset.top += Garnish.$scrollContainer.scrollTop();
      this.getOffset._offset.left += Garnish.$scrollContainer.scrollLeft();
    }

    return this.getOffset._offset;
  },

  /**
   * Returns the distance between two coordinates.
   *
   * @param {number} x1 The first coordinate's X position.
   * @param {number} y1 The first coordinate's Y position.
   * @param {number} x2 The second coordinate's X position.
   * @param {number} y2 The second coordinate's Y position.
   * @return {number}
   */
  getDist: function (x1, y1, x2, y2) {
    return Math.sqrt(Math.pow(x1 - x2, 2) + Math.pow(y1 - y2, 2));
  },

  /**
   * Returns whether an element is touching an x/y coordinate.
   *
   * @param {number}    x    The coordinate's X position.
   * @param {number}    y    The coordinate's Y position.
   * @param {object} elem Either an actual element or a jQuery collection.
   * @return {boolean}
   */
  hitTest: function (x, y, elem) {
    Garnish.hitTest._$elem = $(elem);
    Garnish.hitTest._offset = Garnish.hitTest._$elem.offset();
    Garnish.hitTest._x1 = Garnish.hitTest._offset.left;
    Garnish.hitTest._y1 = Garnish.hitTest._offset.top;
    Garnish.hitTest._x2 =
      Garnish.hitTest._x1 + Garnish.hitTest._$elem.outerWidth();
    Garnish.hitTest._y2 =
      Garnish.hitTest._y1 + Garnish.hitTest._$elem.outerHeight();

    return (
      x >= Garnish.hitTest._x1 &&
      x < Garnish.hitTest._x2 &&
      y >= Garnish.hitTest._y1 &&
      y < Garnish.hitTest._y2
    );
  },

  /**
   * Returns whether the cursor is touching an element.
   *
   * @param {object} ev   The mouse event object containing pageX and pageY properties.
   * @param {object} elem Either an actual element or a jQuery collection.
   * @return {boolean}
   */
  isCursorOver: function (ev, elem) {
    return Garnish.hitTest(ev.pageX, ev.pageY, elem);
  },

  /**
   * Copies text styles from one element to another.
   *
   * @param {object} source The source element. Can be either an actual element or a jQuery collection.
   * @param {object} target The target element. Can be either an actual element or a jQuery collection.
   */
  copyTextStyles: function (source, target) {
    var $source = $(source),
      $target = $(target);

    $target.css({
      fontFamily: $source.css('fontFamily'),
      fontSize: $source.css('fontSize'),
      fontWeight: $source.css('fontWeight'),
      letterSpacing: $source.css('letterSpacing'),
      lineHeight: $source.css('lineHeight'),
      textAlign: $source.css('textAlign'),
      textIndent: $source.css('textIndent'),
      whiteSpace: $source.css('whiteSpace'),
      wordSpacing: $source.css('wordSpacing'),
      wordWrap: $source.css('wordWrap'),
    });
  },

  /**
   * Adds modal ARIA and role attributes to a container
   *
   * @param {object} container The container element. Can be either an actual element or a jQuery collection.
   */
  addModalAttributes: function (container) {
    const $container = $(container);

    $(container).attr({
      'aria-modal': 'true',
      role: 'dialog',
    });
  },

  /**
   * Hide immediate descendants of the body element from screen readers
   *
   */
  hideModalBackgroundLayers: function () {
    const topmostLayer = Garnish.uiLayerManager.currentLayer.$container.get(0);

    Garnish.$bod.children().each(function () {
      // If element is modal or already has jsAria class, do nothing
      if (Garnish.hasJsAriaClass(this) || this === topmostLayer) return;

      if (!Garnish.isScriptOrStyleElement(this)) {
        Garnish.ariaHide(this);
      }
    });
  },

  /**
   * Un-hide elements based on currently active layers
   *
   */
  resetModalBackgroundLayerVisibility: function () {
    const highestModalLayer = Garnish.uiLayerManager.highestModalLayer;
    const hiddenLayerClasses = [
      Garnish.JS_ARIA_CLASS,
      Garnish.JS_ARIA_TRUE_CLASS,
      Garnish.JS_ARIA_FALSE_CLASS,
    ];

    // If there is another modal, make it accessible to AT
    if (highestModalLayer) {
      highestModalLayer.$container
        .removeClass(hiddenLayerClasses)
        .removeAttr('aria-hidden');
      return;
    }

    // If no more modals in DOM, loop through hidden elements and un-hide them
    const hiddenLayerSelector = hiddenLayerClasses
      .map((name) => '.' + name)
      .join(', ');
    const hiddenElements = $(hiddenLayerSelector);

    $(hiddenElements).each(function () {
      if ($(this).hasClass(Garnish.JS_ARIA_CLASS)) {
        $(this).removeClass(Garnish.JS_ARIA_CLASS);
        $(this).removeAttr('aria-hidden');
      } else if ($(this).hasClass(Garnish.JS_ARIA_FALSE_CLASS)) {
        $(this).removeClass(Garnish.JS_ARIA_FALSE_CLASS);
        $(this).attr('aria-hidden', false);
      } else if ($(this).hasClass(Garnish.JS_ARIA_TRUE_CLASS)) {
        $(this).removeClass(Garnish.JS_ARIA_TRUE_CLASS);
        $(this).attr('aria-hidden', true);
      }
    });
  },

  /**
   * Apply aria-hidden="true" to element and store previous value as class
   *
   * @param {object} element The element. Can be either an actual element or a jQuery collection.
   */
  ariaHide: function (element) {
    const ariaHiddenAttribute = $(element).attr('aria-hidden');

    // Capture initial aria-hidden values in an applied class
    if (!ariaHiddenAttribute) {
      $(element).addClass(Garnish.JS_ARIA_CLASS);
    } else if (ariaHiddenAttribute === 'false') {
      $(element).addClass(Garnish.JS_ARIA_FALSE_CLASS);
    } else if (ariaHiddenAttribute === 'true') {
      $(element).addClass(Garnish.JS_ARIA_TRUE_CLASS);
    }

    $(element).attr('aria-hidden', 'true');
  },

  /**
   * Checks to see if element is <script> or <style>
   *
   * @param {object} element The element. Can be either an actual element or a jQuery collection.
   * @return {boolean}
   */
  isScriptOrStyleElement: function (element) {
    return (
      $(element).prop('tagName') === 'SCRIPT' ||
      $(element).prop('tagName') === 'STYLE'
    );
  },

  /**
   * Has been hidden from screen reader users as a result of modal open
   *
   * @param {object} element The element. Can be either an actual element or a jQuery collection.
   */
  hasJsAriaClass: function (element) {
    return (
      $(element).hasClass(Garnish.JS_ARIA_CLASS) ||
      $(element).hasClass(Garnish.JS_ARIA_FALSE_CLASS) ||
      $(element).hasClass(Garnish.JS_ARIA_TRUE_CLASS)
    );
  },

  /**
   * Checks whether focus is inside a given container
   * @param {Object} container
   */
  focusIsInside: function (container) {
    return $(container).find(':focus').length > 0;
  },

  /**
   * Traps focus within a container, so when focus is tabbed out of it, it’s cycled back into it.
   * @param {Object} container
   */
  trapFocusWithin: function (container) {
    const $container = $(container);
    $container.on('keydown.focus-trap', function (ev) {
      if (ev.keyCode === Garnish.TAB_KEY) {
        const $focusableElements = $container.find(':focusable');
        const index = $focusableElements.index(ev.target);

        // Exit focus trap if no focusable elements are inside
        if ($focusableElements.length === 0) return;

        if (index === 0 && ev.shiftKey) {
          ev.preventDefault();
          ev.stopPropagation();
          $focusableElements.last().trigger('focus');
        } else if (index === $focusableElements.length - 1 && !ev.shiftKey) {
          ev.preventDefault();
          ev.stopPropagation();
          $focusableElements.first().trigger('focus');
        }
      }
    });
  },

  /**
   * Sets focus to the first focusable element within a container, or on the container itself.
   * @param {Object} container The container element. Can be either an actual element or a jQuery collection.
   */
  setFocusWithin: function (container) {
    const $container = $(container);
    const $firstFocusable = $(container).find(
      ':focusable:not(.checkbox):first'
    );

    if ($firstFocusable.length > 0) {
      $firstFocusable.trigger('focus');
    } else {
      $container.attr('tabindex', '-1').trigger('focus');
    }
  },

  getFocusedElement: function () {
    return $(':focus');
  },

  /**
   * Returns the body's real scrollTop, discarding any window banding in Safari.
   *
   * @return {number}
   */
  getBodyScrollTop: function () {
    Garnish.getBodyScrollTop._scrollTop = document.body.scrollTop;

    if (Garnish.getBodyScrollTop._scrollTop < 0) {
      Garnish.getBodyScrollTop._scrollTop = 0;
    } else {
      Garnish.getBodyScrollTop._maxScrollTop =
        Garnish.$bod.outerHeight() - Garnish.$win.height();

      if (
        Garnish.getBodyScrollTop._scrollTop >
        Garnish.getBodyScrollTop._maxScrollTop
      ) {
        Garnish.getBodyScrollTop._scrollTop =
          Garnish.getBodyScrollTop._maxScrollTop;
      }
    }

    return Garnish.getBodyScrollTop._scrollTop;
  },

  requestAnimationFrame: (function () {
    var raf =
      window.requestAnimationFrame ||
      window.mozRequestAnimationFrame ||
      window.webkitRequestAnimationFrame ||
      function (fn) {
        return window.setTimeout(fn, 20);
      };

    return function (fn) {
      return raf(fn);
    };
  })(),

  cancelAnimationFrame: (function () {
    var cancel =
      window.cancelAnimationFrame ||
      window.mozCancelAnimationFrame ||
      window.webkitCancelAnimationFrame ||
      window.clearTimeout;

    return function (id) {
      return cancel(id);
    };
  })(),

  /**
   * Scrolls a container element to an element within it.
   *
   * @param {object} container Either an actual element or a jQuery collection.
   * @param {object} elem      Either an actual element or a jQuery collection.
   */
  scrollContainerToElement: function (container, elem) {
    var $elem;

    if (typeof elem === 'undefined') {
      $elem = $(container);
      $container = $elem.scrollParent();
    } else {
      var $container = $(container);
      $elem = $(elem);
    }

    if (
      $container.prop('nodeName') === 'HTML' ||
      $container[0] === Garnish.$doc[0]
    ) {
      $container = Garnish.$win;
    }

    var scrollTop = $container.scrollTop(),
      elemOffset = $elem.offset().top;

    var elemScrollOffset;

    if ($container[0] === window) {
      elemScrollOffset = elemOffset - scrollTop;
    } else {
      elemScrollOffset = elemOffset - $container.offset().top;
    }

    var targetScrollTop = false;

    // Is the element above the fold?
    if (elemScrollOffset < 0) {
      targetScrollTop = scrollTop + elemScrollOffset - 10;
    } else {
      var elemHeight = $elem.outerHeight(),
        containerHeight =
          $container[0] === window
            ? window.innerHeight
            : $container[0].clientHeight;

      // Is it below the fold?
      if (elemScrollOffset + elemHeight > containerHeight) {
        targetScrollTop =
          scrollTop + (elemScrollOffset - (containerHeight - elemHeight)) + 10;
      }
    }

    if (targetScrollTop !== false) {
      // Velocity only allows you to scroll to an arbitrary position if you're scrolling the main window
      if ($container[0] === window) {
        $('html').velocity('scroll', {
          offset: targetScrollTop + 'px',
          mobileHA: false,
        });
      } else {
        $container.scrollTop(targetScrollTop);
      }
    }
  },

  SHAKE_STEPS: 10,
  SHAKE_STEP_DURATION: 25,

  /**
   * Shakes an element.
   *
   * @param {object}  elem Either an actual element or a jQuery collection.
   * @param {string} prop The property that should be adjusted (default is 'margin-left').
   */
  shake: function (elem, prop) {
    var $elem = $(elem);

    if (!prop) {
      prop = 'margin-left';
    }

    var startingPoint = parseInt($elem.css(prop));
    if (isNaN(startingPoint)) {
      startingPoint = 0;
    }

    for (var i = 0; i <= Garnish.SHAKE_STEPS; i++) {
      (function (i) {
        setTimeout(function () {
          Garnish.shake._properties = {};
          Garnish.shake._properties[prop] =
            startingPoint + (i % 2 ? -1 : 1) * (10 - i);
          $elem.velocity(
            Garnish.shake._properties,
            Garnish.SHAKE_STEP_DURATION
          );
        }, Garnish.SHAKE_STEP_DURATION * i);
      })(i);
    }
  },

  /**
   * Returns the first element in an array or jQuery collection.
   *
   * @param {object} elem
   * @return mixed
   */
  getElement: function (elem) {
    return $.makeArray(elem)[0];
  },

  /**
   * Returns the beginning of an input's name= attribute value with any [bracktes] stripped out.
   *
   * @param {object} elem
   * @return string|null
   */
  getInputBasename: function (elem) {
    var name = $(elem).attr('name');

    if (name) {
      return name.replace(/\[.*/, '');
    } else {
      return null;
    }
  },

  /**
   * Returns an input's value as it would be POSTed.
   * So unchecked checkboxes and radio buttons return null,
   * and multi-selects whose name don't end in "[]" only return the last selection
   *
   * @param {object} $input
   * @return {(string|string[])}
   */
  getInputPostVal: function ($input) {
    var type = $input.attr('type'),
      val = $input.val();

    // Is this an unchecked checkbox or radio button?
    if (type === 'checkbox' || type === 'radio') {
      if ($input.prop('checked')) {
        return val;
      } else {
        return null;
      }
    }

    // Flatten any array values whose input name doesn't end in "[]"
    //  - e.g. a multi-select
    else if (Garnish.isArray(val) && $input.attr('name').slice(-2) !== '[]') {
      if (val.length) {
        return val[val.length - 1];
      } else {
        return null;
      }
    }

    // Just return the value
    else {
      return val;
    }
  },

  /**
   * Returns the inputs within a container
   *
   * @param {object} container The container element. Can be either an actual element or a jQuery collection.
   * @return {object}
   */
  findInputs: function (container) {
    return $(container).find('input,text,textarea,select,button');
  },

  /**
   * Returns the post data within a container.
   *
   * @param {object} container
   * @return {array}
   */
  getPostData: function (container) {
    var postData = {},
      arrayInputCounters = {},
      $inputs = Garnish.findInputs(container);

    var inputName;

    for (var i = 0; i < $inputs.length; i++) {
      var $input = $inputs.eq(i);

      if ($input.prop('disabled')) {
        continue;
      }

      inputName = $input.attr('name');
      if (!inputName) {
        continue;
      }

      var inputVal = Garnish.getInputPostVal($input);
      if (inputVal === null) {
        continue;
      }

      var isArrayInput = inputName.slice(-2) === '[]';

      if (isArrayInput) {
        // Get the cropped input name
        var croppedName = inputName.substring(0, inputName.length - 2);

        // Prep the input counter
        if (typeof arrayInputCounters[croppedName] === 'undefined') {
          arrayInputCounters[croppedName] = 0;
        }
      }

      if (!Garnish.isArray(inputVal)) {
        inputVal = [inputVal];
      }

      for (var j = 0; j < inputVal.length; j++) {
        if (isArrayInput) {
          inputName = croppedName + '[' + arrayInputCounters[croppedName] + ']';
          arrayInputCounters[croppedName]++;
        }

        postData[inputName] = inputVal[j];
      }
    }

    return postData;
  },

  copyInputValues: function (source, target) {
    var $sourceInputs = Garnish.findInputs(source),
      $targetInputs = Garnish.findInputs(target);

    for (var i = 0; i < $sourceInputs.length; i++) {
      if (typeof $targetInputs[i] === 'undefined') {
        break;
      }

      $targetInputs.eq(i).val($sourceInputs.eq(i).val());
    }
  },

  /**
   * Returns whether the "Ctrl" key is pressed (or ⌘ if this is a Mac) for a given keyboard event
   *
   * @param ev The keyboard event
   *
   * @return {boolean} Whether the "Ctrl" key is pressed
   */
  isCtrlKeyPressed: function (ev) {
    if (window.navigator.platform.match(/Mac/)) {
      // metaKey maps to ⌘ on Macs
      return ev.metaKey;
    }
    return ev.ctrlKey;
  },

  _eventHandlers: [],

  _normalizeEvents: function (events) {
    if (typeof events === 'string') {
      events = events.split(' ');
    }

    for (var i = 0; i < events.length; i++) {
      if (typeof events[i] === 'string') {
        events[i] = events[i].split('.');
      }
    }

    return events;
  },

  on: function (target, events, data, handler) {
    if (typeof target === 'undefined') {
      console.warn('Garnish.on() called for an invalid target class.');
      return;
    }

    if (typeof data === 'function') {
      handler = data;
      data = {};
    }

    events = this._normalizeEvents(events);

    for (var i = 0; i < events.length; i++) {
      var ev = events[i];
      this._eventHandlers.push({
        target: target,
        type: ev[0],
        namespace: ev[1],
        data: data,
        handler: handler,
      });
    }
  },

  off: function (target, events, handler) {
    events = this._normalizeEvents(events);

    for (var i = 0; i < events.length; i++) {
      var ev = events[i];

      for (var j = this._eventHandlers.length - 1; j >= 0; j--) {
        var eventHandler = this._eventHandlers[j];

        if (
          eventHandler.target === target &&
          eventHandler.type === ev[0] &&
          (!ev[1] || eventHandler.namespace === ev[1]) &&
          eventHandler.handler === handler
        ) {
          this._eventHandlers.splice(j, 1);
        }
      }
    }
  },
});

Object.assign(Garnish, {
  Base,
  BaseDrag,
  CheckboxSelect,
  ContextMenu,
  CustomSelect,
  DisclosureMenu,
  Drag,
  DragDrop,
  DragMove,
  DragSort,
  EscManager,
  HUD,
  MenuBtn,
  MixedInput,
  Modal,
  NiceText,
  Select,
  SelectMenu,
  UiLayerManager,
  /**
   * @deprecated Use UiLayerManager instead.
   */
  ShortcutManager: UiLayerManager,
});

// Custom events
// -----------------------------------------------------------------------------

var erd;

function getErd() {
  if (typeof erd === 'undefined') {
    erd = elementResizeDetectorMaker({
      callOnAdd: false,
    });
  }

  return erd;
}

function triggerResizeEvent(elem) {
  $(elem).trigger('resize');
}

// Work them into jQuery's event system
$.extend($.event.special, {
  activate: {
    setup: function (data, namespaces, eventHandle) {
      var activateNamespace = this._namespace + '-activate';
      var $elem = $(this);

      $elem.on({
        'mousedown.garnish-activate': function (e) {
          // Prevent buttons from getting focus on click
          e.preventDefault();
        },
        'click.garnish-activate': function (e) {
          e.preventDefault();

          if (!$elem.hasClass('disabled')) {
            $elem.trigger('activate');
          }
        },
        'keydown.garnish-activate': function (e) {
          // Ignore if the event was bubbled up, or if it wasn't the space key
          if (this !== $elem[0] || e.keyCode !== Garnish.SPACE_KEY) {
            return;
          }

          e.preventDefault();

          if (!$elem.hasClass('disabled')) {
            $elem.addClass('active');

            Garnish.$doc.on('keyup.garnish-activate', function (e) {
              $elem.removeClass('active');

              if (e.keyCode === Garnish.SPACE_KEY) {
                e.preventDefault();
                $elem.trigger('activate');
              }

              Garnish.$doc.off('keyup.garnish-activate');
            });
          }
        },
      });

      if (!$elem.hasClass('disabled')) {
        $elem.attr('tabindex', '0');
      } else {
        $elem.removeAttr('tabindex');
      }
    },
    teardown: function () {
      $(this).off('.garnish-activate');
    },
  },

  textchange: {
    setup: function (data, namespaces, eventHandle) {
      var $elem = $(this);
      $elem.data('garnish-textchange-value', $elem.val());
      $elem.on(
        'keypress.garnish-textchange keyup.garnish-textchange change.garnish-textchange blur.garnish-textchange',
        function (e) {
          var val = $elem.val();
          if (val !== $elem.data('garnish-textchange-value')) {
            $elem.data('garnish-textchange-value', val);
            $elem.trigger('textchange');
          }
        }
      );
    },
    teardown: function () {
      $(this).off('.garnish-textchange');
    },
    handle: function (ev, data) {
      var el = this;
      var args = arguments;
      var delay =
        data && typeof data.delay !== 'undefined'
          ? data.delay
          : ev.data && ev.data.delay !== undefined
          ? ev.data.delay
          : null;
      var handleObj = ev.handleObj;
      var targetData = $.data(ev.target);

      // Was this event configured with a delay?
      if (delay) {
        if (targetData.delayTimeout) {
          clearTimeout(targetData.delayTimeout);
        }

        targetData.delayTimeout = setTimeout(function () {
          handleObj.handler.apply(el, args);
        }, delay);
      } else {
        return handleObj.handler.apply(el, args);
      }
    },
  },

  resize: {
    setup: function (data, namespaces, eventHandle) {
      // window is the only element that natively supports a resize event
      if (this === window) {
        return false;
      }

      $('> :last-child', this).addClass('last');
      getErd().listenTo(this, triggerResizeEvent);
    },
    teardown: function () {
      if (this === window) {
        return false;
      }

      getErd().removeListener(this, triggerResizeEvent);
    },
  },
});

// Give them their own element collection chaining methods
$.each(['activate', 'textchange', 'resize'], function (i, name) {
  $.fn[name] = function (data, fn) {
    return arguments.length > 0
      ? this.on(name, null, data, fn)
      : this.trigger(name);
  };
});

export default Garnish;
