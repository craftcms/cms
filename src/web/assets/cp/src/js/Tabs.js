/** global: Craft */
/** global: Garnish */
/**
 * Tab manager
 */
Craft.Tabs = Garnish.Base.extend({
  $container: null,
  $tablist: null,
  $menuBtn: null,
  $tabs: null,
  $firstTab: null,
  $lastTab: null,
  $selectedTab: null,
  $focusableTab: null,
  menu: null,

  init: function (container) {
    this.$container = $(container);
    this.$tablist = this.$container.find('> [role="tablist"]:first');
    this.$tabs = this.$tablist.find('> [role="tab"]');
    this.$firstTab = this.$tabs.first();
    this.$lastTab = this.$tabs.last();
    this.$selectedTab = this.$tabs.filter('.sel:first');
    this.$focusableTab = this.$tabs.filter('[tabindex=0]:first');
    this.$menuBtn = this.$container.find('> .menubtn:first').disclosureMenu();
    this.menu = this.$menuBtn.data('trigger').$container;

    // Is there already a tab manager?
    if (this.$container.data('tabs')) {
      console.warn('Double-instantiating a tab manager on an element');
      this.$container.data('tabs').destroy();
    }

    this.$container.data('tabs', this);

    for (let i = 0; i < this.$tabs.length; i++) {
      const $a = this.$tabs.eq(i);

      // Does it link to an anchor?
      const href = $a.attr('href');
      if (href && href.charAt(0) === '#') {
        this.addListener($a, 'keydown', (ev) => {
          if ([Garnish.SPACE_KEY, Garnish.RETURN_KEY].includes(ev.keyCode)) {
            ev.preventDefault();
            this.selectTab(ev.currentTarget, true);
          }
        });
        this.addListener($a, 'click', (ev) => {
          ev.preventDefault();
          this.selectTab(ev.currentTarget, true);
        });
      }

      this.addListener($a, 'keydown', (ev) => {
        let $tab;
        if (
          [Garnish.LEFT_KEY, Garnish.RIGHT_KEY].includes(ev.keyCode) &&
          $.contains(this.$tablist[0], ev.currentTarget)
        ) {
          if (
            ev.keyCode ===
            (Craft.orientation === 'ltr' ? Garnish.LEFT_KEY : Garnish.RIGHT_KEY)
          ) {
            $tab = $(ev.currentTarget).prevAll(
              '[role="tab"]:not(.hidden):first'
            );
            $tab = $tab.length ? $tab : this.$lastTab;
          } else {
            $tab = $(ev.currentTarget).nextAll(
              '[role="tab"]:not(.hidden):first'
            );
            $tab = $tab.length ? $tab : this.$firstTab;
          }
        } else if (
          ev.keyCode === Garnish.HOME_KEY ||
          ev.keyCode === Garnish.END_KEY
        ) {
          $tab =
            ev.keyCode === Garnish.HOME_KEY ? this.$firstTab : this.$lastTab;
        }

        if ($tab) {
          ev.preventDefault();
          this.makeTabFocusable($tab);
          $tab.focus();
          this.scrollToTab($tab);
        }
      });
    }

    this.updateMenuBtn();

    Garnish.$win.on('resize', () => {
      this.updateMenuBtn();
    });

    const $options = this.getMenuOptions();
    this.addListener($options, 'activate', (ev) => {
      const $option = $(ev.currentTarget);

      // Prevent menu options from updating the URL
      const href = $option.attr('href');
      if (href && href.charAt(0) === '#') {
        ev.preventDefault();
      }

      // Select tab
      this.selectTab($option.data('id'));
      this.$menuBtn.data('trigger').hide();
    });
  },

  getMenuOptions: function () {
    return this.menu.find('a');
  },

  getSelectedTabIndex: function () {
    return this.$tabs.index(this.$selectedTab);
  },

  selectTab: function (tab, focusTab = true) {
    const $tab = this._getTab(tab);

    if ($tab[0] === this.$selectedTab[0]) {
      return;
    }

    this.deselectTab();
    this.$selectedTab = $tab.addClass('sel').attr('aria-selected', 'true');
    this.makeTabFocusable($tab);

    if (focusTab) {
      $tab.focus();
    }

    this.scrollToTab($tab);

    this.getMenuOptions().removeClass('sel').removeAttr('aria-current');
    this.getMenuOptions()
      .filter(`[data-id="${$tab.data('id')}"]`)
      .addClass('sel')
      .attr('aria-current', 'true');

    this.trigger('selectTab', {
      $tab: $tab,
    });

    $('#content').trigger('scroll');

    const $slideoutContainer = $tab.closest('.slideout-container');
    if ($slideoutContainer.length) {
      $slideoutContainer.find('.so-content').trigger('scroll');
    }
  },

  deselectTab: function () {
    const $tab = this.$selectedTab
      .removeClass('sel')
      .attr('aria-selected', 'false');
    this.$selectedTab = null;

    this.trigger('deselectTab', {
      $tab: $tab,
    });
  },

  makeTabFocusable: function (tab) {
    const $tab = this._getTab(tab);

    if ($tab[0] === this.$focusableTab[0]) {
      return;
    }

    this.$focusableTab.attr('tabindex', '-1');
    this.$focusableTab = $tab.attr('tabindex', '0');
  },

  scrollToTab: function (tab) {
    const $tab = this._getTab(tab);
    const scrollLeft = this.$tablist.scrollLeft();
    const tabOffset = $tab.offset().left;
    const elemScrollOffset = tabOffset - this.$tablist.offset().left;
    let targetScrollLeft = false;

    // Is the tab hidden on the left?
    if (elemScrollOffset < 0) {
      targetScrollLeft = scrollLeft + elemScrollOffset - 24;
    } else {
      const tabWidth = $tab.outerWidth();
      const ulWidth = this.$tablist.prop('clientWidth');

      // Is it hidden to the right?
      if (elemScrollOffset + tabWidth > ulWidth) {
        targetScrollLeft =
          scrollLeft + (elemScrollOffset - (ulWidth - tabWidth)) + 24;
      }
    }

    if (targetScrollLeft !== false) {
      this.$tablist.scrollLeft(targetScrollLeft);
    }
  },

  updateMenuBtn: function () {
    if (
      Math.floor(this.$tablist.prop('scrollWidth') - 48) >
      this.$container.prop('clientWidth')
    ) {
      this.$tablist.addClass('scrollable');
      this.$menuBtn.removeClass('hidden');
    } else {
      this.$tablist.removeClass('scrollable');
      this.$menuBtn.addClass('hidden');
    }
  },

  _getTab: function (tab) {
    if (typeof tab === 'number') {
      tab = this.$tabs.eq(tab);
    }

    if (tab instanceof jQuery) {
      return tab;
    }

    if (tab instanceof HTMLElement) {
      return $(tab);
    }

    if (typeof tab !== 'string') {
      throw 'Invalid tab ID';
    }

    const $tab = this.$tabs.filter(`[data-id="${tab}"]`);

    if (!$tab.length) {
      throw `Invalid tab ID: ${tab}`;
    }

    return $tab;
  },

  destroy: function () {
    this.$container.removeData('tabs');
    this.base();
  },
});
