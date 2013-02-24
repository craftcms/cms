(function($) {

Craft.UpdatesWidget = Garnish.Base.extend({

	$widget: null,

	init: function(widgetId)
	{
		this.$widget = $('#widget'+widgetId);
		this.$widget.addClass('loading');

		Craft.postActionRequest('dashboard/checkForUpdates', $.proxy(function(response) {

			this.$widget.removeClass('loading');
			this.$widget.find('.body').html(response);

		}, this));
	}
});


})(jQuery);
