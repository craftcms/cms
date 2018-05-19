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
        $header: null,
        $mainContent: null,
        $details: null,
        $selectedTab: null,
        $sidebar: null,
        $contentContainer: null,
        $edition: null,

        $collapsibleTables: null,

        fixedHeader: false,

        enableQueue: true,
        jobInfo: null,
        displayedJobInfo: null,
        displayedJobInfoUnchanged: 1,
        trackJobProgressTimeout: null,
        jobProgressIcon: null,

        checkingForUpdates: false,
        forcingRefreshOnUpdatesCheck: false,
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
            this.$header = $('#header');
            this.$mainContent = $('#main-content');
            this.$details = $('#details');
            this.$sidebar = $('#sidebar');
            this.$contentContainer = $('#content-container');
            this.$collapsibleTables = $('table.collapsible');
            this.$edition = $('#edition');

            this.updateSidebarMenuLabel();

            this.addListener(Garnish.$win, 'scroll', 'updateFixedHeader');
            this.updateFixedHeader();

            Garnish.$doc.ready($.proxy(function() {
                // Update responsive tables on window resize
                this.addListener(Garnish.$win, 'resize', 'updateResponsiveTables');
                this.updateResponsiveTables();

                // Fade the notification out two seconds after page load
                var $errorNotifications = this.$notificationContainer.children('.error'),
                    $otherNotifications = this.$notificationContainer.children(':not(.error)');

                $errorNotifications.delay(Craft.CP.notificationDuration * 2).velocity('fadeOut');
                $otherNotifications.delay(Craft.CP.notificationDuration).velocity('fadeOut');

                // Wait a frame before initializing any confirm-unload forms,
                // so other JS that runs on ready() has a chance to initialize
                Garnish.requestAnimationFrame($.proxy(this, 'initConfirmUnloadForms'));
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
                this.addListener(Garnish.$doc, 'keydown', function(ev) {
                    if (Garnish.isCtrlKeyPressed(ev) && ev.keyCode === Garnish.S_KEY) {
                        ev.preventDefault();
                        this.submitPrimaryForm();
                    }

                    return true;
                });
            }

            this.initTabs();

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
                    $(this).attr('target', '_blank')
                }
            });
        },

        initConfirmUnloadForms: function() {
            // Look for forms that we should watch for changes on
            this.$confirmUnloadForms = $('form[data-confirm-unload]');

            if (!this.$confirmUnloadForms.length) {
                return;
            }

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
        },

        _handleInputFocus: function() {
            this.updateFixedHeader();
        },

        _handleInputBlur: function() {
            this.updateFixedHeader();
        },

        submitPrimaryForm: function() {
            // Give other stuff on the page a chance to prepare
            this.trigger('beforeSaveShortcut');

            if (this.$primaryForm.data('saveshortcut-redirect')) {
                $('<input type="hidden" name="redirect" value="' + this.$primaryForm.data('saveshortcut-redirect') + '"/>').appendTo(this.$primaryForm);
            }

            this.$primaryForm.trigger('submit');
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
            this.$selectedTab = null;

            var $tabs = $('#tabs').find('> ul > li');
            var tabs = [];
            var tabWidths = [];
            var totalWidth = 0;
            var i, a, href;

            for (i = 0; i < $tabs.length; i++) {
                tabs[i] = $($tabs[i]);
                tabWidths[i] = tabs[i].width();
                totalWidth += tabWidths[i];

                // Does it link to an anchor?
                a = tabs[i].children('a');
                href = a.attr('href');
                if (href && href.charAt(0) === '#') {
                    this.addListener(a, 'click', function(ev) {
                        ev.preventDefault();
                        this.selectTab(ev.currentTarget);
                    });

                    if (href === document.location.hash) {
                        this.selectTab(a);
                    }
                }

                if (!this.$selectedTab && a.hasClass('sel')) {
                    this.$selectedTab = a;
                }
            }

            // Now set their max widths
            for (i = 0; i < $tabs.length; i++) {
                tabs[i].css('max-width', (100 * tabWidths[i] / totalWidth) + '%');
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
            Garnish.$win.trigger('resize');
            // Fixes Redactor fixed toolbars on previously hidden panes
            Garnish.$doc.trigger('scroll');
            this.$selectedTab = $tab;
        },

        deselectTab: function() {
            if (!this.$selectedTab) {
                return;
            }

            this.$selectedTab.removeClass('sel');
            if (this.$selectedTab.attr('href').charAt(0) === '#') {
                $(this.$selectedTab.attr('href')).addClass('hidden');
            }
            this.$selectedTab = null;
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

        updateFixedHeader: function() {
            // Have we scrolled passed the top of #main?
            if (this.$main.length && this.$main[0].getBoundingClientRect().top < 0) {
                if (!this.fixedHeader) {
                    var headerHeight = this.$header.outerHeight();
                    var css = {
                        top: headerHeight + 'px',
                        'max-height': 'calc(100vh - ' + headerHeight + 'px)'
                    };
                    this.$sidebar.css(css);
                    this.$details.css(css);

                    this.$mainContent.css('margin-top', this.$header.outerHeight());
                    Garnish.$bod.addClass('fixed-header');
                    this.fixedheader = true;
                }
            }
            else if (this.fixedheader) {
                Garnish.$bod.removeClass('fixed-header');
                this.$details.css({
                    top: null,
                    'max-height': null
                });
                this.$header.css('top', 0);
                this.$mainContent.css('margin-top', 0);
                this.fixedheader = false;
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
            this.$alerts.remove();

            if (Garnish.isArray(alerts) && alerts.length) {
                this.$alerts = $('<ul id="alerts"/>').prependTo(this.$mainContainer);

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

Garnish.$scrollContainer = $('#content-container');
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
            this.$li = $('<li/>').appendTo(Craft.cp.$nav.children('ul'));
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
                    this._animateArc(0, progress / 100);
                }
                else {
                    this._setArc(0, progress / 100);
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
