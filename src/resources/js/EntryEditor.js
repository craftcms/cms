(function($) {


Craft.EntryEditor = Garnish.Base.extend({

	$form: null,

	selectedLocale: null,
	locales: null,

	/*$typeSelect: null,
	$spinner: null,
	$fields: null,*/

	init: function()
	{
		this.$form = $('#entry-form');

		var $localeOptions = $('#locales > li');

		if ($localeOptions.length)
		{
			this.locales = {};

			for (var i = 0; i < $localeOptions.length; i++)
			{
				var $localeOption = $($localeOptions[i]),
					localeId      = $localeOption.data('locale'),
					$link         = $localeOption.children('a'),
					$lightswitch  = $localeOption.children('.lightswitch');

				this.locales[localeId] = {
					$link:             $link,
					$lightswitch:      $lightswitch,
					previouslyEnabled: $lightswitch.hasClass('on')
				};

				if ($link.hasClass('sel'))
				{
					this.selectedLocale = localeId;
				}

				this.addListener($link, 'click', function(ev)
				{
					this.selectLocale($(ev.currentTarget).parent().data('locale'));
				});
			}
		}

		this.addListener(this.$form, 'submit', 'onFormSubmit');


		/*this.$typeSelect = $('#entryType');
		this.$spinner = $('<div class="spinner hidden" style="margin-left: 5px;"/>').insertAfter(this.$typeSelect.parent());
		this.$fields = $('#fields');

		this.addListener(this.$typeSelect, 'change', 'onTypeChange');*/
	},

	selectLocale: function(localeId)
	{
		console.log('selectLocale', localeId);
	},

	onFormSubmit: function(ev)
	{
		// If there are any locales with a different status, add hidden inputs for them
		if (this.locales)
		{
			for (var localeId in this.locales)
			{
				var enabled = this.locales[localeId].$lightswitch.hasClass('on');

				if (enabled != this.locales[localeId].previouslyEnabled)
				{
					$('<input type="hidden" name="locales['+localeId+'][localeEnabled]" value="'+(enabled ? '1' : '')+'"/>').appendTo(this.$form);
				}
			}
		}
	}

	/*onTypeChange: function(ev)
	{
		this.$spinner.removeClass('hidden');

		Craft.postActionRequest('entries/switchEntryType', this.$form.serialize(), $.proxy(function(response, textStatus) {
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				Craft.cp.deselectContentTab();
				Craft.cp.$contentTabsContainer.html(response.tabsHtml);
				this.$fields.html(response.fieldsHtml);
				Craft.cp.initContentTabs();

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
				slugGenerator.setNewSource('#title');
			}
		}, this));
	}*/

});


new Craft.EntryEditor();


})(jQuery);
