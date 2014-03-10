/**
 * Pane class
 */
Craft.Pane = Garnish.Base.extend(
{
	$pane: null,
	$content: null,
	$sidebar: null,
	$sidebarBtn: null,

	tabs: null,
	selectedTab: null,
	hasSidebar: null,
	showingSidebar: null,
	peekingSidebar: null,

	init: function(pane)
	{
		this.$pane = $(pane);
		this.$content = this.$pane.find('.content:not(.hidden):first');

		// Initialize the tabs
		var $tabsContainer = this.$pane.children('.tabs'),
			$tabs = $tabsContainer.find('a')

		if ($tabs.length)
		{
			this.tabs = {};

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

			if (document.location.hash && typeof this.tabs[document.location.hash] != 'undefined')
			{
				this.tabs[document.location.hash].$tab.trigger('activate');
			}
			else if (!this.selectedTab)
			{
				$($tabs[0]).trigger('activate');
			}
		}

		this.initContent();
	},

	/**
	 * Selects a tab.
	 */
	selectTab: function(ev)
	{
		if (!this.selectedTab || ev.currentTarget != this.tabs[this.selectedTab].$tab[0])
		{
			// Hide the selected tab
			this.deselectTab();

			var $tab = $(ev.currentTarget).addClass('sel');
			this.selectedTab = $tab.attr('href');

			var $target = this.tabs[this.selectedTab].$target;
			$target.removeClass('hidden');

			if ($target.hasClass('content'))
			{
				if (this.hasSidebar)
				{
					this.removeListener(this.$content, 'resize');
					this.removeListener(this.$sidebar, 'resize');
				}

				this.$content = $target;
				this.initContent();
			}

			Garnish.$win.trigger('resize');
		}
	},

	/**
	 * Deselects the current tab.
	 */
	deselectTab: function()
	{
		if (this.selectedTab)
		{
			this.tabs[this.selectedTab].$tab.removeClass('sel');
			this.tabs[this.selectedTab].$target.addClass('hidden');
		}
	},

	initContent: function()
	{
		this.hasSidebar = this.$content.hasClass('has-sidebar');

		if (this.hasSidebar)
		{
			this.$sidebar = this.$content.children('.sidebar');

			this.showingSidebar = true;
			this.updateResponsiveSidebar();
			this.addListener(this.$content, 'resize', 'updateResponsiveSidebar');

			this.setMinContentSizeForSidebar();
			this.addListener(this.$sidebar, 'resize', 'setMinContentSizeForSidebar');
		}
	},

	updateResponsiveSidebar: function()
	{
		if (this.$content.width() + parseInt(this.$content.css('margin-left')) < Craft.Pane.minContentWidthForSidebar)
		{
			if (this.showingSidebar)
			{
				this.hideSidebar();
			}
		}
		else
		{
			if (!this.showingSidebar)
			{
				this.showSidebar();
			}
		}
	},

	hideSidebar: function()
	{
		this.$content.addClass('hiding-sidebar');

		this.$sidebarBtn = $('<a class="show-sidebar" title="'+Craft.t('Show sidebar')+'"></a>').appendTo(this.$content);
		this.addListener(this.$sidebarBtn, 'click', 'togglePeekingSidebar');

		this.showingSidebar = false;
		this.setMinContentSizeForSidebar();
	},

	togglePeekingSidebar: function()
	{
		if (this.peekingSidebar)
		{
			this.$content.animate({ left: 0 }, 'fast');
			this.$sidebarBtn.removeClass('showing').attr('title', Craft.t('Show sidebar'));
			this.peekingSidebar = false;

			this.removeListener(this.$sidebar, 'click');
		}
		else
		{
			this.$content.animate({ left: 194 }, 'fast');
			this.$sidebarBtn.addClass('showing').attr('title', Craft.t('Hide sidebar'));
			this.peekingSidebar = true;

			this.addListener(this.$sidebar, 'click', $.proxy(function(ev)
			{
				if (ev.target.nodeName == 'A')
				{
					this.togglePeekingSidebar();
				}
			}, this))
		}

		this.setMinContentSizeForSidebar();
	},

	showSidebar: function()
	{
		this.$content.removeClass('hiding-sidebar');
		this.$sidebarBtn.remove();
		this.showingSidebar = true;
		this.setMinContentSizeForSidebar();
	},

	setMinContentSizeForSidebar: function()
	{
		if (this.showingSidebar || this.peekingSidebar)
		{
			var minHeight = this.$sidebar.height();
		}
		else
		{
			var minHeight = 0;
		}

		this.$content.css('min-height', minHeight);
	}
},
{
	minContentWidthForSidebar: 514 // 320 + 194
});
