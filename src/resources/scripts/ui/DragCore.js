(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Drag Core
 */
blx.ui.DragCore = blx.Base.extend({

	$items: null,
	$handles: null,

	dragging: false,

	mousedownX: null,
	mousedownY: null,
	mouseDistX: null,
	mouseDistY: null,
	$targetItem: null,
	targetItemMouseDiffX: null,
	targetItemMouseDiffY: null,
	mouseX: null,
	mouseY: null,
	lastMouseX: null,
	lastMouseY: null,


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
		this.$handles = $();

		if (items) this.addItems(items);
	},

	/**
	 * On Mouse Down
	 */
	onMouseDown: function(event)
	{
		// ignore if we already have a target
		if (this.$targetItem) return;

		event.preventDefault();

		// capture the target
		var index = $.inArray(event.currentTarget, this.$handles);
		this.$targetItem = $(this.$items[index]);

		// capture the current mouse position
		this.mousedownX = this.mouseX = event.pageX;
		this.mousedownY = this.mouseY = event.pageY;

		// capture the difference between the mouse position and the target item's offset
		var offset = this.$targetItem.offset();
		this.targetItemMouseDiffX = event.pageX - offset.left + parseInt(this.$targetItem.css('marginLeft'));
		this.targetItemMouseDiffY = event.pageY - offset.top  + parseInt(this.$targetItem.css('marginTop'));

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

		this.mouseDistX = this.mouseX - this.mousedownX;
		this.mouseDistY = this.mouseY - this.mousedownY;

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

		this.$targetItem = null;
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
		items = $.makeArray(items);

		for (var i = 0; i < items.length; i++)
		{
			var item = items[i];

			// Make sure this element wasn't already added
			if ($.inArray(item, this.$items) == -1)
			{
				// Add the element
				this.$items.push(item);

				// Get the handle
				if (this.settings.handle)
				{
					if (typeof this.settings.handle == 'object')
						var handle = blx.utils.getElement(this.settings.handle);
					else if (typeof this.settings.handle == 'string')
						var handle = blx.utils.getElement($(item).find(this.settings.handle));
					else if (typeof this.settings.handle == 'function')
						var handle = blx.utils.getElement(this.settings.handle(item));
				}
				else
					var handle = item;

				this.$handles.push(handle);

				// Listen for mousedown's
				this.addListener(handle, 'mousedown', 'onMouseDown');
			}
		}
	},

	/**
	 * Remove Items
	 */
	removeItems: function(items)
	{
		items = $.makeArray(items);

		for (var i = 0; i < items.length; i++)
		{
			var item = items[i];

			// Make sure we actually know about this itme
			var index = $.inArray(item, this.$items);
			if (index != -1)
			{
				// Stop listening to the handle
				var handle = this.$handles[index];
				this.removeAllListeners(handle, 'mousedown');

				// Remove the item and handle records
				this.$items.splice(index, 1);
				this.$handles.splice(index, 1);
			}
		}
	},

	/**
	 * Reset
	 */
	reset: function()
	{
		// unbind the events
		this.removeAllListeners(this.$handles);

		// reset local vars
		this.$items = $();
		this.$handles = $();
	}
},
{
	minMouseDist: 1,

	defaults: {
		handle: null,
		axis: null,

		onDragStart: function() {},
		onDrag: function() {},
		onDragStop: function() {}
	}
});


})(jQuery);
