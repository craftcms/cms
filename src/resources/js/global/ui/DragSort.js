(function($) {

/**
 * DragSort
 */
Blocks.ui.DragSort = Blocks.ui.Drag.extend({

	$insertion: null,
	startDraggeeIndex: null,
	closestItemIndex: null,

	/**
	 * Constructor
	 */
	init: function(items, settings)
	{
		// Param mapping
		if (!settings && Blocks.isObject(items))
		{
			// (settings)
			settings = items;
			items = null;
		}

		settings = $.extend({}, Blocks.ui.DragSort.defaults, settings);
		this.base(items, settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.setInsertion();
		this.setMidpoints();

		this.closestItem = -1;

		this.base();

		this.startDraggeeIndex = this.draggeeIndex;
	},

	/**
	 * Sets the insertion element
	 */
	setInsertion: function()
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
	 * Sets the item midpoints up front so we don't have to keep checking on every mouse move
	 */
	setMidpoints: function()
	{
		for (var i = 0; i < this.$items.length; i++)
		{
			var $item = $(this.$items[i]),
				offset = $item.offset();

			$item.data('midpoint', {
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
		if (this.settings.container && !Blocks.hitTest(this.mouseX, this.mouseY, $(this.settings.container)))
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
			var $item = $(this.$items[i]),
				midpoint = $item.data('midpoint'),
				mouseDiff = Blocks.getDist(midpoint.left, midpoint.top, this.mouseX, this.mouseY);

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
		// Is the closest item one of the draggees?
		if (this.closestItemIndex >= this.draggeeIndex && this.closestItemIndex < this.draggeeIndex + this.$draggee.length)
		{
			return;
		}

		// Going down?
		if (this.closestItemIndex > this.draggeeIndex)
		{
			this.$draggee.insertAfter(this.$closestItem);
		}
		// Going up?
		else
		{
			this.$draggee.insertBefore(this.$closestItem);
		}

		// Update the $items order and the indexes
		this.$items = $().add(this.$items);
		this.$items = $().add(this.$items);
		this.draggeeIndex = $.inArray(this.$draggee[0], this.$items);
		this.closestItemIndex = $.inArray(this.$closestItem[0], this.$items);
		this.setMidpoints();

		if (this.$insertion)
		{
			this.$insertion.insertBefore(this.$draggee);
		}

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
