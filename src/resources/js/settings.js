(function($) {


Craft.Tool = Garnish.Base.extend({

	$trigger: null,
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
			var contentsHtml = this.optionsHtml +
				'<div class="buttons">' +
					'<div class="btn submit">'+this.buttonLabel+'</div>' +
				'</div>';

			this.hud = new Garnish.HUD(this.$trigger, contentsHtml, {
				hudClass: 'hud toolhud',
				triggerSpacing: 10,
				tipWidth: 30
			});
		}
		else
		{
			this.hud.show();
		}
	}

});


})(jQuery);
