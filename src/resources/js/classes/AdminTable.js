/**
 * Admin table class
 */
Craft.AdminTable = Garnish.Base.extend(
{
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

		if (!this.settings.allowDeleteAll)
		{
			this.settings.minObjects = 1;
		}

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
		this.addListener(this.$deleteBtns, 'click', 'handleDeleteBtnClick');

		this.updateUI();
	},

	addRow: function(row)
	{
		if (this.settings.maxObjects && this.totalObjects >= this.settings.maxObjects)
		{
			// Sorry pal.
			return;
		}

		var $row = $(row).appendTo(this.$tbody),
			$deleteBtn = $row.find('.delete');

		if (this.settings.sortable)
		{
			this.sorter.addItems($row);
		}

		this.$deleteBtns = this.$deleteBtns.add($deleteBtn);

		this.addListener($deleteBtn, 'click', 'handleDeleteBtnClick');
		this.totalObjects++;

		this.updateUI();
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

		Craft.postActionRequest(this.settings.reorderAction, data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.onReorderObjects(ids);
					Craft.cp.displayNotice(Craft.t(this.settings.reorderSuccessMessage));
				}
				else
				{
					Craft.cp.displayError(Craft.t(this.settings.reorderFailMessage));
				}
			}

		}, this));
	},

	handleDeleteBtnClick: function(event)
	{
		if (this.settings.minObjects && this.totalObjects <= this.settings.minObjects)
		{
			// Sorry pal.
			return;
		}

		var $row = $(event.target).closest('tr');

		if (this.confirmDeleteObject($row))
		{
			this.deleteObject($row);
		}
	},

	confirmDeleteObject: function($row)
	{
		var name = this.getObjectName($row);
		return confirm(Craft.t(this.settings.confirmDeleteMessage, { name: name }));
	},

	deleteObject: function($row)
	{
		var data = {
			id: this.getObjectId($row)
		};

		Craft.postActionRequest(this.settings.deleteAction, data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				this.handleDeleteObjectResponse(response, $row);
			}
		}, this));
	},

	handleDeleteObjectResponse: function(response, $row)
	{
		var id = this.getObjectId($row),
			name = this.getObjectName($row);

		if (response.success)
		{
			if (this.sorter)
			{
				this.sorter.removeItems($row);
			}

			$row.remove();
			this.totalObjects--;
			this.updateUI();
			this.onDeleteObject(id);

			Craft.cp.displayNotice(Craft.t(this.settings.deleteSuccessMessage, { name: name }));
		}
		else
		{
			Craft.cp.displayError(Craft.t(this.settings.deleteFailMessage, { name: name }));
		}
	},

	onReorderObjects: function(ids)
	{
		this.settings.onReorderObjects(ids);
	},

	onDeleteObject: function(id)
	{
		this.settings.onDeleteObject(id);
	},

	getObjectId: function($row)
	{
		return $row.attr(this.settings.idAttribute);
	},

	getObjectName: function($row)
	{
		return $row.attr(this.settings.nameAttribute);
	},

	updateUI: function()
	{
		// Show the "No Whatever Exists" message if there aren't any
		if (this.totalObjects == 0)
		{
			this.$table.hide();
			this.$noObjects.removeClass('hidden');
		}
		else
		{
			this.$table.show();
			this.$noObjects.addClass('hidden');
		}

		// Disable the sort buttons if there's only one row
		if (this.settings.sortable)
		{
			var $moveButtons = this.$table.find('.move');

			if (this.totalObjects == 1)
			{
				$moveButtons.addClass('disabled');
			}
			else
			{
				$moveButtons.removeClass('disabled');
			}
		}

		// Disable the delete buttons if we've reached the minimum objects
		if (this.settings.minObjects && this.totalObjects <= this.settings.minObjects)
		{
			this.$deleteBtns.addClass('disabled');
		}
		else
		{
			this.$deleteBtns.removeClass('disabled');
		}

		// Hide the New Whatever button if we've reached the maximum objects
		if (this.settings.newObjectBtnSelector)
		{
			if (this.settings.maxObjects && this.totalObjects >= this.settings.maxObjects)
			{
				$(this.settings.newObjectBtnSelector).addClass('hidden');
			}
			else
			{
				$(this.settings.newObjectBtnSelector).removeClass('hidden');
			}
		}
	}
},
{
	defaults: {
		tableSelector: null,
		noObjectsSelector: null,
		newObjectBtnSelector: null,
		idAttribute: 'data-id',
		nameAttribute: 'data-name',
		sortable: false,
		allowDeleteAll: true,
		minObjects: 0,
		maxObjects: null,
		reorderAction: null,
		deleteAction: null,
		reorderSuccessMessage: Craft.t('New order saved.'),
		reorderFailMessage:    Craft.t('Couldn’t save new order.'),
		confirmDeleteMessage:  Craft.t('Are you sure you want to delete “{name}”?'),
		deleteSuccessMessage:  Craft.t('“{name}” deleted.'),
		deleteFailMessage:     Craft.t('Couldn’t delete “{name}”.'),
		onReorderObjects: $.noop,
		onDeleteObject: $.noop
	}
});
