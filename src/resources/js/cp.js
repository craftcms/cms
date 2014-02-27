(function($) {


var CP = Garnish.Base.extend(
{
	$alerts: null,
	$header: null,
	$nav: null,

	$overflowNavMenuItem: null,
	$overflowNavMenuBtn: null,
	$overflowNavMenu: null,
	$overflowNavMenuList: null,

	/* HIDE */
	$customizeNavBtn: null,
	/* end HIDE */

	$notificationWrapper: null,
	$notificationContainer: null,
	$contentTabsContainer: null,
	$main: null,
	$content: null,
	//$sidebar: null,
	//$altSidebar: null,
	//$altSidebarNavBtn: null,
	//$altSidebarNavMenu: null,
	$collapsibleTables: null,

	waitingOnAjax: false,
	ajaxQueue: null,

	navItems: null,
	totalNavItems: null,
	visibleNavItems: null,
	totalNavWidth: null,
	showingOverflowNavMenu: false,
	//showingSidebar: true,

	fixedNotifications: false,
	//fixedSidebar: false,

	tabs: null,
	selectedTab: null,

	init: function()
	{
		// Find all the key elements
		this.$alerts = $('#alerts');
		this.$header = $('#header');
		this.$nav = $('#nav');
		/* HIDE */
		this.$customizeNavBtn = $('#customize-nav');
		/* end HIDE */
		this.$notificationWrapper = $('#notifications-wrapper');
		this.$notificationContainer = $('#notifications');
		this.$main = $('#main');
		this.$content = $('#content');
		//this.$sidebar = $('#sidebar');
		this.$collapsibleTables = this.$content.find('table.collapsible');

		this.ajaxQueue = [];

		// Set the max sidebar height
		//this.setMaxSidebarHeight();

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

		this.addListener(Garnish.$win, 'scroll', 'onWindowScroll');
		this.onWindowScroll();

		// Fade the notification out in two seconds
		var $errorNotifications = this.$notificationContainer.children('.error'),
			$otherNotifications = this.$notificationContainer.children(':not(.error)');

		$errorNotifications.delay(CP.notificationDuration * 2).fadeOut();
		$otherNotifications.delay(CP.notificationDuration).fadeOut();

		/* HIDE */
		// Customize Nav button
		this.addListener(this.$customizeNavBtn, 'click', 'showCustomizeNavModal');
		/* end HIDE */

		// Tabs
		this.initContentTabs();

		if (document.location.hash && typeof this.tabs[document.location.hash] != 'undefined')
		{
			this.tabs[document.location.hash].$tab.trigger('click');
		}

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

		// Make placeholders work for IE9, too.
		$('input[type!=password], textarea').placeholder();

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
	},

	initContentTabs: function()
	{
		this.$contentTabsContainer = $('#tabs');
		this.tabs = {};
		this.selectedTab = null;

		var $tabs = this.$contentTabsContainer.find('a');

		if ($tabs.length)
		{
			// Find the tabs that link to a div on the page
			for (var i = 0; i < $tabs.length; i++)
			{
				var $tab = $($tabs[i]),
					href = $tab.attr('href');

				if (href && href.charAt(0) == '#')
				{
					this.tabs[href] = {
						$tab: $tab,
						$target: $(href)
					};

					this.addListener($tab, 'activate', 'selectContentTab');
				}

				if (!this.selectedTab && $tab.hasClass('sel'))
				{
					this.selectedTab = href;
				}
			}

			if (!this.selectedTab)
			{
				$($tabs[0]).trigger('activate');
			}
		}
	},

	/*setMaxSidebarHeight: function()
	{
		if (this.fixedSidebar)
		{
			this.setMaxSidebarHeight._maxHeight = this.$main.offset().top + this.$main.height() - Garnish.$win.scrollTop();
		}
		else
		{
			this.setMaxSidebarHeight._maxHeight = this.$main.height();
		}

		this.$sidebar.css('max-height', this.setMaxSidebarHeight._maxHeight);
	},*/

	/**
	 * Handles stuff that should happen when the window is resized.
	 */
	onWindowResize: function()
	{
		// Get the new window width
		this.onWindowResize._cpWidth = Math.min(Garnish.$win.width(), CP.maxWidth);

		// Update the responsive nav
		this.updateResponsiveNav();

		// Update the responsive sidebar
		//this.updateResponsiveSidebar();

		// Update any responsive tables
		this.updateResponsiveTables();

		// Reset the max sidebar height
		//this.setMaxSidebarHeight();
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

	/**
	 * Updates the responsive sidebar
	 */
	/*updateResponsiveSidebar: function()
	{
		if (!this.$sidebar.length)
		{
			return;
		}

		if (this.onWindowResize._cpWidth < CP.minSidebarWidth)
		{
			if (this.showingSidebar)
			{
				this.makeSidebarUnfixed();
				this.$main.removeClass(CP.hasSidebarClass);

				if (!this.$altSidebar)
				{
					this.$altSidebar = $('<div id="sidebar-alt"/>').insertAfter(this.$sidebar);

					var $sidebarChildren = this.$sidebar.children();

					for (var i = 0; i < $sidebarChildren.length; i++)
					{
						var $elem = $($sidebarChildren[i]).clone(true);

						if ($elem.prop('nodeName') == 'NAV')
						{
							// Create a menu instead
							var selectedText = $elem.find('.sel:first').text(),
								$list = $elem.children();

							this.$altSidebarNavBtn = $('<div class="btn menubtn">'+selectedText+'</div>').appendTo(this.$altSidebar);
							this.$altSidebarNavMenu = $('<div class="menu padded"/>').appendTo(this.$altSidebar);

							$list.appendTo(this.$altSidebarNavMenu);
							new Garnish.MenuBtn(this.$altSidebarNavBtn);
							$elem.detach();
						}
						else
						{
							$elem.appendTo(this.$altSidebar);
						}
					}
				}
				else
				{
					this.$altSidebar.show();
				}

				this.$sidebar.hide();
				this.showingSidebar = false;
			}
		}
		else
		{
			if (!this.showingSidebar)
			{
				this.$main.addClass(CP.hasSidebarClass);
				this.$altSidebar.hide();
				this.$sidebar.show();
				this.showingSidebar = true;
				this.updateFixedSidebar();
			}
		}
	},*/

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

	/* HIDE */
	/**
	 * Shows the "Customize your nav" modal.
	 */
	showCustomizeNavModal: function()
	{
		if (!this.customizeNavModal)
		{
			var $modal = $('<div id="customize-nav-modal" class="modal"/>').appendTo(document.body),
				$header = $('<header class="header"><h1>'+Craft.t('Customize your nav')+'</h1></header>').appendTo($modal),
				$body = $('<div class="body"/>').appendTo($modal),
				$ul = $('<ul/>').appendTo($body);

			for (var i = 0; i < this.totalNavItems; i++)
			{
				var $navItem = this.navItems[i];
			}

			this.customizeNavModal = new Garnish.Modal($modal);
		}
		else
		{
			this.customizeNavModal.show();
		}
	},
	/* end HIDE */

	/**
	 * Handle stuff that should happen when the window scrolls.
	 */
	onWindowScroll: function()
	{
		this.onWindowScroll._scrollTop = Garnish.$win.scrollTop();

		this.updateFixedNotifications();
		//this.updateFixedSidebar();
	},

	updateFixedNotifications: function()
	{
		this.onWindowScroll._headerHeight = this.$header.height();

		if (this.onWindowScroll._scrollTop > this.onWindowScroll._headerHeight)
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

	/*updateFixedSidebar: function()
	{
		if (this.showingSidebar && this.$sidebar.length)
		{
			// Determine if we've scrolled passed the top of the sidebar,
			// which conveniently is the same as #main

			if (this.onWindowScroll._scrollTop > this.$main.offset().top)
			{
				this.makeSidebarFixed();
				this.setMaxSidebarHeight();
			}
			else
			{
				this.makeSidebarUnfixed();
			}
		}
	},

	/*makeSidebarFixed: function()
	{
		if (!this.fixedSidebar)
		{
			this.$sidebar.addClass('fixed');
			this.fixedSidebar = true;
		}
	},

	makeSidebarUnfixed: function()
	{
		if (this.fixedSidebar)
		{
			this.$sidebar.removeClass('fixed');
			this.fixedSidebar = false;
			this.setMaxSidebarHeight();
		}
	},*/

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

	/**
	 * Deselects the current content tab.
	 */
	deselectContentTab: function()
	{
		if (this.selectedTab)
		{
			this.tabs[this.selectedTab].$tab.removeClass('sel');
			this.tabs[this.selectedTab].$target.addClass('hidden');
		}
	},

	/**
	 * Selects a content tab.
	 */
	selectContentTab: function(ev)
	{
		if (!this.selectedTab || ev.currentTarget != this.tabs[this.selectedTab].$tab[0])
		{
			// Hide the selected tab
			this.deselectContentTab();

			var $tab = $(ev.currentTarget).addClass('sel');
			this.selectedTab = $tab.attr('href');
			this.tabs[this.selectedTab].$target.removeClass('hidden');

			Garnish.$win.trigger('resize');
			//this.setMaxSidebarHeight();
		}
	},

	postActionRequest: function(action, data, callback, options)
	{
		this.ajaxQueue.push({
			action: action,
			data: data,
			callback: callback,
			options: options
		});

		if (!this.waitingOnAjax)
		{
			this.postNextActionRequest();
		}
	},

	postNextActionRequest: function()
	{
		this.waitingOnAjax = true;

		var args = this.ajaxQueue.shift();

		Craft.postActionRequest(args.action, args.data, $.proxy(function(data, textStatus, jqXHR)
		{
			args.callback(data, textStatus, jqXHR);

			if (this.ajaxQueue.length)
			{
				this.postNextActionRequest();
			}
			else
			{
				this.waitingOnAjax = false;
			}

		}, this), args.options);
	},

	fetchAlerts: function()
	{
		var data = {
			path: Craft.path
		};

		this.postActionRequest('app/getCpAlerts', data, $.proxy(this, 'displayAlerts'));
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

			this.$alerts.height(0).animate({ height: height }, 'fast', $.proxy(function()
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
					Craft.postActionRequest('app/transferLicenseToCurrentDomain', $.proxy(function(response, textStatus)
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

				Craft.postActionRequest('app/shunCpAlert', data, $.proxy(function(response, textStatus)
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
		this.postActionRequest('app/checkForUpdates', {}, $.proxy(function(info)
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
		$('#header-actions > li.updates').remove();

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
			'</li>').prependTo($('#header-actions'));

			// Footer link
			$('#footer-updates').text(updateText);
		}
	}
},
{
	maxWidth: 1051, //1024,
	navHeight: 38,
	baseNavWidth: 30,
	//minSidebarWidth: 768,
	//hasSidebarClass: 'has-sidebar',
	notificationDuration: 2000
});


Craft.cp = new CP();


})(jQuery);
