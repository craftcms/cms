(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Drag
 * Used as a base class for DragDrop and DragSort
 */
blx.ui.Drag = blx.ui.DragCore.extend({

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
		// param mapping
		if (typeof items.nodeType == 'undefined' && typeof items.length == 'undefined')
		{
			// (settings)
			settings = items;
			items = null;
		}

		settings = $.extend({}, blx.ui.Drag.defaults, settings);
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
		this.helperLagIncrement = this.helpers.length == 1 ? 0 : blx.ui.Drag.helperLagIncrementDividend / (this.helpers.length-1);
		this.updateHelperPosInterval = setInterval($.proxy(this, 'updateHelperPos'), blx.ui.Drag.updateHelperPosInterval);

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
				this.$draggee = $(this.target);
		}

		// put the target item in the front of the list
		this.$draggee = $([ this.target ].concat(this.$draggee.not(this.target).toArray()));
	},

	/**
	 * Creates helper clones of the draggee(s)
	 */
	createHelpers: function()
	{
		for (var i = 0; i < this.$draggee.length; i++)
		{
			var $draggee = $(this.$draggee[i]),
				draggeeOffset = $draggee.offset(),
				$draggeeHelper = $draggee.clone();

			$draggeeHelper.css({
				width: $draggee.width(),
				height: $draggee.height()
			});

			if (typeof this.settings.helper == 'function')
				$draggeeHelper = this.settings.helper($draggeeHelper);
			else if (this.settings.helper)
				$draggeeHelper = $(this.settings.helper).append($draggeeHelper);

			$draggeeHelper.appendTo(document.body);

			$draggeeHelper.css({
				position: 'absolute',
				top: draggeeOffset.top,
				left: draggeeOffset.left,
				zIndex: blx.ui.Drag.helperZindex, // + this.$draggee.length - i,
				opacity: this.settings.helperOpacity
			});

			this.helperPositions[i] = {
				top:  draggeeOffset.top,
				left: draggeeOffset.left
			};

			this.helpers.push($draggeeHelper);
		}
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
				this.helperTargets[i] = {
					left: this.mouseX - this.targetMouseDiffX + (i * blx.ui.Drag.helperSpacingX),
					top:  this.mouseY - this.targetMouseDiffY + (i * blx.ui.Drag.helperSpacingY)
				};
			}

			this.lastMouseX = this.mouseX;
			this.lastMouseY = this.mouseY;
		}

		// gravitate helpers toward their target positions
		for (var i = 0; i < this.helpers.length; i++)
		{
			var lag = blx.ui.Drag.helperLagBase + (this.helperLagIncrement * i);

			this.helperPositions[i] = {
				left: this.helperPositions[i].left + ((this.helperTargets[i].left - this.helperPositions[i].left) / lag),
				top:  this.helperPositions[i].top  + ((this.helperTargets[i].top  - this.helperPositions[i].top) / lag)
			};

			this.helpers[i].css(this.helperPositions[i]);
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
		helperOpacity: 1
	}
});


})(jQuery);
