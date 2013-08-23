/**
 * Admin table class
 */
Craft.AdminTable = Garnish.Base.extend({

	settings: null,
	totalObjects: null,
	sorter: null,

	$noObjects: null,
	$table: null,
	$tbody: null,
	$deleteBtns: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.AdminTable.defaults);

		this.$noObjects = $(this.settings.noObjectsSelector);
		this.$table = $(this.settings.tableSelector);
		this.$tbody  = this.$table.children('tbody');
		this.totalObjects = this.$tbody.children().length;

		if (this.settings.sortable)
		{
			this.sorter = new Craft.DataTableSorter(this.$table, {
				onSortChange: $.proxy(this, 'reorderObjects')
			});
		}

		this.$deleteBtns = this.$table.find('.delete');
		this.addListener(this.$deleteBtns, 'click', 'deleteObject');

		this.onDeleteObject();
	},

	addRow: function(row)
	{
		var $row = $(row).appendTo(this.$tbody),
			$deleteBtn = $row.find('.delete');

		if (this.settings.sortable)
		{
			this.sorter.addItems($row);
		}

		this.addListener($deleteBtn, 'click', 'deleteObject');
		this.totalObjects++;

		if (this.totalObjects == 1)
		{
			this.$noObjects.addClass('hidden');
			this.$table.show();
		}
		else if (this.totalObjects == 2)
		{
			if (this.settings.sortable)
			{
				this.$table.find('.move').removeClass('disabled');
			}

			if (!this.settings.allowDeleteAll)
			{
				this.$deleteBtns.removeClass('disabled');
			}
		}

		this.$deleteBtns = this.$deleteBtns.add($deleteBtn);
	},

	reorderObjects: function()
	{
		if (!this.settings.sortable)
		{
			return false;
		}

		// Get the new field order
		var ids = [];

		for (var i = 0; i < this.sorter.$items.length; i++)
		{
			var id = $(this.sorter.$items[i]).attr(this.settings.idAttribute);
			ids.push(id);
		}

		// Send it to the server
		var data = {
			ids: JSON.stringify(ids)
		};

		Craft.postActionRequest(this.settings.reorderAction, data, $.proxy(function(response, textStatus) {

			if (textStatus == 'success')
			{
				if (response.success)
				{
					Craft.cp.displayNotice(Craft.t(this.settings.reorderSuccessMessage));
				}
				else
				{
					Craft.cp.displayError(Craft.t(this.settings.reorderFailMessage));
				}
			}

		}, this));
	},

	deleteObject: function(event)
	{
		if (!this.settings.allowDeleteAll && this.totalObjects == 1)
		{
			return;
		}

		var $row = $(event.target).closest('tr'),
			id = $row.attr(this.settings.idAttribute),
			name = $row.attr(this.settings.nameAttribute);

		if (this.confirmDeleteObject($row))
		{
			Craft.postActionRequest(this.settings.deleteAction, { id: id }, $.proxy(function(response, textStatus) {

				if (textStatus == 'success')
				{
					if (response.success)
					{
						$row.remove();
						this.totalObjects--;
						this.onDeleteObject();
						this.settings.onDeleteObject(id);

						Craft.cp.displayNotice(Craft.t(this.settings.deleteSuccessMessage, { name: name }));
					}
					else
					{
						Craft.cp.displayError(Craft.t(this.settings.deleteFailMessage, { name: name }));
					}
				}

			}, this));
		}
	},

	confirmDeleteObject: function($row)
	{
		var name = $row.attr(this.settings.nameAttribute);
		return confirm(Craft.t(this.settings.confirmDeleteMessage, { name: name }));
	},

	onDeleteObject: function()
	{
		if (this.totalObjects == 1)
		{
			this.$table.find('.move').addClass('disabled');

			if (!this.settings.allowDeleteAll)
			{
				this.$deleteBtns.addClass('disabled');
			}
		}
		else if (this.totalObjects == 0)
		{
			this.$table.hide();
			$(this.settings.noObjectsSelector).removeClass('hidden');
		}
	}
},
{
	defaults: {
		tableSelector: null,
		noObjectsSelector: null,
		idAttribute: 'data-id',
		nameAttribute: 'data-name',
		sortable: false,
		allowDeleteAll: true,
		reorderAction: null,
		deleteAction: null,
		reorderSuccessMessage: 'New order saved.',
		reorderFailMessage: 'Couldn’t save new order.',
		confirmDeleteMessage: 'Are you sure you want to delete “{name}”?',
		deleteSuccessMessage: '“{name}” deleted.',
		deleteFailMessage: 'Couldn’t delete “{name}”.',
		onDeleteObject: $.noop
	}
});
