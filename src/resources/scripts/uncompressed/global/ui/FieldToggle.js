(function($) {


/**
 * FieldToggle
 */
Blocks.ui.FieldToggle = Blocks.Base.extend({

	$toggle: null,

	_$target: null,
	_isCheckbox: null,

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

		if (!this.isCheckbox())
			this.findTarget();

		this.addListener(this.$toggle, 'change', 'onToggleChange');
	},

	isCheckbox: function()
	{
		if (!this._isCheckbox)
			this._isCheckbox = (this.$toggle.prop('nodeName') == 'INPUT' && this.$toggle.attr('type').toLowerCase() == 'checkbox');
		return this._isCheckbox;
	},

	getTarget: function()
	{
		if (!this._$target)
			this.findTarget();
		return this._$target;
	},

	findTarget: function()
	{
		if (this.isCheckbox())
			this._$target = $('#'+this.$toggle.attr('data-target'));
		else
			this._$target = $('#'+this.getToggleVal());
	},

	getToggleVal: function()
	{
		return Blocks.getInputPostVal(this.$toggle);
	},

	onToggleChange: function()
	{
		var val = this.getToggleVal();

		if (this.isCheckbox())
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
		if (this.getTarget().length)
		{
			if (this.isCheckbox())
			{
				var $target = this.getTarget();
				$target.height('auto');
				var height = $target.height();
				$target.height(0);
				$target.stop().animate({height: height}, 'fast', $.proxy(function() {
					$target.height('auto');
				}, this));
			}
			else
			{
				this.getTarget().show();
			}
		}
	},

	hideTarget: function()
	{
		if (this.getTarget().length)
		{
			if (this.isCheckbox())
				this.getTarget().stop().animate({height: 0}, 'fast');
			else
				this.getTarget().hide();
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
