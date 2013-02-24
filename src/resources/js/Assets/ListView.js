/**
 * List View
 */
Assets.ListView = Garnish.Base.extend({

	/**
	 * Constructor
	 */
	init: function($container, settings)
	{
		this.$container = $container;
		this.settings = (settings || {});

		this.$table = $('> table', this.$container);
		this.$ths = $('> thead > tr > th', this.$table);
		this.$tbody = $('> tbody', this.$table);
		this.$tds = $('> tbody > tr:first > td', this.$table);
		this.$items;

		this.scrollbarWidth;
		this.scrollLeft = 0;

		this.orderby = this.settings.orderby;
		this.sort = this.settings.sort;

		this._findItems();

		// -------------------------------------------
		//  Column Sorting
		// -------------------------------------------

		if (typeof this.settings.onSortChange == 'function')
		{
			this.addListener(this.$ths, 'click', function(event)
			{
				var orderby = $(event.currentTarget).attr('data-orderby');

				if (orderby != this.orderby)
				{
					// ordering by something new
					this.orderby = orderby;
					this.sort = 'asc';
				}
				else
				{
					// just reverse the sort
					this.sort = (this.sort == 'asc' ? 'desc' : 'asc');
				}

				this.settings.onSortChange(this.orderby, this.sort);
			});
		}
	},

	/**
	 * Find Items
	 */
	_findItems: function(second)
	{
		this.$items = $('> tr', this.$tbody);
	},

	// -------------------------------------------
	//  Public methods
	// -------------------------------------------

	/**
	 * Get Items
	 */
	getItems: function()
	{
		return this.$items;
	},

	/**
	 * Add Items
	 */
	addItems: function($add)
	{
		this.$tbody.append($add);
		this._findItems();
	},

	/**
	 * Remove Items
	 */
	removeItems: function($remove)
	{
		$remove.remove();
		this._findItems();
	},

	/**
	 * Reset Items
	 */
	reset: function()
	{
		this._findItems();
	},

	/**
	 * Get Container
	 */
	getContainer: function()
	{
		return this.$tbody;
	},

	/**
	 * Set Drag Wrapper
	 */
	getDragHelper: function($file)
	{
		var $container = $('<div class="assets-listview assets-lv-drag" />'),
			$table = $('<table cellpadding="0" cellspacing="0" border="0" />').appendTo($container),
			$tbody = $('<tbody />').appendTo($table);

		$table.width(this.$table.width());
		$tbody.append($file);

		return $container;
	},

	/**
	 * Get Drag Caboose
	 */
	getDragCaboose: function()
	{
		return $('<tr class="assets-lv-file assets-lv-dragcaboose" />');
	},

	/**
	 * Get Drag Insertion Placeholder
	 */
	getDragInsertion: function()
	{
		return $('<tr class="assts-lv-file assets-lv-draginsertion"><td colspan="'+this.$ths.length+'">&nbsp;</td></tr>');
	}
});
