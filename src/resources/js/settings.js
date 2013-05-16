(function($) {


Craft.Tool = Garnish.Base.extend({

	$trigger: null,
	$form: null,
	optionsHtml: null,
	buttonLabel: null,
	hud: null,

	init: function(toolClass, optionsHtml, buttonLabel)
	{
		this.$trigger = $('#tool-'+toolClass);
		this.optionsHtml = optionsHtml;
		this.buttonLabel = buttonLabel;

		this.addListener(this.$trigger, 'click', 'showHUD');
	},

	showHUD: function(ev)
	{
		ev.stopPropagation();

		if (!this.hud)
		{
			this.$form = $('<form/>').html(this.optionsHtml +
				'<div class="buttons">' +
					'<input type="submit" class="btn submit" value="'+this.buttonLabel+'">' +
				'</div>');

			this.hud = new Garnish.HUD(this.$trigger, this.$form, {
				hudClass: 'hud toolhud',
				triggerSpacing: 10,
				tipWidth: 30
			});

			this.addListener(this.$form, 'submit', 'onSubmit');
		}
		else
		{
			this.hud.show();
		}
	},

	onSubmit: function(ev)
	{
		ev.preventDefault();
		var data = Garnish.getPostData(this.$form);
	}

});


})(jQuery);
