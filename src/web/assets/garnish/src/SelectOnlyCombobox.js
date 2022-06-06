import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Select-Only Combobox
 */
export default Base.extend(
  {
    settings: null,
    visible: false,

    $selectedOption: null,
    $combobox: null,
    $listbox: null,
    $options: null,

    menuId: null,
    searchString: '',
    searchTimeout: null,

    _windowWidth: null,
    _windowHeight: null,
    _windowScrollLeft: null,
    _windowScrollTop: null,

    _comboboxOffset: null,
    _comboboxWidth: null,
    _comboboxHeight: null,
    _comboboxOffsetRight: null,
    _comboboxOffsetBottom: null,

    _listboxWidth: null,
    _listboxHeight: null,

    /**
     * Constructor
     */
    init: function (button, settings) {
      this.setSettings(settings, Garnish.CustomSelect.defaults);

      this.$combobox = $(button);

      // Get listbox ID from button
      this.listboxId = this.$combobox.data('controls');
      this.$listbox = $(`#${this.listboxId}`);
      this.initializeOptions();
      this.setActiveDescendant();

      // Set active item
      this.$selectedOption = this.getSelectedOption();

      this.addListener(this.$combobox, 'click', this.handleButtonClick);
      this.addListener(this.$combobox, 'keydown', this.handleButtonKeypress);

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
      const isExpanded = this.$combobox.attr('aria-expanded') === 'true';

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
      const currentIndex = this.getSelectedOptionIndex();

      switch (key) {
        case Garnish.DOWN_KEY: {
          const newIndex = currentIndex + 1;
          if (newIndex < numberOfOptions) {
            this.updateVisualFocus(newIndex);
          }
          break;
        }

        case Garnish.UP_KEY: {
          const newIndex = currentIndex - 1;
          if (newIndex >= 0) {
            this.updateVisualFocus(newIndex);
          }
          break;
        }

        case Garnish.HOME_KEY: {
          this.updateVisualFocus(0);
          break;
        }

        case Garnish.END_KEY: {
          this.updateVisualFocus(numberOfOptions - 1);
          break;
        }

        case Garnish.RETURN_KEY:
        case Garnish.SPACE_KEY: {
          this.selectOption($selectedOption);
          this.$combobox.focus();
          break;
        }

        case Garnish.ESC_KEY: {
          // Reset initial value then close
          this.selectOption(this.$selectedOption);
          this.$combobox.focus();
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
      let matchIndex;

      this.getOptions().each(
        function (index, option) {
          const compareTo = $(option).text().toLowerCase();

          if (compareTo.startsWith(this.searchString)) {
            matchIndex = index;
            return false;
          }
        }.bind(this)
      );

      if (matchIndex >= 0) {
        this.updateVisualFocus(matchIndex);
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

    updateVisualFocus: function (index) {
      const $options = this.getOptions();
      const $selected = $options.eq(index);

      $options.each(function () {
        $(this).removeAttr('aria-selected');
      });

      $selected.attr('aria-selected', 'true');
      this.setActiveDescendant();
    },

    setActiveDescendant: function () {
      const selectedOptionId = this.getSelectedOptionId();
      this.$listbox.attr('aria-activedescendant', selectedOptionId);
    },

    getSelectedOption: function () {
      return this.$listbox.find('[aria-selected="true"]').first();
    },

    getSelectedOptionIndex: function () {
      const $selectedOption = this.getSelectedOption();
      return this.getOptionIndex($selectedOption);
    },

    getSelectedOptionId: function () {
      return this.getSelectedOption().attr('id');
    },

    open: function () {
      // Move the menu to the end of the DOM
      this.$listbox.appendTo(Garnish.$bod);

      this.$listbox.removeClass('hidden');
      this.$combobox.attr('aria-expanded', 'true');
      this.$listbox.focus();

      if (this.$combobox) {
        this.setPositionRelativeToCombobox();
      }
    },

    close: function () {
      this.$listbox.addClass('hidden');
      this.$combobox.attr('aria-expanded', 'false');
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
        this.updateVisualFocus(ev.currentTarget);
      });
    },

    setPositionRelativeToCombobox: function () {
      this._windowWidth = Garnish.$win.width();
      this._windowHeight = Garnish.$win.height();
      this._windowScrollLeft = Garnish.$win.scrollLeft();
      this._windowScrollTop = Garnish.$win.scrollTop();

      this._comboboxOffset = this.$combobox.offset();
      this._comboboxWidth = this.$combobox.outerWidth();
      this._comboboxHeight = this.$combobox.outerHeight();
      this._comboboxOffsetRight =
        this._comboboxOffset.left + this._comboboxHeight;
      this._comboboxOffsetBottom =
        this._comboboxOffset.top + this._comboboxHeight;

      this.$listbox.css('minWidth', 0);
      this.$listbox.css(
        'minWidth',
        this._comboboxWidth -
          (this.$listbox.outerWidth() - this.$listbox.width())
      );

      this._listboxWidth = this.$listbox.outerWidth();
      this._listboxHeight = this.$listbox.outerHeight();

      // Is there room for the menu below the anchor?
      var topClearance = this._comboboxOffset.top - this._windowScrollTop,
        bottomClearance =
          this._windowHeight +
          this._windowScrollTop -
          this._comboboxOffsetBottom;

      if (
        bottomClearance >= this._listboxHeight ||
        (topClearance < this._listboxHeight && bottomClearance >= topClearance)
      ) {
        this.$listbox.css({
          top: this._comboboxOffsetBottom,
          maxHeight: bottomClearance - this.settings.windowSpacing,
        });
      } else {
        this.$listbox.css({
          top:
            this._comboboxOffset.top -
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
            (this._comboboxOffset.left + this._menuWidth),
          leftClearance = this._comboboxOffsetRight - this._listboxWidth;

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
      delete this._comboboxOffset;
      delete this._comboboxWidth;
      delete this._comboboxHeight;
      delete this._comboboxOffsetRight;
      delete this._comboboxOffsetBottom;
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

    selectOption: function ($option) {
      const previousOption = this.$selectedOption;
      const index = this.getOptionIndex($option);

      this.$selectedOption = $option;
      this.updateVisualFocus(index);

      // Update combobox text
      const optionHtml = $option.html();
      this.$combobox.html(optionHtml);

      // Close
      this.close();
      // If new selected is different from last, trigger change
    },

    // selectOption: function (option) {
    //   this.settings.onOptionSelect(option);
    //   this.trigger('optionselect', {selectedOption: option});
    //   this.hide();
    // },

    _alignLeft: function () {
      this.$listbox.css({
        left: this._comboboxOffset.left,
        right: 'auto',
      });
    },

    _alignRight: function () {
      this.$listbox.css({
        right:
          this._windowWidth - (this._comboboxOffset.left + this._comboboxWidth),
        left: 'auto',
      });
    },

    _alignCenter: function () {
      var left = Math.round(
        this._comboboxOffset.left +
          this._comboboxWidth / 2 -
          this._listboxWidth / 2
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
