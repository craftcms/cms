(function($) {


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
	$siteNameLink: null,
	$siteName: null,
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

	fixedNotifications: false,

	taskInfo: null,
	workingTaskInfo: null,
	areTasksStalled: false,
	trackTaskProgressTimeout: null,
	taskProgressIcon: null,

	$edition: null,
	upgradeModal: null,

	checkingForUpdates: false,
	forcingRefreshOnUpdatesCheck: false,
	checkForUpdatesCallbacks: null,

	init: function()
	{
		// Is this session going to expire?
		if (Craft.authTimeout != 0)
		{
			this.authManager = new Craft.AuthManager();
		}

		// Find all the key elements
		this.$container = $('#container');
		this.$alerts = $('#alerts');
		this.$globalSidebar = $('#global-sidebar');
		this.$pageHeader = $('#page-header');
		this.$containerTopbar = $('#container .topbar');
		this.$globalSidebarTopbar = this.$globalSidebar.children('.topbar');
		this.$siteNameLink = this.$globalSidebarTopbar.children('a.site-name');
		this.$siteName = this.$siteNameLink.children('h2');
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

		for (var i = 0; i < this.totalNavItems; i++)
		{
			var $li = $($navItems[i]),
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

		for (var i = 0; i < this.totalSubnavItems; i++)
		{
			var $li = $($subnavItems[i]),
				width = $li.width();

			this.subnavItems.push($li);
			this.totalSubnavWidth += width;
		}

		// sidebar

		this.addListener(this.$sidebar.find('nav ul'), 'resize', 'updateResponsiveSidebar');

		this.$sidebarLinks = $('nav a', this.$sidebar);
		this.addListener(this.$sidebarLinks, 'click', 'selectSidebarItem');


		this.addListener(this.$container, 'scroll', 'updateFixedNotifications');
		this.updateFixedNotifications();

		Garnish.$doc.ready($.proxy(function()
		{
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
		if (this.$alerts.length)
		{
			this.initAlerts();
		}

		// Does this page have a primary form?
		if (this.$container.prop('nodeName') == 'FORM')
		{
			this.$primaryForm = this.$container;
		}
		else
		{
			this.$primaryForm = $('form[data-saveshortcut]:first');
		}

		// Does the primary form support the save shortcut?
		if (this.$primaryForm.length && Garnish.hasAttr(this.$primaryForm, 'data-saveshortcut'))
		{
			this.addListener(Garnish.$doc, 'keydown', function(ev)
			{
				if (Garnish.isCtrlKeyPressed(ev) && ev.keyCode == Garnish.S_KEY)
				{
					ev.preventDefault();
					this.submitPrimaryForm();
				}

				return true;
			});
		}

		Garnish.$win.on('load', $.proxy(function()
		{
			// Look for forms that we should watch for changes on
			this.$confirmUnloadForms = $('form[data-confirm-unload]');

			if (this.$confirmUnloadForms.length)
			{
				if (!Craft.forceConfirmUnload)
				{
					this.initialFormValues = [];
				}

				for (var i = 0; i < this.$confirmUnloadForms.length; i++)
				{
					var $form = $(this.$confirmUnloadForms);

					if (!Craft.forceConfirmUnload)
					{
						this.initialFormValues[i] = $form.serialize();
					}

					this.addListener($form, 'submit', function()
					{
						this.removeListener(Garnish.$win, 'beforeunload');
					});
				}

				this.addListener(Garnish.$win, 'beforeunload', function(ev)
				{
					for (var i = 0; i < this.$confirmUnloadForms.length; i++)
					{
						if (
							Craft.forceConfirmUnload ||
							this.initialFormValues[i] != $(this.$confirmUnloadForms[i]).serialize()
						)
						{
							var message = Craft.t('Any changes will be lost if you leave this page.');

							if (ev)
							{
								ev.originalEvent.returnValue = message;
							}
							else
							{
								window.event.returnValue = message;
							}

							return message;
						}
					}
				});
			}
		}, this));

		if (this.$edition.hasClass('hot'))
		{
			this.addListener(this.$edition, 'click', 'showUpgradeModal');
		}
	},

	submitPrimaryForm: function()
	{
		// Give other stuff on the page a chance to prepare
		this.trigger('beforeSaveShortcut');

		if (this.$primaryForm.data('saveshortcut-redirect'))
		{
			$('<input type="hidden" name="redirect" value="'+this.$primaryForm.data('saveshortcut-redirect')+'"/>').appendTo(this.$primaryForm);
		}

		this.$primaryForm.submit();
	},

	updateSidebarMenuLabel: function()
	{
		Garnish.$win.trigger('resize');

		var $selectedLink = $('a.sel:first', this.$sidebar);

		this.selectedItemLabel = $selectedLink.html();
	},

	/**
	 * Handles stuff that should happen when the window is resized.
	 */
	onWindowResize: function()
	{
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

	updateResponsiveGlobalSidebar: function()
	{
		var globalSidebarHeight = window.innerHeight;

		this.$globalSidebar.height(globalSidebarHeight);
	},

	updateResponsiveNav: function()
	{
		if(this.onWindowResize._cpWidth <= 992)
		{
			if (!this.showingNavToggle)
			{
				this.showNavToggle();
			}
		}
		else
		{
			if (this.showingNavToggle)
			{
				this.hideNavToggle();
			}
		}
	},

	showNavToggle: function()
	{
		this.$navBtn = $('<a class="show-nav" title="'+Craft.t('Show nav')+'"></a>').prependTo(this.$containerTopbar);

		this.addListener(this.$navBtn, 'click', 'toggleNav');

		this.showingNavToggle = true;
	},

	hideNavToggle: function()
	{
		this.$navBtn.remove();
		this.showingNavToggle = false;
	},

	toggleNav: function()
	{
		if(Garnish.$bod.hasClass('showing-nav'))
		{
			Garnish.$bod.toggleClass('showing-nav');
		}
		else
		{
			Garnish.$bod.toggleClass('showing-nav');
		}

	},

	updateResponsiveSidebar: function()
	{
		if(this.$sidebar.length > 0)
		{
			if(this.onWindowResize._cpWidth < 769)
			{
				if (!this.showingSidebarToggle)
				{
					this.showSidebarToggle();
				}
			}
			else
			{
				if (this.showingSidebarToggle)
				{
					this.hideSidebarToggle();
				}
			}
		}
	},

	showSidebarToggle: function()
	{
		var $selectedLink = $('a.sel:first', this.$sidebar);

		this.selectedItemLabel = $selectedLink.html();

		this.$sidebarBtn = $('<a class="show-sidebar" title="'+Craft.t('Show sidebar')+'">'+this.selectedItemLabel+'</a>').prependTo(this.$content);

		this.addListener(this.$sidebarBtn, 'click', 'toggleSidebar');

		this.showingSidebarToggle = true;
	},

	selectSidebarItem: function(ev)
	{
		var $link = $(ev.currentTarget);

		this.selectedItemLabel = $link.html();

		if (this.$sidebarBtn)
		{
			this.$sidebarBtn.html(this.selectedItemLabel);

			this.toggleSidebar();
		}
	},

	hideSidebarToggle: function()
	{
		if (this.$sidebarBtn)
		{
			this.$sidebarBtn.remove();
		}

		this.showingSidebarToggle = false;
	},

	toggleSidebar: function()
	{
		var $contentWithSidebar = this.$content.filter('.has-sidebar');

		$contentWithSidebar.toggleClass('showing-sidebar');

		this.updateResponsiveContent();
	},
	updateResponsiveContent: function()
	{
		var $contentWithSidebar = this.$content.filter('.has-sidebar');

		if($contentWithSidebar.hasClass('showing-sidebar'))
		{
			var sidebarHeight = $('nav', this.$sidebar).height();

			if($contentWithSidebar.height() <= sidebarHeight)
			{
				var newContentHeight = sidebarHeight + 48;
				$contentWithSidebar.css('height', newContentHeight+'px');
			}
		}
		else
		{
			$contentWithSidebar.css('min-height', 0);
			$contentWithSidebar.css('height', 'auto');
		}
	},
	updateResponsiveTables: function()
	{
		for (this.updateResponsiveTables._i = 0; this.updateResponsiveTables._i < this.$collapsibleTables.length; this.updateResponsiveTables._i++)
		{
			this.updateResponsiveTables._$table = this.$collapsibleTables.eq(this.updateResponsiveTables._i);
			this.updateResponsiveTables._containerWidth = this.updateResponsiveTables._$table.parent().width();
			this.updateResponsiveTables._check = false;

			if(this.updateResponsiveTables._containerWidth > 0)
			{
				// Is this the first time we've checked this table?
				if (typeof this.updateResponsiveTables._$table.data('lastContainerWidth') === typeof undefined)
				{
					this.updateResponsiveTables._check = true;
				}
				else
				{
					this.updateResponsiveTables._isCollapsed = this.updateResponsiveTables._$table.hasClass('collapsed');

					// Getting wider?
					if (this.updateResponsiveTables._containerWidth > this.updateResponsiveTables._$table.data('lastContainerWidth'))
					{
						if (this.updateResponsiveTables._isCollapsed)
						{
							this.updateResponsiveTables._$table.removeClass('collapsed');
							this.updateResponsiveTables._check = true;
						}
					}
					else if (!this.updateResponsiveTables._isCollapsed)
					{
						this.updateResponsiveTables._check = true;
					}
				}

				// Are we checking the table width?
				if (this.updateResponsiveTables._check)
				{
					if (this.updateResponsiveTables._$table.width() > this.updateResponsiveTables._containerWidth)
					{
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
	addLastVisibleNavItemToOverflowMenu: function()
	{
		this.navItems[this.visibleNavItems-1].prependTo(this.$overflowNavMenuList);
		this.visibleNavItems--;
	},

	/**
	 * Adds the first overflow nav item back to the main nav menu.
	 */
	addFirstOverflowNavItemToMainMenu: function()
	{
		this.navItems[this.visibleNavItems].insertBefore(this.$overflowNavMenuItem);
		this.visibleNavItems++;
	},

	/**
	 * Adds the last visible nav item to the overflow menu.
	 */
	addLastVisibleSubnavItemToOverflowMenu: function()
	{
		this.subnavItems[this.visibleSubnavItems-1].prependTo(this.$overflowSubnavMenuList);
		this.visibleSubnavItems--;
	},

	/**
	 * Adds the first overflow nav item back to the main nav menu.
	 */
	addFirstOverflowSubnavItemToMainMenu: function()
	{
		this.subnavItems[this.visibleSubnavItems].insertBefore(this.$overflowSubnavMenuItem);
		this.visibleSubnavItems++;
	},

	updateFixedNotifications: function()
	{
		this.updateFixedNotifications._headerHeight = this.$globalSidebar.height();

		if (this.$container.scrollTop() > this.updateFixedNotifications._headerHeight)
		{
			if (!this.fixedNotifications)
			{
				this.$notificationWrapper.addClass('fixed');
				this.fixedNotifications = true;
			}
		}
		else
		{
			if (this.fixedNotifications)
			{
				this.$notificationWrapper.removeClass('fixed');
				this.fixedNotifications = false;
			}
		}
	},

	/**
	 * Dispays a notification.
	 *
	 * @param string type
	 * @param string message
	 */
	displayNotification: function(type, message)
	{
		var notificationDuration = Craft.CP.notificationDuration;

		if (type == 'error')
		{
			notificationDuration *= 2;
		}

		var $notification = $('<div class="notification '+type+'">'+message+'</div>')
			.appendTo(this.$notificationContainer);

		var fadedMargin = -($notification.outerWidth()/2)+'px';

		$notification
			.hide()
			.css({ opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin })
			.velocity({ opacity: 1, 'margin-left': '2px', 'margin-right': '2px' }, { display: 'inline-block', duration: 'fast' })
			.delay(notificationDuration)
			.velocity({ opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin }, {
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
	 * @param string message
	 */
	displayNotice: function(message)
	{
		this.displayNotification('notice', message);
	},

	/**
	 * Displays an error.
	 *
	 * @param string message
	 */
	displayError: function(message)
	{
		if (!message)
		{
			message = Craft.t('An unknown error occurred.');
		}

		this.displayNotification('error', message);
	},

	fetchAlerts: function()
	{
		var data = {
			path: Craft.path
		};

		Craft.queueActionRequest('app/getCpAlerts', data, $.proxy(this, 'displayAlerts'));
	},

	displayAlerts: function(alerts)
	{
		if (Garnish.isArray(alerts) && alerts.length)
		{
			this.$alerts = $('<ul id="alerts"/>').insertBefore(this.$containerTopbar);

			for (var i = 0; i < alerts.length; i++)
			{
				$('<li>'+alerts[i]+'</li>').appendTo(this.$alerts);
			}

			var height = this.$alerts.outerHeight();
			this.$alerts.css('margin-top', -height).velocity({ 'margin-top': 0 }, 'fast');

			this.initAlerts();
		}
	},

	initAlerts: function()
	{
		// Is there a domain mismatch?
		var $transferDomainLink = this.$alerts.find('.domain-mismatch:first');

		if ($transferDomainLink.length)
		{
			this.addListener($transferDomainLink, 'click', $.proxy(function(ev)
			{
				ev.preventDefault();

				if (confirm(Craft.t('Are you sure you want to transfer your license to this domain?')))
				{
					Craft.queueActionRequest('app/transferLicenseToCurrentDomain', $.proxy(function(response, textStatus)
					{
						if (textStatus == 'success')
						{
							if (response.success)
							{
								$transferDomainLink.parent().remove();
								this.displayNotice(Craft.t('License transferred.'));
							}
							else
							{
								this.displayError(response.error);
							}
						}

					}, this));
				}
			}, this));
		}

		// Are there any shunnable alerts?
		var $shunnableAlerts = this.$alerts.find('a[class^="shun:"]');

		for (var i = 0; i < $shunnableAlerts.length; i++)
		{
			this.addListener($shunnableAlerts[i], 'click', $.proxy(function(ev)
			{
				ev.preventDefault();

				var $link = $(ev.currentTarget);

				var data = {
					message: $link.prop('className').substr(5)
				};

				Craft.queueActionRequest('app/shunCpAlert', data, $.proxy(function(response, textStatus)
				{
					if (textStatus == 'success')
					{
						if (response.success)
						{
							$link.parent().remove();
						}
						else
						{
							this.displayError(response.error);
						}
					}

				}, this));

			}, this));
		}

		// Is there an edition resolution link?
		var $editionResolutionLink = this.$alerts.find('.edition-resolution:first');

		if ($editionResolutionLink.length)
		{
			this.addListener($editionResolutionLink, 'click', 'showUpgradeModal');
		}
	},

	checkForUpdates: function(forceRefresh, callback)
	{
		// If forceRefresh == true, we're currently checking for updates, and not currently forcing a refresh,
		// then just seta new callback that re-checks for updates when the current one is done.
		if (this.checkingForUpdates && forceRefresh === true && !this.forcingRefreshOnUpdatesCheck)
		{
			var realCallback = callback;

			callback = function() {
				Craft.cp.checkForUpdates(true, realCallback);
			};
		}

		// Callback function?
		if (typeof callback == 'function')
		{
			if (!Garnish.isArray(this.checkForUpdatesCallbacks))
			{
				this.checkForUpdatesCallbacks = [];
			}

			this.checkForUpdatesCallbacks.push(callback);
		}

		if (!this.checkingForUpdates)
		{
			this.checkingForUpdates = true;
			this.forcingRefreshOnUpdatesCheck = (forceRefresh === true);

			var data = {
				forceRefresh: (forceRefresh === true)
			};

			Craft.queueActionRequest('app/checkForUpdates', data, $.proxy(function(info)
			{
				this.displayUpdateInfo(info);
				this.checkingForUpdates = false;

				if (Garnish.isArray(this.checkForUpdatesCallbacks))
				{
					var callbacks = this.checkForUpdatesCallbacks;
					this.checkForUpdatesCallbacks = null;

					for (var i = 0; i < callbacks.length; i++)
					{
						callbacks[i](info);
					}
				}

				this.trigger('checkForUpdates', {
					updateInfo: info
				});
			}, this));
		}
	},

	displayUpdateInfo: function(info)
	{
		// Remove the existing header badge, if any
		this.$globalSidebarTopbar.children('a.updates').remove();

		if (info.total)
		{
			var updateText;

			if (info.total == 1)
			{
				updateText = Craft.t('1 update available');
			}
			else
			{
				updateText = Craft.t('{num} updates available', { num: info.total });
			}

			// Topbar badge
			$('<a class="updates'+(info.critical ? ' critical' : '')+'" href="'+Craft.getUrl('updates')+'" title="'+updateText+'">' +
				'<span data-icon="newstamp">' +
					'<span>'+info.total+'</span>' +
				'</span>' +
			'</span>').insertAfter(this.$siteNameLink);

			// Footer link
			$('#footer-updates').text(updateText);
		}
	},

	runPendingTasks: function()
	{
		if (Craft.runTasksAutomatically)
		{
			Craft.queueActionRequest('tasks/runPendingTasks', $.proxy(function(taskInfo, textStatus)
			{
				if (textStatus == 'success')
				{
					this.trackTaskProgress(false);
				}
			}, this));
		}
		else
		{
			this.trackTaskProgress(false);
		}
	},

	trackTaskProgress: function(delay)
	{
		// Ignore if we're already tracking tasks
		if (this.trackTaskProgressTimeout)
		{
			return;
		}

		if (delay === true)
		{
			// Determine the delay based on the age of the working task
			if (this.workingTaskInfo)
			{
				delay = this.workingTaskInfo.age * 1000;

				// Keep it between .5 and 60 seconds
				delay = Math.min(60000, Math.max(500, delay));
			}
			else
			{
				// No working task. Try again in a minute.
				delay = 60000;
			}
		}

		if (!delay)
		{
			this._trackTaskProgressInternal();
		}
		else
		{
			this.trackTaskProgressTimeout = setTimeout($.proxy(this, '_trackTaskProgressInternal'), delay);
		}
	},

	_trackTaskProgressInternal: function()
	{
		Craft.queueActionRequest('tasks/getTaskInfo', $.proxy(function(taskInfo, textStatus)
		{
			if (textStatus == 'success')
			{
				this.trackTaskProgressTimeout = null;
				this.setTaskInfo(taskInfo, true);

				if (this.workingTaskInfo)
				{
					// Check again after a delay
					this.trackTaskProgress(true);
				}
			}
		}, this));
	},

	setTaskInfo: function(taskInfo, animateIcon)
	{
		this.taskInfo = taskInfo;

		// Update the "running" and "working" task info
		this.workingTaskInfo = this.getWorkingTaskInfo();
		this.areTasksStalled = (this.workingTaskInfo && this.workingTaskInfo.status === 'running' && this.workingTaskInfo.age >= Craft.CP.minStalledTaskAge);
		this.updateTaskIcon(this.getRunningTaskInfo(), animateIcon);

		// Fire a setTaskInfo event
		this.trigger('setTaskInfo');
	},

	/**
	 * Returns the first "running" task
	 */
	getRunningTaskInfo: function()
	{
		var statuses = ['running', 'error', 'pending'];

		for (var i = 0; i < statuses.length; i++)
		{
			for (var j = 0; j < this.taskInfo.length; j++)
			{
				if (this.taskInfo[j].level == 0 && this.taskInfo[j].status === statuses[i])
				{
					return this.taskInfo[j];
				}
			}
		}
	},

	/**
	 * Returns the currently "working" task/subtask
	 */
	getWorkingTaskInfo: function()
	{
		for (var i = this.taskInfo.length - 1; i >= 0; i--)
		{
			if (this.taskInfo[i].status === 'running')
			{
				return this.taskInfo[i];
			}
		}
	},

	updateTaskIcon: function(taskInfo, animate)
	{
		if (taskInfo)
		{
			if (!this.taskProgressIcon)
			{
				this.taskProgressIcon = new TaskProgressIcon();
			}

			if (this.areTasksStalled)
			{
				this.taskProgressIcon.showFailMode(Craft.t('Stalled task'));
			}
			else if (taskInfo.status == 'running' || taskInfo.status == 'pending')
			{
				this.taskProgressIcon.hideFailMode();
				this.taskProgressIcon.setDescription(taskInfo.description);
				this.taskProgressIcon.setProgress(taskInfo.progress, animate);
			}
			else if (taskInfo.status == 'error')
			{
				this.taskProgressIcon.showFailMode(Craft.t('Failed task'));
			}
		}
		else
		{
			if (this.taskProgressIcon)
			{
				this.taskProgressIcon.hideFailMode();
				this.taskProgressIcon.complete();
				delete this.taskProgressIcon;
			}
		}
	},

	showUpgradeModal: function()
	{
		if (!this.upgradeModal)
		{
			this.upgradeModal = new Craft.UpgradeModal();
		}
		else
		{
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

	minStalledTaskAge: 300, // 5 minutes

	normalizeTaskStatus: function(status)
	{
		return (status === 'running' && Craft.cp.areTasksStalled) ? 'stalled' : status;
	}
});

Craft.cp = new Craft.CP();


/**
 * Task progress icon class
 */
var TaskProgressIcon = Garnish.Base.extend(
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

	init: function()
	{
		this.$li = $('<li/>').appendTo(Craft.cp.$nav);
		this.$a = $('<a id="taskicon"/>').appendTo(this.$li);
		this.$canvasContainer = $('<span class="icon"/>').appendTo(this.$a);
		this.$label = $('<span class="label"></span>').appendTo(this.$a);

		this._canvasSupported = !!(document.createElement('canvas').getContext);

		if (this._canvasSupported)
		{
			var m = (window.devicePixelRatio > 1 ? 2 : 1);
			this._canvasSize = 18 * m;
			this._arcPos = this._canvasSize / 2;
			this._arcRadius = 7 * m;
			this._lineWidth = 3 * m;

			this._$bgCanvas     = this._createCanvas('bg', '#61666b');
			this._$staticCanvas = this._createCanvas('static', '#d7d9db');
			this._$hoverCanvas  = this._createCanvas('hover', '#fff');
			this._$failCanvas   = this._createCanvas('fail', '#da5a47').hide();

			this._staticCtx = this._$staticCanvas[0].getContext('2d');
			this._hoverCtx = this._$hoverCanvas[0].getContext('2d');

			this._drawArc(this._$bgCanvas[0].getContext('2d'), 0, 1);
			this._drawArc(this._$failCanvas[0].getContext('2d'), 0, 1);
		}
		else
		{
			this._progressBar = new Craft.ProgressBar(this.$canvasContainer);
			this._progressBar.showProgressBar();
		}

		this.addListener(this.$a, 'click', 'toggleHud');
	},

	setDescription: function(description)
	{
		this.$a.attr('title', description);
		this.$label.text(description);
	},

	setProgress: function(progress, animate)
	{
		if (this._canvasSupported)
		{
			if (animate)
			{
				this._animateArc(0, progress);
			}
			else
			{
				this._setArc(0, progress);
			}
		}
		else
		{
			this._progressBar.setProgressPercentage(progress * 100);
		}
	},

	complete: function()
	{
		if (this._canvasSupported)
		{
			this._animateArc(0, 1, $.proxy(function()
			{
				this._$bgCanvas.velocity('fadeOut');

				this._animateArc(1, 1, $.proxy(function()
				{
					this.$a.remove();
					this.destroy();
				}, this));
			}, this));
		}
		else
		{
			this._progressBar.setProgressPercentage(100);
			this.$a.velocity('fadeOut');
		}
	},

	showFailMode: function(message)
	{
		if (this.failMode)
		{
			return;
		}

		this.failMode = true;

		if (this._canvasSupported)
		{
			this._$bgCanvas.hide();
			this._$staticCanvas.hide();
			this._$hoverCanvas.hide();
			this._$failCanvas.show();
		}
		else
		{
			this._progressBar.$progressBar.css('border-color', '#da5a47');
			this._progressBar.$innerProgressBar.css('background-color', '#da5a47');
			this._progressBar.setProgressPercentage(50);
		}

		this.setDescription(message);
	},

	hideFailMode: function()
	{
		if (!this.failMode)
		{
			return;
		}

		this.failMode = false;

		if (this._canvasSupported)
		{
			this._$bgCanvas.show();
			this._$staticCanvas.show();
			this._$hoverCanvas.show();
			this._$failCanvas.hide();
		}
		else
		{
			this._progressBar.$progressBar.css('border-color', '');
			this._progressBar.$innerProgressBar.css('background-color', '');
			this._progressBar.setProgressPercentage(50);
		}
	},

	toggleHud: function()
	{
		if (!this.hud)
		{
			this.hud = new TaskProgressHUD();
		}
		else
		{
			this.hud.toggle();
		}
	},

	_createCanvas: function(id, color)
	{
		var $canvas = $('<canvas id="taskicon-'+id+'" width="'+this._canvasSize+'" height="'+this._canvasSize+'"/>').appendTo(this.$canvasContainer),
			ctx = $canvas[0].getContext('2d');

		ctx.strokeStyle = color;
		ctx.lineWidth = this._lineWidth;
		ctx.lineCap = 'round';
		return $canvas;
	},

	_setArc: function(startPos, endPos)
	{
		this._arcStartPos = startPos;
		this._arcEndPos = endPos;

		this._drawArc(this._staticCtx, startPos, endPos);
		this._drawArc(this._hoverCtx, startPos, endPos);
	},

	_drawArc: function(ctx, startPos, endPos)
	{
		ctx.clearRect(0, 0, this._canvasSize, this._canvasSize);
		ctx.beginPath();
		ctx.arc(this._arcPos, this._arcPos, this._arcRadius, (1.5+(startPos*2))*Math.PI, (1.5+(endPos*2))*Math.PI);
		ctx.stroke();
		ctx.closePath();
	},

	_animateArc: function(targetStartPos, targetEndPos, callback)
	{
		if (this._arcStepTimeout)
		{
			clearTimeout(this._arcStepTimeout);
		}

		this._arcStep = 0;
		this._arcStartStepSize = (targetStartPos - this._arcStartPos) / 10;
		this._arcEndStepSize = (targetEndPos - this._arcEndPos) / 10;
		this._arcAnimateCallback = callback;
		this._takeNextArcStep();
	},

	_takeNextArcStep: function()
	{
		this._setArc(this._arcStartPos+this._arcStartStepSize, this._arcEndPos+this._arcEndStepSize);

		this._arcStep++;

		if (this._arcStep < 10)
		{
			this._arcStepTimeout = setTimeout($.proxy(this, '_takeNextArcStep'), 50);
		}
		else if (this._arcAnimateCallback)
		{
			this._arcAnimateCallback();
		}
	}
});

var TaskProgressHUD = Garnish.HUD.extend(
{
	tasksById: null,
	completedTasks: null,
	updateViewProxy: null,

	init: function()
	{
		this.tasksById = {};
		this.completedTasks = [];
		this.updateViewProxy = $.proxy(this, 'updateView');

		this.base(Craft.cp.taskProgressIcon.$a);

		this.$main.attr('id', 'tasks-hud');
	},

	onShow: function()
	{
		Craft.cp.on('setTaskInfo', this.updateViewProxy);
		this.updateView();
		this.base();
	},

	onHide: function()
	{
		Craft.cp.off('setTaskInfo', this.updateViewProxy);

		// Clear out any completed tasks
		if (this.completedTasks.length)
		{
			for (var i = 0; i < this.completedTasks.length; i++)
			{
				this.completedTasks[i].destroy();
			}

			this.completedTasks = [];
		}

		this.base();
	},

	updateView: function()
	{
		// First remove any tasks that have completed
		var newTaskIds = [];

		if (Craft.cp.taskInfo)
		{
			for (var i = 0; i < Craft.cp.taskInfo.length; i++)
			{
				newTaskIds.push(Craft.cp.taskInfo[i].id);
			}
		}

		for (var id in this.tasksById)
		{
			if (!Craft.inArray(id, newTaskIds))
			{
				this.tasksById[id].complete();
				this.completedTasks.push(this.tasksById[id]);
				delete this.tasksById[id];
			}
		}

		// Now display the tasks that are still around
		if (Craft.cp.taskInfo && Craft.cp.taskInfo.length)
		{
			for (var i = 0; i < Craft.cp.taskInfo.length; i++)
			{
				var info = Craft.cp.taskInfo[i];

				if (this.tasksById[info.id])
				{
					this.tasksById[info.id].updateStatus(info);
				}
				else
				{
					this.tasksById[info.id] = new TaskProgressHUD.Task(this, info);

					// Place it before the next already known task
					var placed = false;
					for (var j = i + 1; j < Craft.cp.taskInfo.length; j++)
					{
						if (this.tasksById[Craft.cp.taskInfo[j].id])
						{
							this.tasksById[info.id].$container.insertBefore(this.tasksById[Craft.cp.taskInfo[j].id].$container);
							placed = true;
							break;
						}
					}

					if (!placed)
					{
						// Place it before the resize <object> if there is one
						var $object = this.$main.children('object');
						if ($object.length)
						{
							this.tasksById[info.id].$container.insertBefore($object);
						}
						else
						{
							this.tasksById[info.id].$container.appendTo(this.$main);
						}
					}
				}
			}
		}
		else
		{
			this.hide();
		}
	}
});

TaskProgressHUD.Task = Garnish.Base.extend(
{
	hud: null,
	id: null,
	level: null,
	description: null,

	status: null,
	progress: null,

	$container: null,
	$statusContainer: null,
	$descriptionContainer: null,

	_progressBar: null,

	init: function(hud, info)
	{
		this.hud = hud;

		this.id = info.id;
		this.level = info.level;
		this.description = info.description;

		this.$container = $('<div class="task"/>');
		this.$statusContainer = $('<div class="task-status"/>').appendTo(this.$container);
		this.$descriptionContainer = $('<div class="task-description"/>').appendTo(this.$container).text(info.description);

		this.$container.data('task', this);

		if (this.level != 0)
		{
			this.$container.css('padding-'+Craft.left, 24+(this.level*24));
			$('<div class="indent" data-icon="'+(Craft.orientation == 'ltr' ? 'rarr' : 'larr')+'"/>').appendTo(this.$descriptionContainer);
		}

		this.updateStatus(info);
	},

	updateStatus: function(info)
	{
		if (this.status !== (this.status = Craft.CP.normalizeTaskStatus(info.status)))
		{
			this.$statusContainer.empty();

			switch (this.status)
			{
				case 'pending':
				{
					this.$statusContainer.text(Craft.t('Pending'));
					break;
				}
				case 'running':
				{
					this._progressBar = new Craft.ProgressBar(this.$statusContainer);
					this._progressBar.showProgressBar();
					break;
				}
				case 'stalled':
				case 'error':
				{
					$('<span class="error">'+(this.status === 'stalled' ? Craft.t('Stalled') : Craft.t('Failed'))+'</span>').appendTo(this.$statusContainer);

					if (this.level == 0)
					{
						var $actionBtn = $('<a class="menubtn error" title="'+Craft.t('Options')+'"/>').appendTo(this.$statusContainer);
						$(
							'<div class="menu">' +
								'<ul>' +
									'<li><a data-action="rerun">'+Craft.t('Try again')+'</a></li>' +
									'<li><a data-action="cancel">'+Craft.t('Cancel')+'</a></li>' +
								'</ul>' +
							'</div>'
						).appendTo(this.$statusContainer);

						new Garnish.MenuBtn($actionBtn, {
							onOptionSelect: $.proxy(this, 'performErrorAction')
						});
					}

					break;
				}
			}
		}

		if (this.status == 'running')
		{
			this._progressBar.setProgressPercentage(info.progress*100);
		}
	},

	performErrorAction: function(option)
	{
		// Whatever happens, let's remove any following subtasks
		var $nextTaskContainers = this.$container.nextAll();

		for (var i = 0; i < $nextTaskContainers.length; i++)
		{
			var nextTask = $($nextTaskContainers[i]).data('task');

			if (nextTask && nextTask.level != 0)
			{
				nextTask.destroy();
			}
			else
			{
				break;
			}
		}

		// What option did they choose?
		switch ($(option).data('action'))
		{
			case 'rerun':
			{
				Craft.postActionRequest('tasks/rerunTask', { taskId: this.id }, $.proxy(function(taskInfo, textStatus)
				{
					if (textStatus == 'success')
					{
						this.updateStatus(taskInfo);
						Craft.cp.trackTaskProgress(false);
					}
				}, this));
				break;
			}
			case 'cancel':
			{
				Craft.postActionRequest('tasks/deleteTask', { taskId: this.id }, $.proxy(function(taskInfo, textStatus)
				{
					if (textStatus == 'success')
					{
						this.destroy();
						Craft.cp.trackTaskProgress(false);
					}
				}, this));
			}
		}
	},

	complete: function()
	{
		this.$statusContainer.empty();
		$('<div data-icon="check"/>').appendTo(this.$statusContainer);
	},

	destroy: function()
	{
		if (this.hud.tasksById[this.id])
		{
			delete this.hud.tasksById[this.id];
		}

		this.$container.remove();
		this.base();
	}
});


})(jQuery);
