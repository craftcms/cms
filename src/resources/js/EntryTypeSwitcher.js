(function($) {


Craft.EntryTypeSwitcher = Garnish.Base.extend(
{
	$typeSelect: null,
	$spinner: null,
	$fields: null,

	init: function()
	{
		this.$typeSelect = $('#entryType');
		this.$spinner = $('<div class="spinner hidden"/>').insertAfter(this.$typeSelect.parent());
		this.$fields = $('#fields');

		this.addListener(this.$typeSelect, 'change', 'onTypeChange');
	},

	onTypeChange: function(ev)
	{
		this.$spinner.removeClass('hidden');

		Craft.postActionRequest('entries/switchEntryType', Craft.cp.$container.serialize(), $.proxy(function(response, textStatus) {
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				var fieldsPane = this.$fields.data('pane');
				fieldsPane.deselectTab();
				this.$fields.html(response.paneHtml);
				fieldsPane.destroy();
				this.$fields.pane();
				Craft.initUiElements(this.$fields);

				Craft.appendHeadHtml(response.headHtml);
				Craft.appendFootHtml(response.footHtml);

				// Update the slug generator with the new title input
				if (typeof slugGenerator != "undefined")
				{
					slugGenerator.setNewSource('#title');
				}
			}
		}, this));
	}

});


})(jQuery);
