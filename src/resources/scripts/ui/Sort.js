(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Sort
 */
blx.ui.Sort = blx.ui.Drag.extend({

	/**
	 * Constructor
	 */
	constructor: function(container, settings)
	{
		this.$container = $(container);
		this.settings = $.extend({}, blx.ui.Sort.defaults, settings);

		this.otherItemMidpoints;
		this.closestItem;

		// -------------------------------------------
		//  Get the closest container that has a height
		// -------------------------------------------

		while (!this.$container.height())
		{
			this.$container = this.$container.parent();
		}


		this.base(this.settings);
	},

	/**
	 * On Drag Start
	 */
	startDragging: function()
	{
		this.base();

		// get the insertion
		if (this.settings.insertion)
		{
			if (typeof this.settings.insertion == 'function')
				this.$insertion = $(this.settings.insertion());
			else
				this.$insertion = $(this.settings.insertion);
		}

		this.closestItem = null;

		// add the caboose
		if (this.settings.caboose)
		{
			if (!this.$caboose)
			{
				if (typeof this.settings.caboose == 'function')
					this.$caboose = $(this.settings.caboose());
				else
					this.$caboose = $(this.settings.caboose);

				this.$caboose.insertAfter(this.$items[this.$items.length-1]);
				this.otherItems.push(this.$caboose);
			}

			this.$caboose.show();
		}

		// find the midpoints of the other items
		this.otherItemMidpoints = [];

		for (var i = 0; i < this.otherItems.length; i++)
		{
			var $item = $(this.otherItems[i]),
				offset = $item.offset();

			this.otherItemMidpoints.push({
				left: offset.left + $item.width() / 2,
				top:  offset.top + $item.height() / 2
			});
		};
	},

	/**
	 * Drag
	 */
	drag: function(event)
	{
		this.base();

		// -------------------------------------------
		//  Find the closest item
		// -------------------------------------------

		if (this.$insertion)
		{
			if (blx.utils.hitTest(this.mouseX, this.mouseY, this.$container))
			{
				// are there any other items?
				var _closestItem,
					_closestItemMouseDiff;

				for (var i = 0; i < this.otherItems.length; i++)
				{
					var mouseDiff = blx.utils.getDist(this.otherItemMidpoints[i].left, this.otherItemMidpoints[i].top, this.mouseX, this.mouseY);

					if (!_closestItem || mouseDiff < _closestItemMouseDiff)
					{
						_closestItem = this.otherItems[i],
						_closestItemMouseDiff = mouseDiff;
					}
				};

				// new closest item?
				if (_closestItem != this.closestItem)
				{
					this.closestItem = _closestItem;
					this.$insertion.insertBefore(this.closestItem);
				}
			}
			else
			{
				this.$insertion.remove();
				this.closestItem = null;
			}
		}
	},

	/**
	 * Stop Dragging
	 */
	stopDragging: function()
	{
		this.base();
			return;

		if (this.closestItem)
		{
			this.$draggees.insertBefore(this.closestItem);

			if (this.$insertion)
				this.$insertion.remove();

			this.onSortChange();
		}

		// "show" the drag items, but make them invisible
		this.$draggees.css({
			display:    this.draggeeDisplay,
			visibility: 'hidden'
		});

		// return the helpers to the draggees
		this.returnHelpersToDraggees();

		// hide the caboose
		if (this.$caboose)
			this.$caboose.hide();
	},

	/**
	 * On Sort Change
	 */
	onSortChange: function() { }
},{
	default: {}
});


})(jQuery);
