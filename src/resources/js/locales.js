(function($) {


Craft.Locales = Garnish.Base.extend(
{
	$addLocaleField: null,
	$addLocaleInput: null,
	$addLocaleSpinner: null,
	$resultsSheet: null,
	$resultsList: null,
	$activeLocale: null,

	locales: null,
	selectedLocales: null,
	adminTable: null,
	inputVal: null,
	showingResultsSheet: false,

	init: function(locales, selectedLocales)
	{
		this.locales = {};
		for (var id in locales)
		{
			this.locales[id] = {
				name: locales[id],
				words: Craft.asciiString(id+' '+locales[id]).match(Craft.Locales.wordRegex)
			};
		}

		this.selectedLocales = selectedLocales;

		this.$addLocaleField = $('#addlocale');
		this.$addLocaleInput = $('#addlocaleinput');
		this.$addLocaleSpinner = this.$addLocaleField.find('.spinner');

		this.adminTable = new Craft.AdminTable({
			tableSelector: '#locales',
			sortable: true,
			minObjects: 1,
			reorderAction: 'localization/reorderLocales',
			deleteAction: 'localization/deleteLocale',
			confirmDeleteMessage: Craft.t('Are you sure you want to delete “{name}” and all its associated content?'),
			onDeleteObject: $.proxy(function(id) {
				var index = $.inArray(id, this.selectedLocales);
				if (index != -1)
				{
					this.selectedLocales.splice(index, 1);
				}
			}, this)
		});

		this.addListener(this.$addLocaleInput, 'keydown', 'onKeyDown');
		this.addListener(this.$addLocaleInput, 'focus', 'onFocus');
		this.addListener(this.$addLocaleInput, 'blur', 'onBlur');
	},

	onKeyDown: function(ev)
	{
		switch (ev.keyCode)
		{
			case Garnish.ESC_KEY:
			{
				this.$addLocaleInput.val('');
				this.hideResultsSheet();
				return;
			}
			case Garnish.RETURN_KEY:
			{
				ev.preventDefault();
				this.addSelectedLocale();
				return;
			}
			case Garnish.UP_KEY:
			{
				this.setRelativeActiveLocale('prev');
				return;
			}
			case Garnish.DOWN_KEY:
			{
				this.setRelativeActiveLocale('next');
				return;
			}
		}

		setTimeout($.proxy(this, 'checkInputVal'), 1);
	},

	onFocus: function()
	{
		if (this.inputVal)
		{
			this.showResultsSheet();
		}
	},

	onBlur: function()
	{
		this.hideResultsSheet();
	},

	setRelativeActiveLocale: function(dir)
	{
		if (this.$activeLocale)
		{
			var $relLocale = this.$activeLocale.parent()[dir]().children('a');
			if ($relLocale.length)
			{
				this.$activeLocale.removeClass('hover');
				$relLocale.addClass('hover');
				this.$activeLocale = $relLocale;
			}
		}
	},

	checkInputVal: function()
	{
		if (this.inputVal !== (this.inputVal = this.$addLocaleInput.val()))
		{
			var matchingLocales = this.findMatchingLocales();

			if (matchingLocales.length)
			{
				matchingLocales = matchingLocales.sort(function(a, b) {
					return a.length - b.length;
				});

				this.showResultsSheet();
				this.$resultsList.html('');

				for (var i = 0; i < matchingLocales.length; i++)
				{
					var locale = this.locales[matchingLocales[i]],
						$li = $('<li/>').appendTo(this.$resultsList),
						$a = $('<a data-id="'+matchingLocales[i]+'">'+locale.name+' ('+matchingLocales[i]+')</a>').appendTo($li);

					if (i == 0)
					{
						$a.addClass('hover');
						this.$activeLocale = $a;
					}
				}
			}
			else
			{
				this.hideResultsSheet();
				this.$activeLocale = null;
			}
		}
	},

	findMatchingLocales: function()
	{
		var matchingLocales = [],
			inputValWords = Craft.asciiString(this.inputVal).match(Craft.Locales.wordRegex);

		if (inputValWords)
		{
			var inputValWordRegexes = [];
			for (var i = 0; i < inputValWords.length; i++)
			{
				inputValWordRegexes.push(new RegExp('^'+inputValWords[i], 'i'));
			}

			for (var id in this.locales)
			{
				if (Craft.inArray(id, this.selectedLocales))
				{
					continue;
				}

				var includeLocale = true;

				// Loop through all the input val words,
				// and make sure each of them matches something in the locale
				for (var i = 0; i < inputValWordRegexes.length; i++)
				{
					var wordMatches = false;

					for (var j = 0; j < this.locales[id].words.length; j++)
					{
						if (this.locales[id].words[j].search(inputValWordRegexes[i]) != -1)
						{
							wordMatches = true;
							break;
						}
					}

					// Stop checking this locale on the first non-match
					if (!wordMatches)
					{
						includeLocale = false;
						break;
					}
				}

				if (includeLocale)
				{
					matchingLocales.push(id);
				}
			}
		}

		return matchingLocales;
	},

	showResultsSheet: function()
	{
		if (!this.showingResultsSheet)
		{
			if (!this.$resultsSheet)
			{
				this.$resultsSheet = $('<div id="addlocaleresults" class="menu" style="position: relative; margin: 0 1px;"/>').appendTo(this.$addLocaleField);
				this.$resultsList = $('<ul/>').appendTo(this.$resultsSheet);

				this.addListener(this.$resultsList, 'mousedown', 'addSelectedLocale');
			}

			this.$resultsSheet.show();
			this.showingResultsSheet = true;
		}
	},

	hideResultsSheet: function()
	{
		if (this.showingResultsSheet)
		{
			this.$resultsSheet.hide();
			this.showingResultsSheet = false;
		}
	},

	addSelectedLocale: function(ev)
	{
		if (ev)
		{
			var $activeLocale = $(ev.target);
		}
		else
		{
			if (!this.$activeLocale)
			{
				return;
			}

			var $activeLocale = this.$activeLocale;
		}

		this.hideResultsSheet();
		this.$addLocaleInput.val(this.$activeLocale.text()).prop('disabled', true);
		this.$addLocaleSpinner.removeClass('hidden');

		var id = $activeLocale.attr('data-id');

		Craft.postActionRequest('localization/addLocale', { id: id }, $.proxy(function(response, textStatus)
		{
			this.$addLocaleSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					var $tr = $('<tr data-id="'+id+'" data-name="'+this.locales[id].name+'">' +
									'<th scope="row" data-title="'+Craft.t('Name')+'" width="40%">'+this.locales[id].name+'</th>' +
									'<td data-title="'+Craft.t('Locale ID')+'">'+id+'</td>' +
									'<td class="thin"><a class="move icon" title="'+Craft.t('Reorder')+'"></a></td>' +
									'<td class="thin"><a class="delete icon" title="'+Craft.t('Delete')+'"></a></td>' +
								'</tr>');

					this.adminTable.addRow($tr);

					this.selectedLocales.push(id);
					this.$addLocaleInput.val('').prop('disabled', false).trigger('keydown');
					this.checkInputVal();

					Craft.cp.displayNotice(Craft.t('New locale added.'));

					// Now trigger the resave elements task
					Craft.cp.runPendingTasks();
				}
				else
				{
					Craft.cp.displayError(Craft.t('Unable to add the new locale.'));
				}
			}

		}, this))
	}

}, {
	wordRegex: new RegExp('[a-zA-Z]+', 'g')
});


})(jQuery);
