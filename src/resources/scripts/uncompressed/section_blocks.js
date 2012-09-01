(function($) {


var sorter = new blx.ui.DataTableSorter('#blocks', {
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


})(jQuery);
