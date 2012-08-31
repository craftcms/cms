(function($) {


/**
 * FieldToggle
 */
blx.ui.FieldToggle = blx.ui.Drag.extend({

	$toggle: null,
	$target: null,
	isCheckbox: null,

	init: function(toggle)
	{
		this.$toggle = $(toggle);

		// Is this already a field toggle?
		if (this.$toggle.data('fieldtoggle'))
		{
			blx.log('Double-instantiating a field toggle on an element');
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

		console.log('findTarget', this.$target);
	},

	getToggleVal: function()
	{
		return blx.getInputPostVal(this.$toggle);
	},

	onToggleChange: function()
	{
		var val = this.getToggleVal();
		console.log(val);

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
			new blx.ui.FieldToggle(this);
	});
};


blx.$document.ready(function()
{
	$('.fieldtoggle').fieldtoggle();
});


})(jQuery);
