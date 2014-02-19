(function($) {

Craft.UpdatesWidget = Garnish.Base.extend({

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
			this.lookLikeWereChecking();

			Craft.cp.on('checkForUpdates', $.proxy(function(ev) {
				this.showUpdateInfo(ev.updateInfo);
			}, this))
		}
	},

	initBtn: function()
	{
		this.$btn = this.$body.find('.btn:first');
		this.addListener(this.$btn, 'click', $.proxy(this, 'checkForUpdates'));
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

		var data = {
			forceRefresh: true
		};

		Craft.postActionRequest('app/checkForUpdates', data, $.proxy(this, 'showUpdateInfo'));
	},

	showUpdateInfo: function(info)
	{
		this.dontLookLikeWereChecking();

		if (info.total)
		{
			if (info.total == 1)
			{
				var updateText = Craft.t('One update available!');
			}
			else
			{
				var updateText = Craft.t('{total} updates available!', { total: info.total });
			}

			this.$body.html(
				'<p class="centeralign">' +
					updateText +
					' <a class="go" href="'+Craft.getUrl('updates')+'">'+Craft.t('Go to Updates')+'</a>' +
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
