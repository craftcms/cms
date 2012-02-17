(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * DragSort
 */
blx.ui.DragSort = blx.ui.Drag.extend({

	$insertion: null,
	midpoints: null,
	closestItemIndex: null,

	/**
	 * Constructor
	 */
	init: function(items, settings)
	{
		// param mapping
		if (typeof items.nodeType == 'undefined' && typeof items.length == 'undefined')
		{
			// (settings)
			settings = items;
			items = null;
		}

		settings = $.extend({}, blx.ui.DragSort.defaults, settings);
		this.base(items, settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.getInsertion();
		this.getMidpoints();

		this.closestItem = -1;
		this.draggeeStartIndex = this.draggeeIndex;

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
		}
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
		}

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

		var draggee = this.$draggee[0],
			goingDown = (this.closestItemIndex > this.draggeeIndex);

		// Reposition the draggee in the $items array
		this.$items.splice(this.draggeeIndex, 1);
		this.$items.splice(this.closestItemIndex, 0, draggee);

		// Update the draggee index
		this.draggeeIndex = this.closestItemIndex;

		// Going down?
		if (goingDown)
		{
			this.closestItemIndex--;
			this.$draggee.insertAfter(this.$closestItem);
		}
		// Going up?
		else
		{
			this.closestItemIndex++;
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
		if (this.$insertion)
			this.$insertion.remove();

		// "show" the drag items, but make them invisible
		this.$draggee.css({
			display:    this.draggeeDisplay,
			visibility: 'hidden'
		});

		// return the helpers to the draggees
		this.returnHelpersToDraggees();

		this.base();

		// has the item actually moved?
		if (this.startDraggeeIndex != this.draggeeIndex)
		{
			this.settings.onSortChange();
		}
	},

	/**
	 * Return Helpers to Draggees
	 */
	returnHelpersToDraggees: function()
	{
		for (var i = 0; i < this.$draggee.length; i++)
		{
			var $draggee = $(this.$draggee[i]),
				$helper = this.helpers[i],
				draggeeOffset = $draggee.offset();

			// preserve $draggee and $helper for the end of the animation
			(
				function($draggee, $helper)
				{
					$helper.animate({left: draggeeOffset.left, top: draggeeOffset.top}, 'fast',
						function()
						{
							$draggee.css('visibility', 'visible');
							$helper.remove();
						}
					);
				}
			)($draggee, $helper);
		}
	}
},
{
	defaults: {
		container: null,
		insertion: null,
		onInsertionPointChange: function() {},
		onSortChange: function() {}
	}
});


})(jQuery);
