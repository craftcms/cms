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
			this.checkForUpdates();
		}
	},

	initBtn: function()
	{
		this.$btn = this.$body.find('.btn:first');
		this.addListener(this.$btn, 'click', function() {
			this.checkForUpdates(true);
		});
	},

	checkForUpdates: function(forceRefresh)
	{
		if (this.checking)
		{
			return;
		}

		this.checking = true;
		this.$widget.addClass('loading');
		this.$btn.addClass('disabled');

		var data = {
			forceRefresh: forceRefresh
		};

		Craft.postActionRequest('dashboard/checkForUpdates', data, $.proxy(function(response, textStatus) {

			this.checking = false;
			this.$widget.removeClass('loading');

			if (textStatus == 'success')
			{
				this.$body.html(response);
				this.initBtn();
			}
			else
			{
				this.$body.find('p:first').text('An unknown error occurred.');
			}

		}, this), {
			complete: $.noop
		});
	}
});


})(jQuery);
