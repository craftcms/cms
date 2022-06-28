/** global: Craft */
/** global: Garnish */
/**
 * CP class
 */
Craft.CP = Garnish.Base.extend(
  {
    authManager: null,

    $nav: null,
    $navToggle: null,
    $globalSidebar: null,
    $globalContainer: null,
    $mainContainer: null,
    $alerts: null,
    $crumbs: null,
    $breadcrumbList: null,
    $breadcrumbItems: null,
    $notificationContainer: null,
    $main: null,
    $primaryForm: null,
    $headerContainer: null,
    $header: null,
    $mainContent: null,
    $details: null,
    $sidebarContainer: null,
    $sidebarToggle: null,
    $sidebar: null,
    $contentContainer: null,
    $edition: null,

    $confirmUnloadForms: null,
    $deltaForms: null,
    $collapsibleTables: null,

    isMobile: null,
    fixedHeader: false,

    breadcrumbListWidth: 0,
    breadcrumbDisclosureItem: `<li class="breadcrumb-toggle-wrapper" data-disclosure-item><button data-disclosure-trigger aria-controls="breadcrumb-disclosure" aria-haspopup="true">${Craft.t(
      'app',
      'More…'
    )}</button><div id="breadcrumb-disclosure" class="menu menu--disclosure" data-disclosure-menu><ul></ul></div></li>`,

    tabManager: null,

    enableQueue: true,
    totalJobs: 0,
    jobInfo: null,
    displayedJobInfo: null,
    displayedJobInfoUnchanged: 1,
    trackJobProgressTimeout: null,
    jobProgressIcon: null,

    checkingForUpdates: false,
    forcingRefreshOnUpdatesCheck: false,
    includingDetailsOnUpdatesCheck: false,
    checkForUpdatesCallbacks: null,

    resizeTimeout: null,

    init: function () {
      // Is this session going to expire?
      if (Craft.remainingSessionTime !== 0) {
        this.authManager = new Craft.AuthManager();
      }

      // Find all the key elements
      this.$nav = $('#nav');
      this.$navToggle = $('#primary-nav-toggle');
      this.$globalSidebar = $('#global-sidebar');
      this.$globalContainer = $('#global-container');
      this.$mainContainer = $('#main-container');
      this.$alerts = $('#alerts');
      this.$crumbs = $('#crumbs');
      this.$breadcrumbList = $('.breadcrumb-list');
      this.$breadcrumbItems = $('.breadcrumb-list li');
      this.$notificationContainer = $('#notifications');
      this.$main = $('#main');
      this.$primaryForm = $('#main-form');
      this.$headerContainer = $('#header-container');
      this.$header = $('#header');
      this.$mainContent = $('#main-content');
      this.$details = $('#details');
      this.$sidebarContainer = $('#sidebar-container');
      this.$sidebarToggle = $('#sidebar-toggle');
      this.$sidebar = $('#sidebar');
      this.$contentContainer = $('#content-container');
      this.$collapsibleTables = $('table.collapsible');

      this.isMobile = Garnish.isMobileBrowser();

      this.updateContentHeading();

      // Swap any instruction text with info icons
      let $allInstructions = this.$details.find(
        '.meta > .field > .instructions'
      );

      for (let i = 0; i < $allInstructions.length; i++) {
        let $instructions = $allInstructions.eq(i);
        let $label = $instructions.siblings('.heading').children('label');
        $('<span/>', {
          class: 'info',
          html: $instructions.children().html(),
        }).appendTo($label);
        $instructions.remove();
      }

      if (!this.isMobile && this.$header.length) {
        this.addListener(Garnish.$win, 'scroll', 'updateFixedHeader');
        this.updateFixedHeader();
      }

      Garnish.$doc.ready(() => {
        // Update responsive tables on window resize
        this.addListener(Garnish.$win, 'resize', (ev) => {
          // Ignore element resizes
          if (ev.target === window) {
            this.handleWindowResize();

            clearTimeout(this.resizeTimeout);
            var cp = this;
            this.resizeTimeout = setTimeout(function () {
              cp.setSidebarNavAttributes();
            }, 100);
          }
        });
        this.handleWindowResize();
        this.setSidebarNavAttributes();

        // Fade the notification out two seconds after page load
        var $errorNotifications =
            this.$notificationContainer.children('.error'),
          $otherNotifications =
            this.$notificationContainer.children(':not(.error)');

        $errorNotifications
          .delay(Craft.CP.notificationDuration * 2)
          .velocity('fadeOut');
        $otherNotifications
          .delay(Craft.CP.notificationDuration)
          .velocity('fadeOut');

        // Wait a frame before initializing any confirm-unload forms,
        // so other JS that runs on ready() has a chance to initialize
        Garnish.requestAnimationFrame(this.initSpecialForms.bind(this));
      });

      // Alerts
      if (this.$alerts.length) {
        this.initAlerts();
      }

      // Toggles
      this.addListener(this.$navToggle, 'click', 'toggleNav');
      this.addListener(this.$sidebarToggle, 'click', 'toggleSidebar');

      // Does this page have a primary form?
      if (!this.$primaryForm.length) {
        this.$primaryForm = $('form[data-saveshortcut]:first');
      }

      // Does the primary form support the save shortcut?
      if (
        this.$primaryForm.length &&
        Garnish.hasAttr(this.$primaryForm, 'data-saveshortcut')
      ) {
        let shortcuts = [];
        let actions = this.$primaryForm.data('actions');
        if (typeof actions === 'undefined') {
          shortcuts.push([
            {
              keyCode: Garnish.S_KEY,
              ctrl: true,
            },
            {
              redirect: this.$primaryForm.data('saveshortcut-redirect'),
              retainScroll: Garnish.hasAttr(
                this.$primaryForm,
                'saveshortcut-scroll'
              ),
            },
          ]);
        } else {
          for (let i = 0; i < actions.length; i++) {
            let action = actions[i];
            if (!action.shortcut) {
              continue;
            }
            shortcuts.push([
              {
                keyCode: Garnish.S_KEY,
                ctrl: true,
                shift: !!action.shift,
              },
              {
                action: action.action,
                redirect: action.redirect,
                confirm: action.confirm,
                params: action.params,
                data: action.data,
                retainScroll: action.retainScroll,
              },
            ]);
          }
        }
        for (let i = 0; i < shortcuts.length; i++) {
          Garnish.uiLayerManager.registerShortcut(shortcuts[i][0], () => {
            this.submitPrimaryForm(shortcuts[i][1]);
          });
        }
      }

      this.initTabs();

      if (this.tabManager) {
        if (window.LOCATION_HASH) {
          const $tab = this.tabManager.$tabs.filter(
            `[href="#${window.LOCATION_HASH}"]`
          );
          if ($tab.length) {
            this.tabManager.selectTab($tab);
          }
        }
      }

      // Should we match the previous scroll position?
      let scrollY = Craft.getLocalStorage('scrollY');
      if (typeof scrollY !== 'undefined') {
        Craft.removeLocalStorage('scrollY');
        Garnish.$doc.ready(() => {
          Garnish.requestAnimationFrame(() => {
            window.scrollTo(0, scrollY);
          });
        });
      }

      if ($.isTouchCapable()) {
        this.$mainContainer.on(
          'focus',
          'input, textarea, .focusable-input',
          this._handleInputFocus.bind(this)
        );
        this.$mainContainer.on(
          'blur',
          'input, textarea, .focusable-input',
          this._handleInputBlur.bind(this)
        );
      }

      // Announcements HUD
      if (Craft.announcements.length) {
        let $btn = $('#announcements-btn').removeClass('hidden');
        const hasUnreads = Craft.announcements.some((a) => a.unread);
        if (hasUnreads) {
          $btn.addClass('unread');
        }
        let hud;
        this.addListener($btn, 'click', () => {
          if (!hud) {
            let contents = '';
            Craft.announcements.forEach((a) => {
              contents +=
                `<div class="announcement ${a.unread ? 'unread' : ''}">` +
                '<div class="announcement-label-container">' +
                `<div class="announcement-icon">${a.icon}</div>` +
                `<div class="announcement-label">${a.label}</div>` +
                '</div>' +
                `<h2>${a.heading}</h2>` +
                `<p>${a.body}</p>` +
                '</div>';
            });
            hud = new Garnish.HUD(
              $btn,
              `<div id="announcements">${contents}</div>`,
              {
                onShow: () => {
                  $btn.addClass('active');
                },
                onHide: () => {
                  $btn.removeClass('active');
                },
              }
            );

            // Open outbound links in new windows
            $('a', hud.$main).each(function () {
              if (
                this.hostname.length &&
                this.hostname !== location.hostname &&
                typeof $(this).attr('target') === 'undefined'
              ) {
                $(this).attr('rel', 'noopener').attr('target', '_blank');
              }
            });

            if (hasUnreads) {
              $btn.removeClass('unread');
              Craft.sendActionRequest(
                'POST',
                'users/mark-announcements-as-read',
                {
                  data: {
                    ids: Craft.announcements.map((a) => a.id),
                  },
                }
              );
            }
          } else {
            hud.show();
          }
        });
      }
    },

    get $contentHeader() {
      const $contentHeader = $('#content-header');
      if ($contentHeader.length) {
        return $contentHeader;
      }
      return $('<header/>', {
        id: 'content-header',
        class: 'pane-header',
      }).prependTo($('#content'));
    },

    get $noticeContainer() {
      const $noticeContainer = $('#content-notice');
      if ($noticeContainer.length) {
        return $noticeContainer;
      }
      return $('<div id="content-notice"/>')
        .attr('role', 'status')
        .prependTo(this.$contentHeader);
    },

    initSpecialForms: function () {
      // Look for forms that we should watch for changes on
      this.$confirmUnloadForms = $('form[data-confirm-unload]');
      this.$deltaForms = $('form[data-delta]');

      if (!this.$confirmUnloadForms.length) {
        return;
      }

      const $forms = this.$confirmUnloadForms.add(this.$deltaForms);

      for (let i = 0; i < $forms.length; i++) {
        const $form = $forms.eq(i);
        let serialized;
        if (!$form.data('initialSerializedValue')) {
          if (typeof $form.data('serializer') === 'function') {
            serialized = $form.data('serializer')();
          } else {
            serialized = $form.serialize();
          }
          $form.data('initialSerializedValue', serialized);
        }
        this.addListener($form, 'submit', function (ev) {
          if (Garnish.hasAttr($form, 'data-confirm-unload')) {
            this.removeListener(Garnish.$win, 'beforeunload');
          }
          if (Garnish.hasAttr($form, 'data-delta')) {
            ev.preventDefault();
            let serialized;
            if (typeof $form.data('serializer') === 'function') {
              serialized = $form.data('serializer')();
            } else {
              serialized = $form.serialize();
            }
            const data = Craft.findDeltaData(
              $form.data('initialSerializedValue'),
              serialized,
              $form.data('delta-names'),
              null,
              $form.data('initial-delta-values'),
              $form.data('modified-delta-names')
            );
            Craft.createForm(data).appendTo(Garnish.$bod).submit();
          }
        });
      }

      this.addListener(Garnish.$win, 'beforeunload', function (ev) {
        let confirmUnload = false;
        if (
          typeof Craft.livePreview !== 'undefined' &&
          Craft.livePreview.inPreviewMode
        ) {
          confirmUnload = true;
        } else {
          for (let i = 0; i < this.$confirmUnloadForms.length; i++) {
            const $form = this.$confirmUnloadForms.eq(i);
            let serialized;
            if (typeof $form.data('serializer') === 'function') {
              serialized = $form.data('serializer')();
            } else {
              serialized = $form.serialize();
            }
            if ($form.data('initialSerializedValue') !== serialized) {
              confirmUnload = true;
              break;
            }
          }
        }

        if (confirmUnload) {
          var message = Craft.t(
            'app',
            'Any changes will be lost if you leave this page.'
          );

          if (ev) {
            ev.originalEvent.returnValue = message;
          } else {
            window.event.returnValue = message;
          }

          return message;
        }
      });
    },

    _handleInputFocus: function () {
      this.updateFixedHeader();
    },

    _handleInputBlur: function () {
      this.updateFixedHeader();
    },

    /**
     * Submits a form.
     * @param {Object} [options]
     * @param {string} [options.action] The `action` param value override
     * @param {string} [options.redirect] The `redirect` param value override
     * @param {string} [options.confirm] A confirmation message that should be shown to the user before submit
     * @param {Object} [options.params] Additional params that should be added to the form, defined as name/value pairs
     * @param {Object} [options.data] Additional data to be passed to the submit event
     * @param {boolean} [options.retainScroll] Whether the scroll position should be stored and reapplied on the next page load
     */
    submitPrimaryForm: function (options) {
      // Give other stuff on the page a chance to prepare
      this.trigger('beforeSaveShortcut');

      if (typeof options !== 'object' || !$.isPlainObject(options)) {
        options = {};
      }

      if (!options.redirect) {
        options.redirect = this.$primaryForm.data('saveshortcut-redirect');
      }

      if (!options.data) {
        options.data = {};
      }
      options.data.saveShortcut = true;

      Craft.submitForm(this.$primaryForm, options);
    },

    updateSidebarMenuLabel: function () {
      this.updateContentHeading();
    },

    updateContentHeading: function () {
      const $item = this.$sidebar.find('a.sel:first');
      const $label = $item.children('.label');
      $('#content-heading').text($label.length ? $label.text() : $item.text());
      Garnish.$bod.removeClass('showing-sidebar');
    },

    toggleNav: function () {
      const isExpanded = this.navIsExpanded();

      if (isExpanded === null) return;

      if (isExpanded) {
        this.disableGlobalSidebarLinks();
        this.$navToggle.focus();
        this.$navToggle.attr('aria-expanded', 'false');
        Garnish.$bod.removeClass('showing-nav');
      } else {
        this.enableGlobalSidebarLinks();
        this.$globalSidebar.find(':focusable')[0].focus();
        this.$navToggle.attr('aria-expanded', 'true');
        Garnish.$bod.addClass('showing-nav');
      }
    },

    enableGlobalSidebarLinks: function () {
      const focusableItems = this.$globalSidebar.find(':focusable');

      $(focusableItems).each(function () {
        $(this).attr('tabindex', '0');
      });
    },

    disableGlobalSidebarLinks: function () {
      const focusableItems = this.$globalSidebar.find(':focusable');

      $(focusableItems).each(function () {
        $(this).attr('tabindex', '-1');
      });
    },

    setSidebarNavAttributes: function () {
      const isExpanded = this.navIsExpanded();

      if (isExpanded === null) return;

      if (!isExpanded) {
        this.disableGlobalSidebarLinks();
      } else {
        this.enableGlobalSidebarLinks();
      }
    },

    navIsExpanded: function () {
      if (!this.$globalSidebar[0]) return null;

      const isAlwaysVisible = getComputedStyle(this.$globalSidebar[0])
        .getPropertyValue('--is-always-visible')
        .trim();

      return (
        this.$navToggle.attr('aria-expanded') === 'true' ||
        isAlwaysVisible === 'true'
      );
    },

    toggleSidebar: function () {
      const expanded = this.$sidebarToggle.attr('aria-expanded') === 'true';
      const newState = expanded ? 'false' : 'true';
      this.$sidebarToggle.attr('aria-expanded', newState);
      Garnish.$bod.toggleClass('showing-sidebar');
    },

    initTabs: function () {
      if (this.tabManager) {
        this.tabManager.destroy();
        this.tabManager = null;
      }

      const $tabs = $('#tabs');
      if (!$tabs.length) {
        return;
      }

      this.tabManager = new Craft.Tabs($tabs);

      this.tabManager.on('selectTab', (ev) => {
        const href = ev.$tab.attr('href');

        // Show its content area
        if (href && href.charAt(0) === '#') {
          $(href).removeClass('hidden');
        }

        // Trigger a resize event to update any UI components that are listening for it
        Garnish.$win.trigger('resize');

        // Fixes Redactor fixed toolbars on previously hidden panes
        Garnish.$doc.trigger('scroll');

        // If there is a revision menu, set its links to this tab ID
        if (href && href.charAt(0) === '#') {
          const menuBtn = $('#context-btn').menubtn().data('menubtn');
          if (menuBtn) {
            for (let i = 0; i < menuBtn.menu.$options.length; i++) {
              let a = menuBtn.menu.$options[i];
              if (a.href) {
                a.href = a.href.match(/^[^#]*/)[0] + href;
              }
            }
          }
        }

        if (typeof history !== 'undefined') {
          // Delay changing the hash so it doesn't cause the browser to jump on page load
          Garnish.requestAnimationFrame(() => {
            history.replaceState(undefined, undefined, href);
          });
        }
      });

      this.tabManager.on('deselectTab', (ev) => {
        const href = ev.$tab.attr('href');
        if (href && href.charAt(0) === '#') {
          // Hide its content area
          $(ev.$tab.attr('href')).addClass('hidden');
        }
      });
    },

    updateTabs: function (tabs) {
      if (tabs) {
        const $tabContainer = $(tabs).attr('id', 'tabs');
        if (this.tabManager) {
          this.tabManager.$container.replaceWith($tabContainer);
        } else {
          $tabContainer.appendTo(this.$contentHeader);
        }
        this.initTabs();
      } else if (this.tabManager) {
        if (this.tabManager.$container.siblings().length) {
          this.tabManager.$container.remove();
        } else {
          this.tabManager.$container.parent().remove();
        }
        this.tabManager.destroy();
        this.tabManager = null;
      }
    },

    /**
     * @deprecated in 3.7.0
     */
    get $tabsContainer() {
      return this.tabManager ? this.tabManager.$container : undefined;
    },
    /**
     * @deprecated in 3.7.0
     */
    get $tabsList() {
      return this.tabManager ? this.tabManager.$tablist : undefined;
    },
    /**
     * @deprecated in 3.7.0
     */
    get $tabs() {
      return this.tabManager ? this.tabManager.$tablist.find('> a') : undefined;
    },
    /**
     * @deprecated in 3.7.0
     */
    get $selectedTab() {
      return this.tabManager ? this.tabManager.$selectedTab : undefined;
    },
    /**
     * @deprecated in 3.7.0
     */
    get selectedTabIndex() {
      return this.tabManager
        ? this.tabManager.$tabs.index(this.tabManager.$selectedTab)
        : undefined;
    },
    /**
     * @deprecated in 3.7.0
     */
    get $focusableTab() {
      return this.tabManager ? this.tabManager.$focusableTab : undefined;
    },
    /**
     * @param {object} tab
     * @deprecated in 3.7.0
     */
    selectTab: function (tab) {
      if (this.tabManager) {
        this.tabManager.selectTab(tab);
      }
    },
    /**
     * @deprecated in 3.7.0
     */
    deselectTab: function () {
      if (this.tabManager) {
        this.tabManager.deselectTab();
      }
    },

    handleWindowResize: function () {
      this.updateResponsiveTables();
      this.handleBreadcrumbVisibility();
    },

    breadcrumbItemsWrap: function () {
      if (!this.$breadcrumbItems[0]) return;

      this.$breadcrumbList.css(
        Craft.orientation === 'ltr' ? 'margin-right' : 'margin-left',
        ''
      );
      const listWidth = this.$breadcrumbList[0].getBoundingClientRect().width;
      let totalItemWidth = 0;

      // Iterate through all list items (inclusive of more button)
      const $items = this.$breadcrumbList.find('li');
      for (let i = 0; i < $items.length; i++) {
        totalItemWidth += $items.get(i).getBoundingClientRect().width;
      }

      this.breadcrumbListWidth = listWidth;

      if (totalItemWidth <= listWidth) {
        return false;
      }

      // If it's less than a pixel off, it's probably just a rounding error.
      // Give the container an extra pixel to be safe, though
      if (totalItemWidth < listWidth + 1) {
        this.$breadcrumbList.css(
          Craft.orientation === 'ltr' ? 'margin-right' : 'margin-left',
          '-1px'
        );
        return false;
      }

      return true;
    },

    handleBreadcrumbVisibility: function () {
      if (!this.breadcrumbItemsWrap()) return;

      if (this.$breadcrumbList.find('[data-disclosure-item]').length === 0) {
        this.$breadcrumbList.append(this.breadcrumbDisclosureItem);
      }

      const triggerWidth = this.$breadcrumbList.find(
        '[data-disclosure-item]'
      )[0].offsetWidth;
      let visibleItemWidth = triggerWidth;
      let finalIndex;
      let newWidth;
      const listWidth = this.breadcrumbListWidth;

      // Find breadcrumbs that should remain visible without overflowing
      this.$breadcrumbItems.each(function (index) {
        newWidth = visibleItemWidth + this.offsetWidth;

        if (newWidth < listWidth) {
          finalIndex = index;
          visibleItemWidth += this.offsetWidth;
        } else {
          return false;
        }
      });

      // Separate breadcrums that should remain visible vs. hidden
      const shownItems = this.$breadcrumbItems.slice(0, finalIndex + 1);
      const hiddenItems = this.$breadcrumbItems.slice(finalIndex + 1);

      // Empty list DOM and add shown items and trigger item
      this.$breadcrumbList.html('');
      this.$breadcrumbList.append(shownItems);
      this.$breadcrumbList.append(this.breadcrumbDisclosureItem);

      // Add hidden items to disclosure menu and initialize
      this.$breadcrumbList
        .find('[data-disclosure-menu] ul')
        .append(hiddenItems);
      this.$breadcrumbList.find('[data-disclosure-trigger]').disclosureMenu();
    },

    updateResponsiveTables: function () {
      for (
        this.updateResponsiveTables._i = 0;
        this.updateResponsiveTables._i < this.$collapsibleTables.length;
        this.updateResponsiveTables._i++
      ) {
        this.updateResponsiveTables._$table = this.$collapsibleTables.eq(
          this.updateResponsiveTables._i
        );
        this.updateResponsiveTables._containerWidth =
          this.updateResponsiveTables._$table.parent().width();
        this.updateResponsiveTables._check = false;

        if (this.updateResponsiveTables._containerWidth > 0) {
          // Is this the first time we've checked this table?
          if (
            typeof this.updateResponsiveTables._$table.data(
              'lastContainerWidth'
            ) === 'undefined'
          ) {
            this.updateResponsiveTables._check = true;
          } else {
            this.updateResponsiveTables._isCollapsed =
              this.updateResponsiveTables._$table.hasClass('collapsed');

            // Getting wider?
            if (
              this.updateResponsiveTables._containerWidth >
              this.updateResponsiveTables._$table.data('lastContainerWidth')
            ) {
              if (this.updateResponsiveTables._isCollapsed) {
                this.updateResponsiveTables._$table.removeClass('collapsed');
                this.updateResponsiveTables._check = true;
              }
            } else if (!this.updateResponsiveTables._isCollapsed) {
              this.updateResponsiveTables._check = true;
            }
          }

          // Are we checking the table width?
          if (this.updateResponsiveTables._check) {
            if (
              this.updateResponsiveTables._$table.width() - 30 >
              this.updateResponsiveTables._containerWidth
            ) {
              this.updateResponsiveTables._$table.addClass('collapsed');
            }
          }

          // Remember the container width for next time
          this.updateResponsiveTables._$table.data(
            'lastContainerWidth',
            this.updateResponsiveTables._containerWidth
          );
        }
      }
    },

    updateFixedHeader: function () {
      if (this.isMobile) {
        return;
      }

      // Have we scrolled passed the top of #main?
      if (
        this.$main.length &&
        this.$headerContainer[0].getBoundingClientRect().top < 0
      ) {
        if (!this.fixedHeader) {
          var headerHeight = this.$headerContainer.height();

          // Hard-set the minimum content container height
          this.$contentContainer.css(
            'min-height',
            'calc(100vh - ' + (headerHeight + 14 + 48 - 1) + 'px)'
          );

          // Hard-set the header container height
          this.$headerContainer.height(headerHeight);
          Garnish.$bod.addClass('fixed-header');

          // Fix the sidebar and details pane positions if they are taller than #content-container
          var contentHeight = this.$contentContainer.outerHeight();
          var $detailsHeight = this.$details.outerHeight();
          var css = {
            top: headerHeight + 'px',
            'max-height': 'calc(100vh - ' + headerHeight + 'px)',
          };
          this.$sidebar.addClass('fixed').css(css);
          this.$details.addClass('fixed').css(css);
          this.fixedHeader = true;
        }
      } else if (this.fixedHeader) {
        this.$headerContainer.height('auto');
        Garnish.$bod.removeClass('fixed-header');
        this.$contentContainer.css('min-height', '');
        this.$sidebar.removeClass('fixed').css({
          top: '',
          'max-height': '',
        });
        this.$details.removeClass('fixed').css({
          top: '',
          'max-height': '',
        });
        this.fixedHeader = false;
      }
    },

    /**
     * Dispays a notification.
     *
     * @param {string} type
     * @param {string} message
     */
    displayNotification: function (type, message) {
      var notificationDuration = Craft.CP.notificationDuration;

      if (['cp-error', 'error'].includes(type)) {
        notificationDuration *= 2;
        icon = 'alert';
        label = Craft.t('app', 'Error');
      } else {
        icon = 'info';
        label = Craft.t('app', 'Notice');
      }

      var $notification = $(`
            <div class="notification ${type.replace('cp-', '')}">
                <span data-icon="${icon}" aria-label="${label}"></span>
                ${message}
            </div>
            `).appendTo(this.$notificationContainer);

      var fadedMargin = -($notification.outerWidth() / 2) + 'px';

      $notification
        .hide()
        .css({
          opacity: 0,
          'margin-left': fadedMargin,
          'margin-right': fadedMargin,
        })
        .velocity(
          {opacity: 1, 'margin-left': '2px', 'margin-right': '2px'},
          {display: 'inline-block', duration: 'fast'}
        )
        .delay(notificationDuration)
        .velocity(
          {opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin},
          {
            complete: function () {
              $notification.remove();
            },
          }
        );

      this.trigger('displayNotification', {
        notificationType: type,
        message: message,
      });
    },

    /**
     * Displays a notice.
     *
     * @param {string} message
     */
    displayNotice: function (message) {
      this.displayNotification('notice', message);
    },

    /**
     * Displays an error.
     *
     * @param {string} message
     */
    displayError: function (message) {
      if (!message) {
        message = Craft.t('app', 'A server error occurred.');
      }

      this.displayNotification('error', message);
    },

    fetchAlerts: function () {
      return Craft.queue.push(
        () =>
          new Promise((resolve, reject) => {
            const data = {
              path: Craft.path,
            };
            Craft.sendActionRequest('POST', 'app/get-cp-alerts', {data})
              .then(({data}) => {
                resolve(data.alerts);
              })
              .catch(reject);
          })
      );
    },

    displayAlerts: function (alerts) {
      this.$alerts.remove();

      if (Garnish.isArray(alerts) && alerts.length) {
        this.$alerts = $('<ul id="alerts"/>').prependTo($('#page-container'));

        for (var i = 0; i < alerts.length; i++) {
          $(
            `<li><span data-icon="alert" aria-label="${Craft.t(
              'app',
              'Error'
            )}"></span> ${alerts[i]}</li>`
          ).appendTo(this.$alerts);
        }

        var height = this.$alerts.outerHeight();
        this.$alerts
          .css('margin-top', -height)
          .velocity({'margin-top': 0}, 'fast');

        this.initAlerts();
      }
    },

    initAlerts: function () {
      // Are there any shunnable alerts?
      var $shunnableAlerts = this.$alerts.find('a[class^="shun:"]');

      for (var i = 0; i < $shunnableAlerts.length; i++) {
        this.addListener($shunnableAlerts[i], 'click', (ev) => {
          ev.preventDefault();

          Craft.queue.push(
            () =>
              new Promise((resolve, reject) => {
                const $link = $(ev.currentTarget);
                const data = {
                  message: $link.prop('className').substring(5),
                };
                Craft.sendActionRequest('POST', 'app/shun-cp-alert', {data})
                  .then(() => {
                    $link.parent().remove();
                    resolve();
                  })
                  .catch(({response}) => {
                    this.displayError(response.data.message);
                    reject();
                  });
              })
          );
        });
      }
    },

    checkForUpdates: function (forceRefresh, includeDetails, callback) {
      // Make 'includeDetails' optional
      if (typeof includeDetails === 'function') {
        callback = includeDetails;
        includeDetails = false;
      }

      // If forceRefresh == true, we're currently checking for updates, and not currently forcing a refresh,
      // then just set a new callback that re-checks for updates when the current one is done.
      if (
        this.checkingForUpdates &&
        ((forceRefresh === true && !this.forcingRefreshOnUpdatesCheck) ||
          (includeDetails === true && !this.includingDetailsOnUpdatesCheck))
      ) {
        var realCallback = callback;
        callback = () => {
          this.checkForUpdates(forceRefresh, includeDetails, realCallback);
        };
      }

      // Callback function?
      if (typeof callback === 'function') {
        if (!Garnish.isArray(this.checkForUpdatesCallbacks)) {
          this.checkForUpdatesCallbacks = [];
        }

        this.checkForUpdatesCallbacks.push(callback);
      }

      if (!this.checkingForUpdates) {
        this.checkingForUpdates = true;
        this.forcingRefreshOnUpdatesCheck = forceRefresh === true;
        this.includingDetailsOnUpdatesCheck = includeDetails === true;

        this._checkForUpdates(forceRefresh, includeDetails).then((info) => {
          this.updateUtilitiesBadge();
          this.checkingForUpdates = false;

          if (Garnish.isArray(this.checkForUpdatesCallbacks)) {
            var callbacks = this.checkForUpdatesCallbacks;
            this.checkForUpdatesCallbacks = null;

            for (var i = 0; i < callbacks.length; i++) {
              callbacks[i](info);
            }
          }

          this.trigger('checkForUpdates', {
            updateInfo: info,
          });
        });
      }
    },

    _checkForUpdates: function (forceRefresh, includeDetails) {
      return new Promise((resolve, reject) => {
        if (!forceRefresh) {
          this._checkForCachedUpdates(includeDetails).then((info) => {
            if (info.cached !== false) {
              resolve(info);
            }

            this._getUpdates(includeDetails).then((info) => {
              resolve(info);
            });
          });
        } else {
          this._getUpdates(includeDetails).then((info) => {
            resolve(info);
          });
        }
      });
    },

    _checkForCachedUpdates: function (includeDetails) {
      return new Promise(function (resolve, reject) {
        var data = {
          onlyIfCached: true,
          includeDetails: includeDetails,
        };

        Craft.sendActionRequest('POST', 'app/check-for-updates', {data})
          .then((response) => resolve(response.data))
          .catch(({response}) => resolve({cached: false}));
      });
    },

    _getUpdates: function (includeDetails) {
      return new Promise((resolve, reject) => {
        Craft.sendApiRequest('GET', 'updates')
          .then((updates) => {
            this._cacheUpdates(updates, includeDetails).then(resolve);
          })
          .catch((e) => {
            this._cacheUpdates({}).then(resolve);
          });
      });
    },

    _cacheUpdates: function (updates, includeDetails) {
      const data = {
        updates,
        includeDetails,
      };

      return Craft.sendActionRequest('POST', 'app/cache-updates', {data});
    },

    updateUtilitiesBadge: function () {
      var $utilitiesLink = $('#nav-utilities').find('> a:not(.sel)');

      // Ignore if there is no (non-selected) Utilities nav item
      if (!$utilitiesLink.length) {
        return;
      }

      Craft.queue.push(
        () =>
          new Promise((resolve, reject) => {
            Craft.sendActionRequest('POST', 'app/get-utilities-badge-count')
              .then(({data}) => {
                // Get the existing utility nav badge and screen reader text, if any
                let $badge = $utilitiesLink.children('.badge');
                let $screenReaderText = $utilitiesLink.children(
                  '[data-notification]'
                );

                if (data.badgeCount) {
                  if (!$badge.length) {
                    $badge = $(
                      '<span class="badge" aria-hidden="true"/>'
                    ).appendTo($utilitiesLink);
                  }

                  if (!$screenReaderText.length) {
                    $screenReaderText = $(
                      '<span class="visually-hidden" data-notification/>'
                    ).appendTo($utilitiesLink);
                  }

                  $badge.text(data.badgeCount);
                  $screenReaderText.text(
                    Craft.t(
                      'app',
                      '{num, number} {num, plural, =1{notification} other{notifications}}',
                      {
                        num: data.badgeCount,
                      }
                    )
                  );
                } else if ($badge.length && $screenReaderText.length) {
                  $badge.remove();
                  $screenReaderText.remove();
                }
                resolve();
              })
              .catch(reject);
          })
      );
    },

    runQueue: function () {
      if (!this.enableQueue) {
        return;
      }

      if (Craft.runQueueAutomatically) {
        Craft.queue.push(
          () =>
            new Promise((resolve, reject) => {
              Craft.sendActionRequest('POST', 'queue/run')
                .then(() => {
                  this.trackJobProgress(false, true);
                  resolve();
                })
                .catch(reject);
            })
        );
      } else {
        this.trackJobProgress(false, true);
      }
    },

    trackJobProgress: function (delay, force) {
      if (force && this.trackJobProgressTimeout) {
        clearTimeout(this.trackJobProgressTimeout);
        this.trackJobProgressTimeout = null;
      }

      // Ignore if we're already tracking jobs, or the queue is disabled
      if (this.trackJobProgressTimeout || !this.enableQueue) {
        return;
      }

      if (delay === true) {
        // Determine the delay based on how long the displayed job info has remained unchanged
        var timeout = Math.min(60000, this.displayedJobInfoUnchanged * 500);
        this.trackJobProgressTimeout = setTimeout(
          this._trackJobProgressInternal.bind(this),
          timeout
        );
      } else {
        this._trackJobProgressInternal();
      }
    },

    _trackJobProgressInternal: function () {
      Craft.queue.push(
        () =>
          new Promise((resolve, reject) => {
            Craft.sendActionRequest(
              'POST',
              'queue/get-job-info?limit=50&dontExtendSession=1'
            )
              .then(({data}) => {
                this.trackJobProgressTimeout = null;
                this.totalJobs = data.total;
                this.setJobInfo(data.jobs);
                if (this.jobInfo.length) {
                  // Check again after a delay
                  this.trackJobProgress(true);
                }
                resolve();
              })
              .catch(reject);
          })
      );
    },

    setJobInfo: function (jobInfo) {
      if (!this.enableQueue) {
        return;
      }

      this.jobInfo = jobInfo;

      // Update the displayed job info
      var oldInfo = this.displayedJobInfo;
      this.displayedJobInfo = this.getDisplayedJobInfo();

      // Same old same old?
      if (
        oldInfo &&
        this.displayedJobInfo &&
        oldInfo.id === this.displayedJobInfo.id &&
        oldInfo.progress === this.displayedJobInfo.progress &&
        oldInfo.progressLabel === this.displayedJobInfo.progressLabel &&
        oldInfo.status === this.displayedJobInfo.status
      ) {
        this.displayedJobInfoUnchanged++;
      } else {
        // Reset the counter
        this.displayedJobInfoUnchanged = 1;
      }

      this.updateJobIcon();

      // Fire a setJobInfo event
      this.trigger('setJobInfo');
    },

    /**
     * Returns info for the job that should be displayed in the control panel sidebar
     */
    getDisplayedJobInfo: function () {
      if (!this.enableQueue) {
        return null;
      }

      // Set the status preference order
      var statuses = [
        Craft.CP.JOB_STATUS_RESERVED,
        Craft.CP.JOB_STATUS_FAILED,
        Craft.CP.JOB_STATUS_WAITING,
      ];

      for (var i = 0; i < statuses.length; i++) {
        for (var j = 0; j < this.jobInfo.length; j++) {
          if (
            this.jobInfo[j].status === statuses[i] &&
            (statuses[i] !== Craft.CP.JOB_STATUS_WAITING ||
              !this.jobInfo[j].delay)
          ) {
            return this.jobInfo[j];
          }
        }
      }

      return null;
    },

    updateJobIcon: function () {
      if (!this.enableQueue || !this.$nav.length) {
        return;
      }

      if (this.displayedJobInfo) {
        if (!this.jobProgressIcon) {
          this.jobProgressIcon = new JobProgressIcon();
        }

        if (
          this.displayedJobInfo.status === Craft.CP.JOB_STATUS_RESERVED ||
          this.displayedJobInfo.status === Craft.CP.JOB_STATUS_WAITING
        ) {
          this.jobProgressIcon.hideFailMode();
          this.jobProgressIcon.setDescription(
            this.displayedJobInfo.description,
            this.displayedJobInfo.progressLabel
          );
          this.jobProgressIcon.setProgress(this.displayedJobInfo.progress);
        } else if (
          this.displayedJobInfo.status === Craft.CP.JOB_STATUS_FAILED
        ) {
          this.jobProgressIcon.showFailMode(Craft.t('app', 'Failed'));
        }
      } else {
        if (this.jobProgressIcon) {
          this.jobProgressIcon.hideFailMode();
          this.jobProgressIcon.complete();
          delete this.jobProgressIcon;
        }
      }
    },

    /**
     * Returns the active site for the control panel
     *
     * @return {number}
     */
    getSiteId: function () {
      // If the old BaseElementIndex.siteId value is in localStorage, go aheand and remove & return that
      let siteId = Craft.getLocalStorage('BaseElementIndex.siteId');
      if (typeof siteId !== 'undefined') {
        Craft.removeLocalStorage('BaseElementIndex.siteId');
        this.setSiteId(siteId);
        return siteId;
      }
      return Craft.siteId;
    },

    /**
     * Sets the active site for the control panel
     * @param {number} siteId
     */
    setSiteId: function (siteId) {
      const site = Craft.sites.find((s) => s.id === siteId);
      if (site) {
        // update the current URL
        const url = Craft.getUrl(document.location.href, {site: site.handle});
        history.replaceState({}, '', url);

        // update the site--x body class
        for (className of document.body.classList) {
          if (className.match(/^site--/)) {
            document.body.classList.remove(className);
          }
        }
        document.body.classList.add(`site--${site.handle}`);

        // update other URLs on the page
        $('a').each(function () {
          if (
            this.hostname.length &&
            this.hostname === location.hostname &&
            this.href.indexOf(Craft.cpTrigger) !== -1
          ) {
            this.href = Craft.getUrl(this.href, {site: site.handle});
          }
        });
      }
    },
  },
  {
    //maxWidth: 1051, //1024,
    notificationDuration: 2000,

    JOB_STATUS_WAITING: 1,
    JOB_STATUS_RESERVED: 2,
    JOB_STATUS_DONE: 3,
    JOB_STATUS_FAILED: 4,
  }
);

Garnish.$scrollContainer = Garnish.$win;
Craft.cp = new Craft.CP();

/**
 * Job progress icon class
 */
var JobProgressIcon = Garnish.Base.extend({
  $li: null,
  $a: null,
  $label: null,
  $progressLabel: null,

  progress: null,
  failMode: false,

  _$bgCanvas: null,
  _$staticCanvas: null,
  _$hoverCanvas: null,
  _$failCanvas: null,

  _staticCtx: null,
  _hoverCtx: null,
  _canvasSize: null,
  _arcPos: null,
  _arcRadius: null,
  _lineWidth: null,

  _arcStartPos: 0,
  _arcEndPos: 0,
  _arcStartStepSize: null,
  _arcEndStepSize: null,
  _arcStep: null,
  _arcStepTimeout: null,
  _arcAnimateCallback: null,

  _progressBar: null,

  init: function () {
    this.$li = $('<li/>').appendTo(Craft.cp.$nav.children('ul'));
    this.$a = $('<a/>', {
      id: 'job-icon',
      href: Craft.canAccessQueueManager
        ? Craft.getUrl('utilities/queue-manager')
        : null,
    }).appendTo(this.$li);
    this.$canvasContainer = $('<span class="icon"/>').appendTo(this.$a);
    var $labelContainer = $('<span class="label"/>').appendTo(this.$a);
    this.$label = $('<span/>').appendTo($labelContainer);
    this.$progressLabel = $('<span class="progress-label"/>')
      .appendTo($labelContainer)
      .hide();

    let m = window.devicePixelRatio > 1 ? 2 : 1;
    this._canvasSize = 18 * m;
    this._arcPos = this._canvasSize / 2;
    this._arcRadius = 7 * m;
    this._lineWidth = 3 * m;

    this._$bgCanvas = this._createCanvas('bg', '#61666b');
    this._$staticCanvas = this._createCanvas('static', '#d7d9db');
    this._$hoverCanvas = this._createCanvas('hover', '#fff');
    this._$failCanvas = this._createCanvas('fail', '#da5a47').hide();

    this._staticCtx = this._$staticCanvas[0].getContext('2d');
    this._hoverCtx = this._$hoverCanvas[0].getContext('2d');

    this._drawArc(this._$bgCanvas[0].getContext('2d'), 0, 1);
    this._drawArc(this._$failCanvas[0].getContext('2d'), 0, 1);
  },

  setDescription: function (description, progressLabel) {
    this.$a.attr('title', description);
    this.$label.text(description);
    if (progressLabel) {
      this.$progressLabel.text(progressLabel).show();
    } else {
      this.$progressLabel.hide();
    }
  },

  setProgress: function (progress) {
    if (progress == 0) {
      this._$staticCanvas.hide();
      this._$hoverCanvas.hide();
    } else {
      this._$staticCanvas.show();
      this._$hoverCanvas.show();
      if (this.progress && progress > this.progress) {
        this._animateArc(0, progress / 100);
      } else {
        this._setArc(0, progress / 100);
      }
    }

    this.progress = progress;
  },

  complete: function () {
    this._animateArc(0, 1, () => {
      this._$bgCanvas.velocity('fadeOut');

      this._animateArc(1, 1, () => {
        this.$a.remove();
        this.destroy();
      });
    });
  },

  showFailMode: function (message) {
    if (this.failMode) {
      return;
    }

    this.failMode = true;
    this.progress = null;

    this._$bgCanvas.hide();
    this._$staticCanvas.hide();
    this._$hoverCanvas.hide();
    this._$failCanvas.show();

    this.setDescription(message);
  },

  hideFailMode: function () {
    if (!this.failMode) {
      return;
    }

    this.failMode = false;

    this._$bgCanvas.show();
    this._$staticCanvas.show();
    this._$hoverCanvas.show();
    this._$failCanvas.hide();
  },

  _createCanvas: function (id, color) {
    var $canvas = $(
        '<canvas id="job-icon-' +
          id +
          '" width="' +
          this._canvasSize +
          '" height="' +
          this._canvasSize +
          '"/>'
      ).appendTo(this.$canvasContainer),
      ctx = $canvas[0].getContext('2d');

    ctx.strokeStyle = color;
    ctx.lineWidth = this._lineWidth;
    ctx.lineCap = 'round';
    return $canvas;
  },

  _setArc: function (startPos, endPos) {
    this._arcStartPos = startPos;
    this._arcEndPos = endPos;

    this._drawArc(this._staticCtx, startPos, endPos);
    this._drawArc(this._hoverCtx, startPos, endPos);
  },

  _drawArc: function (ctx, startPos, endPos) {
    ctx.clearRect(0, 0, this._canvasSize, this._canvasSize);
    ctx.beginPath();
    ctx.arc(
      this._arcPos,
      this._arcPos,
      this._arcRadius,
      (1.5 + startPos * 2) * Math.PI,
      (1.5 + endPos * 2) * Math.PI
    );
    ctx.stroke();
    ctx.closePath();
  },

  _animateArc: function (targetStartPos, targetEndPos, callback) {
    if (this._arcStepTimeout) {
      clearTimeout(this._arcStepTimeout);
    }

    this._arcStep = 0;
    this._arcStartStepSize = (targetStartPos - this._arcStartPos) / 10;
    this._arcEndStepSize = (targetEndPos - this._arcEndPos) / 10;
    this._arcAnimateCallback = callback;
    this._takeNextArcStep();
  },

  _takeNextArcStep: function () {
    this._setArc(
      this._arcStartPos + this._arcStartStepSize,
      this._arcEndPos + this._arcEndStepSize
    );

    this._arcStep++;

    if (this._arcStep < 10) {
      this._arcStepTimeout = setTimeout(this._takeNextArcStep.bind(this), 50);
    } else if (this._arcAnimateCallback) {
      this._arcAnimateCallback();
    }
  },
});
