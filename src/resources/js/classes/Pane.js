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
	fixedSidebar: null,

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
					this.removeListener(Garnish.$win, 'scroll resize');
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
			this.addListener(this.$content, 'resize', function()
			{
				this.updateResponsiveSidebar();
				this.updateSidebarStyles();
			});

			this.addListener(this.$sidebar, 'resize', 'setMinContentSizeForSidebar');
			this.setMinContentSizeForSidebar();

			this.addListener(Garnish.$win, 'scroll resize', 'updateSidebarStyles');
			this.updateSidebarStyles();
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

	showSidebar: function()
	{
		this.$content.removeClass('hiding-sidebar');
		this.$sidebarBtn.remove();
		this.showingSidebar = true;
		this.setMinContentSizeForSidebar();

		if (this.peekingSidebar)
		{
			this.stopPeeking();
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
			this.stopPeeking();
		}
		else
		{
			this.startPeeking();
		}

		this.setMinContentSizeForSidebar();
	},

	startPeeking: function()
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
	},

	stopPeeking: function()
	{
		this.$content.animate({ left: 0 }, 'fast');
		this.$sidebarBtn.removeClass('showing').attr('title', Craft.t('Show sidebar'));
		this.peekingSidebar = false;

		this.removeListener(this.$sidebar, 'click');
	},

	setMinContentSizeForSidebar: function()
	{
		if (this.showingSidebar || this.peekingSidebar)
		{
			this.setMinContentSizeForSidebar._minHeight = this.$sidebar.prop('scrollHeight') - 48;
		}
		else
		{
			this.setMinContentSizeForSidebar._minHeight = 0;
		}

		this.$content.css('min-height', this.setMinContentSizeForSidebar._minHeight);
	},

	updateSidebarStyles: function()
	{
		if (this.showingSidebar || this.peekingSidebar)
		{
			this.updateSidebarStyles._styles = {};

			this.updateSidebarStyles._scrollTop = Garnish.$win.scrollTop();
			this.updateSidebarStyles._contentOffset = this.$content.offset().top;
			this.updateSidebarStyles._contentHeight = this.$content.height() - 24;
			this.updateSidebarStyles._windowHeight = Garnish.$win.height();

			// Have we scrolled passed the top of the content div?
			if (this.updateSidebarStyles._scrollTop > this.updateSidebarStyles._contentOffset - 24)
			{
				// Set the top position to the difference
				this.updateSidebarStyles._styles.top = this.updateSidebarStyles._scrollTop - this.updateSidebarStyles._contentOffset;
			}
			else
			{
				this.updateSidebarStyles._styles.top = -24;
			}

			// Now figure out how tall the sidebar can be
			this.updateSidebarStyles._styles.maxHeight = Math.min(this.updateSidebarStyles._contentHeight - this.updateSidebarStyles._styles.top, this.updateSidebarStyles._windowHeight - 48);

			// The sidebar should be at least 100px tall if possible
			if (this.updateSidebarStyles._styles.top != 0 && this.updateSidebarStyles._styles.maxHeight < 100)
			{
				this.updateSidebarStyles.newTop = Math.max(0, this.updateSidebarStyles._styles.top - (100 - this.updateSidebarStyles._styles.maxHeight));
				this.updateSidebarStyles._styles.maxHeight += this.updateSidebarStyles._styles.top - this.updateSidebarStyles.newTop;
				this.updateSidebarStyles._styles.top = this.updateSidebarStyles.newTop;
			}

			this.$sidebar.css(this.updateSidebarStyles._styles);
		}
	}
},
{
	minContentWidthForSidebar: 514 // 320 + 194
});
