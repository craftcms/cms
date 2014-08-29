(function($) {


/**
 * CP class
 */
var CP = Garnish.Base.extend(
{
	authManager: null,

	$alerts: null,
	$header: null,
	$headerActionsList: null,
	$siteName: null,
	$nav: null,

	$overflowNavMenuItem: null,
	$overflowNavMenuBtn: null,
	$overflowNavMenu: null,
	$overflowNavMenuList: null,

	$notificationWrapper: null,
	$notificationContainer: null,
	$main: null,
	$content: null,
	$collapsibleTables: null,

	navItems: null,
	totalNavItems: null,
	visibleNavItems: null,
	totalNavWidth: null,
	showingOverflowNavMenu: false,

	fixedNotifications: false,

	runningTaskInfo: null,
	trackTaskProgressTimeout: null,
	taskProgressIcon: null,

	$upgradePromo: null,
	upgradeModal: null,

	init: function()
	{
		// Is this session going to expire?
		if (Craft.authTimeout != 0)
		{
			this.authManager = new Craft.AuthManager();
		}

		// Find all the key elements
		this.$alerts = $('#alerts');
		this.$header = $('#header');
		this.$headerActionsList = this.$header.find('#header-actions');
		this.$siteName = this.$header.find('h2');
		this.$nav = $('#nav');
		this.$notificationWrapper = $('#notifications-wrapper');
		this.$notificationContainer = $('#notifications');
		this.$main = $('#main');
		this.$content = $('#content');
		this.$collapsibleTables = this.$content.find('table.collapsible');
		this.$upgradePromo = $('#upgradepromo > a');

		// Keep the site name contained
		this.onActionItemListResize();
		this.addListener(this.$headerActionsList, 'resize', 'onActionItemListResize');

		// Find all the nav items
		this.navItems = [];
		this.totalNavWidth = CP.baseNavWidth;

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

		this.addListener(Garnish.$win, 'resize', 'onWindowResize');
		this.onWindowResize();

		this.addListener(Garnish.$win, 'scroll', 'updateFixedNotifications');
		this.updateFixedNotifications();

		// Fade the notification out in two seconds
		var $errorNotifications = this.$notificationContainer.children('.error'),
			$otherNotifications = this.$notificationContainer.children(':not(.error)');

		$errorNotifications.delay(CP.notificationDuration * 2).fadeOut();
		$otherNotifications.delay(CP.notificationDuration).fadeOut();

		// Secondary form submit buttons
		this.addListener($('.formsubmit'), 'activate', function(ev)
		{
			var $btn = $(ev.currentTarget);

			if ($btn.attr('data-confirm'))
			{
				if (!confirm($btn.attr('data-confirm')))
				{
					return;
				}
			}

			// Is this a menu item?
			if ($btn.data('menu'))
			{
				var $form = $btn.data('menu').$trigger.closest('form');
			}
			else
			{
				var $form = $btn.closest('form');
			}

			if ($btn.attr('data-action'))
			{
				$('<input type="hidden" name="action" value="'+$btn.attr('data-action')+'"/>').appendTo($form);
			}

			if ($btn.attr('data-redirect'))
			{
				$('<input type="hidden" name="redirect" value="'+$btn.attr('data-redirect')+'"/>').appendTo($form);
			}

			$form.submit();
		});

		// Alerts
		if (this.$alerts.length)
		{
			this.initAlerts();
		}

		// Listen for save shortcuts in primary forms
		var $primaryForm = $('form[data-saveshortcut="1"]:first');

		if ($primaryForm.length == 1)
		{
			this.addListener(Garnish.$doc, 'keydown', function(ev)
			{
				if ((ev.metaKey || ev.ctrlKey) && ev.keyCode == Garnish.S_KEY)
				{
					ev.preventDefault();

					// Give other stuff on the page a chance to prepare
					this.trigger('beforeSaveShortcut');

					if ($primaryForm.data('saveshortcut-redirect'))
					{
						$('<input type="hidden" name="redirect" value="'+$primaryForm.data('saveshortcut-redirect')+'"/>').appendTo($primaryForm);
					}

					$primaryForm.submit();
				}
				return true;
			});
		}

		Garnish.$win.on('load', $.proxy(function()
		{
			// Look for forms that we should watch for changes on
			this.$confirmUnloadForms = $('form[data-confirm-unload="1"]');

			if (this.$confirmUnloadForms.length)
			{
				this.initialFormValues = [];

				for (var i = 0; i < this.$confirmUnloadForms.length; i++)
				{
					var $form = $(this.$confirmUnloadForms);
					this.initialFormValues[i] = $form.serialize();
					this.addListener($form, 'submit', function()
					{
						this.removeListener(Garnish.$win, 'beforeunload');
					});
				}

				this.addListener(Garnish.$win, 'beforeunload', function()
				{
					for (var i = 0; i < this.$confirmUnloadForms.length; i++)
					{
						var newFormValue = $(this.$confirmUnloadForms[i]).serialize();

						if (this.initialFormValues[i] != newFormValue)
						{
							return Craft.t('Any changes will be lost if you leave this page.');
						}
					}
				});
			}
		}, this));

		this.addListener(this.$upgradePromo, 'click', 'showUpgradeModal');

		var $wrongEditionModalContainer = $('#wrongedition-modal');

		if ($wrongEditionModalContainer.length)
		{
			new Craft.WrongEditionModal($wrongEditionModalContainer);
		}
	},

	/**
	 * Handles stuff that should happen when the window is resized.
	 */
	onWindowResize: function()
	{
		// Get the new window width
		this.onWindowResize._cpWidth = Math.min(Garnish.$win.width(), CP.maxWidth);

		// Update the responsive nav
		this.updateResponsiveNav();

		// Update any responsive tables
		this.updateResponsiveTables();
	},

	onActionItemListResize: function()
	{
		this.$siteName.css('max-width', 'calc(100% - '+(this.$headerActionsList.width()+14)+'px)');
	},

	updateResponsiveNav: function()
	{
		// Is an overflow menu going to be needed?
		if (this.onWindowResize._cpWidth < this.totalNavWidth)
		{
			// Show the overflow menu button
			if (!this.showingOverflowNavMenu)
			{
				if (!this.$overflowNavMenuBtn)
				{
					// Create it
					this.$overflowNavMenuItem = $('<li/>').appendTo(this.$nav);
					this.$overflowNavMenuBtn = $('<a class="menubtn" title="'+Craft.t('More')+'">…</a>').appendTo(this.$overflowNavMenuItem);
					this.$overflowNavMenu = $('<div id="overflow-nav" class="menu" data-align="right"/>').appendTo(this.$overflowNavMenuItem);
					this.$overflowNavMenuList = $('<ul/>').appendTo(this.$overflowNavMenu);
					new Garnish.MenuBtn(this.$overflowNavMenuBtn);
				}
				else
				{
					this.$overflowNavMenuItem.show();
				}

				this.showingOverflowNavMenu = true;
			}

			// Is the nav too tall?
			if (this.$nav.height() > CP.navHeight)
			{
				// Move items to the overflow menu until the nav is back to its normal height
				do
				{
					this.addLastVisibleNavItemToOverflowMenu();
				}
				while ((this.$nav.height() > CP.navHeight) && (this.visibleNavItems > 0));
			}
			else
			{
				// See if we can fit any more nav items in the main menu
				do
				{
					this.addFirstOverflowNavItemToMainMenu();
				}
				while ((this.$nav.height() == CP.navHeight) && (this.visibleNavItems < this.totalNavItems));

				// Now kick the last one back.
				this.addLastVisibleNavItemToOverflowMenu();
			}
		}
		else
		{
			if (this.showingOverflowNavMenu)
			{
				// Hide the overflow menu button
				this.$overflowNavMenuItem.hide();

				// Move any nav items in the overflow menu back to the main nav menu
				while (this.visibleNavItems < this.totalNavItems)
				{
					this.addFirstOverflowNavItemToMainMenu();
				}

				this.showingOverflowNavMenu = false;
			}
		}
	},

	updateResponsiveTables: function()
	{
		if (!Garnish.isMobileBrowser())
		{
			return;
		}

		this.updateResponsiveTables._contentWidth = this.$content.width();

		for (this.updateResponsiveTables._i = 0; this.updateResponsiveTables._i < this.$collapsibleTables.length; this.updateResponsiveTables._i++)
		{
			this.updateResponsiveTables._$table = $(this.$collapsibleTables[this.updateResponsiveTables._i]);
			this.updateResponsiveTables._check = false;

			if (typeof this.updateResponsiveTables._lastContentWidth != 'undefined')
			{
				this.updateResponsiveTables._isLinear = this.updateResponsiveTables._$table.hasClass('collapsed');

				// Getting wider?
				if (this.updateResponsiveTables._contentWidth > this.updateResponsiveTables._lastContentWidth)
				{
					if (this.updateResponsiveTables._isLinear)
					{
						this.updateResponsiveTables._$table.removeClass('collapsed');
						this.updateResponsiveTables._check = true;
					}
				}
				else
				{
					if (!this.updateResponsiveTables._isLinear)
					{
						this.updateResponsiveTables._check = true;
					}
				}
			}
			else
			{
				this.updateResponsiveTables._check = true;
			}

			if (this.updateResponsiveTables._check)
			{
				if (this.updateResponsiveTables._$table.width() > this.updateResponsiveTables._contentWidth)
				{
					this.updateResponsiveTables._$table.addClass('collapsed');
				}
			}
		}

		this.updateResponsiveTables._lastContentWidth = this.updateResponsiveTables._contentWidth;
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

	updateFixedNotifications: function()
	{
		this.updateFixedNotifications._headerHeight = this.$header.height();

		if (Garnish.$win.scrollTop() > this.updateFixedNotifications._headerHeight)
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
		var notificationDuration = CP.notificationDuration;

		if (type == 'error')
		{
			notificationDuration *= 2;
		}

		$('<div class="notification '+type+'">'+message+'</div>')
			.appendTo(this.$notificationContainer)
			.hide()
			.fadeIn('fast')
			.delay(notificationDuration)
			.fadeOut();
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
			this.$alerts = $('<ul id="alerts"/>').insertBefore($('#header'));

			for (var i = 0; i < alerts.length; i++)
			{
				$('<li>'+alerts[i]+'</li>').appendTo(this.$alerts);
			}

			var height = this.$alerts.height();

			this.$alerts.height(0).velocity({ height: height }, 'fast', $.proxy(function()
			{
				this.$alerts.height('auto');
			}, this));

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
								Craft.cp.displayError(response.error);
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
							Craft.cp.displayError(response.error);
						}
					}

				}, this));

			}, this));
		}
	},

	checkForUpdates: function()
	{
		Craft.queueActionRequest('app/checkForUpdates', $.proxy(function(info)
		{
			this.displayUpdateInfo(info);

			this.trigger('checkForUpdates', {
				updateInfo: info
			});
		}, this));
	},

	displayUpdateInfo: function(info)
	{
		// Remove the existing header badge, if any
		this.$headerActionsList.children('li.updates').remove();

		if (info.total)
		{
			if (info.total == 1)
			{
				var updateText = Craft.t('1 update available');
			}
			else
			{
				var updateText = Craft.t('{num} updates available', { num: info.total });
			}

			// Header badge
			$('<li class="updates'+(info.critical ? ' critical' : '')+'">' +
				'<a data-icon="newstamp" href="'+Craft.getUrl('updates')+'" title="'+updateText+'">' +
					'<span>'+info.total+'</span>' +
				'</a>' +
			'</li>').prependTo(this.$headerActionsList);

			// Footer link
			$('#footer-updates').text(updateText);
		}
	},

	runPendingTasks: function()
	{
		Craft.queueActionRequest('tasks/runPendingTasks', $.proxy(function(taskInfo, textStatus)
		{
			if (taskInfo)
			{
				this.setRunningTaskInfo(taskInfo);
				this.trackTaskProgress();
			}
		}, this));
	},

	trackTaskProgress: function()
	{
		// Ignore if we're already tracking tasks
		if (this.trackTaskProgressTimeout)
		{
			return;
		}

		this.trackTaskProgressTimeout = setTimeout($.proxy(function()
		{
			this.trackTaskProgressTimeout = null;

			Craft.queueActionRequest('tasks/getRunningTaskInfo', $.proxy(function(taskInfo, textStatus)
			{
				if (textStatus == 'success')
				{
					this.setRunningTaskInfo(taskInfo, true);

					if (taskInfo.status == 'running')
					{
						// Keep checking
						this.trackTaskProgress();
					}
				}
			}, this));
		}, this), 1000);
	},

	stopTrackingTaskProgress: function()
	{
		if (this.trackTaskProgressTimeout)
		{
			clearTimeout(this.trackTaskProgressTimeout);
			this.trackTaskProgressTimeout = null;
		}
	},

	setRunningTaskInfo: function(taskInfo, animateIcon)
	{
		this.runningTaskInfo = taskInfo;

		if (taskInfo)
		{
			if (!this.taskProgressIcon)
			{
				this.taskProgressIcon = new TaskProgressIcon();
			}

			if (taskInfo.status == 'running')
			{
				this.taskProgressIcon.hideFailMode();
				this.taskProgressIcon.setDescription(taskInfo.description);
				this.taskProgressIcon.setProgress(taskInfo.progress, animateIcon);
			}
			else if (taskInfo.status == 'error')
			{
				this.taskProgressIcon.showFailMode();
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
	notificationDuration: 2000
});

Craft.cp = new CP();


/**
 * Task progress icon class
 */
var TaskProgressIcon = Garnish.Base.extend(
{
	$li: null,
	$a: null,
	hud: null,
	completed: false,
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
		this.$li = $('<li/>').prependTo(Craft.cp.$headerActionsList);
		this.$a = $('<a id="taskicon"/>').appendTo(this.$li);

		this._canvasSupported = !!(document.createElement('canvas').getContext);

		if (this._canvasSupported)
		{
			var m = (window.devicePixelRatio > 1 ? 2 : 1);
			this._canvasSize = 30 * m;
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
			this._progressBar = new Craft.ProgressBar(this.$a);
			this._progressBar.showProgressBar();
		}

		this.addListener(this.$a, 'click', 'toggleHud');
	},

	setDescription: function(description)
	{
		this.$a.attr('title', description);
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
		this.completed = true;

		if (this._canvasSupported)
		{
			this._animateArc(0, 1, $.proxy(function()
			{
				this._$bgCanvas.fadeOut();

				this._animateArc(1, 1, $.proxy(function()
				{
					this.$li.remove();
					this.destroy();
				}, this));
			}, this));
		}
		else
		{
			this._progressBar.setProgressPercentage(100);
			this.$a.fadeOut();
		}
	},

	showFailMode: function()
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

		this.setDescription(Craft.t('Failed task'));
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
		var $canvas = $('<canvas id="taskicon-'+id+'" width="'+this._canvasSize+'" height="'+this._canvasSize+'"/>').appendTo(this.$a),
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
	icon: null,

	tasksById: null,
	completedTasks: null,
	updateTasksTimeout: null,

	completed: false,

	init: function()
	{
		this.icon = Craft.cp.taskProgressIcon;
		this.tasksById = {};
		this.completedTasks = [];

		this.base(this.icon.$a);
		this.$body.attr('id', 'tasks-hud');

		// Use the known task as a starting point
		if (Craft.cp.runningTaskInfo && Craft.cp.runningTaskInfo.status != 'error')
		{
			this.showTaskInfo([Craft.cp.runningTaskInfo]);
		}

		this.$hud.trigger('resize');
	},

	onShow: function()
	{
		Craft.cp.stopTrackingTaskProgress();

		this.updateTasks();
		this.base();
	},

	onHide: function()
	{
		if (this.updateTasksTimeout)
		{
			clearTimeout(this.updateTasksTimeout);
		}

		if (!this.completed)
		{
			Craft.cp.trackTaskProgress();
		}

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

	updateTasks: function()
	{
		this.completed = false;

		Craft.postActionRequest('tasks/getTaskInfo', $.proxy(function(taskInfo, textStatus)
		{
			if (textStatus == 'success')
			{
				this.showTaskInfo(taskInfo);
			}
		}, this))
	},

	showTaskInfo: function(taskInfo)
	{
		// First remove any tasks that have completed
		var newTaskIds = [];

		if (taskInfo)
		{
			for (var i = 0; i < taskInfo.length; i++)
			{
				newTaskIds.push(taskInfo[i].id);
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
		if (taskInfo && taskInfo.length)
		{
			var anyTasksRunning = false,
				anyTasksFailed = false;

			for (var i = 0; i < taskInfo.length; i++)
			{
				var info = taskInfo[i];

				if (!anyTasksRunning && info.status == 'running')
				{
					anyTasksRunning = true;
				}
				else if (!anyTasksFailed && info.status == 'error')
				{
					anyTasksFailed = true;
				}

				if (this.tasksById[info.id])
				{
					this.tasksById[info.id].updateStatus(info);
				}
				else
				{
					this.tasksById[info.id] = new TaskProgressHUD.Task(this, info);

					// Place it before the next already known task
					for (var j = i + 1; j < taskInfo.length; j++)
					{
						if (this.tasksById[taskInfo[j].id])
						{
							this.tasksById[info.id].$container.insertBefore(this.tasksById[taskInfo[j].id].$container);
							break;
						}
					}
				}
			}

			if (anyTasksRunning)
			{
				this.updateTasksTimeout = setTimeout($.proxy(this, 'updateTasks'), 500);
			}
			else
			{
				this.completed = true;

				if (anyTasksFailed)
				{
					Craft.cp.setRunningTaskInfo({ status: 'error' });
				}
			}
		}
		else
		{
			this.completed = true;
			Craft.cp.setRunningTaskInfo(null);
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

		this.$container = $('<div class="task"/>').appendTo(this.hud.$body);
		this.$statusContainer = $('<div class="task-status"/>').appendTo(this.$container);
		this.$descriptionContainer = $('<div class="task-description"/>').appendTo(this.$container).text(info.description);

		this.$container.data('task', this);

		if (this.level != 0)
		{
			this.$container.css('padding-'+Craft.left, 24+(this.level*24));
			$('<div class="indent" data-icon="'+(Craft.orientation == 'ltr' ? 'rarr' : 'larr')+'"/>').appendTo(this.$descriptionContainer);;
		}

		this.updateStatus(info);
	},

	updateStatus: function(info)
	{
		if (this.status != info.status)
		{
			this.$statusContainer.empty();
			this.status = info.status;

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
				case 'error':
				{
					$('<span class="error">'+Craft.t('Failed')+'</span>').appendTo(this.$statusContainer);

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

			if (this.level == 0)
			{
				// Update the task icon
				Craft.cp.setRunningTaskInfo(info, true);
			}
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

						if (this.hud.completed)
						{
							this.hud.updateTasks();
						}
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

						if (this.hud.completed)
						{
							this.hud.updateTasks();
						}
					}
				}, this))
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
