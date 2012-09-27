(function($) {

/**
 * DragDrop
 */
Blocks.ui.DragDrop = Blocks.ui.Drag.extend({

	$dropTargets: null,
	$activeDropTarget: null,

	/**
	 * Constructor
	 */
	init: function(settings)
	{
		settings = $.extend({}, Blocks.ui.DragDrop.defaults, settings);
		this.base(settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		if (this.settings.dropTargets)
		{
			if (typeof this.settings.dropTargets == 'function')
				this.$dropTargets = $(this.settings.dropTargets());
			else
				this.$dropTargets = $(this.settings.dropTargets);

			// ignore if an empty array
			if (!this.$dropTargets.length)
				this.$dropTargets = null;
		}

		this.$activeDropTarget = null;

		this.base();
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		if (this.$dropTargets)
		{
			var _activeDropTarget;

			// is the cursor over any of the drop target?
			for (var i = 0; i < this.$dropTargets.length; i++)
			{
				var elem = this.$dropTargets[i];

				if (Blocks.hitTest(this.mouseX, this.mouseY, elem))
				{
					_activeDropTarget = elem;
					break;
				}
			}

			// has the drop target changed?
			if (!this.$activeDropTarget || _activeDropTarget != this.$activeDropTarget[0])
			{
				// was there a previous one?
				if (this.$activeDropTarget)
				{
					this.$activeDropTarget.removeClass(this.settings.activeDropTargetClass);
				}

				// remember the new drop target
				this.$activeDropTarget = $(_activeDropTarget);

				// is there a new one?
				if (this.$activeDropTarget)
				{
					this.$activeDropTarget.addClass(this.settings.activeDropTargetClass);
				}

				this.settings.onDropTargetChange();
			}
		}

		this.base();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.$dropTargets && this.$activeDropTarget)
			this.$activeDropTarget.removeClass(this.settings.activeDropTargetClass);

		this.base();
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
		}
	}
},
{
	defaults: {
		dropTargets: null,
		onDropTargetChange: function() {}
	}
});

})(jQuery);
