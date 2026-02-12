import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Mixed input
 *
 * @todo RTL support, in the event that the input doesn't have dir="ltr".
 */
export default Base.extend({
  $container: null,
  elements: null,
  focussedElement: null,
  blurTimeout: null,

  init: function (container, settings) {
    this.$container = $(container);
    this.setSettings(settings, Garnish.MixedInput.defaults);

    this.elements = [];

    // Allow the container to receive focus
    this.$container.attr('tabindex', 0);
    this.addListener(this.$container, 'focus', 'onFocus');
  },

  getElementIndex: function ($elem) {
    return $.inArray($elem, this.elements);
  },

  isText: function ($elem) {
    return $elem.prop('nodeName') === 'INPUT';
  },

  onFocus: function () {
    // Set focus to the last element
    if (this.elements.length) {
      var $elem = this.elements[this.elements.length - 1];
      this.setFocus($elem);
      if (this.isText($elem)) {
        this.setCaretPos($elem, $elem.val().length);
      }
    } else {
      this.addTextElement();
    }
  },

  addTextElement: function (index, focus = true) {
    var text = new TextElement(this);
    this.addElement(text.$input, index, focus);
    text.setWidth();
    return text;
  },

  addElement: function ($elem, index, focus = true) {
    // Was a target index passed, and is it valid?
    if (typeof index === 'undefined') {
      if (this.focussedElement) {
        var focussedElement = this.focussedElement,
          focussedElementIndex = this.getElementIndex(focussedElement);

        // Is the focus on a text element?
        if (this.isText(focussedElement)) {
          var selectionStart = focussedElement.prop('selectionStart'),
            selectionEnd = focussedElement.prop('selectionEnd'),
            val = focussedElement.val(),
            preVal = val.substring(0, selectionStart),
            postVal = val.substring(selectionEnd);

          if (preVal && postVal) {
            // Split the input into two
            focussedElement.val(preVal).trigger('change');
            var newText = new TextElement(this);
            newText.$input.val(postVal).trigger('change');
            this.addElement(newText.$input, focussedElementIndex + 1);

            // Insert the new element in between them
            index = focussedElementIndex + 1;
          } else if (!preVal) {
            // Insert the new element before this one
            index = focussedElementIndex;
          } else {
            // Insert it after this one
            index = focussedElementIndex + 1;
          }
        } else {
          // Just insert the new one after this one
          index = focussedElementIndex + 1;
        }
      } else {
        // Insert the new element at the end
        index = this.elements.length;
      }
    }

    // Add the element
    if (typeof this.elements[index] !== 'undefined') {
      $elem.insertBefore(this.elements[index]);
      this.elements.splice(index, 0, $elem);
    } else {
      // Just for safe measure, set the index to what it really will be
      index = this.elements.length;

      this.$container.append($elem);
      this.elements.push($elem);
    }

    // Make sure that there are text elements surrounding all non-text elements
    if (!this.isText($elem)) {
      // Add a text element before?
      if (index === 0 || !this.isText(this.elements[index - 1])) {
        this.addTextElement(index);
        index++;
      }

      // Add a text element after?
      if (
        index === this.elements.length - 1 ||
        !this.isText(this.elements[index + 1])
      ) {
        this.addTextElement(index + 1);
      }
    }

    // Add event listeners
    this.addListener($elem, 'click', function () {
      this.setFocus($elem);
    });

    if (focus) {
      // Set focus to the new element
      setTimeout(
        function () {
          this.setFocus($elem);
        }.bind(this),
        1
      );
    }
  },

  removeElement: function ($elem) {
    var index = this.getElementIndex($elem);
    if (index !== -1) {
      this.elements.splice(index, 1);

      if (!this.isText($elem)) {
        // Combine the two now-adjacent text elements
        var $prevElem = this.elements[index - 1],
          $nextElem = this.elements[index];

        if (this.isText($prevElem) && this.isText($nextElem)) {
          var prevElemVal = $prevElem.val(),
            newVal = prevElemVal + $nextElem.val();
          $prevElem.val(newVal).trigger('change');
          this.removeElement($nextElem);
          this.setFocus($prevElem);
          this.setCaretPos($prevElem, prevElemVal.length);
        }
      }

      $elem.remove();
    }
  },

  setFocus: function ($elem) {
    this.$container.addClass('focus');

    if (!this.focussedElement) {
      // Prevent the container from receiving focus
      // as long as one of its elements has focus
      this.$container.attr('tabindex', '-1');
    } else {
      // Blur the previously-focussed element
      this.blurFocussedElement();
    }

    $elem.attr('tabindex', '0');
    $elem.focus();
    this.focussedElement = $elem;

    this.addListener($elem, 'blur', function () {
      this.blurTimeout = setTimeout(
        function () {
          if (this.focussedElement === $elem) {
            this.blurFocussedElement();
            this.focussedElement = null;
            this.$container.removeClass('focus');

            // Get ready for future focus
            this.$container.attr('tabindex', '0');
          }
        }.bind(this),
        1
      );
    });
  },

  blurFocussedElement: function () {
    this.removeListener(this.focussedElement, 'blur');
    this.focussedElement.attr('tabindex', '-1');
  },

  focusPreviousElement: function ($from) {
    var index = this.getElementIndex($from);

    if (index > 0) {
      var $elem = this.elements[index - 1];
      this.setFocus($elem);

      // If it's a text element, put the caret at the end
      if (this.isText($elem)) {
        var length = $elem.val().length;
        this.setCaretPos($elem, length);
      }
    }
  },

  focusNextElement: function ($from) {
    var index = this.getElementIndex($from);

    if (index < this.elements.length - 1) {
      var $elem = this.elements[index + 1];
      this.setFocus($elem);

      // If it's a text element, put the caret at the beginning
      if (this.isText($elem)) {
        this.setCaretPos($elem, 0);
      }
    }
  },

  focusStart: function () {
    const $elem = this.elements[0];
    this.setFocus($elem);

    // If it's a text element, put the caret at the beginning
    if (this.isText($elem)) {
      this.setCaretPos($elem, 0);
    }
  },

  focusEnd: function () {
    const $elem = this.elements[this.elements.length - 1];
    this.setFocus($elem);

    // If it's a text element, put the caret at the end
    if (this.isText($elem)) {
      this.setCaretPos($elem, $elem.val().length);
    }
  },

  /** @deprecated */
  setCarotPos: function ($elem, pos) {
    this.setCaretPos($elem, pos);
  },

  setCaretPos: function ($elem, pos) {
    $elem.prop('selectionStart', pos);
    $elem.prop('selectionEnd', pos);
  },
});

