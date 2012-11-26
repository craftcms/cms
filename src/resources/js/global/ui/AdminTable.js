(function($) {


/**
 * Admin table class
 */
Blocks.ui.AdminTable = Blocks.Base.extend({

	settings: null,
	$table: null,
	totalObjects: null,
	sorter: null,

	init: function(settings)
	{
		this.setSettings(settings, Blocks.ui.AdminTable.defaults);

		this.$table = $(this.settings.tableSelector);
		this.totalObjects = this.$table.children('tbody').children().length;

		if (this.settings.sortable)
		{
			this.sorter = new Blocks.ui.DataTableSorter(this.$table, {
				onSortChange: $.proxy(this, 'reorderObjects')
			});
		}

		var $deleteButtons = this.$table.find('.delete');
		this.addListener($deleteButtons, 'click', 'deleteObject');
	},

	reorderObjects: function()
	{
		if (!this.settings.sortable)
			return false;

		// Get the new block order
		var ids = [];

		for (var i = 0; i < this.sorter.$items.length; i++)
		{
			var id = parseInt($(this.sorter.$items[i]).attr(this.settings.idAttribute));
			ids.push(id);
		}

		// Send it to the server
		var data = {
			ids: JSON.stringify(ids)
		};

		Blocks.postActionRequest(this.settings.reorderAction, data, $.proxy(function(response)
		{
			if (response.success)
			{
				Blocks.cp.displayNotice(Blocks.t(this.settings.reorderSuccessMessage));
			}
			else
			{
				Blocks.cp.displayError(Blocks.t(this.settings.reorderFailMessage));
			}
		}, this));
	},

	deleteObject: function(event)
	{
		var $row = $(event.target).closest('tr'),
			id = $row.attr(this.settings.idAttribute),
			name = $row.attr(this.settings.nameAttribute);

		if (this.confirmDeleteObject($row))
		{
			Blocks.postActionRequest(this.settings.deleteAction, { id: id }, $.proxy(function(response) {
				if (response.success)
				{
					$row.remove();

					this.totalObjects--;
					if (this.totalObjects == 0)
					{
						this.$table.remove();
						$(this.settings.noObjectsSelector).removeClass('hidden');
					}

					Blocks.cp.displayNotice(Blocks.t(this.settings.deleteSuccessMessage, { name: name }));
				}
				else
				{
					Blocks.cp.displayError(Blocks.t(this.settings.deleteFailMessage, { name: name }));
				}
			}, this));
		}
	},

	confirmDeleteObject: function($row)
	{
		var name = $row.attr(this.settings.nameAttribute);
		return confirm(Blocks.t(this.settings.confirmDeleteMessage, { name: name }));
	}
}, {
	defaults: {
		tableSelector: null,
		noObjectsSelector: null,
		idAttribute: 'data-id',
		nameAttribute: 'data-name',
		sortable: false,
		reorderAction: null,
		deleteAction: null,
		reorderSuccessMessage: 'New order saved.',
		reorderFailMessage: 'Couldn’t save new order.',
		confirmDeleteMessage: 'Are you sure you want to delete “{name}”?',
		deleteSuccessMessage: '“{name}” deleted.',
		deleteFailMessage: 'Couldn’t delete “{name}”.'
	}
});


})(jQuery);
