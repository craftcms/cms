/**
 * Thumb View
 */
Assets.ThumbView = Garnish.Base.extend({

	/**
	 * Constructor
	 */
	init: function($container)
	{
		this.$container = $container;
		this.$ul = $('> ul', this.$container);

		this._findItems();
	},

	/**
	 * Find Items
	 */
	_findItems: function(second)
	{
		this.$items = $('> li', this.$ul);
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
		this.$ul.append($add);
		this._findItems()
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
	 * Destroy
	 */
	destroy: function()
	{
		// delete this ThumbView instance
		delete obj;
	},

	/**
	 * Get Container
	 */
	getContainer: function()
	{
		return this.$ul;
	},

	/**
	 * Set Drag Wrapper
	 */
	getDragHelper: function($file)
	{
		return $('<ul class="assets-tv-drag" />').append($file.removeClass('assets-selected'));
	},

	/**
	 * Get Drag Caboose
	 */
	getDragCaboose: function()
	{
		return $('<li class="assets-tv-file assets-tv-dragcaboose" />');
	},

	/**
	 * Get Drag Insertion Placeholder
	 */
	getDragInsertion: function($draggee)
	{
		return $draggee.first().clone().show().css({ 'margin-right': 0, visibility: 'visible' }).addClass('assets-draginsertion');
	}

});