var TextElement = Base.extend(
  {
    parentInput: null,
    $input: null,
    $stage: null,
    val: null,
    focussed: false,
    interval: null,

    init: function (parentInput) {
      this.parentInput = parentInput;

      this.$input = $('<input type="text"/>').appendTo(
        this.parentInput.$container
      );
      this.$input.css('margin-right', 2 - TextElement.padding + 'px');

      this.setWidth(true);

      this.addListener(this.$input, 'focus', 'onFocus');
      this.addListener(this.$input, 'blur', 'onBlur');
      this.addListener(this.$input, 'keydown', 'onKeyDown');
      this.addListener(this.$input, 'change', 'checkInput');
    },

    getIndex: function () {
      return this.parentInput.getElementIndex(this.$input);
    },

    buildStage: function () {
      this.$stage = $('<stage/>').appendTo(Garnish.$bod);

      // replicate the textarea's text styles
      this.$stage.css({
        position: 'absolute',
        top: -9999,
        left: -9999,
        wordWrap: 'nowrap',
      });

      Garnish.copyTextStyles(this.$input, this.$stage);
    },

    getTextWidth: function (val) {
      if (!this.$stage) {
        this.buildStage();
      }

      if (val) {
        // Ampersand entities
        val = val.replace(/&/g, '&amp;');

        // < and >
        val = val.replace(/</g, '&lt;');
        val = val.replace(/>/g, '&gt;');

        // Spaces
        val = val.replace(/ /g, '&nbsp;');
      }

      this.$stage.html(val);
      this.stageWidth = this.$stage.width();
      return this.stageWidth;
    },

    onFocus: function () {
      this.focussed = true;
      this.interval = setInterval(
        this.checkInput.bind(this),
        Garnish.NiceText.interval
      );
      this.checkInput();
    },

    onBlur: function () {
      this.focussed = false;
      clearInterval(this.interval);
      this.checkInput();
    },

    onKeyDown: function (ev) {
      setTimeout(this.checkInput.bind(this), 1);

      switch (ev.keyCode) {
        case Garnish.LEFT_KEY: {
          if (Garnish.isCtrlKeyPressed(ev)) {
            ev.preventDefault();
            this.parentInput.focusStart();
          } else if (
            this.$input.prop('selectionStart') === 0 &&
            this.$input.prop('selectionEnd') === 0
          ) {
            // Set focus to the previous element
            this.parentInput.focusPreviousElement(this.$input);
          }
          break;
        }

        case Garnish.RIGHT_KEY: {
          if (Garnish.isCtrlKeyPressed(ev)) {
            ev.preventDefault();
            this.parentInput.focusEnd();
          } else if (
            this.$input.prop('selectionStart') === this.val.length &&
            this.$input.prop('selectionEnd') === this.val.length
          ) {
            // Set focus to the next element
            this.parentInput.focusNextElement(this.$input);
          }
          break;
        }

        case Garnish.BACKSPACE_KEY:
        case Garnish.DELETE_KEY: {
          if (
            this.$input.prop('selectionStart') === 0 &&
            this.$input.prop('selectionEnd') === 0
          ) {
            // Set focus to the previous element
            this.parentInput.focusPreviousElement(this.$input);
            ev.preventDefault();
          }
        }
      }
    },

    getVal: function () {
      this.val = this.$input.val();
      return this.val;
    },

    setVal: function (val) {
      this.$input.val(val);
      this.checkInput();
    },

    checkInput: function () {
      // Has the value changed?
      var changed = this.val !== this.getVal();
      if (changed) {
        this.setWidth();
        this.onChange();
      }

      return changed;
    },

    setWidth: function (force = false) {
      // has the width changed?
      if (this.stageWidth !== this.getTextWidth(this.val) || force) {
        // update the textarea width
        var width = this.stageWidth + TextElement.padding;
        this.$input.width(width);
      }
    },

    onChange: $.noop,
  },
  {
    padding: 20,
  }
);
