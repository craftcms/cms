/**
 * Pane class
 */
Craft.Pane = Garnish.Base.extend(
{
	$pane: null,
	$content: null,

	isSettingsPane: null,
	tabs: null,
	selectedTab: null,

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

		this.isSettingsPane = this.$pane.hasClass('settings-pane');
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

		if (this.isSettingsPane)
		{
			var $inputs = Garnish.findInputs(this.$pane);
			this.addListener($inputs, 'focus', 'focusField');
			this.addListener($inputs, 'blur', 'blurField');
		}

		// this.initContent();
	},

	focusField: function(ev)
	{
		$(ev.currentTarget).closest('.field')
			.removeClass('has-errors')
			.addClass('has-focus');
	},

	blurField: function(ev)
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

	destroy: function()
	{
		this.base();
		this.$pane.data('pane', null);
	}
});
