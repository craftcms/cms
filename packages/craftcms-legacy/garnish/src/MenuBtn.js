/* jshint esversion: 6, strict: false */
import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * Menu Button
 */
export default Base.extend(
  {
    $btn: null,
    menu: null,
    showingMenu: false,
    disabled: true,
    observer: null,
    searchStr: '',
    clearSearchStrTimeout: null,

    /**
     * Constructor
     */
    init: function (btn, menu, settings) {
      // Param mapping
      if (typeof settings === 'undefined' && $.isPlainObject(menu)) {
        // (btn, settings)
        settings = menu;
        menu = null;
      }

      this.$btn = $(btn);

      if (!this.$btn.length) {
        console.warn('Menu button instantiated without a DOM element.');
        return;
      }

      let $menu;

      // Is this already a menu button?
      if (this.$btn.data('menubtn')) {
        // Grab the old MenuBtn's menu container
        if (!menu) {
          $menu = this.$btn.data('menubtn').menu.$container;
        }

        console.warn('Double-instantiating a menu button on an element');
        this.$btn.data('menubtn').destroy();
      } else if (!menu) {
        $menu = this.$btn.next('.menu').detach();
      }

      this.$btn.data('menubtn', this);

      this.setSettings(settings, Garnish.MenuBtn.defaults);

      this.menu = menu || new Garnish.CustomSelect($menu);
      this.menu.$anchor = $(this.settings.menuAnchor || this.$btn);
      this.menu.on(
        'optionselect',
        function (ev) {
          this.onOptionSelect(ev.selectedOption);
        }.bind(this)
      );
      this.menu.on('hide', () => {
        this.clearSearchStr();
      });
      this.menu.on('show', () => {
        this.clearSearchStr();
      });

      this.$btn.attr({
        role: 'combobox',
        'aria-controls': this.menu.menuId,
        'aria-haspopup': 'listbox',
        'aria-expanded': 'false',
      });

      // If no label is set on the listbox, set one based on the combobox label
      const comboboxLabel = this.$btn.attr('aria-labelledby');

      if (!this.menu.$container.attr('aria-labelledby') && comboboxLabel) {
        this.menu.$container.attr('aria-labelledby', comboboxLabel);
      }

      this.menu.on('hide', this.onMenuHide.bind(this));
      this.addListener(this.$btn, 'mousedown', 'onMouseDown');
      this.addListener(this.$btn, 'keydown', 'onKeyDown');
      this.addListener(this.$btn, 'blur', 'onBlur');

      this.observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
          if (
            mutation.type === 'attributes' &&
            mutation.attributeName === 'disabled'
          ) {
            this.handleStatusChange();
            break;
          }
        }
      });

      this.observer.observe(this.$btn[0], {attributes: true});

      this.handleStatusChange();
    },

    onBlur: function () {
      if (this.showingMenu) {
        Garnish.requestAnimationFrame(
          function () {
            if (
              !$.contains(this.menu.$container.get(0), document.activeElement)
            ) {
              this.hideMenu();
            }
          }.bind(this)
        );
      }
    },

    onKeyDown: function (ev) {
      if (Garnish.isCtrlKeyPressed(ev)) {
        return;
      }

      // Searching for an option?
      if (
        ev.key &&
        (ev.key.match(/^[^ ]$/) || (this.searchStr.length && ev.key === ' '))
      ) {
        // show the menu and set visual focus to the first matching option
        let $option;

        if (!this.showingMenu) {
          this.showMenu();
          // go with the selected option by default
          $option = this.menu.$options.filter('.sel:first');
          if ($option.length === 0) {
            $option = this.menu.$options.first();
          }
        }

        // see if there's a matching option
        this.searchStr += ev.key.toLowerCase();
        for (let i = 0; i < this.menu.$options.length; i++) {
          const $o = this.menu.$options.eq(i);
          if (typeof $o.data('searchText') === 'undefined') {
            // clone without nested SVGs
            const $clone = $o.clone();
            $clone.find('svg').remove();
            $o.data('searchText', $clone.text().toLowerCase().trimStart());
          }
          if ($o.data('searchText').startsWith(this.searchStr)) {
            $option = $o;
            break;
          }
        }

        if ($option && $option.length) {
          this.focusOption($option);
        }

        // update the timeout
        if (this.clearSearchStrTimeout) {
          clearTimeout(this.clearSearchStrTimeout);
        }
        this.clearSearchStrTimeout = setTimeout(() => {
          this.clearSearchStr();
        }, 1000);

        return;
      }

      if (this.showingMenu) {
        switch (ev.keyCode) {
          case Garnish.RETURN_KEY:
          case Garnish.SPACE_KEY:
          case Garnish.TAB_KEY: {
            // select the visually-focused option and close the menu
            if (ev.keyCode !== Garnish.TAB_KEY) {
              ev.preventDefault();
            }
            const $currentOption = this.menu.$options.filter('.hover');
            if ($currentOption.length > 0) {
              $currentOption.get(0).click();
            } else {
              this.hideMenu();
            }
            break;
          }

          case Garnish.UP_KEY:
          case Garnish.PAGE_UP_KEY: {
            // move visual focus up
            ev.preventDefault();
            const dist = ev.keyCode === Garnish.UP_KEY ? 1 : 10;
            this.moveFocusUp(dist);
            break;
          }

          case Garnish.DOWN_KEY:
          case Garnish.PAGE_DOWN_KEY: {
            // move visual focus down
            ev.preventDefault();
            const dist = ev.keyCode === Garnish.DOWN_KEY ? 1 : 10;
            this.moveFocusDown(dist);
            break;
          }

          case Garnish.HOME_KEY: {
            // move visual focus to the first option
            ev.preventDefault();
            this.focusFirstOption();
            break;
          }

          case Garnish.END_KEY: {
            // move visual focus to the last option
            ev.preventDefault();
            this.focusLastOption();
            break;
          }
        }
      } else {
        switch (ev.keyCode) {
          case Garnish.RETURN_KEY:
          case Garnish.SPACE_KEY:
          case Garnish.DOWN_KEY: {
            // show the menu and set visual focus to the selected option
            ev.preventDefault();
            this.showMenu();
            this.focusSelectedOption();
            break;
          }

          case Garnish.UP_KEY:
          case Garnish.HOME_KEY: {
            // show the menu and set visual focus to the first option
            ev.preventDefault();
            this.showMenu();
            this.focusFirstOption();
            break;
          }

          case Garnish.END_KEY: {
            // show the menu and set visual focus to the last option
            ev.preventDefault();
            this.showMenu();
            this.focusLastOption();
            break;
          }
        }
      }
    },

    clearSearchStr: function () {
      this.searchStr = '';
      if (this.clearSearchStrTimeout) {
        clearTimeout(this.clearSearchStrTimeout);
        this.clearSearchStrTimeout = null;
      }
    },

    focusOption: function ($option) {
      if ($option.hasClass('hover')) {
        return;
      }

      this.menu.$options.removeClass('hover');
      this.menu.$ariaOptions.attr('aria-selected', 'false');

      $option.addClass('hover');
      this.$btn.attr('aria-activedescendant', $option.parent('li').attr('id'));

      Garnish.scrollContainerToElement(this.menu.$container, $option);
    },

    focusSelectedOption: function () {
      let $option = this.menu.$options.filter('.sel:first');
      if ($option.length) {
        this.focusOption($option);
      } else {
        this.focusFirstOption();
      }
    },

    focusFirstOption: function () {
      const $option = this.menu.$options.first();
      this.focusOption($option);
    },

    focusLastOption: function () {
      const $option = this.menu.$options.last();
      this.focusOption($option);
    },

    /**
     * @param {number} [dist=1]
     */
    moveFocusUp: function (dist) {
      const $focusedOption = this.menu.$options.filter('.hover');
      if ($focusedOption.length) {
        const index = this.menu.$options.index($focusedOption[0]);
        let $option = this.menu.$options.eq(Math.max(index - dist, 0));
        while ($option.hasClass('disabled') && index - dist >= 0) {
          dist++;
          $option = this.menu.$options.eq(Math.max(index - dist, 0));
        }
        this.focusOption($option);
      } else {
        this.focusFirstOption();
      }
    },

    /**
     * @param {number} [dist=1]
     */
    moveFocusDown: function (dist) {
      const $focusedOption = this.menu.$options.filter('.hover');
      if ($focusedOption.length) {
        const index = this.menu.$options.index($focusedOption[0]);
        let $option = this.menu.$options.eq(
          Math.min(index + dist, this.menu.$options.length - 1)
        );
        while (
          $option.hasClass('disabled') &&
          index + dist <= this.menu.$options.length - 1
        ) {
          dist++;
          $option = this.menu.$options.eq(
            Math.min(index + dist, this.menu.$options.length - 1)
          );
        }
        this.focusOption($option);
      } else {
        this.focusFirstOption();
      }
    },

    onMouseDown: function (ev) {
      if (!Garnish.isPrimaryClick(ev) || ev.target.nodeName === 'INPUT') {
        return;
      }

      ev.preventDefault();

      if (this.showingMenu) {
        this.hideMenu();
      } else {
        this.showMenu();
      }
    },

    showMenu: function () {
      if (this.disabled) {
        return;
      }

      this.menu.show();
      this.$btn.addClass('active');
      this.$btn.focus();
      this.$btn.attr('aria-expanded', 'true');

      this.showingMenu = true;

      setTimeout(
        function () {
          this.addListener(Garnish.$doc, 'mousedown', 'onMouseDown');
        }.bind(this),
        1
      );
    },

    hideMenu: function () {
      this.menu.hide();
    },

    onMenuHide: function () {
      this.$btn.removeClass('active');
      this.$btn.attr({
        'aria-expanded': 'false',
        'aria-activedescendant': null,
      });
      this.showingMenu = false;

      this.removeListener(Garnish.$doc, 'mousedown');
    },

    onOptionSelect: function (option) {
      this.settings.onOptionSelect(option);
      this.trigger('optionSelect', {option: option});
    },

    enable: function () {
      if (!this.$btn) {
        return;
      }

      this.$btn.removeAttr('disabled');
    },

    disable: function () {
      if (!this.$btn) {
        return;
      }

      this.$btn.attr('disabled', 'disabled');
    },

    handleStatusChange: function () {
      if (!this.$btn) {
        return;
      }

      if (
        Garnish.hasAttr(this.$btn[0], 'disabled') ||
        this.$btn.attr('aria-disabled') === 'true'
      ) {
        this.disabled = true;
        this.$btn.addClass('disabled');
      } else {
        this.disabled = false;
        this.$btn.removeClass('disabled');
      }
    },

    /**
     * Destroy
     */
    destroy: function () {
      this.menu.destroy();
      this.$btn.removeData('menubtn');
      this.observer.disconnect();
      this.observer = null;
      this.base();
    },
  },
  {
    defaults: {
      menuAnchor: null,
      onOptionSelect: $.noop,
    },
  }
);
