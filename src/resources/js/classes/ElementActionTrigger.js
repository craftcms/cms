(function($) {


Craft.ElementActionTrigger = Garnish.Base.extend(
{
	maxLevels: null,
	newChildUrl: null,
	$trigger: null,
	$selectedItems: null,
	triggerEnabled: true,

	init: function(settings)
	{
		this.setSettings(settings, Craft.ElementActionTrigger.defaults);

		this.$trigger = $('#'+settings.handle+'-actiontrigger');

		if (this.$trigger.prop('nodeName') == 'FORM')
		{
			// Remove the element index's submit handler
			this.$trigger.off('submit');

			this.addListener(this.$trigger, 'submit', 'handleTriggerFire');
		}
		else
		{
			// Prevent the element index's click handler
			this.$trigger.attr('href', 'javascript:void(0)');

			this.addListener(this.$trigger, 'click', 'handleTriggerFire');
		}

		this.updateTrigger();
		Craft.elementIndex.elementSelect.on('selectionChange', $.proxy(this, 'updateTrigger'));
	},

	updateTrigger: function()
	{
		// Ignore if the last element was just unselected
		if (Craft.elementIndex.elementSelect.totalSelected == 0)
		{
			return;
		}

		if (this.validateSelection())
		{
			this.enableTrigger();
		}
		else
		{
			this.disableTrigger();
		}
	},

	/**
	 * Determines if this action can be performed on the currently selected elements.
	 *
	 * @return bool
	 */
	validateSelection: function()
	{
		var valid = true;
		this.$selectedItems = Craft.elementIndex.elementSelect.$selectedItems;

		if (!this.settings.batch && this.$selectedItems.length > 1)
		{
			valid = false;
		}
		else if (typeof this.settings.validateSelection == 'function')
		{
			valid = this.settings.validateSelection(this.$selectedItems);
		}

		return valid;
	},

	enableTrigger: function()
	{
		if (this.triggerEnabled)
		{
			return;
		}

		this.$trigger.removeClass('disabled');
		this.triggerEnabled = true;
	},

	disableTrigger: function()
	{
		if (!this.triggerEnabled)
		{
			return;
		}

		this.$trigger.addClass('disabled');
		this.triggerEnabled = false;
	},

	handleTriggerFire: function(ev)
	{
		ev.preventDefault();
		ev.stopPropagation();

		if (this.triggerEnabled)
		{
			this.settings.activate(this.$selectedItems);
		}
	}
},
{
	defaults: {
		handle: null,
		batch: false,
		validateSelection: null,
		activate: $.noop
	}
});


})(jQuery)
