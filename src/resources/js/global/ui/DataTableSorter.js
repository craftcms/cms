(function($) {


/**
 * DataTableSorter
 */
Blocks.ui.DataTableSorter = Blocks.ui.DragSort.extend({

	$table: null,

	init: function(table, settings)
	{
		this.$table = $(table);
		var $rows = this.$table.children('tbody').children(':not(.filler)');

		settings = $.extend({}, Blocks.ui.DataTableSorter.defaults, settings);

		settings.helper = $.proxy(this, 'getHelper');
		settings.axis = 'y';

		this.base($rows, settings);
	},

	getHelper: function($helperRow)
	{
		var $helper = $('<div class="'+this.settings.helperClass+'"/>').appendTo(Blocks.$body),
			$table = $('<table/>').appendTo($helper),
			$tbody = $('<tbody/>').appendTo($table);

		$helperRow.appendTo($tbody);

		// Copy the table width and classes
		$table.width(this.$table.width());
		$table.prop('className', this.$table.prop('className'));

		// Copy the column widths
		var $firstRow = this.$table.find('tr:first'),
			$cells = $firstRow.children(),
			$helperCells = $helperRow.children();

		for (var i = 0; i < $helperCells.length; i++)
		{
			$($helperCells[i]).width($($cells[i]).width());
		}

		return $helper;
	}

}, {
	defaults: {
		handle: '.move',
		helperClass: 'datatablesorthelper'
	}
});


})(jQuery);
