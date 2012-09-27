(function($) {

/**
 * Drag
 * Used as a base class for DragDrop and DragSort
 */
Blocks.ui.Drag = Blocks.ui.BaseDrag.extend({

	$draggee: null,
	helpers: null,
	helperTargets: null,
	helperPositions: null,
	helperLagIncrement: null,
	updateHelperPosInterval: null,

	/**
	 * init
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

		settings = $.extend({}, Blocks.ui.Drag.defaults, settings);
		this.base(items, settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.helpers = [];
		this.helperTargets = [];
		this.helperPositions = [];

		this.getDraggee();
		this.draggeeIndex = $.inArray(this.$draggee[0], this.$items);

		// save their display style (block/table-row) so we can re-apply it later
		this.draggeeDisplay = this.$draggee.css('display');

		this.createHelpers();

		// remove/hide the draggee
		if (this.settings.removeDraggee)
			this.$draggee.hide();
		else
			this.$draggee.css('visibility', 'hidden');

		this.lastMouseX = this.lastMouseY = null;

		// keep the helpers following the cursor, with a little lag to smooth it out
		this.helperLagIncrement = this.helpers.length == 1 ? 0 : Blocks.ui.Drag.helperLagIncrementDividend / (this.helpers.length-1);
		this.updateHelperPosInterval = setInterval($.proxy(this, 'updateHelperPos'), Blocks.ui.Drag.updateHelperPosInterval);

		this.base();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		// clear the helper interval
		clearInterval(this.updateHelperPosInterval);

		this.base();
	},

	/**
	 * Get the draggee(s) based on the filter setting, with the clicked item listed first
	 */
	getDraggee: function()
	{
		switch (typeof this.settings.filter)
		{
			case 'function':
				this.$draggee = this.settings.filter();
				break;

			case 'string':
				this.$draggee = this.$items.filter(this.settings.filter);
				break;

			default:
				this.$draggee = this.$targetItem;
		}

		// put the target item in the front of the list
		this.$draggee = $([ this.$targetItem[0] ].concat(this.$draggee.not(this.$targetItem[0]).toArray()));
	},

	/**
	 * Creates helper clones of the draggee(s)
	 */
	createHelpers: function()
	{
		for (var i = 0; i < this.$draggee.length; i++)
		{
			var $draggee = $(this.$draggee[i]),
				$draggeeHelper = $draggee.clone();

			$draggeeHelper.css({
				width: $draggee.width(),
				height: $draggee.height(),
				margin: 0
			});

			if (typeof this.settings.helper == 'function')
				$draggeeHelper = this.settings.helper($draggeeHelper);
			else if (this.settings.helper)
				$draggeeHelper = $(this.settings.helper).append($draggeeHelper);

			$draggeeHelper.appendTo(document.body);

			var helperPos = this.getHelperTarget(i);

			$draggeeHelper.css({
				position: 'absolute',
				top: helperPos.top,
				left: helperPos.left,
				zIndex: Blocks.ui.Drag.helperZindex, // + this.$draggee.length - i,
				opacity: this.settings.helperOpacity
			});

			this.helperPositions[i] = {
				top:  helperPos.top,
				left: helperPos.left
			};

			this.helpers.push($draggeeHelper);
		}
	},

	/**
	 * Get the helper position for a draggee helper
	 */
	getHelperTarget: function(i)
	{
		return {
			left: this.mouseX - this.targetItemMouseDiffX + (i * Blocks.ui.Drag.helperSpacingX),
			top:  this.mouseY - this.targetItemMouseDiffY + (i * Blocks.ui.Drag.helperSpacingY)
		};
	},

	/**
	 * Update Helper Position
	 */
	updateHelperPos: function()
	{
		// has the mouse moved?
		if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY)
		{
			// get the new target helper positions
			for (var i = 0; i < this.helpers.length; i++)
			{
				this.helperTargets[i] = this.getHelperTarget(i);
			}

			this.lastMouseX = this.mouseX;
			this.lastMouseY = this.mouseY;
		}

		// gravitate helpers toward their target positions
		for (var j = 0; j < this.helpers.length; j++)
		{
			var lag = Blocks.ui.Drag.helperLagBase + (this.helperLagIncrement * j);

			this.helperPositions[j] = {
				left: this.helperPositions[j].left + ((this.helperTargets[j].left - this.helperPositions[j].left) / lag),
				top:  this.helperPositions[j].top  + ((this.helperTargets[j].top  - this.helperPositions[j].top) / lag)
			};

			this.helpers[j].css(this.helperPositions[j]);
		}
	},

	/**
	 * Return Helpers to Draggee(s)
	 */
	returnHelpersToDraggee: function()
	{
		for (var i = 0; i < this.$draggee.length; i++)
		{
			var $draggee = $(this.$draggee[i]),
				$draggeeHelper = this.helpers[i],
				draggeeOffset = $draggee.offset();

			// preserve $draggee and $draggeeHelper for the end of the animation
			(function($draggee, $draggeeHelper) {
				$draggeeHelper.animate({
					left: draggeeOffset.left,
					top: draggeeOffset.top
				}, 'fast', function() {
					$draggee.css('visibility', 'visible');
					$draggeeHelper.remove();
				});
			})($draggee, $draggeeHelper);
		}
	}
},
{
	helperZindex: 1000,
	helperLagBase: 1,
	helperLagIncrementDividend: 1.5,
	updateHelperPosInterval: 20,
	helperSpacingX: 5,
	helperSpacingY: 5,

	defaults: {
		removeDraggee: false,
		helperOpacity: 1,
		helper: null
	}
});

})(jQuery);
