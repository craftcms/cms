(function($) {


/**
 * Checkbox select class
 */
Blocks.ui.CheckboxSelect = Blocks.Base.extend({

	$container: null,
	$all: null,
	$options: null,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a checkbox select?
		if (this.$container.data('checkboxSelect'))
		{
			Blocks.log('Double-instantiating a checkbox select on an element');
			this.$container.data('checkbox-select').destroy();
		}

		this.$container.data('checkboxSelect', this);

		var $checkboxes = this.$container.find('input');
		this.$all = $checkboxes.filter('.all:first');
		this.$options = $checkboxes.not(this.$all);

		this.addListener(this.$all, 'change', 'onAllChange');
	},

	onAllChange: function()
	{
		var isAllChecked = this.$all.prop('checked');

		this.$options.attr({
			checked:  isAllChecked,
			disabled: isAllChecked
		});
	}

});


$.fn.checkboxSelect = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'checkboxSelect'))
			new Blocks.ui.CheckboxSelect(this);
	});
};

Blocks.$document.ready(function()
{
	$('.checkbox-select').checkboxSelect();
});


})(jQuery);
