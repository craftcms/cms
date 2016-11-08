(function($) {

Craft.UpdatesWidget = Garnish.Base.extend(
{
	$widget: null,
	$body: null,
	$btn: null,
	checking: false,

	init: function(widgetId, cached)
	{
		this.$widget = $('#widget'+widgetId);
		this.$body = this.$widget.find('.body:first');
		this.initBtn();

		if (!cached)
		{
			this.checkForUpdates(false);
		}
	},

	initBtn: function()
	{
		this.$btn = this.$body.find('.btn:first');
		this.addListener(this.$btn, 'click', function() {
			this.checkForUpdates(true);
		});
	},

	lookLikeWereChecking: function()
	{
		this.checking = true;
		this.$widget.addClass('loading');
		this.$btn.addClass('disabled');
	},

	dontLookLikeWereChecking: function()
	{
		this.checking = false;
		this.$widget.removeClass('loading');
	},

	checkForUpdates: function(forceRefresh)
	{
		if (this.checking)
		{
			return;
		}

		this.lookLikeWereChecking();
		Craft.cp.checkForUpdates(forceRefresh, $.proxy(this, 'showUpdateInfo'));
	},

	showUpdateInfo: function(info)
	{
		this.dontLookLikeWereChecking();

		if (info.total)
		{
			var updateText;

			if (info.total == 1)
			{
				updateText = Craft.t('One update available!');
			}
			else
			{
				updateText = Craft.t('{total} updates available!', { total: info.total });
			}

			this.$body.html(
				'<p class="centeralign">' +
					updateText +
					' <a class="go nowrap" href="'+Craft.getUrl('updates')+'">'+Craft.t('Go to Updates')+'</a>' +
				'</p>'
			);
		}
		else
		{
			this.$body.html(
				'<p class="centeralign">'+Craft.t('Congrats! You’re up-to-date.')+'</p>' +
				'<p class="centeralign"><a class="btn" data-icon="refresh">'+Craft.t('Check again')+'</a></p>'
			);

			this.initBtn();
		}

		// Update the CP header badge
		Craft.cp.displayUpdateInfo(info);
	}
});


})(jQuery);
