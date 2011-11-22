(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * DragSort
 */
blx.ui.DragSort = blx.ui.Drag.extend({

	$insertion: null,
	caboose: null,
	midpoints: null,
	closestItemIndex: null,

	/**
	 * Constructor
	 */
	constructor: function(settings)
	{
		settings = $.extend({}, blx.ui.DragSort.defaults, settings);
		this.base(settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.getInsertion();
		this.addCaboose();
		this.getMidpoints();

		this.closestItem = -1;

		this.base();
	},

	/**
	 * Get the insertion element
	 */
	getInsertion: function()
	{
		// get the insertion
		if (this.settings.insertion)
		{
			if (typeof this.settings.insertion == 'function')
				this.$insertion = $(this.settings.insertion());
			else
				this.$insertion = $(this.settings.insertion);
		}
	},

	/**
	 * Adds the caboose to the end of the items
	 */
	addCaboose: function()
	{
		// add the caboose
		if (this.settings.caboose)
		{
			if (typeof this.settings.caboose == 'function')
				this.$caboose = $(this.settings.caboose());
			else
				this.$caboose = $(this.settings.caboose);

			this.$caboose.insertAfter(this.$items[this.$items.length-1]);
			this.otherItems.push(this.$caboose);
		}
	},

	/**
	 * Get the item midpoints up front so we don't have to keep checking on every mouse move
	 */
	getMidpoints: function()
	{
		this.midpoints = [];

		for (var i = 0; i < this.$items.length; i++)
		{
			var $item = $(this.$items[i]),
				offset = $item.offset();

			this.midpoints.push({
				left: offset.left + $item.width() / 2,
				top:  offset.top + $item.height() / 2
			});
		};
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		// if there's a container set, make sure that we're hovering over it
		if (this.settings.container && !blx.utils.hitTest(this.mouseX, this.mouseY, $(this.settings.container)))
		{
			if (this.closestItemIndex != -1)
			{
				this.$insertion.remove();
				this.closestItemIndex = -1;
			}
		}
		else
		{
			// is there a new closest item?
			if (this.closestItemIndex !== (this.closestItemIndex = this.getClosestItemIndex()))
			{
				this.$closestItem = $(this.$items[this.closestItemIndex]);
				this.onInsertionPointChange();
			}
		}

		this.base();
	},

	/**
	 * Returns the index of the closest item to the cursor
	 */
	getClosestItemIndex: function()
	{
		var closestItemIndex = -1,
			closestItemMouseDiff;

		for (var i = 0; i < this.$items.length; i++)
		{
			var mouseDiff = blx.utils.getDist(this.midpoints[i].left, this.midpoints[i].top, this.mouseX, this.mouseY);

			if (closestItemIndex == -1 || mouseDiff < closestItemMouseDiff)
			{
				closestItemIndex = i;
				closestItemMouseDiff = mouseDiff;
			}
		};

		return closestItemIndex;
	},

	/**
	 * On Insertion Point Change
	 */
	onInsertionPointChange: function()
	{
		// is this just the draggee?
		if (this.closestItemIndex == this.draggeeIndex)
			return;

		// going right?
		if (this.closestItemIndex > this.draggeeIndex)
		{
			// reposition the draggee in the $items array
			this.$items.splice(this.draggeeIndex, 1);
			this.$items.splice(this.closestItemIndex, 0, this.$draggee[0]);

			// update the indexes
			this.draggeeIndex = this.closestItemIndex;
			this.closestItemIndex--;

			// put the draggee in place
			this.$draggee.insertAfter(this.$closestItem);
		}
		// going left
		else
		{
			// reposition the draggee in teh $items array
			this.$items.splice(this.draggeeIndex, 1);
			this.$items.splice(this.closestItemIndex, 0, this.$draggee[0])

			// update the indexes
			this.draggeeIndex = this.closestItemIndex;
			this.closestItemIndex++;

			// put the draggee in place
			this.$draggee.insertBefore(this.$closestItem);
		}

		if (this.$insertion)
			this.$insertion.insertBefore(this.$draggee);

		this.settings.onInsertionPointChange();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
			return;

		if (this.closestItemIndex != -1)
		{
			this.$draggee.insertBefore(this.closestItemIndex);

			if (this.$insertion)
				this.$insertion.remove();

			this.settings.onSortChange();
		}

		// "show" the drag items, but make them invisible
		this.$draggee.css({
			display:    this.draggeeDisplay,
			visibility: 'hidden'
		});

		// return the helpers to the draggees
		this.returnHelpersToDraggees();

		// hide the caboose
		if (this.$caboose)
			this.$caboose.remove();

		this.base();
	},
},
{
	defaults: {
		container: null,
		insertion: null,
		caboose: null,
		onInsertionPointChange: function() {},
		onSortChange: function() {}
	}
});


})(jQuery);
