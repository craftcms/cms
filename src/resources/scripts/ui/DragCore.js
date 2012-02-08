(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Drag Core
 */
blx.ui.DragCore = blx.Base.extend({

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

	/**
	 * Init
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

		this.settings = $.extend({}, blx.ui.DragCore.defaults, settings);

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
		this.addListener(blx.$document, 'mousemove', 'onMouseMove');
		this.addListener(blx.$document, 'mouseup', 'onMouseUp');
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

			if (mouseDist >= blx.ui.DragCore.minMouseDist)
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
		this.removeAllListeners(blx.$document);

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
		this.onDragStart();
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
	 * Add Items
	 */
	addItems: function(items)
	{
		var $items = $(items);

		// make a record of it
		this.$items = this.$items.add($items);

		// bind mousedown listener
		this.addListener($items, 'mousedown', 'onMouseDown');
	},

	/**
	 * Remove Items
	 */
	removeItems: function(items)
	{
		var $items = $(items);

		// unbind all events
		this.removeAllListeners($items);

		// remove the record of it
		this.$items = this.$items.not($items);
	},

	/**
	 * Reset
	 */
	reset: function()
	{
		// unbind the events
		this.removeAllListeners($items);

		// reset local vars
		this.$items = $();
	}
},
{
	minMouseDist: 1,

	defaults: {
		axis: null,

		onDragStart: function() {},
		onDrag: function() {},
		onDragStop: function() {}
	}
});


})(jQuery);
