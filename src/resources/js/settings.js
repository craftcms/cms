(function($) {


Craft.Tool = Garnish.Base.extend({

	$trigger: null,
	optionsHtml: null,
	hud: null,

	init: function(toolClass, optionsHtml)
	{
		this.$trigger = $('#tool-'+toolClass);
		this.optionsHtml = optionsHtml;

		this.addListener(this.$trigger, 'click', 'showHUD');
	},

	showHUD: function(ev)
	{
		ev.stopPropagation();

		if (!this.hud)
		{
			var contentsHtml = this.optionsHtml +
				'<div class="buttons">' +
					'<div class="btn submit">'+Craft.t('Go!')+'</div>' +
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
