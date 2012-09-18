(function($) {


var $table = $('#widgets'),
	totalWidgets = $table.children('tbody').children().length;


var sorter = new Blocks.ui.DataTableSorter($table, {
	onSortChange: function() {

		// Get the new widget order
		var widgetIds = [];

		for (var i = 0; i < sorter.$items.length; i++)
		{
			var widgetId = parseInt($(sorter.$items[i]).attr('data-widget-id'));
			widgetIds.push(widgetId);
		}

		// Send it to the server
		var data = {
			widgetIds: JSON.stringify(widgetIds)
		};

		$.post(Blocks.actionUrl+'dashboard/reorderUserWidgets', data, function(response) {
			if (response.success)
				Blocks.cp.displayNotice(Blocks.t('New widget order saved.'));
			else
				Blocks.cp.displayError(Blocks.t('Couldn’t save new widget order.'));
		});
	}
});


$table.find('.deletebtn').click(function() {
	var $row = $(this).closest('tr'),
		widgetName = $row.children(':first').children('a').text();

	if (confirm(Blocks.t('Are you sure you want to delete the widget “{widget}”?', { widget: widgetName })))
	{
		var data = {
			widgetId: $row.attr('data-widget-id')
		};

		$.post(Blocks.actionUrl+'dashboard/deleteUserWidget', data, function(response) {
			if (response.success)
			{
				$row.remove();

				totalWidgets--;
				if (totalWidgets == 0)
				{
					$table.remove();
					$('#nowidgets').show();
				}

				Blocks.cp.displayNotice(Blocks.t('Widget deleted.'));
			}
			else
				Blocks.cp.displayError(Blocks.t('Couldn’t delete widget.'));
		});
	}
});


})(jQuery);
