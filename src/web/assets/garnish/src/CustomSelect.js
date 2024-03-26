import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Custom Select Menu
 */
export default Base.extend(
  {
    settings: null,
    visible: false,

    $container: null,
    $options: null,
    $ariaOptions: null,
    $anchor: null,

    menuId: null,

    _windowWidth: null,
    _windowHeight: null,
    _windowScrollLeft: null,
    _windowScrollTop: null,

    _anchorOffset: null,
    _anchorWidth: null,
    _anchorHeight: null,
    _anchorOffsetRight: null,
    _anchorOffsetBottom: null,

    _menuWidth: null,
    _menuHeight: null,

    /**
     * Constructor
     */
    init: function (container, settings) {
      this.setSettings(settings, Garnish.CustomSelect.defaults);

      this.$container = $(container);

      this.$options = $();
      this.$ariaOptions = $();

      // Menu List
      this.menuId = 'menu' + this._namespace;
      this.$container.attr({
        role: 'listbox',
        id: this.menuId,
      });

      this.$container.find('ul').attr('role', 'group');
      this.addOptions(this.$container.find('a,.menu-item,.menu-option'));

      // Deprecated
      if (this.settings.attachToElement) {
        this.settings.anchor = this.settings.attachToElement;
        console.warn(
          "The 'attachToElement' setting is deprecated. Use 'anchor' instead."
        );
      }

      if (this.settings.anchor) {
        this.$anchor = $(this.settings.anchor);
      }

      // Prevent clicking on the container from hiding the menu
      this.addListener(this.$container, 'mousedown', function (ev) {
        ev.stopPropagation();

        if (ev.target.nodeName !== 'INPUT') {
          // Prevent this from causing the menu button to blur
          ev.preventDefault();
        }
      });
    },

    addOptions: function ($options) {
      this.$options = this.$options.add($options);
      $options.data('menu', this);

      for (let i = 0; i < $options.length; i++) {
        const $option = $options.eq(i);
        const $ariaOption = $option.parent('li');
        $option.attr({
          tabindex: '-1',
          id: $option.attr('id') || `${this.menuId}-option-${i + 1}`,
        });
        $ariaOption.attr({
          role: 'option',
          'aria-selected': $option.hasClass('sel') ? 'true' : 'false',
          id: $ariaOption.attr('id') || `${this.menuId}-aria-option-${i + 1}`,
        });
        this.$ariaOptions = this.$ariaOptions.add($ariaOption);

        // keep aria-selected in-line with .sel
        $option.data(
          'menu-mutationObserver',
          new MutationObserver((mutations) => {
            for (const mutation of mutations) {
              if (
                mutation.type === 'attributes' &&
                mutation.attributeName === 'class'
              ) {
                const optionHasHover = this.$options.is('.hover');
                $ariaOption.attr(
                  'aria-selected',
                  (!optionHasHover && $option.hasClass('sel')) ||
                    $option.hasClass('hover')
                    ? 'true'
                    : 'false'
                );
                break;
              }
            }
          })
        );
        $option
          .data('menu-mutationObserver')
          .observe($option[0], {attributes: true});
      }

      this.removeAllListeners($options);
      this.addListener($options, 'click', function (ev) {
        this.selectOption(ev.currentTarget);
      });
    },

    setPositionRelativeToAnchor: function () {
      this._windowWidth = Garnish.$win.width();
      this._windowHeight = Garnish.$win.height();
      this._windowScrollLeft = Garnish.$win.scrollLeft();
      this._windowScrollTop = Garnish.$win.scrollTop();

      this._anchorOffset = this.$anchor.offset();
      this._anchorWidth = this.$anchor.outerWidth();
      this._anchorHeight = this.$anchor.outerHeight();
      this._anchorOffsetRight = this._anchorOffset.left + this._anchorHeight;
      this._anchorOffsetBottom = this._anchorOffset.top + this._anchorHeight;

      this.$container.css('minWidth', 0);
      this.$container.css(
        'minWidth',
        this._anchorWidth -
          (this.$container.outerWidth() - this.$container.width())
      );

      this._menuWidth = this.$container.outerWidth();
      this._menuHeight = this.$container.outerHeight();

      // Is there room for the menu below the anchor?
      var topClearance = this._anchorOffset.top - this._windowScrollTop,
        bottomClearance =
          this._windowHeight + this._windowScrollTop - this._anchorOffsetBottom;

      if (
        bottomClearance >= this._menuHeight ||
        (topClearance < this._menuHeight && bottomClearance >= topClearance)
      ) {
        this.$container.css({
          top: this._anchorOffsetBottom,
          maxHeight: bottomClearance - this.settings.windowSpacing,
        });
      } else {
        this.$container.css({
          top:
            this._anchorOffset.top -
            Math.min(
              this._menuHeight,
              topClearance - this.settings.windowSpacing
            ),
          maxHeight: topClearance - this.settings.windowSpacing,
        });
      }

      // Figure out how we're aliging it
      var align = this.$container.data('align');

      if (align !== 'left' && align !== 'center' && align !== 'right') {
        align = 'left';
      }

      if (align === 'center') {
        this._alignCenter();
      } else {
        // Figure out which options are actually possible
        var rightClearance =
            this._windowWidth +
            this._windowScrollLeft -
            (this._anchorOffset.left + this._menuWidth),
          leftClearance = this._anchorOffsetRight - this._menuWidth;

        if (
          ((align === 'right' && leftClearance >= 0) || rightClearance < 0) &&
          this._menuWidth < this._anchorOffset.left + this._anchorWidth
        ) {
          this._alignRight();
        } else {
          this._alignLeft();
        }
      }

      delete this._windowWidth;
      delete this._windowHeight;
      delete this._windowScrollLeft;
      delete this._windowScrollTop;
      delete this._anchorOffset;
      delete this._anchorWidth;
      delete this._anchorHeight;
      delete this._anchorOffsetRight;
      delete this._anchorOffsetBottom;
      delete this._menuWidth;
      delete this._menuHeight;
    },

    show: function () {
      if (this.visible) {
        return;
      }

      // Move the menu to the end of the DOM
      this.$container.appendTo(Garnish.$bod);

      if (this.$anchor) {
        this.setPositionRelativeToAnchor();
      }

      this.$container.velocity('stop');
      this.$container.css({
        opacity: 1,
        display: 'block',
      });

      Garnish.uiLayerManager
        .addLayer(this.$container)
        .registerShortcut(Garnish.ESC_KEY, this.hide.bind(this));

      this.addListener(
        Garnish.$scrollContainer,
        'scroll',
        'setPositionRelativeToAnchor'
      );
      this.addListener(Garnish.$win, 'resize', 'setPositionRelativeToAnchor');

      this.visible = true;
      this.trigger('show');
    },

    hide: function () {
      if (!this.visible) {
        return;
      }

      this.$options.removeClass('hover');
      this.$options.filter('.sel').parent('li').attr('aria-selected', 'true');

      this.$container.velocity(
        'fadeOut',
        {duration: Garnish.FX_DURATION},
        () => {
          this.$container.detach();
        }
      );

      Garnish.uiLayerManager.removeLayer(this.$container);
      this.removeListener(Garnish.$scrollContainer, 'scroll');
      this.visible = false;
      this.trigger('hide');
    },

    selectOption: function (option) {
      this.settings.onOptionSelect(option);
      this.trigger('optionselect', {selectedOption: option});
      this.hide();
    },

    _alignLeft: function () {
      this.$container.css({
        left: this._anchorOffset.left,
        right: 'auto',
      });

      // if menuWidth is larger than the screen estate we have
      // - set max-width with a slight margin (10)
      if (this._menuWidth > this._windowWidth - this._anchorOffset.left) {
        this.$container.css({
          maxWidth: this._windowWidth - this._anchorOffset.left - 10,
        });
      }
    },

    _alignRight: function () {
      this.$container.css({
        right:
          this._windowWidth - (this._anchorOffset.left + this._anchorWidth),
        left: 'auto',
      });

      // if menuWidth is larger than the screen estate we have
      // - set max-width with a slight margin (10)
      if (this._menuWidth > this._anchorOffset.left + this._anchorWidth) {
        this.$container.css({
          maxWidth: this._anchorOffset.left + this._anchorWidth - 10,
        });
      }
    },

    _alignCenter: function () {
      var left = Math.round(
        this._anchorOffset.left + this._anchorWidth / 2 - this._menuWidth / 2
      );

      if (left < 0) {
        left = 0;
      }

      this.$container.css('left', left);
    },

    destroy: function () {
      for (let i = 0; i < this.$options.length; i++) {
        const $option = this.$options.eq(i);
        $option.data('menu-mutationObserver').disconnect();
        $option.removeData('menu-mutationObserver');
      }

      this.base();
    },
  },
  {
    defaults: {
      anchor: null,
      windowSpacing: 5,
      onOptionSelect: $.noop,
    },
  }
);
