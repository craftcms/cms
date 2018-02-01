/**
 * Pane class
 */
Craft.Pane = Garnish.Base.extend(
{
	$pane: null,
	$content: null,
	$sidebar: null,
	$tabsContainer: null,

	tabs: null,
	selectedTab: null,
	hasSidebar: null,

	init: function(pane)
	{
		this.$pane = $(pane);

		// Is this already a pane?
		if (this.$pane.data('pane'))
		{
			Garnish.log('Double-instantiating a pane on an element');
			this.$pane.data('pane').destroy();
		}

		this.$pane.data('pane', this);

		this.$content = this.$pane.find('.content:not(.hidden):first');

		// Initialize the tabs
		this.$tabsContainer = this.$pane.children('.tabs');
		var $tabs = this.$tabsContainer.find('a');

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

		if (this.$pane.hasClass('meta'))
		{
			var $inputs = Garnish.findInputs(this.$pane);
			this.addListener($inputs, 'focus', 'focusMetaField');
			this.addListener($inputs, 'blur', 'blurMetaField');
		}

		this.initContent();
	},

	focusMetaField: function(ev)
	{
		$(ev.currentTarget).closest('.field')
			.removeClass('has-errors')
			.addClass('has-focus');
	},

	blurMetaField: function(ev)
	{
		$(ev.currentTarget).closest('.field')
			.removeClass('has-focus');
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
				this.$content = $target;
			}

			Garnish.$win.trigger('resize');

			// Fixes Redactor fixed toolbars on previously hidden panes
			Garnish.$doc.trigger('scroll');
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

			this.addListener(this.$content, 'resize', function()
			{
				this.updateSidebarStyles();
			});

			this.addListener(this.$sidebar, 'resize', 'setMinContentSizeForSidebar');
			this.setMinContentSizeForSidebar();

			this.addListener(Garnish.$win, 'resize', 'updateSidebarStyles');
			this.addListener(Garnish.$win, 'scroll', 'updateSidebarStyles');

			this.updateSidebarStyles();
		}
	},

	setMinContentSizeForSidebar: function()
	{
		if (true || this.$pane.hasClass('showing-sidebar'))
		{
			this.setMinContentSizeForSidebar._minHeight = this.$sidebar.prop('scrollHeight') - (this.$tabsContainer.height() || 0) - 48;
		}
		else
		{
			this.setMinContentSizeForSidebar._minHeight = 0;
		}

		this.$content.css('min-height', this.setMinContentSizeForSidebar._minHeight);
	},

	updateSidebarStyles: function()
	{
		this.updateSidebarStyles._styles = {};

		this.updateSidebarStyles._scrollTop = Garnish.$win.scrollTop();
		this.updateSidebarStyles._paneOffset = this.$pane.offset().top + (this.$tabsContainer.height() || 0);
		this.updateSidebarStyles._paneHeight = this.$pane.outerHeight() - (this.$tabsContainer.height() || 0);
		this.updateSidebarStyles._windowHeight = Garnish.$win.height();

		// Have we scrolled passed the top of the pane?
		if (Garnish.$win.width() > 992 && this.updateSidebarStyles._scrollTop > this.updateSidebarStyles._paneOffset)
		{
			// Set the top position to the difference
			this.updateSidebarStyles._styles.position = 'fixed';
			this.updateSidebarStyles._styles.top = '24px';
		}
		else
		{
			this.updateSidebarStyles._styles.position = 'absolute';
			this.updateSidebarStyles._styles.top = 'auto';
		}

		// Now figure out how tall the sidebar can be
		this.updateSidebarStyles._styles.maxHeight = Math.min(
			this.updateSidebarStyles._paneHeight - (this.updateSidebarStyles._scrollTop - this.updateSidebarStyles._paneOffset),
			this.updateSidebarStyles._windowHeight
		);

		if(this.updateSidebarStyles._paneHeight > this.updateSidebarStyles._windowHeight)
		{
			this.updateSidebarStyles._styles.height = this.updateSidebarStyles._styles.maxHeight;
		}
		else
		{
			this.updateSidebarStyles._styles.height = this.updateSidebarStyles._paneHeight;
		}

		this.$sidebar.css(this.updateSidebarStyles._styles);
	},

	destroy: function()
	{
		this.base();
		this.$pane.data('pane', null);
	}
});
