(function($) {


var WidgetAdmin = b.ui.AdminPane.extend({

	menuBtn: null,

	init: function()
	{
		var $container = $('#widgetadmin');
		this.base($container);

		this.removeListener(this.$addBtn, 'click');
		this.menuBtn = new b.ui.MenuBtn(this.$addBtn, {
			onOptionSelect: $.proxy(this, 'addItem')
		});
	},

	addItem: function(option)
	{
		var item = this.base();

		var $option = $(option),
			widgetClass = $option.attr('data-widget-class'),
			widgetTitle = $option.attr('data-widget-title'),
			widgetType = $option.html(),
			$freshWidgetSettings = $('#freshwidgetsettings-'+widgetClass);

		if ($freshWidgetSettings.length)
			var freshWidgetSettingsHtml = $freshWidgetSettings.html().replace(/ITEM_ID/g, item.id)
		else
			var freshWidgetSettingsHtml = '';

		item.setName(widgetTitle);
		item.setType(widgetType);
		item.$item.append($('<input type="hidden" name="widgets['+item.id+'][class]" value="'+widgetClass+'"/>'));
		item.$settings.prepend(freshWidgetSettingsHtml);

		this.setHeight();
	}

});


var widgetAdmin = new WidgetAdmin();


})(jQuery);
