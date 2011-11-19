(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Drag
 */
blx.ui.Drag = Base.extend({

	/**
	 * Constructor
	 */
	constructor: function(settings)
	{
		this.settings = $.extend({}, blx.ui.Drag.defaults, settings);

		this.$items = $();

		this.mousedownX;
		this.mousedownY;
		this.targetMouseDiffX;
		this.targetMouseDiffY;
		this.mouseX;
		this.mouseY;
		this.lastMouseX;
		this.lastMouseY;

		this.dragging = false;
		this.target;
		this.$draggees;
		this.otherItems;

		this.helpers;
		this.helperTargets;
		this.helperPositions;
		this.helperLagIncrement;
		this.updateHelperPosInterval;

		this.activeDropTarget;
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

		this.mouseX = event.pageX;
		this.mouseY = event.pageY;

		if (!this.dragging)
		{
			// has the mouse moved far enough to initiate dragging yet?
			var mouseDist = blx.utils.getDist(this.mousedownX, this.mousedownY, this.mouseX, this.mouseY);

			if (mouseDist >= 1)
				this.startDragging();
			else
				return;
		}

		this.drag();
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

		// get the draggees, based on the filter setting
		switch (typeof this.settings.filter)
		{
			case 'function':
				this.$draggees = this.settings.filter();
				break;

			case 'string':
				this.$draggees = this.$items.filter(this.settings.filter);
				break;

			default:
				this.$draggees = $(this.target);
		}

		// put the target item in the front of the list
		this.$draggees = $([ this.target ].concat(this.$draggees.not(this.target).toArray()));

		this.draggeeDisplay = this.$draggees.css('display');

		for (var i = 0; i < this.$draggees.length; i++)
		{
			var $draggee = $(this.$draggees[i]),
				draggeeOffset = $draggee.offset(),
				$draggeeHelper = $draggee.clone();

			$draggeeHelper.css({
				width: $draggee.width(),
				height: $draggee.height()
			});

			if (typeof this.settings.helper == 'function')
				$draggeeHelper = this.settings.helper($draggeeHelper);

			$draggeeHelper.appendTo(document.body);

			$draggeeHelper.css({
				position: 'absolute',
				top: draggeeOffset.top,
				left: draggeeOffset.left,
				zIndex: 1000 + this.$draggees.length - i,
				opacity: (typeof this.settings.helperOpacity != 'undefined' ? this.settings.helperOpacity : 1)
			});

			this.helperPositions[i] = {
				top:  draggeeOffset.top,
				left: draggeeOffset.left
			};

			this.helpers.push($draggeeHelper);

			if (this.settings.draggeePlaceholders)
				$draggee.css('visibility', 'hidden')
			else
				$draggee.hide();
		};

		this.lastMouseX = this.lastMouseY = null;

		this.helperLagIncrement = this.helpers.length == 1 ? 0 : 1.5 / (this.helpers.length-1);
		this.updateHelperPosInterval = setInterval($.proxy(this, 'updateHelperPos'), 20);

		// -------------------------------------------
		//  Deal with the remaining items
		// -------------------------------------------

		// create an array of all the other items
		this.otherItems = [];

		for (var i = 0; i < this.$items.length; i++)
		{
			var item = this.$items[i];

			if ($.inArray(item, this.$draggees) == -1)
				this.otherItems.push(item);
		};

		// -------------------------------------------
		//  Drop Targets
		// -------------------------------------------

		if (this.settings.dropTargets)
		{
			if (typeof this.settings.dropTargets == 'function')
				this.dropTargets = this.settings.dropTargets();
			else
				this.dropTargets = this.settings.dropTargets;

			// ignore if an empty array
			if (!this.dropTargets.length)
				this.dropTargets = false;
		}
		else
		{
			this.dropTargets = false;
		}

		this.activeDropTarget = null;
	},

	/**
	 * Drag
	 */
	drag: function()
	{
		// -------------------------------------------
		//  Drop Targets
		// -------------------------------------------

		if (this.dropTargets)
		{
			var _activeDropTarget;

			// is the cursor over any of the drop target?
			for (var i = 0; i < this.dropTargets.length; i++)
			{
				var elem = this.dropTargets[i];

				if (blx.utils.hitTest(this.mouseX, this.mouseY, elem))
				{
					_activeDropTarget = elem;
					break;
				}
			}

			// has the drop target changed?
			if (_activeDropTarget != this.activeDropTarget)
			{
				// was there a previous one?
				if (this.activeDropTarget)
				{
					this.activeDropTarget.removeClass(this.settings.activeDropTargetClass);
				}

				// remember the new drop target
				this.activeDropTarget = _activeDropTarget;

				// is there a new one?
				if (this.activeDropTarget)
				{
					this.activeDropTarget.addClass(this.settings.activeDropTargetClass);
				}

				this.onDropTargetChange();
			}
		}
	},

	/**
	 * On Drop Target Change
	 */
	onDropTargetChange: function() { },

	/**
	 * Stop Dragging
	 */
	stopDragging: function()
	{
		this.dragging = false;

		// clear the helper interval
		clearInterval(this.updateHelperPosInterval);

		// -------------------------------------------
		//  Drop Targets
		// -------------------------------------------

		if (this.settings.dropTargets && this.activeDropTarget)
			this.activeDropTarget.removeClass(this.settings.activeDropTargetClass);
	},

	/**
	 * Update Helper Position
	 */
	updateHelperPos: function()
	{
		// has the mouse moved?
		if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY)
		{
			this.lastMouseX = this.mouseX;
			this.lastMouseY = this.mouseY;

			// figure out what each of the helpers' target positions are
			// (they will gravitate toward their targets in updateHelperPos())
			for (var i = 0; i < this.helpers.length; i++)
			{
				this.helperTargets[i] = {
					left: this.mouseX - this.targetMouseDiffX + (i * 5),
					top:  this.mouseY - this.targetMouseDiffY + (i * 5)
				};
			};
		}

		for (var i = 0; i < this.helpers.length; i++)
		{
			var lag = 1 + (this.helperLagIncrement * i);

			this.helperPositions[i] = {
				left: this.helperPositions[i].left + ((this.helperTargets[i].left - this.helperPositions[i].left) / lag),
				top:  this.helperPositions[i].top  + ((this.helperTargets[i].top  - this.helperPositions[i].top) / lag)
			};

			this.helpers[i].css(this.helperPositions[i]);
		};
	},

	/**
	 * Return Helpers to Draggees
	 */
	returnHelpersToDraggees: function()
	{
		for (var i = 0; i < this.$draggees.length; i++)
		{
			var $draggee = $(this.$draggees[i]),
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
	 * Fade Out Helpers
	 */
	fadeOutHelpers: function()
	{
		for (var i = 0; i < this.helpers.length; i++)
		{
			(function($draggeeHelper) {
				$draggeeHelper.fadeOut('fast', function() {
					$draggeeHelper.remove();
				});
			})(this.helpers[i]);
		};
	},

	/**
	 * Add Items
	 */
	addItems: function($items)
	{
		// make a record of it
		this.$items = this.$items.add($items);

		// bind mousedown listener
		$items.on('mousedown.drag', $.proxy(this, 'onMouseDown'));
	},

	/**
	 * Remove Items
	 */
	removeItems: function($items)
	{
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
},{
	defaults: {}
});


})(jQuery);
