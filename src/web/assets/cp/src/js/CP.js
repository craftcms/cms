/** global: Craft */
/** global: Garnish */
/**
 * CP class
 */
Craft.CP = Garnish.Base.extend(
    {
        authManager: null,

        $container: null,
        $alerts: null,
        $globalSidebar: null,
        $globalSidebarTopbar: null,
        $systemNameLink: null,
        $systemName: null,
        $nav: null,
        $subnav: null,
        $pageHeader: null,
        $containerTopbar: null,

        $overflowNavMenuItem: null,
        $overflowNavMenuBtn: null,
        $overflowNavMenu: null,
        $overflowNavMenuList: null,

        $overflowSubnavMenuItem: null,
        $overflowSubnavMenuBtn: null,
        $overflowSubnavMenu: null,
        $overflowSubnavMenuList: null,

        $notificationWrapper: null,
        $notificationContainer: null,
        $main: null,
        $content: null,
        $collapsibleTables: null,
        $primaryForm: null,

        navItems: null,
        totalNavItems: null,
        visibleNavItems: null,
        totalNavWidth: null,
        showingOverflowNavMenu: false,
        showingNavToggle: null,
        showingSidebarToggle: null,

        subnavItems: null,
        totalSubnavItems: null,
        visibleSubnavItems: null,
        totalSubnavWidth: null,
        showingOverflowSubnavMenu: false,

        selectedItemLabel: null,

        fixedHeader: false,
        fixedNotifications: false,

        enableQueue: true,
        jobInfo: null,
        displayedJobInfo: null,
        displayedJobInfoUnchanged: 1,
        trackJobProgressTimeout: null,
        jobProgressIcon: null,

        $edition: null,
        upgradeModal: null,

        checkingForUpdates: false,
        forcingRefreshOnUpdatesCheck: false,
        checkForUpdatesCallbacks: null,

        init: function() {
            // Is this session going to expire?
            if (Craft.remainingSessionTime !== 0) {
                this.authManager = new Craft.AuthManager();
            }

            // Find all the key elements
            this.$container = $('#container');
            this.$alerts = $('#alerts');
            this.$globalSidebar = $('#global-sidebar');
            this.$pageHeader = $('#page-header');
            this.$containerTopbar = this.$container.find('.topbar');
            this.$globalSidebarTopbar = this.$globalSidebar.children('.topbar');
            this.$systemNameLink = this.$globalSidebarTopbar.children('a.system-name');
            this.$systemName = this.$systemNameLink.children('h2');
            this.$nav = $('#nav');
            this.$subnav = $('#subnav');
            this.$sidebar = $('#sidebar');
            this.$notificationWrapper = $('#notifications-wrapper');
            this.$notificationContainer = $('#notifications');
            this.$main = $('#main');
            this.$content = $('#content');
            this.$collapsibleTables = $('table.collapsible');
            this.$edition = $('#edition');

            // global sidebar
            this.addListener(Garnish.$win, 'touchend', 'updateResponsiveGlobalSidebar');

            // Find all the nav items
            this.navItems = [];
            this.totalNavWidth = Craft.CP.baseNavWidth;

            var $navItems = this.$nav.children();
            this.totalNavItems = $navItems.length;
            this.visibleNavItems = this.totalNavItems;

            var i, $li, width;

            for (i = 0; i < this.totalNavItems; i++) {
                $li = $($navItems[i]);
                width = $li.width();

                this.navItems.push($li);
                this.totalNavWidth += width;
            }

            // Find all the sub nav items
            this.subnavItems = [];
            this.totalSubnavWidth = Craft.CP.baseSubnavWidth;

            var $subnavItems = this.$subnav.children();
            this.totalSubnavItems = $subnavItems.length;
            this.visibleSubnavItems = this.totalSubnavItems;

            for (i = 0; i < this.totalSubnavItems; i++) {
                $li = $($subnavItems[i]);
                width = $li.width();

                this.subnavItems.push($li);
                this.totalSubnavWidth += width;
            }

            // sidebar

            this.addListener(this.$sidebar.find('nav ul'), 'resize', 'updateResponsiveSidebar');

            this.$sidebarLinks = $('nav a', this.$sidebar);
            this.addListener(this.$sidebarLinks, 'click', 'selectSidebarItem');

            this.addListener(Garnish.$win, 'scroll', 'updateFixedNotifications');
            this.updateFixedNotifications();

            this.addListener(Garnish.$win, 'scroll', 'updateFixedHeader');
            this.updateFixedHeader();

            Garnish.$doc.ready($.proxy(function() {
                // Set up the window resize listener
                this.addListener(Garnish.$win, 'resize', 'onWindowResize');
                this.onWindowResize();

                // Fade the notification out two seconds after page load
                var $errorNotifications = this.$notificationContainer.children('.error'),
                    $otherNotifications = this.$notificationContainer.children(':not(.error)');

                $errorNotifications.delay(Craft.CP.notificationDuration * 2).velocity('fadeOut');
                $otherNotifications.delay(Craft.CP.notificationDuration).velocity('fadeOut');
            }, this));

            // Alerts
            if (this.$alerts.length) {
                this.initAlerts();
            }

            // Does this page have a primary form?
            if (this.$container.prop('nodeName') === 'FORM') {
                this.$primaryForm = this.$container;
            }
            else {
                this.$primaryForm = $('form[data-saveshortcut]:first');
            }

            // Does the primary form support the save shortcut?
            if (this.$primaryForm.length && Garnish.hasAttr(this.$primaryForm, 'data-saveshortcut')) {
                this.addListener(Garnish.$doc, 'keydown', function(ev) {
                    if (Garnish.isCtrlKeyPressed(ev) && ev.keyCode === Garnish.S_KEY) {
                        ev.preventDefault();
                        this.submitPrimaryForm();
                    }

                    return true;
                });
            }

            Garnish.$win.on('load', $.proxy(function() {
                // Look for forms that we should watch for changes on
                this.$confirmUnloadForms = $('form[data-confirm-unload]');

                if (this.$confirmUnloadForms.length) {
                    if (!Craft.forceConfirmUnload) {
                        this.initialFormValues = [];
                    }

                    for (var i = 0; i < this.$confirmUnloadForms.length; i++) {
                        var $form = $(this.$confirmUnloadForms);

                        if (!Craft.forceConfirmUnload) {
                            this.initialFormValues[i] = $form.serialize();
                        }

                        this.addListener($form, 'submit', function() {
                            this.removeListener(Garnish.$win, 'beforeunload');
                        });
                    }

                    this.addListener(Garnish.$win, 'beforeunload', function(ev) {
                        for (var i = 0; i < this.$confirmUnloadForms.length; i++) {
                            if (
                                Craft.forceConfirmUnload ||
                                this.initialFormValues[i] !== $(this.$confirmUnloadForms[i]).serialize()
                            ) {
                                var message = Craft.t('app', 'Any changes will be lost if you leave this page.');

                                if (ev) {
                                    ev.originalEvent.returnValue = message;
                                }
                                else {
                                    window.event.returnValue = message;
                                }

                                return message;
                            }
                        }
                    });
                }
            }, this));

            if (this.$edition.hasClass('hot')) {
                this.addListener(this.$edition, 'click', 'showUpgradeModal');
            }

            if ($.isTouchCapable()) {
                this.$container.on('focus', 'input, textarea, div.redactor-box', $.proxy(this, '_handleInputFocus'));
                this.$container.on('blur', 'input, textarea, div.redactor-box', $.proxy(this, '_handleInputBlur'));
            }
        },

        _handleInputFocus: function() {
            Garnish.$bod.addClass('focused');
            this.updateFixedHeader();
            this.updateResponsiveGlobalSidebar();
        },

        _handleInputBlur: function() {
            Garnish.$bod.removeClass('focused');
            this.updateFixedHeader();
            this.updateResponsiveGlobalSidebar();
        },

        submitPrimaryForm: function() {
            // Give other stuff on the page a chance to prepare
            this.trigger('beforeSaveShortcut');

            if (this.$primaryForm.data('saveshortcut-redirect')) {
                $('<input type="hidden" name="redirect" value="' + this.$primaryForm.data('saveshortcut-redirect') + '"/>').appendTo(this.$primaryForm);
            }

            this.$primaryForm.submit();
        },

        updateSidebarMenuLabel: function() {
            Garnish.$win.trigger('resize');

            var $selectedLink = $('a.sel:first', this.$sidebar);

            this.selectedItemLabel = $selectedLink.html();
        },

        /**
         * Handles stuff that should happen when the window is resized.
         */
        onWindowResize: function() {
            // Get the new window width
            this.onWindowResize._cpWidth = Math.min(Garnish.$win.width(), Craft.CP.maxWidth);


            // Update the responsive global sidebar
            this.updateResponsiveGlobalSidebar();

            // Update the responsive nav
            this.updateResponsiveNav();

            // Update the responsive sidebar
            this.updateResponsiveSidebar();

            // Update any responsive tables
            this.updateResponsiveTables();
        },

        updateResponsiveGlobalSidebar: function() {
            if (Garnish.$bod.hasClass('focused')) {
                this.$globalSidebar.height(this.$container.height());
            }
            else {
                var globalSidebarHeight = window.innerHeight;

                this.$globalSidebar.height(globalSidebarHeight);
            }
        },

        updateResponsiveNav: function() {
            if (this.onWindowResize._cpWidth <= 992) {
                if (!this.showingNavToggle) {
                    this.showNavToggle();
                }
            }
            else {
                if (this.showingNavToggle) {
                    this.hideNavToggle();
                }
            }
        },

        showNavToggle: function() {
            this.$navBtn = $('<a class="show-nav" title="' + Craft.t('app', 'Show nav') + '"></a>').prependTo(this.$containerTopbar);

            this.addListener(this.$navBtn, 'click', 'toggleNav');

            this.showingNavToggle = true;
        },

        hideNavToggle: function() {
            this.$navBtn.remove();
            this.showingNavToggle = false;
        },

        toggleNav: function() {
            if (Garnish.$bod.hasClass('showing-nav')) {
                Garnish.$bod.toggleClass('showing-nav');
            }
            else {
                Garnish.$bod.toggleClass('showing-nav');
            }

        },

        updateResponsiveSidebar: function() {
            if (this.$sidebar.length > 0) {
                if (this.onWindowResize._cpWidth < 769) {
                    if (!this.showingSidebarToggle) {
                        this.showSidebarToggle();
                    }
                }
                else {
                    if (this.showingSidebarToggle) {
                        this.hideSidebarToggle();
                    }
                }
            }
        },

        showSidebarToggle: function() {
            var $selectedLink = $('a.sel:first', this.$sidebar);

            this.selectedItemLabel = $selectedLink.html();

            this.$sidebarBtn = $('<a class="show-sidebar" title="' + Craft.t('app', 'Show sidebar') + '">' + this.selectedItemLabel + '</a>').prependTo(this.$content);

            this.addListener(this.$sidebarBtn, 'click', 'toggleSidebar');

            this.showingSidebarToggle = true;
        },

        selectSidebarItem: function(ev) {
            var $link = $(ev.currentTarget);

            this.selectedItemLabel = $link.html();

            if (this.$sidebarBtn) {
                this.$sidebarBtn.html(this.selectedItemLabel);

                this.toggleSidebar();
            }
        },

        hideSidebarToggle: function() {
            if (this.$sidebarBtn) {
                this.$sidebarBtn.remove();
            }

            this.showingSidebarToggle = false;
        },

        toggleSidebar: function() {
            var $contentWithSidebar = this.$content.filter('.has-sidebar');

            $contentWithSidebar.toggleClass('showing-sidebar');

            this.updateResponsiveContent();
        },
        updateResponsiveContent: function() {
            var $contentWithSidebar = this.$content.filter('.has-sidebar');

            if ($contentWithSidebar.hasClass('showing-sidebar')) {
                var sidebarHeight = $('nav', this.$sidebar).height();

                if ($contentWithSidebar.height() <= sidebarHeight) {
                    var newContentHeight = sidebarHeight + 48;
                    $contentWithSidebar.css('height', newContentHeight + 'px');
                }
            }
            else {
                $contentWithSidebar.css('min-height', 0);
                $contentWithSidebar.css('height', 'auto');
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
                        if (this.updateResponsiveTables._$table.width() > this.updateResponsiveTables._containerWidth) {
                            this.updateResponsiveTables._$table.addClass('collapsed');
                        }
                    }

                    // Remember the container width for next time
                    this.updateResponsiveTables._$table.data('lastContainerWidth', this.updateResponsiveTables._containerWidth);
                }
            }
        },

        /**
         * Adds the last visible nav item to the overflow menu.
         */
        addLastVisibleNavItemToOverflowMenu: function() {
            this.navItems[this.visibleNavItems - 1].prependTo(this.$overflowNavMenuList);
            this.visibleNavItems--;
        },

        /**
         * Adds the first overflow nav item back to the main nav menu.
         */
        addFirstOverflowNavItemToMainMenu: function() {
            this.navItems[this.visibleNavItems].insertBefore(this.$overflowNavMenuItem);
            this.visibleNavItems++;
        },

        /**
         * Adds the last visible nav item to the overflow menu.
         */
        addLastVisibleSubnavItemToOverflowMenu: function() {
            this.subnavItems[this.visibleSubnavItems - 1].prependTo(this.$overflowSubnavMenuList);
            this.visibleSubnavItems--;
        },

        /**
         * Adds the first overflow nav item back to the main nav menu.
         */
        addFirstOverflowSubnavItemToMainMenu: function() {
            this.subnavItems[this.visibleSubnavItems].insertBefore(this.$overflowSubnavMenuItem);
            this.visibleSubnavItems++;
        },

        updateFixedHeader: function() {
            this.updateFixedHeader._topbarHeight = this.$containerTopbar.height();
            this.updateFixedHeader._pageHeaderHeight = this.$pageHeader.outerHeight();

            if (Garnish.$win.scrollTop() > this.updateFixedHeader._topbarHeight) {
                if (!this.fixedHeader) {
                    this.$pageHeader.addClass('fixed');

                    if (Garnish.$bod.hasClass('showing-nav') && Garnish.$win.width() <= 992) {
                        this.$pageHeader.css('top', Garnish.$win.scrollTop());
                    }
                    else {
                        if (Garnish.$bod.hasClass('focused')) {
                            this.$pageHeader.css('top', Garnish.$win.scrollTop());
                        }
                        else {
                            this.$pageHeader.css('top', 0);
                        }
                    }

                    this.$main.css('margin-top', this.updateFixedHeader._pageHeaderHeight);
                    this.fixedheader = true;
                }
            }
            else {
                if (this.fixedheader) {
                    this.$pageHeader.removeClass('fixed');
                    this.$pageHeader.css('top', 0);
                    this.$main.css('margin-top', 0);
                    this.fixedheader = false;
                }
            }
        },

        updateFixedNotifications: function() {
            this.updateFixedNotifications._headerHeight = this.$globalSidebar.height();

            if (Garnish.$win.scrollTop() > this.updateFixedNotifications._headerHeight) {
                if (!this.fixedNotifications) {
                    this.$notificationWrapper.addClass('fixed');
                    this.fixedNotifications = true;
                }
            }
            else {
                if (this.fixedNotifications) {
                    this.$notificationWrapper.removeClass('fixed');
                    this.fixedNotifications = false;
                }
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
                message = Craft.t('app', 'An unknown error occurred.');
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
            if (Garnish.isArray(alerts) && alerts.length) {
                this.$alerts = $('<ul id="alerts"/>').insertBefore(this.$containerTopbar);

                for (var i = 0; i < alerts.length; i++) {
                    $('<li>' + alerts[i] + '</li>').appendTo(this.$alerts);
                }

                var height = this.$alerts.outerHeight();
                this.$alerts.css('margin-top', -height).velocity({'margin-top': 0}, 'fast');

                this.initAlerts();
            }
        },

        initAlerts: function() {
            // Is there a domain mismatch?
            var $transferDomainLink = this.$alerts.find('.domain-mismatch:first');

            if ($transferDomainLink.length) {
                this.addListener($transferDomainLink, 'click', $.proxy(function(ev) {
                    ev.preventDefault();

                    if (confirm(Craft.t('app', 'Are you sure you want to transfer your license to this domain?'))) {
                        Craft.queueActionRequest('app/transfer-license-to-current-domain', $.proxy(function(response, textStatus) {
                            if (textStatus === 'success') {
                                if (response.success) {
                                    $transferDomainLink.parent().remove();
                                    this.displayNotice(Craft.t('app', 'License transferred.'));
                                }
                                else {
                                    this.displayError(response.error);
                                }
                            }

                        }, this));
                    }
                }, this));
            }

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

            // Is there an edition resolution link?
            var $editionResolutionLink = this.$alerts.find('.edition-resolution:first');

            if ($editionResolutionLink.length) {
                this.addListener($editionResolutionLink, 'click', 'showUpgradeModal');
            }
        },

        checkForUpdates: function(forceRefresh, callback) {
            // If forceRefresh == true, we're currently checking for updates, and not currently forcing a refresh,
            // then just set a new callback that re-checks for updates when the current one is done.
            if (this.checkingForUpdates && forceRefresh === true && !this.forcingRefreshOnUpdatesCheck) {
                var realCallback = callback;

                callback = function() {
                    Craft.cp.checkForUpdates(true, realCallback);
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
                this.forcingRefreshOnUpdatesCheck = (forceRefresh === true);

                var data = {
                    forceRefresh: (forceRefresh === true)
                };

                Craft.queueActionRequest('app/check-for-updates', data, $.proxy(function(info) {
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
                }, this));
            }
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
            Craft.queueActionRequest('queue/get-job-info?dontExtendSession=1', $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    this.trackJobProgressTimeout = null;
                    this.setJobInfo(response, true);

                    if (this.jobInfo.length) {
                        // Check again after a delay
                        this.trackJobProgress(true);
                    }
                }
            }, this));
        },

        setJobInfo: function(jobInfo, animateIcon) {
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
                oldInfo.status === this.displayedJobInfo.status
            ) {
                this.displayedJobInfoUnchanged++;
            } else {
                // Reset the counter
                this.displayedJobInfoUnchanged = 1;
            }

            this.updateJobIcon(animateIcon);

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

        updateJobIcon: function(animate) {
            if (!this.enableQueue || !this.$nav.length) {
                return;
            }

            if (this.displayedJobInfo) {
                if (!this.jobProgressIcon) {
                    this.jobProgressIcon = new JobProgressIcon();
                }

                if (this.displayedJobInfo.status === Craft.CP.JOB_STATUS_RESERVED || this.displayedJobInfo.status === Craft.CP.JOB_STATUS_WAITING) {
                    this.jobProgressIcon.hideFailMode();
                    this.jobProgressIcon.setDescription(this.displayedJobInfo.description);
                    this.jobProgressIcon.setProgress(this.displayedJobInfo.progress, animate);
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

        showUpgradeModal: function() {
            if (!this.upgradeModal) {
                this.upgradeModal = new Craft.UpgradeModal();
            }
            else {
                this.upgradeModal.show();
            }
        }
    },
    {
        maxWidth: 1051, //1024,
        navHeight: 38,
        baseNavWidth: 30,
        subnavHeight: 38,
        baseSubnavWidth: 30,
        notificationDuration: 2000,

        JOB_STATUS_WAITING: 1,
        JOB_STATUS_RESERVED: 2,
        JOB_STATUS_DONE: 3,
        JOB_STATUS_FAILED: 4
    });

Craft.cp = new Craft.CP();


/**
 * Job progress icon class
 */
var JobProgressIcon = Garnish.Base.extend(
    {
        $li: null,
        $a: null,
        $label: null,

        hud: null,
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
            this.$li = $('<li/>').appendTo(Craft.cp.$nav);
            this.$a = $('<a id="job-icon"/>').appendTo(this.$li);
            this.$canvasContainer = $('<span class="icon"/>').appendTo(this.$a);
            this.$label = $('<span class="label"></span>').appendTo(this.$a);

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

            this.addListener(this.$a, 'click', 'toggleHud');
        },

        setDescription: function(description) {
            this.$a.attr('title', description);
            this.$label.text(description);
        },

        setProgress: function(progress, animate) {
            if (this._canvasSupported) {
                if (animate) {
                    this._animateArc(0, progress/100);
                }
                else {
                    this._setArc(0, progress/100);
                }
            }
            else {
                this._progressBar.setProgressPercentage(progress);
            }
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

        toggleHud: function() {
            if (!this.hud) {
                this.hud = new QueueHUD();
            }
            else {
                this.hud.toggle();
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

var QueueHUD = Garnish.HUD.extend(
    {
        jobsById: null,
        completedJobs: null,
        updateViewProxy: null,

        init: function() {
            this.jobsById = {};
            this.completedJobs = [];
            this.updateViewProxy = $.proxy(this, 'updateView');

            this.base(Craft.cp.jobProgressIcon.$a);

            this.$main.attr('id', 'queue-hud');
        },

        onShow: function() {
            Craft.cp.on('setJobInfo', this.updateViewProxy);
            this.updateView();
            this.base();
        },

        onHide: function() {
            Craft.cp.off('setJobInfo', this.updateViewProxy);

            // Clear out any completed jobs
            if (this.completedJobs.length) {
                for (var i = 0; i < this.completedJobs.length; i++) {
                    this.completedJobs[i].destroy();
                }

                this.completedJobs = [];
            }

            this.base();
        },

        updateView: function() {
            // First remove any jobs that have completed
            var newJobIds = [];

            var i;

            if (Craft.cp.jobInfo) {
                for (i = 0; i < Craft.cp.jobInfo.length; i++) {
                    newJobIds.push(parseInt(Craft.cp.jobInfo[i].id));
                }
            }

            for (var id in this.jobsById) {
                if (!this.jobsById.hasOwnProperty(id)) {
                    continue;
                }
                if (!Craft.inArray(parseInt(id), newJobIds)) {
                    this.jobsById[id].complete();
                    this.completedJobs.push(this.jobsById[id]);
                    delete this.jobsById[id];
                }
            }

            // Now display the jobs that are still around
            if (Craft.cp.jobInfo && Craft.cp.jobInfo.length) {
                for (i = 0; i < Craft.cp.jobInfo.length; i++) {
                    var info = Craft.cp.jobInfo[i];

                    if (this.jobsById[info.id]) {
                        this.jobsById[info.id].updateStatus(info);
                    }
                    else {
                        this.jobsById[info.id] = new QueueHUD.Job(this, info);

                        // Place it before the next already known job
                        var placed = false;
                        for (var j = i + 1; j < Craft.cp.jobInfo.length; j++) {
                            if (this.jobsById[Craft.cp.jobInfo[j].id]) {
                                this.jobsById[info.id].$container.insertBefore(this.jobsById[Craft.cp.jobInfo[j].id].$container);
                                placed = true;
                                break;
                            }
                        }

                        if (!placed) {
                            // Place it before the resize <object> if there is one
                            var $object = this.$main.children('object');
                            if ($object.length) {
                                this.jobsById[info.id].$container.insertBefore($object);
                            }
                            else {
                                this.jobsById[info.id].$container.appendTo(this.$main);
                            }
                        }
                    }
                }
            }
            else {
                this.hide();
            }
        }
    });

QueueHUD.Job = Garnish.Base.extend(
    {
        hud: null,
        id: null,
        description: null,

        status: null,
        progress: null,

        $container: null,
        $statusContainer: null,
        $descriptionContainer: null,

        _progressBar: null,

        init: function(hud, info) {
            this.hud = hud;

            this.id = info.id;
            this.description = info.description;

            this.$container = $('<div class="job"/>');
            this.$statusContainer = $('<div class="job-status"/>').appendTo(this.$container);
            this.$descriptionContainer = $('<div class="job-description"/>').appendTo(this.$container).text(info.description);

            this.$container.data('job', this);

            this.updateStatus(info);
        },

        updateStatus: function(info) {
            if (this.status !== (this.status = info.status)) {
                this.$statusContainer.empty();

                switch (this.status) {
                    case Craft.CP.JOB_STATUS_WAITING: {
                        this.$statusContainer.text(Craft.t('app', 'Pending'));
                        break;
                    }
                    case Craft.CP.JOB_STATUS_RESERVED: {
                        this._progressBar = new Craft.ProgressBar(this.$statusContainer);
                        this._progressBar.showProgressBar();
                        break;
                    }
                    case Craft.CP.JOB_STATUS_FAILED: {
                        $('<span/>', {
                            'class': 'error',
                            text: Craft.t('app', 'Failed'),
                            title: info.error
                        }).appendTo(this.$statusContainer);

                        var $actionBtn = $('<a class="menubtn error" title="' + Craft.t('app', 'Options') + '"/>').appendTo(this.$statusContainer);
                        $(
                            '<div class="menu">' +
                            '<ul>' +
                            '<li><a data-action="retry">' + Craft.t('app', 'Try again') + '</a></li>' +
                            '<li><a data-action="release">' + Craft.t('app', 'Cancel') + '</a></li>' +
                            '</ul>' +
                            '</div>'
                        ).appendTo(this.$statusContainer);

                        new Garnish.MenuBtn($actionBtn, {
                            onOptionSelect: $.proxy(this, 'performErrorAction')
                        });

                        break;
                    }
                }
            }

            if (this.status === Craft.CP.JOB_STATUS_RESERVED) {
                this._progressBar.setProgressPercentage(info.progress);
            }
        },

        performErrorAction: function(option) {
            // What option did they choose?
            switch ($(option).data('action')) {
                case 'retry': {
                    // Update the icon
                    Craft.cp.displayedJobInfo.status = Craft.CP.JOB_STATUS_WAITING;
                    Craft.cp.displayedJobInfo.progress = 0;
                    Craft.cp.updateJobIcon(false);

                    Craft.postActionRequest('queue/retry', {id: this.id}, $.proxy(function(response, textStatus) {
                        if (textStatus === 'success') {
                            Craft.cp.trackJobProgress(false, true);
                        }
                    }, this));
                    break;
                }
                case 'release': {
                    Craft.postActionRequest('queue/release', {id: this.id}, $.proxy(function(response, textStatus) {
                        if (textStatus === 'success') {
                            Craft.cp.trackJobProgress(false, true);
                        }
                    }, this));
                }
            }
        },

        complete: function() {
            this.$statusContainer.empty();
            $('<div data-icon="check"/>').appendTo(this.$statusContainer);
        },

        destroy: function() {
            if (this.hud.jobsById[this.id]) {
                delete this.hud.jobsById[this.id];
            }

            this.$container.remove();
            this.base();
        }
    });
