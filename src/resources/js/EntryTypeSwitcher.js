(function($) {


Craft.EntryTypeSwitcher = Garnish.Base.extend(
{
	$form: null,
	$typeSelect: null,
	$spinner: null,
	$fields: null,

	init: function()
	{
		this.$form = $('#entry-form');
		this.$typeSelect = $('#entryType');
		this.$spinner = $('<div class="spinner hidden" style="position: absolute; margin-'+Craft.left+': 2px;"/>').insertAfter(this.$typeSelect.parent());
		this.$fields = $('#fields');

		this.addListener(this.$typeSelect, 'change', 'onTypeChange');
	},

	onTypeChange: function(ev)
	{
		this.$spinner.removeClass('hidden');

		Craft.postActionRequest('entries/switchEntryType', this.$form.serialize(), $.proxy(function(response, textStatus) {
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				var fieldsPane = this.$fields.data('pane');
				fieldsPane.deselectTab();
				this.$fields.html(response.paneHtml);
				fieldsPane.destroy();
				this.$fields.pane();
				Craft.initUiElements(this.$fields);

				var html = '';

				if (response.headHtml)
				{
					html += response.headHtml;
				}

				if (response.footHtml)
				{
					html += response.footHtml;
				}

				if (html)
				{
					$(html).appendTo(Garnish.$bod);
				}

				// Update the slug generator with the new title input
				if (typeof slugGenerator != "undefined")
				{
					slugGenerator.setNewSource('#title');
				}

				// Trigger a resize event to force a grid update
				Garnish.$win.trigger('resize');
			}
		}, this));
	}

});


})(jQuery);
