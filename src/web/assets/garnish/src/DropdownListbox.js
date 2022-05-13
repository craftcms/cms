import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Dropdown Listbox
 */
export default Base.extend(
  {
    settings: null,
    visible: false,

    $container: null,
    $button: null,
    $listbox: null,
    $options: null,
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
    init: function (button, settings) {
      // this.setSettings(settings, Garnish.CustomSelect.defaults);

      this.$button = $(button);

      // Get listbox ID from button
      const listboxId = this.$button.data('controls');
      this.listboxId = `#${listboxId}`;
      this.$listbox = $(this.listboxId);
      this.$listbox.find('li').attr('role', 'option');

      this.addListener(this.$button, 'click', this.handleClick);
      this.addListener(this.$listbox, 'keydown', this.handleKeypress);
    },

    handleClick: function () {
      const isExpanded = this.$button.attr('aria-expanded') === 'true';

      if (!isExpanded) {
        this.open();
      } else {
        this.close();
      }
    },

    handleKeypress: function (event) {
      event.preventDefault();
      const key = event.keyCode;
      const numberOfOptions = this.getOptions().length;

      // Find index of option in relation to its siblings
      const $selectedOption = this.getSelectedOption();
      const optionSelector = `${this.listboxId} [role="option"]`;
      const currentIndex = $selectedOption.index(optionSelector);

      switch (key) {
        case Garnish.DOWN_KEY: {
          const newIndex = currentIndex + 1;
          if (newIndex < numberOfOptions) {
            this.selectOption(newIndex);
          }
          break;
        }

        case Garnish.UP_KEY: {
          const newIndex = currentIndex - 1;
          if (newIndex >= 0) {
            this.selectOption(newIndex);
          }
          break;
        }

        case Garnish.HOME_KEY: {
          this.selectOption(0);
          break;
        }

        case Garnish.END_KEY: {
          this.selectOption(numberOfOptions - 1);
          break;
        }

        case Garnish.RETURN_KEY:
        case Garnish.ESC_KEY: {
          this.close();
          break;
        }
      }
    },

    getOptions: function () {
      return this.$listbox.find('[role="option"]');
    },

    selectOption: function (index) {
      const $options = this.getOptions();
      const $selected = $options.eq(index);

      $options.each(function() {
        $(this).removeAttr('aria-selected');
      });

      $selected.attr('aria-selected', 'true');
      const optionHtml = $selected.html();
      this.$button.html(optionHtml);
    },

    getSelectedOption: function () {
      return this.$listbox.find('[aria-selected="true"]');
    },

    open: function () {
      this.$listbox.removeClass('hidden');
      this.$button.attr('aria-expanded', 'true');
      this.$listbox.focus();
    },

    close: function () {
      this.$listbox.addClass('hidden');
      this.$button.attr('aria-expanded', 'false');
    },

    addOptions: function ($options) {
      this.$options = this.$options.add($options);
      $options.data('menu', this);

      $options.each(
        function (optionKey, option) {
          $(option).attr({
            role: 'option',
            tabindex: '-1',
            id: this.menuId + '-option-' + optionKey,
          });
        }.bind(this)
      );

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

        if ((align === 'right' && leftClearance >= 0) || rightClearance < 0) {
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

      this.$menuList.attr('aria-hidden', 'false');

      Garnish.uiLayerManager
        .addLayer(this.$container)
        .registerShortcut(Garnish.ESC_KEY, this.hide.bind(this));

      this.addListener(
        Garnish.$scrollContainer,
        'scroll',
        'setPositionRelativeToAnchor'
      );

      this.visible = true;
      this.trigger('show');
    },

    hide: function () {
      if (!this.visible) {
        return;
      }

      this.$menuList.attr('aria-hidden', 'true');

      this.$container.velocity(
        'fadeOut',
        {duration: Garnish.FX_DURATION},
        function () {
          this.$container.detach();
        }.bind(this)
      );

      Garnish.uiLayerManager.removeLayer();
      this.removeListener(Garnish.$scrollContainer, 'scroll');
      this.visible = false;
      this.trigger('hide');
    },

    // selectOption: function (option) {
    //   this.settings.onOptionSelect(option);
    //   this.trigger('optionselect', {selectedOption: option});
    //   this.hide();
    // },

    _alignLeft: function () {
      this.$container.css({
        left: this._anchorOffset.left,
        right: 'auto',
      });
    },

    _alignRight: function () {
      this.$container.css({
        right:
          this._windowWidth - (this._anchorOffset.left + this._anchorWidth),
        left: 'auto',
      });
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
  },
  {
    defaults: {
      anchor: null,
      windowSpacing: 5,
      onOptionSelect: $.noop,
    },
  }
);
