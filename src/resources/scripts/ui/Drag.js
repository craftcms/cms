(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Drag
 * Used as a base class for DragDrop and DragSort
 */
blx.ui.Drag = Base.extend({

	$items: null,

	mousedownX: null,
	mousedownY: null,
	targetMouseDiffX: null,
	targetMouseDiffY: null,
	mouseX: null,
	mouseY: null,
	lastMouseX: null,
	lastMouseY: null,

	dragging: false,
	target: null,
	$draggee: null,

	helpers: null,
	helperTargets: null,
	helperPositions: null,
	helperLagIncrement: null,
	updateHelperPosInterval: null,

	/**
	 * Constructor
	 */
	constructor: function(items, settings)
	{
		// param mapping
		if (typeof items.nodeType == 'undefined' && typeof items.length == 'undefined')
		{
			// (settings)
			settings = items;
			items = null;
		}

		this.settings = $.extend({}, blx.ui.Drag.defaults, settings);

		this.$items = $();
		if (items) this.addItems(items);
	},

	/**
	 * On Mouse Down
	 */
	onMouseDown: function(event)
	{
		// ignore if we already have a target
		if (this.target) return;

		event.preventDefault();

		// capture the target
		this.target = event.currentTarget;

		// capture the current mouse position
		this.mousedownX = this.mouseX = event.pageX;
		this.mousedownY = this.mouseY = event.pageY;

		// capture the difference between the mouse position and the target item's offset
		var offset = $(this.target).offset();
		this.targetMouseDiffX = event.pageX - offset.left;
		this.targetMouseDiffY = event.pageY - offset.top;

		// listen for mousemove, mouseup
		$(document).on('mousemove.drag', $.proxy(this, 'onMouseMove'));
		$(document).on('mouseup.drag', $.proxy(this, 'onMouseUp'));
	},

	/**
	 * On Moues Move
	 */
	onMouseMove: function(event)
	{
		event.preventDefault();

		if (this.settings.axis != 'y') this.mouseX = event.pageX;
		if (this.settings.axis != 'x') this.mouseY = event.pageY;

		if (!this.dragging)
		{
			// has the mouse moved far enough to initiate dragging yet?
			var mouseDist = blx.utils.getDist(this.mousedownX, this.mousedownY, this.mouseX, this.mouseY);

			if (mouseDist >= blx.ui.Drag.minMouseDist)
				this.startDragging();
			else
				return;
		}

		this.onDrag();
	},

	/**
	 * On Moues Up
	 */
	onMouseUp: function(event)
	{
		// unbind the document events
		$(document).off('.drag');

		if (this.dragging)
			this.stopDragging();

		this.target = null;
	},

	/**
	 * Start Dragging
	 */
	startDragging: function()
	{
		this.dragging = true;

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
			this.$draggee.css('visibility', 'hidden')

		this.lastMouseX = this.lastMouseY = null;

		// keep the helpers following the cursor, with a little lag to smooth it out
		this.helperLagIncrement = this.helpers.length == 1 ? 0 : blx.ui.Drag.helperLagIncrementDividend / (this.helpers.length-1);
		this.updateHelperPosInterval = setInterval($.proxy(this, 'updateHelperPos'), blx.ui.Drag.updateHelperPosInterval);

		this.onDragStart();
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
		};
	},

	/**
	 * Stop Dragging
	 */
	stopDragging: function()
	{
		this.dragging = false;

		// clear the helper interval
		clearInterval(this.updateHelperPosInterval);

		this.onDragStop();
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.settings.onDragStart();
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		this.settings.onDrag();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		this.settings.onDragStop();
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
			};

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
		};
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
		};
	},

	/**
	 * Add Items
	 */
	addItems: function(items)
	{
		var $items = $(items);

		// make a record of it
		this.$items = this.$items.add($items);

		// bind mousedown listener
		$items.on('mousedown.drag', $.proxy(this, 'onMouseDown'));
	},

	/**
	 * Remove Items
	 */
	removeItems: function(items)
	{
		var $items = $(items);

		// unbind all events
		$items.off('.drag');

		// remove the record of it
		this.$items = this.$items.not($items);
	},

	/**
	 * Reset
	 */
	reset: function()
	{
		// unbind the events
		this.$items.off('.drag');

		// reset local vars
		this.$items = $();
	}
},
{
	minMouseDist: 1,
	helperZindex: 1000,
	helperLagBase: 1,
	helperLagIncrementDividend: 1.5,
	updateHelperPosInterval: 20,
	helperSpacingX: 5,
	helperSpacingY: 5,

	defaults: {
		axis: null,
		removeDraggee: false,
		helperOpacity: 1,

		onDragStart: function() {},
		onDrag: function() {},
		onDragStop: function() {}
	}
});


})(jQuery);
