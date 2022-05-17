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
    selectedOption: null,

    $button: null,
    $listbox: null,
    $options: null,

    menuId: null,
    searchString: '',
    searchTimeout: null,

    _windowWidth: null,
    _windowHeight: null,
    _windowScrollLeft: null,
    _windowScrollTop: null,

    _buttonOffset: null,
    _buttonWidth: null,
    _buttonHeight: null,
    _buttonOffsetRight: null,
    _buttonOffsetBottom: null,

    _listboxWidth: null,
    _listboxHeight: null,

    /**
     * Constructor
     */
    init: function (button, settings) {
      this.setSettings(settings, Garnish.CustomSelect.defaults);

      this.$button = $(button);

      // Get listbox ID from button
      this.listboxId = this.$button.data('controls');
      this.$listbox = $(`#${this.listboxId}`);
      this.initializeOptions();
      this.setActiveDescendant();

      this.addListener(this.$button, 'click', this.handleButtonClick);
      this.addListener(this.$button, 'keydown', this.handleButtonKeypress);

      this.addListener(this.$listbox, 'click', this.handleListboxClick);
      this.addListener(this.$listbox, 'keydown', this.handleListboxKeypress);
    },

    initializeOptions: function () {
      this.$listbox.find('li').attr('role', 'option');
      const {listboxId} = this;

      this.getOptions().each(function (index) {
        $(this).attr('id', `${listboxId}-option-${index}`);
      });
    },

    handleButtonClick: function () {
      const isExpanded = this.$button.attr('aria-expanded') === 'true';

      if (!isExpanded) {
        this.open();
      } else {
        this.close();
      }
    },

    handleButtonKeypress: function (event) {
      const key = event.keyCode;

      if (key === Garnish.DOWN_KEY || key === Garnish.UP_KEY) {
        event.preventDefault();
        this.open();
      }
    },

    handleListboxClick: function (event) {
      const $option = $(event.target.closest('[role="option"]'));
      this.selectOption($option);
    },

    handleListboxKeypress: function (event) {
      event.preventDefault();
      const key = event.keyCode;
      const numberOfOptions = this.getOptions().length;

      // Find index of option in relation to its siblings
      const $selectedOption = this.getSelectedOption();
      const currentIndex = this.getOptionIndex($selectedOption);

      switch (key) {
        case Garnish.DOWN_KEY: {
          const newIndex = currentIndex + 1;
          if (newIndex < numberOfOptions) {
            this.updateOption(newIndex);
          }
          break;
        }

        case Garnish.UP_KEY: {
          const newIndex = currentIndex - 1;
          if (newIndex >= 0) {
            this.updateOption(newIndex);
          }
          break;
        }

        case Garnish.HOME_KEY: {
          this.updateOption(0);
          break;
        }

        case Garnish.END_KEY: {
          this.updateOption(numberOfOptions - 1);
          break;
        }

        case Garnish.RETURN_KEY:
        case Garnish.SPACE_KEY: {
          this.selectOption($selectedOption);
          this.$button.focus();
          break;
        }

        case Garnish.ESC_KEY: {
          // Reset initial value then close
          this.$button.focus();
          break;
        }
      }

      if (String.fromCharCode(key).match(/(\w|\s)/g)) {
        const character = String.fromCharCode(key).toLowerCase();
        this.searchString += character;
        this.startSearch();
        return;
      }
    },

    startSearch: function () {
      if (!this.searchTimeout) {
        this.searchTimeout = setTimeout(
          function () {
            this.searchOptions();
            this.clearSearch();
          }.bind(this),
          250
        );
      }
    },

    searchOptions: function () {
      const {searchString} = this;
      let matchIndex;

      this.getOptions().each(function (index) {
        const compareTo = $(this).text().toLowerCase();
        if (compareTo.startsWith(searchString)) {
          matchIndex = index;
          return false;
        }
      });

      if (matchIndex) {
        this.updateOption(matchIndex);
      }
    },

    clearSearch: function () {
      this.searchTimeout = null;
      this.searchString = '';
    },

    getOptions: function () {
      return this.$listbox.find('[role="option"]');
    },

    getOptionIndex: function ($option) {
      const optionSelector = `#${this.listboxId} [role="option"]`;
      return $option.index(optionSelector);
    },

    updateOption: function (index) {
      const $options = this.getOptions();
      const $selected = $options.eq(index);

      $options.each(function () {
        $(this).removeAttr('aria-selected');
      });

      $selected.attr('aria-selected', 'true');
      this.setActiveDescendant();
      const optionHtml = $selected.html();
      this.$button.html(optionHtml);
    },

    setActiveDescendant: function () {
      const selectedOptionId = this.getSelectedOptionId();
      this.$listbox.attr('aria-activedescendant', selectedOptionId);
    },

    getSelectedOption: function () {
      return this.$listbox.find('[aria-selected="true"]');
    },

    getSelectedOptionId: function () {
      return this.getSelectedOption().attr('id');
    },

    open: function () {
      this.$listbox.removeClass('hidden');
      this.$button.attr('aria-expanded', 'true');
      this.$listbox.focus();

      if (this.$button) {
        this.setPositionRelativeToButton();
      }
    },

    close: function () {
      this.$listbox.addClass('hidden');
      this.$button.attr('aria-expanded', 'false');
      this.clearSearch();
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
        this.updateOption(ev.currentTarget);
      });
    },

    setPositionRelativeToButton: function () {
      this._windowWidth = Garnish.$win.width();
      this._windowHeight = Garnish.$win.height();
      this._windowScrollLeft = Garnish.$win.scrollLeft();
      this._windowScrollTop = Garnish.$win.scrollTop();

      this._buttonOffset = this.$button.offset();
      this._buttonWidth = this.$button.outerWidth();
      this._buttonHeight = this.$button.outerHeight();
      this._buttonOffsetRight = this._buttonOffset.left + this._buttonHeight;
      this._buttonOffsetBottom = this._buttonOffset.top + this._buttonHeight;

      this.$listbox.css('minWidth', 0);
      this.$listbox.css(
        'minWidth',
        this._buttonWidth - (this.$listbox.outerWidth() - this.$listbox.width())
      );

      this._listboxWidth = this.$listbox.outerWidth();
      this._listboxHeight = this.$listbox.outerHeight();

      // Is there room for the menu below the anchor?
      var topClearance = this._buttonOffset.top - this._windowScrollTop,
        bottomClearance =
          this._windowHeight + this._windowScrollTop - this._buttonOffsetBottom;

      if (
        bottomClearance >= this._listboxHeight ||
        (topClearance < this._listboxHeight && bottomClearance >= topClearance)
      ) {
        this.$listbox.css({
          top: this._buttonOffsetBottom,
          maxHeight: bottomClearance - this.settings.windowSpacing,
        });
      } else {
        this.$listbox.css({
          top:
            this._buttonOffset.top -
            Math.min(
              this._listboxHeight,
              topClearance - this.settings.windowSpacing
            ),
          maxHeight: topClearance - this.settings.windowSpacing,
        });
      }

      // Figure out how we're aliging it
      var align = this.$listbox.data('align');

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
            (this._buttonOffset.left + this._menuWidth),
          leftClearance = this._buttonOffsetRight - this._listboxWidth;

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
      delete this._buttonOffset;
      delete this._buttonWidth;
      delete this._buttonHeight;
      delete this._buttonOffsetRight;
      delete this._buttonOffsetBottom;
      delete this._listboxWidth;
      delete this._listboxHeight;
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

    selectOption: function (option) {
      const index = this.getOptionIndex(option);
      this.updateOption(index);
      this.close();
    },

    // selectOption: function (option) {
    //   this.settings.onOptionSelect(option);
    //   this.trigger('optionselect', {selectedOption: option});
    //   this.hide();
    // },

    _alignLeft: function () {
      this.$listbox.css({
        left: this._buttonOffset.left,
        right: 'auto',
      });
    },

    _alignRight: function () {
      this.$listbox.css({
        right:
          this._windowWidth - (this._buttonOffset.left + this._buttonWidth),
        left: 'auto',
      });
    },

    _alignCenter: function () {
      var left = Math.round(
        this._buttonOffset.left + this._buttonWidth / 2 - this._listboxWidth / 2
      );

      if (left < 0) {
        left = 0;
      }

      this.$listbox.css('left', left);
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
