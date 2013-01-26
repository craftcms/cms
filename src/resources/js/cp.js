(function($) {


var CP = Garnish.Base.extend({

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

	navItems: null,
	totalNavItems: null,
	visibleNavItems: null,
	totalNavWidth: null,
	showingOverflowNavMenu: false,

	fixedNotifications: false,

	tabs: null,
	selectedTab: null,

	init: function()
	{
		// Find all the key elements
		this.$header = $('#header');
		this.$nav = $('#nav');
		/* HIDE */
		this.$customizeNavBtn = $('#customize-nav');
		/* end HIDE */
		this.$notificationWrapper = $('#notifications-wrapper');
		this.$notificationContainer = $('#notifications');

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
		var $notifications = this.$notificationContainer.children();
		$notifications.delay(CP.notificationDuration).fadeOut();

		/* HIDE */
		// Customize Nav button
		this.addListener(this.$customizeNavBtn, 'click', 'showCustomizeNavModal');
		/* end HIDE */

		// Tabs
		this.tabs = {};
		var $tabs = $('#tabs a');

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

				this.addListener($tab, 'activate', 'selectTab');
			}

			if (!this.selectedTab && $tab.hasClass('sel'))
			{
				this.selectedTab = href;
			}
		}

		// Secondary form submit buttons
		$('.formsubmit').click(function() {
			var $btn = $(this),
				$form = $btn.closest('form');

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
	},

	/**
	 * Handles stuff that should happen when the window is resized.
	 */
	onWindowResize: function()
	{
		// Get the new window width
		this.onWindowResize._windowWidth = Math.min(Garnish.$win.width(), CP.maxWidth);
		this.onWindowResize._$lastNavItem = null;

		// Is an overflow menu going to be needed?
		if (this.onWindowResize._windowWidth < this.totalNavWidth)
		{
			// Show the overflow menu button
			if (!this.showingOverflowNavMenu)
			{
				if (!this.$overflowNavMenuBtn)
				{
					// Create it
					this.$overflowNavMenuItem = $('<li/>').appendTo(this.$nav);
					this.$overflowNavMenuBtn = $('<a class="menubtn" title="'+Blocks.t('More')+'">…</a>').appendTo(this.$overflowNavMenuItem);
					this.$overflowNavMenu = $('<div id="overflow-nav" class="menu" data-align="right"/>').appendTo(this.$overflowNavMenuItem);
					this.$overflowNavMenuList = $('<ul/>').appendTo(this.$overflowNavMenu);
					this.$overflowNavMenuBtn.menubtn();
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
				$header = $('<header class="header"><h1>'+Blocks.t('Customize your nav')+'</h1></header>').appendTo($modal),
				$body = $('<div class="body"/>').appendTo($modal),
				$ul = $('<ul/>').appendTo($body);

			for (var i = 0; i < this.totalNavItems; i++)
			{
				var $navItem = this.navItems[i];
				$
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

	/**
	 * Dispays a notification.
	 *
	 * @param string type
	 * @param string message
	 */
	displayNotification: function(type, message)
	{
		$('<div class="notification '+type+'">'+message+'</div>')
			.appendTo(this.$notificationContainer)
			.fadeIn('fast')
			.delay(CP.notificationDuration)
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
		this.displayNotification('error', message);
	},

	/**
	 * Select a tab
	 */
	selectTab: function(ev)
	{
		if (!this.selectedTab || ev.currentTarget != this.tabs[this.selectedTab].$tab[0])
		{
			// Hide the selected tab
			if (this.selectedTab)
			{
				this.tabs[this.selectedTab].$tab.removeClass('sel');
				this.tabs[this.selectedTab].$target.addClass('hidden');
			}

			var $tab = $(ev.currentTarget).addClass('sel');
			this.selectedTab = $tab.attr('href');
			this.tabs[this.selectedTab].$target.removeClass('hidden');
		}
	}

},
{
	maxWidth: 1000,
	navHeight: 38,
	baseNavWidth: 30,
	notificationDuration: 2000
});


Blocks.cp = new CP();


})(jQuery);
