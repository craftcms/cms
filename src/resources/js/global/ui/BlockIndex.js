(function($) {


/**
 * Block Index class
 */
Blocks.ui.BlockIndex = Blocks.Base.extend({

	controller: null,
	$table: null,
	totalBlocks: null,
	sorter: null,

	init: function(controller)
	{
		this.controller = controller;

		this.$table = $('#blocks');
		this.totalBlocks = this.$table.children('tbody').children().length;

		this.sorter = new Blocks.ui.DataTableSorter(this.$table, {
			onSortChange: $.proxy(this, 'reorderBlocks')
		});

		var $deleteButtons = this.$table.find('.deletebtn');
		this.addListener($deleteButtons, 'click', 'deleteBlock');
	},

	reorderBlocks: function()
	{
		// Get the new block order
		var blockIds = [];

		for (var i = 0; i < this.sorter.$items.length; i++)
		{
			var blockId = parseInt($(this.sorter.$items[i]).attr('data-block-id'));
			blockIds.push(blockId);
		}

		// Send it to the server
		var data = {
			blockIds: JSON.stringify(blockIds)
		};

		$.post(Blocks.actionUrl+this.controller+'/reorderBlocks', data, $.proxy(function(response) {
			if (response.success)
			{
				Blocks.cp.displayNotice(Blocks.t('New block order saved.'));
			}
			else
			{
				Blocks.cp.displayError(Blocks.t('Couldn’t save new block order.'));
			}
		}, this));
	},

	deleteBlock: function(event)
	{
		var $row = $(event.target).closest('tr'),
			blockName = $row.children(':first').children('a').text();

		if (confirm(Blocks.t('Are you sure you want to delete the block “{block}”?', { block: blockName })))
		{
			var data = {
				blockId: $row.attr('data-block-id')
			};

			$.post(Blocks.actionUrl+this.controller+'/deleteBlock', data, $.proxy(function(response) {
				if (response.success)
				{
					$row.remove();

					this.totalBlocks--;
					if (this.totalBlocks == 0)
					{
						this.$table.remove();
						$('#noblocks').show();
					}

					Blocks.cp.displayNotice(Blocks.t('Block deleted.'));
				}
				else
				{
					Blocks.cp.displayError(Blocks.t('Couldn’t delete block.'));
				}
			}, this));
		}
	}
});


})(jQuery);
