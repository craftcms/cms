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

      const triggerId = this.$trigger.attr('aria-controls');
      this.$container = $('#' + triggerId);

      this.$trigger.data('disclosureMenu', this);
      this.$container.data('disclosureMenu', this);

      // for BC
      this.$trigger.data('trigger', this);
      this.$container.data('trigger', this);

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
      // if trigger is in a slideout, we need to initialise UI elements
      if (this.$trigger.parents('.slideout').length > 0) {
        Craft.initUiElements(this.$container);
      }
      this.addDisclosureMenuEventListeners();
    },

    addDisclosureMenuEventListeners: function () {
      this.addListener(this.$trigger, 'mousedown', (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
      });

      this.addListener(this.$trigger, 'mouseup', (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
      });

      this.addListener(this.$trigger, 'click', (ev) => {
        ev.stopPropagation();
        ev.preventDefault();
        this.handleTriggerClick();
      });

      this.addListener(this.$container, 'keydown', (ev) => {
        this.handleKeypress(ev);
      });

      this.addListener(Garnish.$doc, 'mousedown', (ev) => {
        this.handleMousedown(ev);
      });

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
      Garnish.uiLayerManager.removeLayer();
    },

    focusIsInMenu: function () {
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

    isPadded: function () {
      return this.$container.children('.padded').length;
    },

    createItem: function (item) {
      if (item.nodeType === Node.ELEMENT_NODE) {
        return item;
      }

      if (item instanceof jQuery) {
        return item[0];
      }

      if (!$.isPlainObject(item)) {
        throw 'Unsupported item configuration.';
      }

      let type;
      if (item.type) {
        type = item.type;
      } else if (item.action) {
        type = 'button';
      } else {
        type = 'link';
      }

      const li = document.createElement('li');
      if (item.hidden) {
        li.classList.add('hidden');
      }

      const el = document.createElement(type === 'button' ? 'button' : 'a');
      el.id = item.id || `menu-item-${Math.floor(Math.random() * 1000000)}`;
      el.className = 'menu-item';
      if (item.selected) {
        el.classList.add('sel');
      }
      if (item.destructive) {
        el.classList.add('error');
        el.setAttribute('data-destructive', 'true');
      }
      if (item.action) {
        el.classList.add('formsubmit');
      }
      if (type === 'link') {
        el.href = Craft.getUrl(item.url);
      }
      if (item.icon) {
        el.setAttribute('data-icon', item.icon);
      }
      if (item.action) {
        el.setAttribute('data-action', item.action);
        el.setAttribute('data-form', 'false');
      }
      if (item.params) {
        el.setAttribute(
          'data-params',
          typeof item.params === 'string'
            ? item.params
            : JSON.stringify(item.params)
        );
      }
      if (item.confirm) {
        el.setAttribute('data-confirm', item.confirm);
      }
      if (item.redirect) {
        el.setAttribute('data-redirect', item.redirect);
      }
      li.append(el);

      if (item.status) {
        const status = document.createElement('div');
        status.className = `status ${item.status}`;
        el.append(status);
      }

      const label = document.createElement('span');
      label.className = 'menu-item-label';
      if (item.label) {
        label.textContent = item.label;
      } else if (item.html) {
        label.innerHTML = item.html;
      }
      el.append(label);

      if (item.description) {
        const description = document.createElement('div');
        description.className = 'menu-item-description smalltext light';
        description.textContent = item.description;
        el.append(description);
      }

      this.addListener(el, 'click', () => {
        this.hide();
      });

      return li;
    },

    addItem: function (item, ul) {
      item = this.createItem(item);

      if (!ul) {
        ul = this.$container.children('ul').last().get(0) || this.addGroup();
      }

      ul.append(item);
      return item.querySelector('a, button');
    },

    addHr: function (before) {
      const hr = document.createElement('hr');
      if (this.isPadded()) {
        hr.className = 'padded';
      }

      if (before) {
        before.parentNode.insertBefore(hr, before);
      } else {
        this.$container.append(hr);
      }

      return hr;
    },

    getFirstDestructiveGroup: function () {
      return this.$container
        .children('ul:has([data-destructive]):first')
        .get(0);
    },

    addGroup: function (heading, addHrs, before) {
      const padded = this.isPadded();

      if (heading) {
        const h6 = document.createElement('h6');
        if (padded) {
          h6.className = 'padded';
        }
        h6.textContent = heading;

        if (before) {
          before.parentNode.insertBefore(h6, before);
        } else {
          this.$container.append(h6);
        }
      }

      const ul = document.createElement('ul');
      if (padded) {
        ul.className = 'padded';
      }

      if (before) {
        before.parentNode.insertBefore(ul, before);
      } else {
        this.$container.append(ul);
      }

      if (addHrs) {
        if (
          ul.previousElementSibling &&
          ul.previousElementSibling.nodeName !== 'HR'
        ) {
          this.addHr(ul);
        }
        if (ul.nextElementSibling && ul.nextElementSibling !== 'HR') {
          this.addHr(ul.nextElementSibling);
        }
      }

      return ul;
    },

    /**
     * Destroy
     */
    destroy: function () {
      this.$trigger.removeData('trigger');
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
