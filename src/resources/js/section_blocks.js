(function($) {


var $table = $('#blocks'),
	totalBlocks = $table.children('tbody').children().length;


var sorter = new Blocks.ui.DataTableSorter($table, {
	onSortChange: function() {

		// Get the new block order
		var blockIds = [];

		for (var i = 0; i < sorter.$items.length; i++)
		{
			var blockId = parseInt($(sorter.$items[i]).attr('data-block-id'));
			blockIds.push(blockId);
		}

		// Send it to the server
		var data = {
			blockIds: JSON.stringify(blockIds)
		};

		$.post(Blocks.actionUrl+'content/reorderEntryBlocks', data, function(response) {
			if (response.success)
				Blocks.cp.displayNotice(Blocks.t('New block order saved.'));
			else
				Blocks.cp.displayError(Blocks.t('Couldn’t save new block order.'));
		});
	}
});


$table.find('.deletebtn').click(function() {
	var $row = $(this).closest('tr'),
		blockName = $row.children(':first').children('a').text();

	if (confirm(Blocks.t('Are you sure you want to delete the entry block “{block}”?', { block: blockName })))
	{
		var data = {
			blockId: $row.attr('data-block-id')
		};

		$.post(Blocks.actionUrl+'content/deleteEntryBlock', data, function(response) {
			if (response.success)
			{
				$row.remove();

				totalBlocks--;
				if (totalBlocks == 0)
				{
					$table.remove();
					$('#noblocks').show();
				}

				Blocks.cp.displayNotice(Blocks.t('Entry block deleted.'));
			}
			else
				Blocks.cp.displayError(Blocks.t('Couldn’t delete entry block.'));
		});
	}
});


})(jQuery);
