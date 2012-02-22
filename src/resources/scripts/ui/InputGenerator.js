(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Handle Generator
 */
blx.ui.InputGenerator = blx.Base.extend({

	$source: null,
	$target: null,

	init: function(source, target)
	{
		this.$source = $(source);
		this.$target = $(target);

		this.addListener(this.$source, 'keypress,keyup,change,change,blur', 'updateTarget');
		this.addListener(this.$target, 'keypress,keyup,change,change', 'stopUpdatingTarget');
	},

	updateTarget: function()
	{
		var sourceVal = this.$source.val(),
			targetVal = this.generateTargetValue(sourceVal);

		this.$target.val(targetVal);
	},

	generateTargetValue: function(sourceVal)
	{
		return sourceVal;
	},

	stopUpdatingTarget: function()
	{
		this.removeAllListeners(this.$source);
		this.removeAllListeners(this.$target);
	}
});


})(jQuery);
