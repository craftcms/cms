(function($) {


Craft.EntryTypeSwitcher = Garnish.Base.extend({

	$form: null,
	$typeSelect: null,
	$spinner: null,
	$fields: null,

	init: function()
	{
		this.$form = $('#entry-form');
		this.$typeSelect = $('#entryType');
		this.$spinner = $('<div class="spinner hidden" style="margin-left: 5px;"/>').insertAfter(this.$typeSelect.parent());
		this.$fields = $('#fields');

		this.addListener(this.$typeSelect, 'change', 'onTypeChange');
	},

	onTypeChange: function(ev)
	{
		this.$spinner.removeClass('hidden');

		Craft.postActionRequest('entries/switchEntryType', this.$form.serialize(), $.proxy(function(response) {
			this.$spinner.addClass('hidden');

			Craft.cp.deselectContentTab();
			Craft.cp.$contentTabsContainer.html(response.tabsHtml);
			this.$fields.html(response.fieldsHtml);
			Craft.cp.initContentTabs();

			$(response.headHtml + response.footHtml).appendTo(Garnish.$bod);
		}, this));
	}

});


})(jQuery);
