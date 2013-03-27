/**
 * Input Generator
 */
Craft.BaseInputGenerator = Garnish.Base.extend({

	$source: null,
	$target: null,

	listening: null,

	init: function(source, target)
	{
		this.$source = $(source);
		this.$target = $(target);

		this.startListening();
	},

	startListening: function()
	{
		if (this.listening)
			return;

		this.listening = true;

		this.addListener(this.$source, 'textchange', 'updateTarget');

		this.addListener(this.$target, 'focus', function() {
			this.addListener(this.$target, 'textchange', 'stopListening');
			this.addListener(this.$target, 'blur', function() {
				this.removeListener(this.$target, 'textchange,blur');
			});
		});
	},

	stopListening: function()
	{
		if (!this.listening)
		{
			return;
		}

		this.listening = false;

		this.removeAllListeners(this.$source);
		this.removeAllListeners(this.$target);
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
	}
});
