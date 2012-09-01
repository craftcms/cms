(function($) {


var $table = $('#blocks'),
	totalBlocks = $table.children('thead').children().length;


var sorter = new blx.ui.DataTableSorter($table, {
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
			sectionId: sectionId,
			blockIds: JSON.stringify(blockIds)
		};

		$.post(blx.actionUrl+'content/updateSectionBlockOrder', data, function(response) {
			if (response.success)
				blx.displayNotice(blx.t('New block order saved.'));
			else
				blx.displayError(blx.t('Couldn’t save new block order.'));
		});
	}
});


$table.find('.deletebtn').click(function() {
	var $row = $(this).closest('tr'),
		blockName = $row.children(':first').children('a').text();

	if (confirm(blx.t('Are you sure you want to delete the content block “{block}”?', { block: blockName })))
	{
		var data = {
			sectionId: sectionId,
			blockId: $row.attr('data-block-id')
		};

		$.post(blx.actionUrl+'content/deleteSectionBlock', data, function(response) {
			if (response.success)
			{
				$row.remove();

				totalBlocks--;
				if (totalBlocks == 0)
				{
					$table.remove();
					$('#noblocks').show();
				}

				blx.displayNotice(blx.t('Content block deleted.'));
			}
			else
				blx.displayError(blx.t('Couldn’t delete content block.'));
		});
	}
});


})(jQuery);
