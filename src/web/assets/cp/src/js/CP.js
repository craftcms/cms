/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

/**
 * CP class
 */
Craft.CP = Garnish.Base.extend(
  {
    elementThumbLoader: null,
    authManager: null,
    announcerTimeout: null,
    modalLayers: [],

    $nav: null,
    $navToggle: null,
    $globalLiveRegion: null,
    $activeLiveRegion: null,
    $globalSidebar: null,
    $globalContainer: null,
    $mainContainer: null,
    $pageContainer: null,
    $alerts: null,
    $crumbs: null,
    $crumbList: null,
    $crumbItems: null,
    $crumbMenuTriggerItem: null,
    $crumbMenu: null,
    $crumbMenuList: null,
    $crumbMenuItems: null,
    $notificationContainer: null,
    $notificationHeading: null,
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

    tabManager: null,

    enableQueue: true,
    totalJobs: 0,
    jobInfo: null,
    displayedJobInfo: null,
    displayedJobInfoUnchanged: 1,
    trackJobProgressTimeout: null,
    trackingJobProgress: false,
    jobProgressCancelToken: null,
    jobProgressIcon: null,

    checkingForUpdates: false,
    forcingRefreshOnUpdatesCheck: false,
    includingDetailsOnUpdatesCheck: false,
    checkForUpdatesCallbacks: null,
    checkForUpdatesFailureCallbacks: null,

    resizeTimeout: null,

    init: function () {
      this.elementThumbLoader = new Craft.ElementThumbLoader();

      // Is this session going to expire?
      if (Craft.remainingSessionTime !== 0) {
        this.authManager = new Craft.AuthManager();
      }

      // Find all the key elements
      this.$nav = $('#nav');
      this.$navToggle = $('#primary-nav-toggle');
      this.$globalLiveRegion = $('#global-live-region');
      this.$activeLiveRegion = this.$globalLiveRegion;
      this.$globalSidebar = $('#global-sidebar');
      this.$globalContainer = $('#global-container');
      this.$mainContainer = $('#main-container');
      this.$pageContainer = $('#page-container');
      this.$alerts = $('#alerts');
      this.$crumbs = $('#crumbs');
      this.$crumbList = $('#crumb-list');
      this.$crumbItems = this.$crumbList.children('li');
      this.$notificationContainer = $('#notifications');
      this.$notificationHeading = $('#cp-notification-heading');
      this.$main = $('#main');
      this.$primaryForm = $('#main-form');
      this.$headerContainer = $('#header-container');
      this.$header = $('#header');
      this.$mainContent = $('#main-content');
      this.$details = $('#details');
      this.$detailsContainer = $('#details-container');
      this.$sidebarContainer = $('#sidebar-container');
      this.$sidebarToggle = $('#sidebar-toggle');
      this.$sidebar = $('#sidebar');
      this.$contentContainer = $('#content-container');
      this.$collapsibleTables = $('table.collapsible');

      this.isMobile = Garnish.isMobileBrowser();

      //this.updateContentHeading();

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

      // Layers
      Garnish.uiLayerManager.on('addLayer', () => {
        this.handleLayerUpdates();
      });

      Garnish.uiLayerManager.on('removeLayer', () => {
        this.handleLayerUpdates();
      });

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
      let scrollY;
      const location = document.location;
      const params = new URLSearchParams(location.search);
      if (params.has('scrollY')) {
        scrollY = params.get('scrollY');
        params.delete('scrollY');
        Craft.setUrl(
          Craft.getUrl(
            `${location.origin}${location.pathname}${location.hash}`,
            params.toString()
          )
        );
      } else {
        scrollY = Craft.getLocalStorage('scrollY');
        if (scrollY !== undefined) {
          Craft.removeLocalStorage('scrollY');
        }
      }
      if (scrollY !== undefined) {
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
        let $unreadMessage;
        if (hasUnreads) {
          $unreadMessage = $('<span/>', {
            class: 'visually-hidden',
            html: Craft.t('app', 'Unread messages'),
          });
          $btn.addClass('unread').append($unreadMessage);
        }
        let hud;
        this.addListener($btn, 'click', () => {
          if (!hud) {
            let contents = '';
            Craft.announcements.forEach((a) => {
              contents +=
                `<div class="announcement ${
                  a.unread ? 'unread' : ''
                }" role="listitem">` +
                '<div class="announcement__header">' +
                `<h3 class="announcement__heading h2">${a.heading}</h3>` +
                '<div class="announcement-label-container">' +
                `<div class="announcement-icon" aria-hidden="true">${a.icon}</div>` +
                `<div class="announcement-label">${a.label}</div>` +
                '</div>' +
                '</div>' +
                `<p>${a.body}</p>` +
                '</div>';
            });
            hud = new Garnish.HUD(
              $btn,
              `<h2 class="visually-hidden">${Craft.t(
                'app',
                'Announcements'
              )}</h2><div id="announcements" role="list">${contents}</div>`,
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
              $unreadMessage.remove();
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

      // Add .stuck class to #footer when stuck
      // h/t https://stackoverflow.com/a/61115077/1688568
      const footer = document.getElementById('footer');
      if (footer) {
        const observer = new IntersectionObserver(
          ([ev]) => {
            ev.target.classList.toggle('stuck', ev.intersectionRatio < 1);
          },
          {
            rootMargin: '0px 0px -1px 0px',
            threshold: [1],
          }
        );
        observer.observe(footer);
      }

      // Load any element thumbs
      this.elementThumbLoader.load(this.$pageContainer);

      // Add notification close listeners
      this.on('notificationClose', () => {
        this.updateNotificationHeadingDisplay();
      });
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

    get notificationCount() {
      return this.$notificationContainer.find('.notification').length;
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
        if (!$form.data('initialSerializedValue')) {
          const serializer =
            $form.data('serializer') || (() => $form.serialize());
          $form.data('initialSerializedValue', serializer());
        }
        this.addListener($form, 'submit', function (ev) {
          if (Garnish.hasAttr($form, 'data-confirm-unload')) {
            this.removeListener(Garnish.$win, 'beforeunload');
          }
          if (Garnish.hasAttr($form, 'data-delta')) {
            ev.preventDefault();
            const serializer =
              $form.data('serializer') || (() => $form.serialize());
            const data = Craft.findDeltaData(
              $form.data('initialSerializedValue'),
              serializer(),
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

    toggleNav: function () {
      const isExpanded = this.navIsExpanded();

      if (isExpanded === null) return;

      if (isExpanded) {
        this.disableGlobalSidebar();
        this.$navToggle.focus();
        this.$navToggle.attr('aria-expanded', 'false');
        Garnish.$bod.removeClass('showing-nav');
      } else {
        this.enableGlobalSidebar();
        this.$globalSidebar.find(':focusable')[0].focus();
        this.$navToggle.attr('aria-expanded', 'true');
        Garnish.$bod.addClass('showing-nav');
      }
    },

    /**
     * Makes the global sidebar navigable by screen reader and keyboard users
     **/
    enableGlobalSidebar: function () {
      this.$globalSidebar.attr('aria-hidden', 'false');
      this.$globalSidebar.find(':focusable').attr('tabindex', '0');
    },

    /**
     * Hides the global sidebar from screen reader and keyboard users
     **/
    disableGlobalSidebar: function () {
      this.$globalSidebar.attr('aria-hidden', 'true');
      this.$globalSidebar.find(':focusable').attr('tabindex', '-1');
    },

    setSidebarNavAttributes: function () {
      const isExpanded = this.navIsExpanded();

      if (isExpanded === null) return;

      if (!isExpanded) {
        this.disableGlobalSidebar();
      } else {
        this.enableGlobalSidebar();
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

        // If there is a site crumb menu or context menu, set their links to this tab ID
        if (href && href.charAt(0) === '#') {
          const contextLinks = document.querySelectorAll(
            '#site-crumb-menu a[href], #context-menu a[href]'
          );
          for (const link of contextLinks) {
            link.href = link.href.match(/^[^#]*/)[0] + href;
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
     * @param {(jQuery|HTMLElement|string)} tab
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

    handleBreadcrumbVisibility: function () {
      if (!this.$crumbItems.length) {
        return;
      }

      if (this.$crumbMenuItems) {
        // put everything back
        this.$crumbItems.css('max-width', '');
        this.$crumbMenuItems.insertAfter(this.$crumbMenuTriggerItem);
        this.$crumbMenuTriggerItem.detach();
        this.$crumbMenuItems = null;
      }

      const maxWidth = Math.ceil(
        this.$crumbs[0].getBoundingClientRect().width -
          this.$navToggle[0].getBoundingClientRect().width
      );
      const itemWidths = [];

      for (let i = 0; i < this.$crumbItems.length; i++) {
        const $crumb = this.$crumbItems.eq(i);
        itemWidths[i] = $crumb[0].getBoundingClientRect().width;
      }

      const totalWidth = itemWidths.reduce((sum, width) => sum + width, 0);

      if (totalWidth > maxWidth) {
        // add the menu trigger
        if (!this.$crumbMenuTriggerItem) {
          this.$crumbMenuTriggerItem = $('<li/>', {
            class: 'crumb',
          }).prependTo(this.$crumbList);
          const $labelContainer = $('<div/>', {
            class: 'crumb-label',
          }).appendTo(this.$crumbMenuTriggerItem);
          const $trigger = $('<button/>', {
            id: 'crumb-menu-trigger',
            'data-icon': 'ellipsis',
            'data-disclosure-trigger': 'true',
            'aria-controls': 'crumb-menu',
            'aria-haspopup': 'true',
            'aria-label': Craft.t('app', 'More…'),
            title: Craft.t('app', 'More…'),
          }).appendTo($labelContainer);

          this.$crumbMenu = $('<div/>', {
            id: 'crumb-menu',
            class: 'menu menu--disclosure',
            'data-disclosure-menu': 'true',
          }).appendTo($labelContainer);
          this.$crumbMenuList = $('<ul/>').appendTo(this.$crumbMenu);

          $trigger.disclosureMenu();
        } else {
          this.$crumbMenuTriggerItem.prependTo(this.$crumbList);
        }

        // see how many crumbs we can include, starting at the end
        let visibleTotalWidth =
          this.$crumbMenuTriggerItem[0].getBoundingClientRect().width;

        for (let i = this.$crumbItems.length - 1; i >= 0; i--) {
          if (visibleTotalWidth + itemWidths[i] > maxWidth) {
            this.$crumbMenuItems = this.$crumbItems.slice(0, i + 1);
            this.$crumbMenuItems.appendTo(this.$crumbMenuList);
            break;
          }

          visibleTotalWidth += itemWidths[i];
        }
      }
    },

    handleWindowResize: function () {
      this.updateResponsiveTables();
      this.handleBreadcrumbVisibility();
    },

    handleLayerUpdates: function () {
      // Exit if the number of modal layers remains the same
      if (Garnish.uiLayerManager.modalLayers.length === this.modalLayers.length)
        return;

      // Store modal layers
      this.modalLayers = Garnish.uiLayerManager.modalLayers;

      if (this.announcerTimeout) {
        clearTimeout(this.announcerTimeout);
      }

      if (Garnish.uiLayerManager.modalLayers.length === 0) {
        this.$activeLiveRegion = this.$globalLiveRegion;
      } else {
        const $modal = Garnish.uiLayerManager.highestModalLayer.$container;
        let modalObj;

        if ($modal.hasClass('modal')) {
          modalObj = $modal.data('modal');
        } else if ($modal.hasClass('slideout-container')) {
          modalObj = $modal.find('.slideout').data('slideout');
        }

        if (!modalObj) {
          console.warn('There is no modal object');
        }

        if (!modalObj?.$liveRegion) {
          console.warn('There is no live region in the active modal layer.');
          this.$activeLiveRegion = null;
        } else {
          this.$activeLiveRegion = modalObj.$liveRegion;
        }
      }

      // Empty in case it was already populated and not cleared
      this.$activeLiveRegion?.empty();
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
      // Checking if the sidebar toggle is visible
      // https://stackoverflow.com/a/21696585
      if (
        this.isMobile ||
        (this.$sidebarToggle?.length &&
          this.$sidebarToggle[0].offsetParent !== null)
      ) {
        return;
      }

      // Have we scrolled passed the top of #main?
      if (
        this.$main.length &&
        this.$headerContainer[0].getBoundingClientRect().top < 0
      ) {
        const headerHeight = this.$headerContainer.height();
        const headerWidth = this.$header.width();
        if (!this.fixedHeader) {
          // Hard-set the minimum content container height
          this.$contentContainer.css(
            'min-height',
            'calc(100vh - ' + (headerHeight + 14 + 48 - 1) + 'px)'
          );

          // Hard-set the header container height
          this.$headerContainer.height(headerHeight);
          this.$header.width(headerWidth);
          Garnish.$bod.addClass('fixed-header');

          this.fixedHeader = true;
        }

        this._setFixedTopPos(this.$sidebar, headerHeight);
        this.$detailsContainer.css('top', headerHeight + 14);
      } else if (this.fixedHeader) {
        this.$headerContainer.height('auto');
        this.$header.width('auto');
        Garnish.$bod.removeClass('fixed-header');
        this.$contentContainer.css('min-height', '');
        this.$sidebar.removeClass('fixed').css('top', '');
        this.$detailsContainer.css('top', '');
        this.fixedHeader = false;
      }
    },

    /**
     * Updates display property of "Notifications" heading based on whether there are active notifications
     **/
    updateNotificationHeadingDisplay() {
      if (this.notificationCount > 0) {
        this.$notificationHeading.removeClass('hidden');
      } else {
        this.$notificationHeading.addClass('hidden');
      }
    },

    _setFixedTopPos: function ($element, headerHeight) {
      if (!$element.length || !this.$contentContainer.length) {
        return;
      }

      if ($element.outerHeight() >= this.$contentContainer.outerHeight()) {
        $element.removeClass('fixed').css('top', '');
        return;
      }

      $element
        .addClass('fixed')
        .css(
          'top',
          Math.min(
            headerHeight + 14,
            Math.max(
              this.$mainContent[0].getBoundingClientRect().top,
              document.documentElement.clientHeight - $element.outerHeight()
            )
          ) + 'px'
        );
    },

    /**
     * Updates the active live region with a screen reader announcement
     *
     * @param {string} message
     */
    announce: function (message) {
      if (
        !message ||
        !this.$activeLiveRegion ||
        !document.contains(this.$activeLiveRegion[0])
      ) {
        console.warn('There was an error announcing this message.');
        return;
      }

      if (this.announcerTimeout) {
        clearTimeout(this.announcerTimeout);
      }

      this.$activeLiveRegion?.empty().text(message);

      // Clear message after interval
      this.announcerTimeout = setTimeout(() => {
        this.$activeLiveRegion?.empty();
      }, 5000);
    },

    /**
     * Displays a notification.
     *
     * @param {string} type `notice`, `success`, or `error`
     * @param {string} message
     * @param {Object} [settings]
     * @param {string} [settings.icon] The icon to show on the notification
     * @param {string} [settings.iconLabel] The icon’s ARIA label
     * @param {string} [settings.details] Any additional HTML that should be included below the message
     * @returns {Object} The notification
     */
    displayNotification: function (type, message, settings) {
      const notification = new Craft.CP.Notification(type, message, settings);

      this.trigger('displayNotification', {
        notificationType: type,
        message,
        notification,
      });

      this.updateNotificationHeadingDisplay();

      return notification;
    },

    /**
     * Displays a notice.
     *
     * @param {string} message
     * @param {Object} [settings]
     * @param {string} [settings.icon] The icon to show on the notification
     * @param {string} [settings.iconLabel] The icon’s ARIA label
     * @param {string} [settings.details] Any additional HTML that should be included below the message
     * @returns {Object} The notification
     */
    displayNotice: function (message, settings) {
      return this.displayNotification(
        'notice',
        message,
        Object.assign(
          {
            icon: 'info',
            iconLabel: Craft.t('app', 'Notice'),
          },
          settings
        )
      );
    },

    /**
     * Displays a success notification.
     *
     * @param {string} message
     * @param {Object} [settings]
     * @param {string} [settings.icon] The icon to show on the notification
     * @param {string} [settings.iconLabel] The icon’s ARIA label
     * @param {string} [settings.details] Any additional HTML that should be included below the message
     * @returns {Object} The notification
     */
    displaySuccess: function (message, settings) {
      return this.displayNotification(
        'success',
        message,
        Object.assign(
          {
            icon: 'check',
            iconLabel: Craft.t('app', 'Success'),
          },
          settings
        )
      );
    },

    /**
     * Displays an error.
     *
     * @param {string} message
     * @param {Object} [settings]
     * @param {string} [settings.icon] The icon to show on the notification
     * @param {string} [settings.iconLabel] The icon’s ARIA label
     * @param {string} [settings.details] Any additional HTML that should be included below the message
     * @returns {Object} The notification
     */
    displayError: function (message, settings) {
      if (!message || typeof message === 'object') {
        settings = message;
        message = Craft.t('app', 'A server error occurred.');
      }

      return this.displayNotification(
        'error',
        message,
        Object.assign(
          {
            icon: 'alert',
            iconLabel: Craft.t('app', 'Error'),
          },
          settings
        )
      );
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

    displayAlerts: function (alerts, animate = true) {
      this.$alerts.remove();

      if (Array.isArray(alerts) && alerts.length) {
        this.$alerts = $('<ul id="alerts"/>').prependTo(this.$pageContainer);

        for (let alert of alerts) {
          if (!$.isPlainObject(alert)) {
            alert = {
              content: alert,
              showIcon: true,
            };
          }
          let content = alert.content;
          if (alert.showIcon) {
            content = `<span data-icon="alert" aria-label="${Craft.t(
              'app',
              'Error'
            )}"></span> ${content}`;
          }
          $(`<li>${content}</li>`).appendTo(this.$alerts);
        }

        if (animate) {
          const height = this.$alerts.outerHeight();
          this.$alerts
            .css('margin-top', -height)
            .velocity({'margin-top': 0}, 'fast');
        }

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

      const $resolvableButtonsContainer = this.$alerts.find(
        '.resolvable-alert-buttons'
      );
      if ($resolvableButtonsContainer.length) {
        const $refreshBtn = Craft.ui
          .createButton({
            label: Craft.t('app', 'Refresh'),
            spinner: true,
          })
          .appendTo($resolvableButtonsContainer);
        $refreshBtn.on('click', async () => {
          $refreshBtn.addClass('loading');
          try {
            await Craft.sendApiRequest('GET', 'ping');
            const alerts = await this.fetchAlerts();
            this.displayAlerts(alerts, false);
          } finally {
            $refreshBtn.removeClass('loading');
          }
        });
      }
    },

    updateContext: function (label, description) {
      const contextBtnLabel = document.querySelector(
        '#context-menu-container > span'
      );
      if (contextBtnLabel) {
        contextBtnLabel.textContent = label;
      }

      const menuItem = document.querySelector('#context-menu a.sel');
      if (menuItem) {
        const labelEl = menuItem.querySelector('.menu-item-label');
        labelEl.textContent = label;

        let descriptionEl = menuItem.querySelector('.menu-item-description');
        if (description) {
          if (!descriptionEl) {
            descriptionEl = document.createElement('div');
            descriptionEl.className = 'menu-item-description smalltext light';
            menuItem.append(descriptionEl);
          }
          descriptionEl.textContent = description;
        } else if (descriptionEl) {
          descriptionEl.remove();
        }
      }
    },

    showSiteCrumbMenuItem: function (siteId) {
      const menuItem = document.querySelector(
        `#site-crumb-menu a[data-site-id="${siteId}"]`
      );
      if (menuItem) {
        const li = menuItem.closest('li');
        li.classList.remove('hidden');
        const group = li.closest('.menu-group');
        if (group) {
          group.classList.remove('hidden');
        }
      }
    },

    setSiteCrumbMenuItemStatus: function (siteId, status) {
      const menuItem = document.querySelector(
        `#site-crumb-menu a[data-site-id="${siteId}"]`
      );
      if (menuItem) {
        let statusEl = menuItem.querySelector('.status');

        if (status) {
          if (!statusEl) {
            statusEl = document.createElement('div');
            menuItem.prepend(statusEl);
          }
          statusEl.className = `status ${status}`;
        } else if (statusEl) {
          statusEl.remove();
        }
      }
    },

    checkForUpdates: function (
      forceRefresh,
      includeDetails,
      onSuccess,
      onFailure
    ) {
      // Make 'includeDetails' optional
      if (typeof includeDetails === 'function') {
        onFailure = onSuccess;
        onSuccess = includeDetails;
        includeDetails = false;
      }

      // If forceRefresh == true, we're currently checking for updates, and not currently forcing a refresh,
      // then just set a new callback that re-checks for updates when the current one is done.
      if (
        this.checkingForUpdates &&
        ((forceRefresh === true && !this.forcingRefreshOnUpdatesCheck) ||
          (includeDetails === true && !this.includingDetailsOnUpdatesCheck))
      ) {
        const realOnSuccess = onSuccess;
        const realOnFailure = onFailure;
        onSuccess = () => {
          this.checkForUpdates(
            forceRefresh,
            includeDetails,
            realOnSuccess,
            realOnFailure
          );
        };
      }

      // Callback functions?
      if (typeof onSuccess === 'function') {
        if (!Array.isArray(this.checkForUpdatesCallbacks)) {
          this.checkForUpdatesCallbacks = [];
        }
        this.checkForUpdatesCallbacks.push(onSuccess);
      }
      if (typeof onFailure === 'function') {
        if (!Array.isArray(this.checkForUpdatesFailureCallbacks)) {
          this.checkForUpdatesFailureCallbacks = [];
        }
        this.checkForUpdatesFailureCallbacks.push(onFailure);
      }

      if (!this.checkingForUpdates) {
        this.checkingForUpdates = true;
        this.forcingRefreshOnUpdatesCheck = forceRefresh === true;
        this.includingDetailsOnUpdatesCheck = includeDetails === true;

        this._checkForUpdates(forceRefresh, includeDetails)
          .then((info) => {
            this.updateUtilitiesBadge();
            this.checkingForUpdates = false;

            if (Array.isArray(this.checkForUpdatesCallbacks)) {
              const callbacks = this.checkForUpdatesCallbacks;
              this.checkForUpdatesCallbacks = null;

              for (let callback of callbacks) {
                callback(info);
              }
            }

            this.trigger('checkForUpdates', {
              updateInfo: info,
            });
          })
          .catch(() => {
            this.checkingForUpdates = false;

            if (Array.isArray(this.checkForUpdatesFailureCallbacks)) {
              const callbacks = this.checkForUpdatesFailureCallbacks;
              this.checkForUpdatesFailureCallbacks = null;

              for (let callback of callbacks) {
                callback();
              }
            }
          });
      }
    },

    _checkForUpdates: function (forceRefresh, includeDetails) {
      return new Promise((resolve, reject) => {
        if (!forceRefresh) {
          this._checkForCachedUpdates(includeDetails)
            .then((info) => {
              if (info.cached) {
                resolve(info);
                return;
              }

              this._getUpdates(includeDetails)
                .then((info) => {
                  resolve(info);
                })
                .catch(reject);
            })
            .catch(reject);
        } else {
          this._getUpdates(includeDetails).then(resolve).catch(reject);
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
          .then(({data}) => {
            resolve(data);
          })
          .catch(() => {
            resolve({cached: false});
          });
      });
    },

    _getUpdates: function (includeDetails) {
      return new Promise((resolve, reject) => {
        Craft.sendApiRequest('GET', 'updates')
          .then((updates) => {
            this._cacheUpdates(updates, includeDetails)
              .then((data) => {
                resolve(data);
              })
              .catch(reject);
          })
          .catch(reject);
      });
    },

    _cacheUpdates: function (updates, includeDetails) {
      return new Promise((resolve, reject) => {
        const data = {
          updates,
          includeDetails,
        };

        Craft.sendActionRequest('POST', 'app/cache-updates', {data})
          .then(({data}) => {
            resolve(data);
          })
          .catch(reject);
      });
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
                let $badge = $utilitiesLink.children('.sidebar-action__badge');

                if (data.badgeCount && !$badge.length) {
                  $badge = $(
                    '<span class="sidebar-action__badge">' +
                      '<span class="badge" aria-hidden="true"></span>' +
                      '<span class="visually-hidden" data-notification></span>' +
                      '</span>'
                  ).appendTo($utilitiesLink);
                }

                const $badgeLabel = $badge.children('.badge');
                const $screenReaderText = $badge.children(
                  '[data-notification]'
                );

                if (data.badgeCount) {
                  $badgeLabel.text(data.badgeCount);
                  $screenReaderText.text(
                    Craft.t(
                      'app',
                      '{num, number} {num, plural, =1{notification} other{notifications}}',
                      {
                        num: data.badgeCount,
                      }
                    )
                  );
                } else if ($badge.length) {
                  $badge.remove();
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
      // Ignore if we're already tracking jobs, or the queue is disabled
      if ((this.trackJobProgressTimeout && !force) || !this.enableQueue) {
        return;
      }

      this.cancelJobTracking();

      if (delay) {
        // Determine the delay based on how long the displayed job info has remained unchanged
        if (delay === true) {
          delay = this.getNextJobDelay();
        }
        this.trackJobProgressTimeout = setTimeout(
          this._trackJobProgressInternal.bind(this),
          delay
        );
      } else {
        this._trackJobProgressInternal();
      }
    },

    getNextJobDelay: function () {
      return Math.min(60000, this.displayedJobInfoUnchanged * 500);
    },

    _trackJobProgressInternal: function () {
      if (!Craft.remainingSessionTime) {
        // Try again after login
        Garnish.once(Craft.AuthManager, 'login', () => {
          this._trackJobProgressInternal();
        });
        return;
      }

      this.trackingJobProgress = true;

      Craft.queue.push(async () => {
        // has this been cancelled?
        if (!this.trackingJobProgress) {
          return;
        }

        // Tell other browser windows to stop tracking job progress
        if (Craft.broadcaster) {
          Craft.broadcaster.postMessage({
            event: 'beforeTrackJobProgress',
          });
        }

        this.jobProgressCancelToken = axios.CancelToken.source();

        let data;
        try {
          const response = await Craft.sendActionRequest(
            'POST',
            'queue/get-job-info?limit=50&dontExtendSession=1',
            {
              cancelToken: this.jobProgressCancelToken.token,
            }
          );
          data = response.data;
        } catch (e) {
          if (e?.response?.status === 400) {
            // Try again after login
            Garnish.once(Craft.AuthManager, 'login', () => {
              this._trackJobProgressInternal();
            });
          } else if (this.trackingJobProgress) {
            // only throw if we weren't expecting this
            throw e;
          }
          return;
        } finally {
          this.trackingJobProgress = false;
          this.trackJobProgressTimeout = null;
          this.jobProgressCancelToken = null;
        }

        this.setJobData(data);

        if (this.jobInfo.length) {
          // Check again after a delay
          this.trackJobProgress(true);
        }

        // Notify the other browser tabs about the jobs
        if (Craft.broadcaster) {
          Craft.broadcaster.postMessage({
            event: 'trackJobProgress',
            jobData: data,
          });
        }
      });
    },

    setJobData: function (data) {
      this.totalJobs = data.total;
      this.setJobInfo(data.jobs);
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

    cancelJobTracking: function () {
      this.trackingJobProgress = false;

      if (this.trackJobProgressTimeout) {
        clearTimeout(this.trackJobProgressTimeout);
        this.trackJobProgressTimeout = null;
      }

      if (this.jobProgressCancelToken) {
        this.jobProgressCancelToken.cancel();
      }
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
     * @returns {number}
     */
    getSiteId: function () {
      // If the old BaseElementIndex.siteId value is in localStorage, go aheand and remove & return that
      let siteId = Craft.getLocalStorage('BaseElementIndex.siteId');
      if (typeof siteId !== 'undefined') {
        Craft.removeLocalStorage('BaseElementIndex.siteId');
        try {
          this.setSiteId(siteId);
        } catch (e) {}
      }
      return Craft.siteId;
    },

    /**
     * Sets the active site for the control panel
     * @param {number} siteId
     */
    setSiteId: function (siteId) {
      const site = Craft.sites.find((s) => s.id === siteId);

      if (!site) {
        throw `Invalid site ID: ${siteId}`;
      }

      Craft.siteId = siteId;

      // update the base URLs used get Craft.getUrl(), etc.
      Craft.actionUrl = Craft.getUrl(Craft.actionUrl, {site: site.handle});
      Craft.baseCpUrl = Craft.getUrl(Craft.baseCpUrl, {site: site.handle});
      Craft.baseUrl = Craft.getUrl(Craft.baseUrl, {site: site.handle});

      // update the current URL
      const url = Craft.getUrl(document.location.href, {site: site.handle});
      history.replaceState({}, '', url);

      // update the site--x body class
      for (let className of document.body.classList) {
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
    },
  },
  {
    //maxWidth: 1051, //1024,

    /**
     * @deprecated in 4.2.0. Use Craft.notificationDuration instead.
     */
    notificationDuration: 5000,

    JOB_STATUS_WAITING: 1,
    JOB_STATUS_RESERVED: 2,
    JOB_STATUS_DONE: 3,
    JOB_STATUS_FAILED: 4,
  }
);

Craft.CP.Notification = Garnish.Base.extend({
  type: null,
  message: null,
  settings: null,
  closing: false,
  closeTimeout: null,
  _preventDelayedClose: false,
  $container: null,
  $closeBtn: null,
  originalActiveElement: null,

  init: function (type, message, settings) {
    this.type = type;
    this.message = message;
    this.settings = settings || {};

    this.$container = $('<div/>', {
      class: 'notification',
      'data-type': this.type,
    }).appendTo(Craft.cp.$notificationContainer);

    const $body = $('<div class="notification-body"/>').appendTo(
      this.$container
    );

    if (this.settings.icon) {
      const $icon = $('<span/>', {
        class: 'notification-icon',
        'data-icon': this.settings.icon,
      }).appendTo($body);
      if (this.settings.iconLabel) {
        $icon.attr({
          'aria-label': this.settings.iconLabel,
          role: 'img',
        });
      } else {
        $icon.attr('aria-hidden', 'true');
      }
    }

    const $main = $('<div class="notification-main"/>').appendTo($body);

    $('<div/>', {
      class: 'notification-message',
      text: this.message,
    }).appendTo($main);

    const $closeBtnContainer = $('<div/>').appendTo(this.$container);
    this.$closeBtn = $('<button/>', {
      type: 'button',
      class: 'notification-close-btn',
      'aria-label': Craft.t('app', 'Close'),
      'data-icon': 'remove',
    }).appendTo($closeBtnContainer);

    if (this.settings.details) {
      const $detailsContainer = $('<div class="notification-details"/>')
        .append(this.settings.details)
        .appendTo($main);

      if ($detailsContainer.find('button,input').length) {
        this.originalActiveElement = document.activeElement;
        this.$container.attr('tabindex', '-1').focus();
        this.addListener(this.$container, 'keydown', (ev) => {
          if (ev.keyCode === Garnish.ESC_KEY) {
            this.close();
          }
        });
      }
    }

    this.$container
      .css({
        opacity: 0,
        'margin-bottom': this._negMargin(),
      })
      .velocity({opacity: 1, 'margin-bottom': 0}, {duration: 'fast'});

    Craft.initUiElements(this.$container);

    this.addListener(this.$closeBtn, 'click', 'close');

    if (Craft.notificationDuration) {
      this._initDelayedClose();
    }
  },

  _initDelayedClose: function () {
    if (this._preventDelayedClose) {
      return;
    }

    if (!Craft.isVisible()) {
      Garnish.$doc.one('visibilitychange', () => {
        this._initDelayedClose();
      });
      return;
    }

    this.delayedClose();

    this.$container.on(
      'keypress keyup change focus click mousedown mouseup',
      (ev) => {
        if (ev.target != this.$closeBtn[0]) {
          this.$container.off(
            'keypress keyup change focus click mousedown mouseup'
          );
          this.preventDelayedClose();
        }
      }
    );
  },

  _negMargin: function () {
    return `-${this.$container.outerHeight() + 12}px`;
  },

  close: function () {
    if (this.closing) {
      return;
    }

    if (this.closeTimeout) {
      clearTimeout(this.closeTimeout);
      this.closeTimeout = null;
    }

    this.closing = true;

    if (
      this.originalActiveElement &&
      document.activeElement &&
      (document.activeElement === this.$container[0] ||
        $.contains(this.$container[0], document.activeElement))
    ) {
      $(this.originalActiveElement).focus();
    }

    this.$container.velocity(
      {opacity: 0, 'margin-bottom': this._negMargin()},
      {
        duration: 'fast',
        complete: () => {
          this.destroy();
          Craft.cp.trigger('notificationClose');
        },
      }
    );
  },

  delayedClose: function () {
    this.closeTimeout = setTimeout(() => {
      this.close();
    }, Craft.notificationDuration);

    // Hold off on closing automatically on hover
    this.$container.one('mouseover', () => {
      clearTimeout(this.closeTimeout);
      this.closeTimeout = null;

      this.$container.on('mouseout', (ev) => {
        if (ev.target == this.$container[0]) {
          this.$container.off('mouseout');
          this.delayedClose();
        }
      });
    });
  },

  preventDelayedClose: function () {
    this._preventDelayedClose = true;

    if (this.closeTimeout) {
      clearTimeout(this.closeTimeout);
      this.closeTimeout = null;
    }

    this.$container.off('mouseover mouseout');
  },

  destroy: function () {
    this.$container.remove();
    this.base();
  },
});

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
  $tooltip: $(),

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
    this.$li = $('<li/>', {
      class: 'nav-item nav-item--job',
    }).appendTo(Craft.cp.$nav.children('ul'));
    this.$a = $('<a/>', {
      id: 'job-icon',
      class: 'sidebar-action sidebar-action--job',
      href: Craft.canAccessQueueManager
        ? Craft.getUrl('utilities/queue-manager')
        : null,
    }).appendTo(this.$li);
    const $prefixContainer = $('<span class="sidebar-action__prefix"/>');
    this.$canvasContainer = $('<span class="nav-icon"/>').appendTo(
      $prefixContainer
    );
    $prefixContainer.appendTo(this.$a);

    const $labelContainer = $('<span class="sidebar-action__label">');
    $labelContainer.appendTo(this.$a);
    this.$label = $('<span class="label"/>').appendTo($labelContainer);
    this.$progressLabel = $('<span class="progress-label"/>')
      .appendTo($labelContainer)
      .hide();

    // If the sidebar is collapsed, make sure to add a tooltip.
    // CraftGlobalSidebar.js will handle removing it and adding it back on expand/contract
    if (Garnish.$bod.data('sidebar') === 'collapsed') {
      this.$tooltip = $('<craft-tooltip/>', {
        placement: 'right',
        'self-managed': true,
        'aria-label': this.$label.text(),
      }).appendTo(this.$a);
    }

    let m = window.devicePixelRatio > 1 ? 2 : 1;
    this._canvasSize = 18 * m;
    this._arcPos = this._canvasSize / 2;
    this._arcRadius = 7 * m;
    this._lineWidth = 3 * m;

    this._$bgCanvas = this._createCanvas('bg', '#a3afbb');
    this._$staticCanvas = this._createCanvas('static', this.$li.css('color'));
    this._$hoverCanvas = this._createCanvas('hover', this.$li.css('color'));
    this._$failCanvas = this._createCanvas('fail', '#da5a47').hide();

    this._staticCtx = this._$staticCanvas[0].getContext('2d');
    this._hoverCtx = this._$hoverCanvas[0].getContext('2d');

    this._drawArc(this._$bgCanvas[0].getContext('2d'), 0, 1);
    this._drawArc(this._$failCanvas[0].getContext('2d'), 0, 1);
  },

  setDescription: function (description, progressLabel) {
    this.$label.text(description);
    if (progressLabel) {
      this.$progressLabel.text(progressLabel).show();
    } else {
      this.$progressLabel.hide();
    }

    if (this.$tooltip.length) {
      this.$tooltip.attr('aria-label', description);
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
        this.$li.remove();
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
