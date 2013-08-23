(function($) {

Craft.UpdatesWidget = Garnish.Base.extend({

	$widget: null,

	init: function(widgetId)
	{
		this.$widget = $('#widget'+widgetId);
		this.$widget.addClass('loading');

		Craft.postActionRequest('dashboard/checkForUpdates', $.proxy(function(response, textStatus) {

			this.$widget.removeClass('loading');

			if (textStatus == 'success')
			{
				var text = response;
			}
			else
			{
				var text = Craft.t('An unknown error occurred.');
			}

			this.$widget.find('.body').html(text);

		}, this), {
			complete: $.noop
		});
	}
});


})(jQuery);
