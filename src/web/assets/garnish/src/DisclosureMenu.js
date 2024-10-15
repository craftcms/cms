import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Disclosure Widget
 */
export default Base.extend(
  {
    settings: null,

    $trigger: null,
    $container: null,
    $alignmentElement: null,
    $nextFocusableElement: null,

    _viewportWidth: null,
    _viewportHeight: null,
    _viewportScrollLeft: null,
    _viewportScrollTop: null,

    _alignmentElementOffset: null,
    _alignmentElementWidth: null,
    _alignmentElementHeight: null,
    _alignmentElementOffsetRight: null,
    _alignmentElementOffsetBottom: null,

    _menuWidth: null,
    _menuHeight: null,

    /**
     * Constructor
     */
    init: function (trigger, settings) {
      this.setSettings(settings, Garnish.DisclosureMenu.defaults);

      this.$trigger = $(trigger);

      // Is this already a disclosure button?
      if (this.$trigger.data('trigger')) {
        console.warn('Double-instantiating a disclosure menu on an element');
        return;
      }

      const containerId = this.$trigger.attr('aria-controls');
      this.$container = $('#' + containerId);
      if (!this.$container.length) {
        // see if it's the next element
        const $next = this.$trigger.next();
        if ($next.is(`#${containerId}`)) {
          this.$container = $next;
        } else {
          throw 'No disclosure container found.';
        }
      }

      this.$trigger.data('trigger', this);

      // Get and store expanded state from trigger
      const expanded = this.$trigger.attr('aria-expanded');

      // If no expanded state exists on trigger, add for a11y
      if (!expanded) {
        this.$trigger.attr('aria-expanded', 'false');
      }

      // Capture additional alignment element
      const alignmentSelector = this.$container.data('align-to');
      if (alignmentSelector) {
        this.$alignmentElement = this.$trigger.find(alignmentSelector).first();
      } else {
        this.$alignmentElement = this.$trigger;
      }

      this.$container.appendTo(Garnish.$bod);
      this.addDisclosureMenuEventListeners();
    },

    addDisclosureMenuEventListeners: function () {
      this.addListener(this.$trigger, 'mousedown', function (event) {
        event.stopPropagation();
      });

      this.addListener(this.$trigger, 'click', () => {
        this.handleTriggerClick();
      });

      this.addListener(this.$container, 'keydown', function (event) {
        this.handleKeypress(event);
      });

      this.addListener(Garnish.$doc, 'mousedown', this.handleMousedown);

      // When the menu is expanded, tabbing on the trigger should move focus into it
      this.addListener(this.$trigger, 'keydown', (ev) => {
        if (
          ev.keyCode === Garnish.TAB_KEY &&
          !ev.shiftKey &&
          this.isExpanded()
        ) {
          const $focusableElement = this.$container.find(':focusable:first');
          if ($focusableElement.length) {
            ev.preventDefault();
            $focusableElement.focus();
          }
        }
      });
    },

    focusElement: function (direction) {
      const currentFocus = $(':focus');

      const focusable = this.$container.find(':focusable');

      const currentIndex = focusable.index(currentFocus);
      let newIndex;

      if (direction === 'prev') {
        newIndex = currentIndex - 1;
      } else {
        newIndex = currentIndex + 1;
      }

      if (newIndex >= 0 && newIndex < focusable.length) {
        const elementToFocus = focusable[newIndex];
        elementToFocus.focus();
      }
    },

    handleMousedown: function (event) {
      const newTarget = event.target;
      const triggerButton = $(newTarget).closest('[data-disclosure-trigger]');
      const newTargetIsInsideDisclosure =
        this.$container[0] === event.target ||
        this.$container.has(newTarget).length > 0;

      // If click target matches trigger element or disclosure child, do nothing
      if ($(triggerButton).is(this.$trigger) || newTargetIsInsideDisclosure) {
        return;
      }

      this.hide();
    },

    handleKeypress: function (event) {
      const keyCode = event.keyCode;

      switch (keyCode) {
        case Garnish.RIGHT_KEY:
        case Garnish.DOWN_KEY:
          event.preventDefault();
          this.focusElement('next');
          break;
        case Garnish.LEFT_KEY:
        case Garnish.UP_KEY:
          event.preventDefault();
          this.focusElement('prev');
          break;
        case Garnish.TAB_KEY:
          const $focusableElements = this.$container.find(':focusable');
          const index = $focusableElements.index(event.target);

          if (index === 0 && event.shiftKey) {
            event.preventDefault();
            this.$trigger.focus();
          } else if (
            index === $focusableElements.length - 1 &&
            !event.shiftKey &&
            this.$nextFocusableElement
          ) {
            event.preventDefault();
            this.$nextFocusableElement.focus();
          }
          break;
      }
    },

    isExpanded: function () {
      const isExpanded = this.$trigger.attr('aria-expanded');
      return isExpanded === 'true';
    },

    handleTriggerClick: function () {
      if (!this.isExpanded()) {
        this.show();
      } else {
        this.hide();
      }
    },

    show: function () {
      if (this.isExpanded() || this.$trigger.hasClass('disabled')) {
        return;
      }

      // Move the menu to the end of the DOM
      this.$container.appendTo(Garnish.$bod);

      this.setContainerPosition();
      this.addListener(
        Garnish.$scrollContainer,
        'scroll',
        'setContainerPosition'
      );
      const $scrollParent = this.$trigger.scrollParent();
      if ($scrollParent.get(0) !== document.body) {
        this.addListener($scrollParent, 'scroll', 'setContainerPosition');
      }
      this.addListener(Garnish.$win, 'resize', 'setContainerPosition');

      this.$container.velocity('stop');
      this.$container.css({
        opacity: 1,
        display: '',
      });

      // In case its default display is set to none
      if (this.$container.css('display') === 'none') {
        this.$container.css('display', 'block');
      }

      // Set ARIA attribute for expanded
      this.$trigger.attr('aria-expanded', 'true');

      // Focus first focusable element
      const firstFocusableEl = this.$container.find(':focusable')[0];
      if (firstFocusableEl) {
        firstFocusableEl.focus();
      } else {
        this.$container.attr('tabindex', '-1');
        this.$container.focus();
      }

      // Find the next focusable element in the DOM after the trigger.
      // Shift-tabbing on it should take focus back into the container.
      const $focusableElements = Garnish.$bod.find(':focusable');
      const triggerIndex = $focusableElements.index(this.$trigger[0]);
      if (triggerIndex !== -1 && $focusableElements.length > triggerIndex + 1) {
        this.$nextFocusableElement = $focusableElements.eq(triggerIndex + 1);
        this.addListener(this.$nextFocusableElement, 'keydown', (ev) => {
          if (ev.keyCode === Garnish.TAB_KEY && ev.shiftKey) {
            const $focusableElement = this.$container.find(':focusable:last');
            if ($focusableElement.length) {
              ev.preventDefault();
              $focusableElement.focus();
            }
          }
        });
      }

      this.trigger('show');
      Garnish.uiLayerManager.addLayer(this.$container);
      Garnish.uiLayerManager.registerShortcut(
        Garnish.ESC_KEY,
        function () {
          this.hide();
        }.bind(this)
      );
    },

    hide: function () {
      if (!this.isExpanded()) {
        return;
      }

      this.$container.velocity('fadeOut', {duration: Garnish.FX_DURATION});

      this.$trigger.attr('aria-expanded', 'false');

      if (this.focusIsInMenu()) {
        this.$trigger.focus();
      }

      if (this.$nextFocusableElement) {
        this.removeListener(this.$nextFocusableElement, 'keydown');
        this.$nextFocusableElement = null;
      }

      this.trigger('hide');
      this.removeListener(Garnish.$scrollContainer, 'scroll');
      this.removeListener(Garnish.$win, 'resize');
      Garnish.uiLayerManager.removeLayer(this.$container);
    },

    focusIsInMenu: function () {
      if (!this.$container.length) {
        return false;
      }
      const $focusedEl = Garnish.getFocusedElement();
      return $focusedEl.length && $.contains(this.$container[0], $focusedEl[0]);
    },

    setContainerPosition: function () {
      this._viewportWidth = Garnish.$win.width();
      this._viewportHeight = Garnish.$win.height();
      this._viewportScrollLeft = Garnish.$win.scrollLeft();
      this._viewportScrollTop = Garnish.$win.scrollTop();

      this._alignmentElementOffset = this.$alignmentElement.offset();
      this._alignmentElementWidth = this.$alignmentElement.outerWidth();
      this._alignmentElementHeight = this.$alignmentElement.outerHeight();
      this._alignmentElementOffsetRight =
        this._alignmentElementOffset.left + this._alignmentElementWidth;
      this._alignmentElementOffsetBottom =
        this._alignmentElementOffset.top + this._alignmentElementHeight;

      this.$container.css('minWidth', 0);
      this.$container.css(
        'minWidth',
        this._alignmentElementWidth -
          (this.$container.outerWidth() - this.$container.width())
      );

      this._menuWidth = this.$container.outerWidth();
      this._menuHeight = this.$container.outerHeight();

      if (this._menuWidth > this._viewportWidth) {
        this.$container.css('maxWidth', this._viewportWidth);
        this._menuWidth = this._viewportWidth;
      }

      // Is there room for the menu below the trigger?
      const topClearance =
        this._alignmentElementOffset.top - this._viewportScrollTop;
      const bottomClearance =
        this._viewportHeight +
        this._viewportScrollTop -
        this._alignmentElementOffsetBottom;

      if (
        bottomClearance >= this._menuHeight ||
        (topClearance < this._menuHeight && bottomClearance >= topClearance)
      ) {
        this.$container.css({
          top: this._alignmentElementOffsetBottom,
          maxHeight: bottomClearance - this.settings.windowSpacing,
        });
      } else {
        this.$container.css({
          top:
            this._alignmentElementOffset.top -
            Math.min(
              this._menuHeight,
              topClearance - this.settings.windowSpacing
            ),
          maxHeight: topClearance - this.settings.windowSpacing,
        });
      }

      // Figure out how we're aligning it
      let align = this.$container.data('align');

      if (align !== 'left' && align !== 'center' && align !== 'right') {
        align = 'left';
      }

      if (this._menuWidth === this._viewportWidth || align === 'center') {
        this._alignCenter();
      } else {
        // Figure out which options are actually possible
        const rightClearance =
          this._viewportWidth +
          this._viewportScrollLeft -
          (this._alignmentElementOffset.left + this._menuWidth);
        const leftClearance =
          this._alignmentElementOffsetRight - this._menuWidth;

        if (leftClearance < 0 && rightClearance < 0) {
          this._alignCenter();
        } else if (
          (align === 'right' && leftClearance >= 0) ||
          rightClearance < 0
        ) {
          this._alignRight();
        } else {
          this._alignLeft();
        }
      }

      delete this._viewportWidth;
      delete this._viewportHeight;
      delete this._viewportScrollLeft;
      delete this._viewportScrollTop;
      delete this._alignmentElementOffset;
      delete this._alignmentElementWidth;
      delete this._alignmentElementHeight;
      delete this._alignmentElementOffsetRight;
      delete this._alignmentElementOffsetBottom;
      delete this._menuWidth;
      delete this._menuHeight;
    },

    /**
     * Destroy
     */
    destroy: function () {
      this.$trigger.removeData('trigger');
      this.removeListener(this.$trigger, 'click');
      this.removeListener(this.$container, 'keydown');
      this.base();
    },

    _alignLeft: function () {
      this.$container.css({
        left: Math.max(this._alignmentElementOffset.left, 0),
        right: 'auto',
      });
    },

    _alignRight: function () {
      const right =
        this._viewportWidth -
        (this._alignmentElementOffset.left + this._alignmentElementWidth);

      this.$container.css({
        right: Math.max(right, 0),
        left: 'auto',
      });
    },

    _alignCenter: function () {
      const left = Math.round(
        this._alignmentElementOffset.left +
          this._alignmentElementWidth / 2 -
          this._menuWidth / 2
      );

      this.$container.css({
        left: Math.max(left, 0),
        right: 'auto',
      });
    },
  },
  {
    defaults: {
      windowSpacing: 5,
    },
  }
);
