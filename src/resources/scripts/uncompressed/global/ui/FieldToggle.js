(function($) {


/**
 * FieldToggle
 */
Blocks.ui.FieldToggle = Blocks.Base.extend({

	$toggle: null,
	$target: null,
	isCheckbox: null,

	init: function(toggle)
	{
		this.$toggle = $(toggle);

		// Is this already a field toggle?
		if (this.$toggle.data('fieldtoggle'))
		{
			Blocks.log('Double-instantiating a field toggle on an element');
			this.$toggle.data('fieldtoggle').destroy();
		}

		this.$toggle.data('fieldtoggle', this);

		this.isCheckbox = (this.$toggle.prop('nodeName') == 'INPUT' && this.$toggle.attr('type').toLowerCase() == 'checkbox');
		this.findTarget();

		this.addListener(this.$toggle, 'change', 'onToggleChange');
	},

	findTarget: function()
	{
		if (this.isCheckbox)
			this.$target = $('#'+this.$toggle.attr('data-target'));
		else
			this.$target = $('#'+this.getToggleVal());
	},

	getToggleVal: function()
	{
		return Blocks.getInputPostVal(this.$toggle);
	},

	onToggleChange: function()
	{
		var val = this.getToggleVal();

		if (this.isCheckbox)
		{
			if (val)
				this.showTarget();
			else
				this.hideTarget();
		}
		else
		{
			this.hideTarget();
			this.findTarget();
			this.showTarget();
		}
	},

	showTarget: function()
	{
		if (this.$target.length)
		{
			if (this.isCheckbox)
			{
				var $target = this.$target;
				$target.height('auto');
				var height = $target.height();
				$target.height(0);
				$target.stop().animate({height: height}, 'fast', $.proxy(function() {
					$target.height('auto');
				}, this));
			}
			else
			{
				this.$target.show();
			}
		}
	},

	hideTarget: function()
	{
		if (this.$target.length)
		{
			if (this.isCheckbox)
				this.$target.stop().animate({height: 0}, 'fast');
			else
				this.$target.hide();
		}
	}
});


$.fn.fieldtoggle = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'fieldtoggle'))
			new Blocks.ui.FieldToggle(this);
	});
};


Blocks.$document.ready(function()
{
	$('.fieldtoggle').fieldtoggle();
});


})(jQuery);
