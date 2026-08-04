import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * HUD
 */
export default Base.extend(
  {
    $trigger: null,
    $fixedTriggerParent: null,
    $hud: null,
    $tip: null,
    $body: null,
    $header: null,
    $footer: null,
    $mainContainer: null,
    $main: null,
    $shade: null,
    $nextFocusableElement: null,

    showing: false,
    orientation: null,

    updatingSizeAndPosition: false,
    windowWidth: null,
    windowHeight: null,
    scrollTop: null,
    scrollLeft: null,
    mainWidth: null,
    mainHeight: null,

    /**
     * Constructor
     *
     * @param {jQuery|HTMLElement} trigger
     * @param {jQuery|HTMLElement|string} [bodyContents]
     * @param {Object} [settings]
     */
    init: function (trigger, bodyContents = '', settings = {}) {
      this.$trigger = $(trigger);

      if ($.isPlainObject(bodyContents)) {
        // (trigger, settings)
        settings = bodyContents;
        bodyContents = '';
      }

      this.setSettings(settings, Garnish.HUD.defaults);
      this.on('show', this.settings.onShow);
      this.on('hide', this.settings.onHide);
      this.on('submit', this.settings.onSubmit);

      this.$trigger.attr('aria-expanded', 'false');

      if (typeof Garnish.HUD.activeHUDs === 'undefined') {
        Garnish.HUD.activeHUDs = {};
      }

      if (this.settings.withShade) {
        this.$shade = $('<div/>', {class: this.settings.shadeClass});
      }
      this.$hud = $('<div/>', {class: this.settings.hudClass}).data(
        'hud',
        this
      );
      this.$tip = $('<div/>', {class: this.settings.tipClass}).appendTo(
        this.$hud
      );
      this.$body = $('<form/>', {class: this.settings.bodyClass}).appendTo(
        this.$hud
      );
      this.$mainContainer = $('<div/>', {
        class: this.settings.mainContainerClass,
      }).appendTo(this.$body);
      this.$main = $('<div/>', {class: this.settings.mainClass}).appendTo(
        this.$mainContainer
      );

      this.updateBody(bodyContents);

      // See if the trigger is fixed
      var $parent = this.$trigger;

      do {
        if ($parent.css('position') === 'fixed') {
          this.$fixedTriggerParent = $parent;
          break;
        }

        $parent = $parent.offsetParent();
      } while ($parent.length && $parent.prop('nodeName') !== 'HTML');

      if (this.$fixedTriggerParent) {
        this.$hud.css('position', 'fixed');
      } else {
        this.$hud.css('position', 'absolute');
      }

      this.addListener(this.$body, 'submit', '_handleSubmit');

      if (this.settings.withShade && this.settings.hideOnShadeClick) {
        this.addListener(this.$shade, 'tap,click', 'hide');
      }

      if (this.settings.closeBtn) {
        this.addListener(this.settings.closeBtn, 'activate', 'hide');
      }

      this.addListener(Garnish.$win, 'resize', 'updateSizeAndPosition');
      if (!this.$fixedTriggerParent) {
        this.addListener(
          Garnish.$scrollContainer,
          'scroll',
          'updateSizeAndPosition'
        );
      }
      if (this.settings.listenToMainResize) {
        this.addListener(this.$main, 'resize', 'updateSizeAndPosition');
      }

      // When the menu is expanded, tabbing on the trigger should move focus into it
      this.addListener(this.$trigger, 'keydown', (ev) => {
        if (ev.keyCode === Garnish.TAB_KEY && !ev.shiftKey && this.showing) {
          const $focusableElement = Garnish.getKeyboardFocusableElements(
            this.$hud
          ).first();
          if ($focusableElement.length) {
            ev.preventDefault();
            $focusableElement.focus();
          }
        }
      });

      // Add listener to manage focus
      this.addListener(this.$hud, 'keydown', function (event) {
        const {keyCode} = event;

        if (keyCode !== Garnish.TAB_KEY) return;

        const $focusableElements = Garnish.getKeyboardFocusableElements(
          this.$hud
        );
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
      });

      if (this.settings.showOnInit) {
        // Hide the HUD until it gets positioned
        this.$hud.css('opacity', 0);
        this.show();
        this.$hud.css('opacity', 1);
      } else {
        this.$hud.appendTo(Garnish.$bod);
        this.hideContainer();
      }

      Garnish.HUD.instances.push(this);
    },

    /**
     * Update the body contents
     */
    updateBody: function (bodyContents) {
      // Cleanup
      this.$main.html('');

      if (this.$header) {
        this.$hud.removeClass('has-header');
        this.$header.remove();
        this.$header = null;
      }

      if (this.$footer) {
        this.$hud.removeClass('has-footer');
        this.$footer.remove();
        this.$footer = null;
      }

      // Append the new body contents
      this.$main.append(bodyContents);

      // Look for a header and footer
      var $header = this.$main.find('.' + this.settings.headerClass + ':first'),
        $footer = this.$main.find('.' + this.settings.footerClass + ':first');

      if ($header.length) {
        this.$header = $header.insertBefore(this.$mainContainer);
        this.$hud.addClass('has-header');
      }

      if ($footer.length) {
        this.$footer = $footer.insertAfter(this.$mainContainer);
        this.$hud.addClass('has-footer');
      }
    },

    /**
     * Show
     */
    show: function (ev) {
      if (ev && ev.stopPropagation) {
        ev.stopPropagation();
      }

      if (this.showing) {
        return;
      }

      if (this.settings.closeOtherHUDs) {
        for (var hudID in Garnish.HUD.activeHUDs) {
          if (!Garnish.HUD.activeHUDs.hasOwnProperty(hudID)) {
            continue;
          }
          Garnish.HUD.activeHUDs[hudID].hide();
        }
      }

      // Blur the active element, if there is one, to prenent the page from jumping
      if (document.activeElement !== document.body) {
        $(document.activeElement).blur();
      }

      // Move it to the end of <body> so it gets the highest sub-z-index
      if (this.settings.withShade) {
        this.$shade.appendTo(Garnish.$bod);
        this.$shade.show();
      }

      this.$hud.appendTo(Garnish.$bod);
      this.$trigger.attr('aria-expanded', 'true');
      this.showContainer();

      this.showing = true;
      Garnish.HUD.activeHUDs[this._namespace] = this;

      Garnish.uiLayerManager.addLayer(this.$hud);

      if (this.settings.hideOnEsc) {
        Garnish.uiLayerManager.registerShortcut(
          Garnish.ESC_KEY,
          this.hide.bind(this)
        );
      }

      // Find the next focusable element in the DOM after the trigger.
      // Shift-tabbing on it should take focus back into the container.
      const $focusableElements = Garnish.$bod.find(':focusable');
      const triggerIndex = $focusableElements.index(this.$trigger[0]);
      if (triggerIndex !== -1 && $focusableElements.length > triggerIndex + 1) {
        this.$nextFocusableElement = $focusableElements.eq(triggerIndex + 1);
        this.addListener(this.$nextFocusableElement, 'keydown', (ev) => {
          if (ev.keyCode === Garnish.TAB_KEY && ev.shiftKey) {
            const $focusableElement = Garnish.getKeyboardFocusableElements(
              this.$hud
            ).last();
            if ($focusableElement.length) {
              ev.preventDefault();
              $focusableElement.focus();
            }
          }
        });
      }

      this.onShow();
      this.enable();

      if (this.updateRecords()) {
        // Prevent the browser from jumping
        this.$hud.css('top', Garnish.$scrollContainer.scrollTop());

        this.updateSizeAndPosition(true);
      }
    },

    showContainer: function () {
      this.$hud.show();
    },

    onShow: function () {
      this.trigger('show');
    },

    updateRecords: function () {
      let changed = false;

      changed =
        this.windowWidth !== (this.windowWidth = Garnish.$win.width()) ||
        changed;
      changed =
        this.windowHeight !== (this.windowHeight = Garnish.$win.height()) ||
        changed;
      changed =
        this.scrollTop !==
          (this.scrollTop = Garnish.$scrollContainer.scrollTop()) || changed;
      changed =
        this.scrollLeft !==
          (this.scrollLeft = Garnish.$scrollContainer.scrollLeft()) || changed;
      changed =
        this.mainWidth !== (this.mainWidth = this.$main.outerWidth()) ||
        changed;
      changed =
        this.mainHeight !== (this.mainHeight = this.$main.outerHeight()) ||
        changed;

      const $scrollParent = this.$trigger.scrollParent();
      if ($scrollParent.get(0) !== Garnish.$scrollContainer.get(0)) {
        changed =
          this.spWidth !== (this.spWidth = $scrollParent.width()) || changed;
        changed =
          this.spHeight !== (this.spHeight = $scrollParent.height()) || changed;
        changed =
          this.spScrollTop !== (this.spScrollTop = $scrollParent.scrollTop()) ||
          changed;
        changed =
          this.spScrollLeft !==
            (this.spScrollLeft = $scrollParent.scrollLeft()) || changed;
      }

      return changed;
    },

    updateSizeAndPosition: function (force) {
      if (
        force === true ||
        (this.updateRecords() && !this.updatingSizeAndPosition)
      ) {
        this.updatingSizeAndPosition = true;
        Garnish.requestAnimationFrame(
          this.updateSizeAndPositionInternal.bind(this)
        );
      }
    },

    updateSizeAndPositionInternal: function () {
      var triggerWidth,
        triggerHeight,
        triggerOffset,
        windowScrollLeft,
        windowScrollTop,
        scrollContainerTriggerOffset,
        scrollContainerScrollLeft,
        scrollContainerScrollTop,
        hudBodyWidth,
        hudBodyHeight;

      // Get the window sizes and trigger offset

      windowScrollLeft = Garnish.$win.scrollLeft();
      windowScrollTop = Garnish.$win.scrollTop();

      // Get the trigger's dimensions
      triggerWidth = this.$trigger.outerWidth();
      triggerHeight = this.$trigger.outerHeight();

      // Get the offsets for each side of the trigger element
      triggerOffset = this.$trigger.offset();

      if (this.$fixedTriggerParent) {
        triggerOffset.left -= windowScrollLeft;
        triggerOffset.top -= windowScrollTop;

        scrollContainerTriggerOffset = triggerOffset;

        windowScrollLeft = 0;
        windowScrollTop = 0;
        scrollContainerScrollLeft = 0;
        scrollContainerScrollTop = 0;
      } else {
        scrollContainerTriggerOffset = Garnish.getOffset(this.$trigger);

        scrollContainerScrollLeft = Garnish.$scrollContainer.scrollLeft();
        scrollContainerScrollTop = Garnish.$scrollContainer.scrollTop();
      }

      triggerOffset.right = triggerOffset.left + triggerWidth;
      triggerOffset.bottom = triggerOffset.top + triggerHeight;

      scrollContainerTriggerOffset.right =
        scrollContainerTriggerOffset.left + triggerWidth;
      scrollContainerTriggerOffset.bottom =
        scrollContainerTriggerOffset.top + triggerHeight;

      // Get the HUD dimensions
      this.$hud.css({
        width: '',
      });

      this.$mainContainer.css({
        height: '',
        'overflow-x': '',
        'overflow-y': '',
      });

      hudBodyWidth = this.$body.width();
      hudBodyHeight = this.$body.height();

      // Determine the best orientation for the HUD

      // Find the actual available top/right/bottom/left clearances
      var clearances = {
        bottom:
          this.windowHeight +
          scrollContainerScrollTop -
          scrollContainerTriggerOffset.bottom,
        top: scrollContainerTriggerOffset.top - scrollContainerScrollTop,
        right:
          this.windowWidth +
          scrollContainerScrollLeft -
          scrollContainerTriggerOffset.right,
        left: scrollContainerTriggerOffset.left - scrollContainerScrollLeft,
      };

      // Find the first position that has enough room
      this.orientation = null;

      for (var i = 0; i < this.settings.orientations.length; i++) {
        var orientation = this.settings.orientations[i],
          relevantSize =
            orientation === 'top' || orientation === 'bottom'
              ? hudBodyHeight
              : hudBodyWidth;

        if (
          clearances[orientation] -
            (this.settings.windowSpacing + this.settings.triggerSpacing) >=
          relevantSize
        ) {
          // This is the first orientation that has enough room in order of preference, so we'll go with this
          this.orientation = orientation;
          break;
        }

        if (
          !this.orientation ||
          clearances[orientation] > clearances[this.orientation]
        ) {
          // Use this as a fallback as it's the orientation with the most clearance so far
          this.orientation = orientation;
        }
      }

      // Just in case...
      if (
        !this.orientation ||
        $.inArray(this.orientation, ['bottom', 'top', 'right', 'left']) === -1
      ) {
        this.orientation = 'bottom';
      }

      // Update the tip class
      if (this.tipClass) {
        this.$tip.removeClass(this.tipClass);
      }

      this.tipClass =
        this.settings.tipClass + '-' + Garnish.HUD.tipClasses[this.orientation];
      this.$tip.addClass(this.tipClass);

      // Make sure the HUD body is within the allowed size

      var maxHudBodyWidth, maxHudBodyHeight;

      if (this.orientation === 'top' || this.orientation === 'bottom') {
        maxHudBodyWidth = this.windowWidth - this.settings.windowSpacing * 2;
        maxHudBodyHeight =
          clearances[this.orientation] -
          this.settings.windowSpacing -
          this.settings.triggerSpacing;
      } else {
        maxHudBodyWidth =
          clearances[this.orientation] -
          this.settings.windowSpacing -
          this.settings.triggerSpacing;
        maxHudBodyHeight = this.windowHeight - this.settings.windowSpacing * 2;
      }

      if (maxHudBodyWidth < this.settings.minBodyWidth) {
        maxHudBodyWidth = this.settings.minBodyWidth;
      }

      if (maxHudBodyHeight < this.settings.minBodyHeight) {
        maxHudBodyHeight = this.settings.minBodyHeight;
      }

      if (
        hudBodyWidth > maxHudBodyWidth ||
        hudBodyWidth < this.settings.minBodyWidth
      ) {
        if (hudBodyWidth > maxHudBodyWidth) {
          hudBodyWidth = maxHudBodyWidth;
        } else {
          hudBodyWidth = this.settings.minBodyWidth;
        }

        this.$hud.width(hudBodyWidth);

        // Is there any overflow now?
        if (this.mainWidth > maxHudBodyWidth) {
          this.$mainContainer.css('overflow-x', 'scroll');
        }

        // The height may have just changed
        hudBodyHeight = this.$body.height();
      }

      if (
        hudBodyHeight > maxHudBodyHeight ||
        hudBodyHeight < this.settings.minBodyHeight
      ) {
        if (hudBodyHeight > maxHudBodyHeight) {
          hudBodyHeight = maxHudBodyHeight;
        } else {
          hudBodyHeight = this.settings.minBodyHeight;
        }

        var mainHeight = hudBodyHeight;

        if (this.$header) {
          mainHeight -= this.$header.outerHeight();
        }

        if (this.$footer) {
          mainHeight -= this.$footer.outerHeight();
        }

        this.$mainContainer.height(mainHeight);

        // Is there any overflow now?
        if (this.mainHeight > mainHeight) {
          this.$mainContainer.css('overflow-y', 'scroll');
        }
      }

      // Set the HUD/tip positions
      let triggerCenter, left, top;

      this.$hud.css({
        'border-top-left-radius': '',
        'border-top-right-radius': '',
        'boredr-bottom-right-radius': '',
        'border-bottom-left-radius': '',
      });
      const borderRadius = parseInt(this.$hud.css('border-radius'));

      if (this.orientation === 'top' || this.orientation === 'bottom') {
        // Center the HUD horizontally
        var maxLeft =
          this.windowWidth +
          windowScrollLeft -
          (hudBodyWidth + this.settings.windowSpacing);
        var minLeft = windowScrollLeft + this.settings.windowSpacing;
        triggerCenter = triggerOffset.left + Math.round(triggerWidth / 2);
        left = triggerCenter - Math.round(hudBodyWidth / 2);

        if (left > maxLeft) {
          left = maxLeft;
        }
        if (left < minLeft) {
          left = minLeft;
        }

        this.$hud.css('left', left);

        const tipLeft = Garnish.within(
          triggerCenter - left - this.settings.tipWidth / 2,
          0,
          hudBodyWidth - this.settings.tipWidth
        );
        this.$tip.css({left: tipLeft, top: ''});

        if (this.orientation === 'top') {
          top =
            triggerOffset.top - (hudBodyHeight + this.settings.triggerSpacing);
          this.$hud.css('top', top);
        } else {
          top = triggerOffset.bottom + this.settings.triggerSpacing;
          this.$hud.css('top', top);
        }

        const adjustRadius = this.orientation === 'top' ? 'bottom' : 'top';
        if (tipLeft < borderRadius) {
          this.$hud.css(`border-${adjustRadius}-left-radius`, 2);
        } else if (
          tipLeft >
          hudBodyWidth - borderRadius - this.settings.tipWidth
        ) {
          this.$hud.css(`border-${adjustRadius}-right-radius`, 2);
        }
      } else {
        // Center the HUD vertically
        var maxTop =
          this.windowHeight +
          windowScrollTop -
          (hudBodyHeight + this.settings.windowSpacing);
        var minTop = windowScrollTop + this.settings.windowSpacing;
        triggerCenter = triggerOffset.top + Math.round(triggerHeight / 2);
        top = triggerCenter - Math.round(hudBodyHeight / 2);

        if (top > maxTop) {
          top = maxTop;
        }
        if (top < minTop) {
          top = minTop;
        }

        this.$hud.css('top', top);

        const tipTop = Garnish.within(
          triggerCenter - top - this.settings.tipWidth / 2,
          0,
          hudBodyHeight - this.settings.tipWidth
        );
        this.$tip.css({top: tipTop, left: ''});

        if (this.orientation === 'left') {
          left =
            triggerOffset.left - (hudBodyWidth + this.settings.triggerSpacing);
          this.$hud.css('left', left);
        } else {
          left = triggerOffset.right + this.settings.triggerSpacing;
          this.$hud.css('left', left);
        }

        const adjustRadius = this.orientation === 'left' ? 'right' : 'left';
        if (tipTop < borderRadius) {
          this.$hud.css(`border-top-${adjustRadius}-radius`, 2);
        } else if (
          tipTop >
          hudBodyHeight - borderRadius - this.settings.tipWidth
        ) {
          this.$hud.css(`border-bottom-${adjustRadius}-radius`, 2);
        }
      }

      this.updatingSizeAndPosition = false;
      this.trigger('updateSizeAndPosition');
    },

    /**
     * Hide
     */
    hide: function () {
      if (!this.showing) {
        return;
      }

      this.disable();
      this.$trigger.attr('aria-expanded', 'false');
      this.hideContainer();

      if (this.settings.withShade) {
        this.$shade.hide();
      }

      this.showing = false;
      delete Garnish.HUD.activeHUDs[this._namespace];
      Garnish.uiLayerManager.removeLayer();

      if (Garnish.focusIsInside(this.$hud)) {
        this.$trigger.focus();
      }

      if (this.$nextFocusableElement) {
        this.removeListener(this.$nextFocusableElement, 'keydown');
        this.$nextFocusableElement = null;
      }

      this.onHide();
    },

    hideContainer: function () {
      this.$hud.hide();
    },

    onHide: function () {
      this.trigger('hide');
    },

    toggle: function () {
      if (this.showing) {
        this.hide();
      } else {
        this.show();
      }
    },

    submit: function () {
      this.onSubmit();
    },

    onSubmit: function () {
      this.trigger('submit');
    },

    _handleSubmit: function (ev) {
      ev.preventDefault();
      this.submit();
    },

    /**
     * Destroy
     */
    destroy: function () {
      if (this.$hud) {
        this.$hud.remove();
      }

      if (this.settings.withShade && this.$shade) {
        this.$shade.remove();
      }

      Garnish.HUD.instances = Garnish.HUD.instances.filter((o) => o !== this);

      this.base();
    },
  },
  {
    tipClasses: {bottom: 'top', top: 'bottom', right: 'left', left: 'right'},

    defaults: {
      shadeClass: 'hud-shade',
      hudClass: 'hud',
      tipClass: 'tip',
      bodyClass: 'body',
      headerClass: 'hud-header',
      footerClass: 'hud-footer',
      mainContainerClass: 'main-container',
      mainClass: 'main',
      orientations: ['bottom', 'top', 'right', 'left'],
      triggerSpacing: 10,
      windowSpacing: 10,
      tipWidth: 30,
      minBodyWidth: 200,
      minBodyHeight: 0,
      withShade: true,
      onShow: $.noop,
      onHide: $.noop,
      onSubmit: $.noop,
      closeBtn: null,
      listenToMainResize: true,
      showOnInit: true,
      closeOtherHUDs: true,
      hideOnEsc: true,
      hideOnShadeClick: true,
    },

    /**
     * @type {Garnish.HUD[]}
     */
    instances: [],
  }
);
