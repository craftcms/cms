/** global: Craft */
/** global: Garnish */
/**
 * CP class
 */
Craft.CP = Garnish.Base.extend(
    {
        authManager: null,

        $nav: null,
        $mainContainer: null,
        $alerts: null,
        $crumbs: null,
        $notificationContainer: null,
        $main: null,
        $primaryForm: null,
        $headerContainer: null,
        $header: null,
        $mainContent: null,
        $details: null,
        $tabsContainer: null,
        $tabsList: null,
        $tabs: null,
        $overflowTabBtn: null,
        $overflowTabList: null,
        $selectedTab: null,
        selectedTabIndex: null,
        $sidebarContainer: null,
        $sidebar: null,
        $contentContainer: null,
        $edition: null,

        $confirmUnloadForms: null,
        $deltaForms: null,
        $collapsibleTables: null,

        fixedHeader: false,

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

        init: function() {
            // Is this session going to expire?
            if (Craft.remainingSessionTime !== 0) {
                this.authManager = new Craft.AuthManager();
            }

            // Find all the key elements
            this.$nav = $('#nav');
            this.$mainContainer = $('#main-container');
            this.$alerts = $('#alerts');
            this.$crumbs = $('#crumbs');
            this.$notificationContainer = $('#notifications');
            this.$main = $('#main');
            this.$primaryForm = $('#main-form');
            this.$headerContainer = $('#header-container');
            this.$header = $('#header');
            this.$mainContent = $('#main-content');
            this.$details = $('#details');
            this.$sidebarContainer = $('#sidebar-container');
            this.$sidebar = $('#sidebar');
            this.$contentContainer = $('#content-container');
            this.$collapsibleTables = $('table.collapsible');
            this.$edition = $('#edition');

            this.updateSidebarMenuLabel();

            if (this.$header.length) {
                this.addListener(Garnish.$win, 'scroll', 'updateFixedHeader');
                this.updateFixedHeader();
            }

            Garnish.$doc.ready($.proxy(function() {
                // Update responsive tables on window resize
                this.addListener(Garnish.$win, 'resize', 'handleWindowResize');
                this.handleWindowResize();

                // Fade the notification out two seconds after page load
                var $errorNotifications = this.$notificationContainer.children('.error'),
                    $otherNotifications = this.$notificationContainer.children(':not(.error)');

                $errorNotifications.delay(Craft.CP.notificationDuration * 2).velocity('fadeOut');
                $otherNotifications.delay(Craft.CP.notificationDuration).velocity('fadeOut');

                // Wait a frame before initializing any confirm-unload forms,
                // so other JS that runs on ready() has a chance to initialize
                Garnish.requestAnimationFrame($.proxy(this, 'initSpecialForms'));
            }, this));

            // Alerts
            if (this.$alerts.length) {
                this.initAlerts();
            }

            // Toggles
            this.addListener($('#nav-toggle'), 'click', 'toggleNav');
            this.addListener($('#sidebar-toggle'), 'click', 'toggleSidebar');

            // Does this page have a primary form?
            if (!this.$primaryForm.length) {
                this.$primaryForm = $('form[data-saveshortcut]:first');
            }

            // Does the primary form support the save shortcut?
            if (this.$primaryForm.length && Garnish.hasAttr(this.$primaryForm, 'data-saveshortcut')) {
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
                            retainScroll: Garnish.hasAttr(this.$primaryForm, 'saveshortcut-scroll'),
                        }
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
                            }
                        ]);
                    }
                }
                for (let i = 0; i < shortcuts.length; i++) {
                    Garnish.shortcutManager.registerShortcut(shortcuts[i][0], () => {
                        this.submitPrimaryForm(shortcuts[i][1]);
                    });
                }
            }

            this.initTabs();

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

            if (this.$edition.hasClass('hot')) {
                this.addListener(this.$edition, 'click', function() {
                    document.location.href = Craft.getUrl('plugin-store/upgrade-craft');
                });
            }

            if ($.isTouchCapable()) {
                this.$mainContainer.on('focus', 'input, textarea, .focusable-input', $.proxy(this, '_handleInputFocus'));
                this.$mainContainer.on('blur', 'input, textarea, .focusable-input', $.proxy(this, '_handleInputBlur'));
            }

            // Open outbound links in new windows
            // hat tip: https://stackoverflow.com/a/2911045/1688568
            $('a').each(function() {
                if (this.hostname.length && this.hostname !== location.hostname && typeof $(this).attr('target') === 'undefined') {
                    $(this).attr('rel', 'noopener').attr('target', '_blank')
                }
            });
        },

        initSpecialForms: function() {
            // Look for forms that we should watch for changes on
            this.$confirmUnloadForms = $('form[data-confirm-unload]');
            this.$deltaForms = $('form[data-delta]');

            if (!this.$confirmUnloadForms.length) {
                return;
            }

            var $forms = this.$confirmUnloadForms.add(this.$deltaForms);
            var $form, serialized;

            for (var i = 0; i < $forms.length; i++) {
                $form = $forms.eq(i);
                if (!$form.data('initialSerializedValue')) {
                    if (typeof $form.data('serializer') === 'function') {
                        serialized = $form.data('serializer')();
                    } else {
                        serialized = $form.serialize();
                    }
                    $form.data('initialSerializedValue', serialized);
                }
                this.addListener($form, 'submit', function(ev) {
                    if (Garnish.hasAttr($form, 'data-confirm-unload')) {
                        this.removeListener(Garnish.$win, 'beforeunload');
                    }
                    if (Garnish.hasAttr($form, 'data-delta')) {
                        ev.preventDefault();
                        var serialized;
                        if (typeof $form.data('serializer') === 'function') {
                            serialized = $form.data('serializer')();
                        } else {
                            serialized = $form.serialize();
                        }
                        var data = Craft.findDeltaData($form.data('initialSerializedValue'), serialized, Craft.deltaNames);
                        Craft.createForm(data)
                            .appendTo(Garnish.$bod)
                            .submit();
                    }
                });
            }

            this.addListener(Garnish.$win, 'beforeunload', function(ev) {
                var confirmUnload = false;
                var $form, serialized;
                if (typeof Craft.livePreview !== 'undefined' && Craft.livePreview.inPreviewMode) {
                    confirmUnload = true;
                } else {
                    for (var i = 0; i < this.$confirmUnloadForms.length; i++) {
                        $form = this.$confirmUnloadForms.eq(i);
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
                    var message = Craft.t('app', 'Any changes will be lost if you leave this page.');

                    if (ev) {
                        ev.originalEvent.returnValue = message;
                    }
                    else {
                        window.event.returnValue = message;
                    }

                    return message;
                }
            });
        },

        _handleInputFocus: function() {
            this.updateFixedHeader();
        },

        _handleInputBlur: function() {
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
        submitPrimaryForm: function(options) {
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

        updateSidebarMenuLabel: function() {
            var $item = this.$sidebar.find('a.sel:first');
            var $label = $item.children('.label');
            $('#selected-sidebar-item-label').text($label.length ? $label.text() : $item.text());
            Garnish.$bod.removeClass('showing-sidebar');
        },

        toggleNav: function() {
            Garnish.$bod.toggleClass('showing-nav');
        },

        toggleSidebar: function() {
            Garnish.$bod.toggleClass('showing-sidebar');
        },

        initTabs: function() {
            // Clear out all our old info in case the tabs were just replaced
            this.$tabsList = this.$tabs = this.$overflowTabBtn = this.$overflowTabList = this.$selectedTab =
                this.selectedTabIndex = null;

            this.$tabsContainer = $('#tabs');
            if (!this.$tabsContainer.length) {
                this.$tabsContainer = null;
                return;
            }

            this.$tabsList = this.$tabsContainer.find('> ul');
            this.$tabs = this.$tabsList.find('> li');
            this.$overflowTabBtn = $('#overflow-tab-btn');
            if (!this.$overflowTabBtn.data('menubtn')) {
                new Garnish.MenuBtn(this.$overflowTabBtn);
            }
            this.$overflowTabList = this.$overflowTabBtn.data('menubtn').menu.$container.find('> ul');
            var i, $tab, $a, href;

            for (i = 0; i < this.$tabs.length; i++) {
                $tab = this.$tabs.eq(i);

                // Does it link to an anchor?
                $a = $tab.children('a');
                href = $a.attr('href');
                if (href && href.charAt(0) === '#') {
                    this.addListener($a, 'click', function(ev) {
                        ev.preventDefault();
                        this.selectTab(ev.currentTarget);
                    });

                    if (encodeURIComponent(href.substr(1)) === document.location.hash.substr(1)) {
                        this.selectTab($a);
                    }
                }

                if (!this.$selectedTab && $a.hasClass('sel')) {
                    this._selectTab($a, i);
                }
            }
        },

        selectTab: function(tab) {
            var $tab = $(tab);

            if (this.$selectedTab) {
                if (this.$selectedTab.get(0) === $tab.get(0)) {
                    return;
                }
                this.deselectTab();
            }

            $tab.addClass('sel');
            var href = $tab.attr('href')
            $(href).removeClass('hidden');
            if (typeof history !== 'undefined') {
                history.replaceState(undefined, undefined, href);
            }
            this._selectTab($tab, this.$tabs.index($tab.parent()));
            this.updateTabs();
            this.$overflowTabBtn.data('menubtn').menu.hide();
        },

        _selectTab: function($tab, index) {
            if ($tab === this.$selectedTab) {
                return;
            }

            this.$selectedTab = $tab;
            this.selectedTabIndex = index;
            if (index === 0) {
                $('#content').addClass('square');
            } else {
                $('#content').removeClass('square');
            }

            Garnish.$win.trigger('resize');
            // Fixes Redactor fixed toolbars on previously hidden panes
            Garnish.$doc.trigger('scroll');

            // If there is a revision menu, set its links to this tab ID
            let href = $tab && $tab.attr('href');
            if (href && href.charAt(0) === '#') {
                let menubtn = $('#context-btn').menubtn().data('menubtn');
                if (menubtn) {
                    for (let i = 0; i < menubtn.menu.$options.length; i++) {
                        let a = menubtn.menu.$options[i];
                        if (a.href) {
                            a.href = a.href.match(/^[^#]*/)[0] + href;
                        }
                    }
                }
            }
        },

        deselectTab: function() {
            if (!this.$selectedTab) {
                return;
            }

            this.$selectedTab.removeClass('sel');
            if (this.$selectedTab.attr('href').charAt(0) === '#') {
                $(this.$selectedTab.attr('href')).addClass('hidden');
            }
            this._selectTab(null, null);
        },

        handleWindowResize: function() {
            this.updateTabs();
            this.updateResponsiveTables();
        },

        updateTabs: function() {
            if (!this.$tabsContainer) {
                return;
            }

            var maxWidth = Math.floor(this.$tabsContainer.width()) - 40;
            var totalWidth = 0;
            var showOverflowMenu = false;
            var tabMargin = Garnish.$bod.width() >= 768 ? -12 : -7;
            var $tab;

            // Start with the selected tab, because that needs to be visible
            if (this.$selectedTab) {
                this.$selectedTab.parent('li').appendTo(this.$tabsList);
                totalWidth = Math.ceil(this.$selectedTab.parent('li').width());
            }

            for (var i = 0; i < this.$tabs.length; i++) {
                $tab = this.$tabs.eq(i).appendTo(this.$tabsList);
                if (i !== this.selectedTabIndex) {
                    totalWidth += Math.ceil($tab.width());
                    // account for the negative margin
                    if (i !== 0 || this.$selectedTab) {
                        totalWidth += tabMargin;
                    }
                }

                if (i === this.selectedTabIndex || totalWidth <= maxWidth) {
                    $tab.find('> a').removeAttr('role');
                } else {
                    $tab.appendTo(this.$overflowTabList).find('> a').attr('role', 'option');
                    showOverflowMenu = true;
                }
            }

            if (showOverflowMenu) {
                this.$overflowTabBtn.removeClass('hidden');
            } else {
                this.$overflowTabBtn.addClass('hidden');
            }
        },

        updateResponsiveTables: function() {
            for (this.updateResponsiveTables._i = 0; this.updateResponsiveTables._i < this.$collapsibleTables.length; this.updateResponsiveTables._i++) {
                this.updateResponsiveTables._$table = this.$collapsibleTables.eq(this.updateResponsiveTables._i);
                this.updateResponsiveTables._containerWidth = this.updateResponsiveTables._$table.parent().width();
                this.updateResponsiveTables._check = false;

                if (this.updateResponsiveTables._containerWidth > 0) {
                    // Is this the first time we've checked this table?
                    if (typeof this.updateResponsiveTables._$table.data('lastContainerWidth') === 'undefined') {
                        this.updateResponsiveTables._check = true;
                    }
                    else {
                        this.updateResponsiveTables._isCollapsed = this.updateResponsiveTables._$table.hasClass('collapsed');

                        // Getting wider?
                        if (this.updateResponsiveTables._containerWidth > this.updateResponsiveTables._$table.data('lastContainerWidth')) {
                            if (this.updateResponsiveTables._isCollapsed) {
                                this.updateResponsiveTables._$table.removeClass('collapsed');
                                this.updateResponsiveTables._check = true;
                            }
                        }
                        else if (!this.updateResponsiveTables._isCollapsed) {
                            this.updateResponsiveTables._check = true;
                        }
                    }

                    // Are we checking the table width?
                    if (this.updateResponsiveTables._check) {
                        if (this.updateResponsiveTables._$table.width() - 30 > this.updateResponsiveTables._containerWidth) {
                            this.updateResponsiveTables._$table.addClass('collapsed');
                        }
                    }

                    // Remember the container width for next time
                    this.updateResponsiveTables._$table.data('lastContainerWidth', this.updateResponsiveTables._containerWidth);
                }
            }
        },

        updateFixedHeader: function() {
            // Have we scrolled passed the top of #main?
            if (this.$main.length && this.$headerContainer[0].getBoundingClientRect().top < 0) {
                if (!this.fixedHeader) {
                    var headerHeight = this.$headerContainer.height();

                    // Hard-set the minimum content container height
                    this.$contentContainer.css('min-height', 'calc(100vh - ' + (headerHeight + 14 + 48 - 1) + 'px)');

                    // Hard-set the header container height
                    this.$headerContainer.height(headerHeight);
                    Garnish.$bod.addClass('fixed-header');

                    // Fix the sidebar and details pane positions if they are taller than #content-container
                    var contentHeight = this.$contentContainer.outerHeight();
                    var $detailsHeight = this.$details.outerHeight();
                    var css = {
                        top: headerHeight + 'px',
                        'max-height': 'calc(100vh - ' + headerHeight + 'px)'
                    };
                    this.$sidebar.addClass('fixed').css(css);
                    this.$details.addClass('fixed').css(css);
                    this.fixedHeader = true;
                }
            }
            else if (this.fixedHeader) {
                this.$headerContainer.height('auto');
                Garnish.$bod.removeClass('fixed-header');
                this.$contentContainer.css('min-height', '');
                this.$sidebar.removeClass('fixed').css({
                    top: '',
                    'max-height': ''
                });
                this.$details.removeClass('fixed').css({
                    top: '',
                    'max-height': ''
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
        displayNotification: function(type, message) {
            var notificationDuration = Craft.CP.notificationDuration;

            if (type === 'error') {
                notificationDuration *= 2;
            }

            var $notification = $('<div class="notification ' + type + '">' + message + '</div>')
                .appendTo(this.$notificationContainer);

            var fadedMargin = -($notification.outerWidth() / 2) + 'px';

            $notification
                .hide()
                .css({opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin})
                .velocity({opacity: 1, 'margin-left': '2px', 'margin-right': '2px'}, {display: 'inline-block', duration: 'fast'})
                .delay(notificationDuration)
                .velocity({opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin}, {
                    complete: function() {
                        $notification.remove();
                    }
                });

            this.trigger('displayNotification', {
                notificationType: type,
                message: message
            });
        },

        /**
         * Displays a notice.
         *
         * @param {string} message
         */
        displayNotice: function(message) {
            this.displayNotification('notice', message);
        },

        /**
         * Displays an error.
         *
         * @param {string} message
         */
        displayError: function(message) {
            if (!message) {
                message = Craft.t('app', 'A server error occurred.');
            }

            this.displayNotification('error', message);
        },

        fetchAlerts: function() {
            var data = {
                path: Craft.path
            };

            Craft.queueActionRequest('app/get-cp-alerts', data, $.proxy(this, 'displayAlerts'));
        },

        displayAlerts: function(alerts) {
            this.$alerts.remove();

            if (Garnish.isArray(alerts) && alerts.length) {
                this.$alerts = $('<ul id="alerts"/>').prependTo($('#page-container'));

                for (var i = 0; i < alerts.length; i++) {
                    $('<li>' + alerts[i] + '</li>').appendTo(this.$alerts);
                }

                var height = this.$alerts.outerHeight();
                this.$alerts.css('margin-top', -height).velocity({'margin-top': 0}, 'fast');

                this.initAlerts();
            }
        },

        initAlerts: function() {
            // Are there any shunnable alerts?
            var $shunnableAlerts = this.$alerts.find('a[class^="shun:"]');

            for (var i = 0; i < $shunnableAlerts.length; i++) {
                this.addListener($shunnableAlerts[i], 'click', $.proxy(function(ev) {
                    ev.preventDefault();

                    var $link = $(ev.currentTarget);

                    var data = {
                        message: $link.prop('className').substr(5)
                    };

                    Craft.queueActionRequest('app/shun-cp-alert', data, $.proxy(function(response, textStatus) {
                        if (textStatus === 'success') {
                            if (response.success) {
                                $link.parent().remove();
                            }
                            else {
                                this.displayError(response.error);
                            }
                        }
                    }, this));
                }, this));
            }
        },

        checkForUpdates: function(forceRefresh, includeDetails, callback) {
            // Make 'includeDetails' optional
            if (typeof includeDetails === 'function') {
                callback = includeDetails;
                includeDetails = false;
            }

            // If forceRefresh == true, we're currently checking for updates, and not currently forcing a refresh,
            // then just set a new callback that re-checks for updates when the current one is done.
            if (this.checkingForUpdates && (
                (forceRefresh === true && !this.forcingRefreshOnUpdatesCheck) ||
                (includeDetails === true && !this.includingDetailsOnUpdatesCheck)
            )) {
                var realCallback = callback;

                callback = function() {
                    this.checkForUpdates(forceRefresh, includeDetails, realCallback);
                }.bind(this);
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
                this.forcingRefreshOnUpdatesCheck = (forceRefresh === true);
                this.includingDetailsOnUpdatesCheck = (includeDetails === true);

                this._checkForUpdates(forceRefresh, includeDetails)
                    .then(function(info) {
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
                            updateInfo: info
                        });
                    }.bind(this));
            }
        },

        _checkForUpdates: function(forceRefresh, includeDetails) {
            return new Promise(function(resolve, reject) {
                if (!forceRefresh) {
                    this._checkForCachedUpdates(includeDetails)
                        .then(function(info) {
                            if (info.cached !== false) {
                                resolve(info);
                            }

                            this._getUpdates(includeDetails)
                                .then(function(info) {
                                    resolve(info);
                                });
                        }.bind(this));
                } else {
                    this._getUpdates(includeDetails)
                        .then(function(info) {
                            resolve(info);
                        });
                }
            }.bind(this));
        },

        _checkForCachedUpdates: function(includeDetails) {
            return new Promise(function(resolve, reject) {
                var data = {
                    onlyIfCached: true,
                    includeDetails: includeDetails,
                };
                Craft.postActionRequest('app/check-for-updates', data, function(info, textStatus) {
                    if (textStatus === 'success') {
                        resolve(info);
                    } else {
                        resolve({ cached: false });
                    }
                });
            });
        },

        _getUpdates: function(includeDetails) {
            return new Promise(function(resolve, reject) {
                Craft.sendApiRequest('GET', 'updates')
                    .then(function(updates) {
                        this._cacheUpdates(updates, includeDetails).then(resolve);
                    }.bind(this))
                    .catch(function(e) {
                        this._cacheUpdates({}).then(resolve);
                    }.bind(this));
            }.bind(this));
        },

        _cacheUpdates: function(updates, includeDetails) {
            return new Promise(function(resolve, reject) {
                Craft.postActionRequest('app/cache-updates', {
                    updates: updates,
                    includeDetails: includeDetails,
                }, function(info, textStatus) {
                    if (textStatus === 'success') {
                        resolve(info);
                    } else {
                        reject();
                    }
                }, {
                    contentType: 'json'
                });
            });
        },

        updateUtilitiesBadge: function() {
            var $utilitiesLink = $('#nav-utilities').find('> a:not(.sel)');

            // Ignore if there is no (non-selected) Utilities nav item
            if (!$utilitiesLink.length) {
                return;
            }

            Craft.queueActionRequest('app/get-utilities-badge-count', $.proxy(function(response) {
                // Get the existing utility nav badge, if any
                var $badge = $utilitiesLink.children('.badge');

                if (response.badgeCount) {
                    if (!$badge.length) {
                        $badge = $('<span class="badge"/>').appendTo($utilitiesLink);
                    }
                    $badge.text(response.badgeCount);
                } else if ($badge.length) {
                    $badge.remove();
                }
            }, this));
        },

        runQueue: function() {
            if (!this.enableQueue) {
                return;
            }

            if (Craft.runQueueAutomatically) {
                Craft.queueActionRequest('queue/run', $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        this.trackJobProgress(false, true);
                    }
                }, this));
            }
            else {
                this.trackJobProgress(false, true);
            }
        },

        trackJobProgress: function(delay, force) {
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
                this.trackJobProgressTimeout = setTimeout($.proxy(this, '_trackJobProgressInternal'), timeout);
            } else {
                this._trackJobProgressInternal();
            }
        },

        _trackJobProgressInternal: function() {
            Craft.queueActionRequest('queue/get-job-info?limit=50&dontExtendSession=1', $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    this.trackJobProgressTimeout = null;
                    this.totalJobs = response.total;
                    this.setJobInfo(response.jobs);

                    if (this.jobInfo.length) {
                        // Check again after a delay
                        this.trackJobProgress(true);
                    }
                }
            }, this));
        },

        setJobInfo: function(jobInfo) {
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
         * Returns info for the job that should be displayed in the CP sidebar
         */
        getDisplayedJobInfo: function() {
            if (!this.enableQueue) {
                return null;
            }

            // Set the status preference order
            var statuses = [
                Craft.CP.JOB_STATUS_RESERVED,
                Craft.CP.JOB_STATUS_FAILED,
                Craft.CP.JOB_STATUS_WAITING
            ];

            for (var i = 0; i < statuses.length; i++) {
                for (var j = 0; j < this.jobInfo.length; j++) {
                    if (this.jobInfo[j].status === statuses[i]) {
                        return this.jobInfo[j];
                    }
                }
            }
        },

        updateJobIcon: function() {
            if (!this.enableQueue || !this.$nav.length) {
                return;
            }

            if (this.displayedJobInfo) {
                if (!this.jobProgressIcon) {
                    this.jobProgressIcon = new JobProgressIcon();
                }

                if (this.displayedJobInfo.status === Craft.CP.JOB_STATUS_RESERVED || this.displayedJobInfo.status === Craft.CP.JOB_STATUS_WAITING) {
                    this.jobProgressIcon.hideFailMode();
                    this.jobProgressIcon.setDescription(this.displayedJobInfo.description, this.displayedJobInfo.progressLabel);
                    this.jobProgressIcon.setProgress(this.displayedJobInfo.progress);
                }
                else if (this.displayedJobInfo.status === Craft.CP.JOB_STATUS_FAILED) {
                    this.jobProgressIcon.showFailMode(Craft.t('app', 'Failed'));
                }
            }
            else {
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
        getSiteId: function() {
            // If the old BaseElementIndex.siteId value is in localStorage, go aheand and remove & return that
            let siteId = Craft.getLocalStorage('BaseElementIndex.siteId');
            if (typeof siteId !== 'undefined') {
                Craft.removeLocalStorage('BaseElementIndex.siteId');
                this.setSiteId(siteId);
                return siteId;
            }
            return Craft.getCookie('siteId');
        },

        /**
         * Sets the active site for the control panel
         * @param {number} siteId
         */
        setSiteId: function(siteId) {
            Craft.setCookie('siteId', siteId, {
                maxAge: 31536000 // 1 year
            });
        }
    },
    {
        //maxWidth: 1051, //1024,
        notificationDuration: 2000,

        JOB_STATUS_WAITING: 1,
        JOB_STATUS_RESERVED: 2,
        JOB_STATUS_DONE: 3,
        JOB_STATUS_FAILED: 4
    });

Garnish.$scrollContainer = Garnish.$win;
Craft.cp = new Craft.CP();


/**
 * Job progress icon class
 */
var JobProgressIcon = Garnish.Base.extend(
    {
        $li: null,
        $a: null,
        $label: null,
        $progressLabel: null,

        progress: null,
        failMode: false,

        _canvasSupported: null,

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

        init: function() {
            this.$li = $('<li/>').appendTo(Craft.cp.$nav.children('ul'));
            this.$a = $('<a/>', {
                id: 'job-icon',
                href: Craft.canAccessQueueManager ? Craft.getUrl('utilities/queue-manager') : null,
            }).appendTo(this.$li);
            this.$canvasContainer = $('<span class="icon"/>').appendTo(this.$a);
            var $labelContainer = $('<span class="label"/>').appendTo(this.$a);
            this.$label = $('<span/>').appendTo($labelContainer);
            this.$progressLabel = $('<span class="progress-label"/>').appendTo($labelContainer).hide();

            this._canvasSupported = !!(document.createElement('canvas').getContext);

            if (this._canvasSupported) {
                var m = (window.devicePixelRatio > 1 ? 2 : 1);
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
            }
            else {
                this._progressBar = new Craft.ProgressBar(this.$canvasContainer);
                this._progressBar.showProgressBar();
            }
        },

        setDescription: function(description, progressLabel) {
            this.$a.attr('title', description);
            this.$label.text(description);
            if (progressLabel) {
                this.$progressLabel.text(progressLabel).show();
            } else {
                this.$progressLabel.hide();
            }
        },

        setProgress: function(progress) {
            if (this._canvasSupported) {
                if (progress == 0) {
                    this._$staticCanvas.hide();
                    this._$hoverCanvas.hide();
                } else {
                    this._$staticCanvas.show();
                    this._$hoverCanvas.show();
                    if (this.progress && progress > this.progress) {
                        this._animateArc(0, progress / 100);
                    }
                    else {
                        this._setArc(0, progress / 100);
                    }
                }
            }
            else {
                this._progressBar.setProgressPercentage(progress);
            }

            this.progress = progress;
        },

        complete: function() {
            if (this._canvasSupported) {
                this._animateArc(0, 1, $.proxy(function() {
                    this._$bgCanvas.velocity('fadeOut');

                    this._animateArc(1, 1, $.proxy(function() {
                        this.$a.remove();
                        this.destroy();
                    }, this));
                }, this));
            }
            else {
                this._progressBar.setProgressPercentage(100);
                this.$a.velocity('fadeOut');
            }
        },

        showFailMode: function(message) {
            if (this.failMode) {
                return;
            }

            this.failMode = true;
            this.progress = null;

            if (this._canvasSupported) {
                this._$bgCanvas.hide();
                this._$staticCanvas.hide();
                this._$hoverCanvas.hide();
                this._$failCanvas.show();
            }
            else {
                this._progressBar.$progressBar.css('border-color', '#da5a47');
                this._progressBar.$innerProgressBar.css('background-color', '#da5a47');
                this._progressBar.setProgressPercentage(50);
            }

            this.setDescription(message);
        },

        hideFailMode: function() {
            if (!this.failMode) {
                return;
            }

            this.failMode = false;

            if (this._canvasSupported) {
                this._$bgCanvas.show();
                this._$staticCanvas.show();
                this._$hoverCanvas.show();
                this._$failCanvas.hide();
            }
            else {
                this._progressBar.$progressBar.css('border-color', '');
                this._progressBar.$innerProgressBar.css('background-color', '');
                this._progressBar.setProgressPercentage(50);
            }
        },

        _createCanvas: function(id, color) {
            var $canvas = $('<canvas id="job-icon-' + id + '" width="' + this._canvasSize + '" height="' + this._canvasSize + '"/>').appendTo(this.$canvasContainer),
                ctx = $canvas[0].getContext('2d');

            ctx.strokeStyle = color;
            ctx.lineWidth = this._lineWidth;
            ctx.lineCap = 'round';
            return $canvas;
        },

        _setArc: function(startPos, endPos) {
            this._arcStartPos = startPos;
            this._arcEndPos = endPos;

            this._drawArc(this._staticCtx, startPos, endPos);
            this._drawArc(this._hoverCtx, startPos, endPos);
        },

        _drawArc: function(ctx, startPos, endPos) {
            ctx.clearRect(0, 0, this._canvasSize, this._canvasSize);
            ctx.beginPath();
            ctx.arc(this._arcPos, this._arcPos, this._arcRadius, (1.5 + (startPos * 2)) * Math.PI, (1.5 + (endPos * 2)) * Math.PI);
            ctx.stroke();
            ctx.closePath();
        },

        _animateArc: function(targetStartPos, targetEndPos, callback) {
            if (this._arcStepTimeout) {
                clearTimeout(this._arcStepTimeout);
            }

            this._arcStep = 0;
            this._arcStartStepSize = (targetStartPos - this._arcStartPos) / 10;
            this._arcEndStepSize = (targetEndPos - this._arcEndPos) / 10;
            this._arcAnimateCallback = callback;
            this._takeNextArcStep();
        },

        _takeNextArcStep: function() {
            this._setArc(this._arcStartPos + this._arcStartStepSize, this._arcEndPos + this._arcEndStepSize);

            this._arcStep++;

            if (this._arcStep < 10) {
                this._arcStepTimeout = setTimeout($.proxy(this, '_takeNextArcStep'), 50);
            }
            else if (this._arcAnimateCallback) {
                this._arcAnimateCallback();
            }
        }
    });
