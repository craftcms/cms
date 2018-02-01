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

		this.adminTable = new LocalesTable(this);

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
				this.$resultsSheet = $('<div id="addlocaleresults" class="menu" style="position: relative;"/>').appendTo(this.$addLocaleField);
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
		var $activeLocale;

		if (ev)
		{
			$activeLocale = $(ev.target);
		}
		else
		{
			if (!this.$activeLocale)
			{
				return;
			}

			$activeLocale = this.$activeLocale;
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

		}, this));
	}

}, {
	wordRegex: new RegExp('[a-zA-Z]+', 'g')
});


var LocalesTable = Craft.AdminTable.extend(
{
	manager: null,
	confirmDeleteModal: null,

	$rowToDelete: null,
	$deleteActionRadios: null,
	$deleteSubmitBtn: null,
	$deleteSpinner: null,

	_deleting: false,

	init: function(manager)
	{
		this.manager = manager;

		this.base({
			tableSelector: '#locales',
			sortable: true,
			minObjects: 1,
			reorderAction: 'localization/reorderLocales',
			deleteAction: 'localization/deleteLocale',
		});
	},

	confirmDeleteObject: function($row)
	{
		if (this.confirmDeleteModal)
		{
			this.confirmDeleteModal.destroy();
			delete this.confirmDeleteModal;
		}

		this._createConfirmDeleteModal($row);

		// Auto-focus the first radio
		if (!Garnish.isMobileBrowser(true))
		{
			setTimeout($.proxy(function() {
				this.$deleteActionRadios.first().trigger('focus');
			}, this), 100);
		}

		return false;
	},

	onDeleteObject: function(id)
	{
		var index = $.inArray(id, this.manager.selectedLocales);

		if (index != -1)
		{
			this.manager.selectedLocales.splice(index, 1);
		}

		this.base(id);
	},

	validateDeleteInputs: function()
	{
		var validates = (
			this.$deleteActionRadios.eq(0).prop('checked') ||
			this.$deleteActionRadios.eq(1).prop('checked')
		);

		if (validates)
		{
			this.$deleteSubmitBtn.removeClass('disabled');
		}
		else
		{
			this.$deleteSubmitBtn.addClass('disabled');
		}

		return validates;
	},

	submitDeleteLocale: function(ev)
	{
		ev.preventDefault();

		if (this._deleting || !this.validateDeleteInputs())
		{
			return;
		}

		this.$deleteSubmitBtn.addClass('active');
		this.$deleteSpinner.removeClass('hidden');
		this.disable();
		this._deleting = true;

		var data = {
			id: this.getObjectId(this.$rowToDelete)
		};

		// Are we transferring content?
		if (this.$deleteActionRadios.eq(0).prop('checked'))
		{
			data.transferContentTo = this.$transferSelect.val();
		}

		Craft.postActionRequest(this.settings.deleteAction, data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				this._deleting = false;
				this.enable();
				this.confirmDeleteModal.hide();
				this.handleDeleteObjectResponse(response, this.$rowToDelete);
			}
		}, this));
	},

	// Private Methods
	// =========================================================================

	_createConfirmDeleteModal: function($row)
	{
		this.$rowToDelete = $row;

		var id = this.getObjectId($row),
			name = this.getObjectName($row);

		var $form = $(
				'<form id="confirmdeletemodal" class="modal fitted" method="post" accept-charset="UTF-8">' +
					Craft.getCsrfInput() +
					'<input type="hidden" name="action" value="localization/deleteLocale"/>' +
					'<input type="hidden" name="id" value="'+id+'"/>' +
				'</form>'
			).appendTo(Garnish.$bod),
			$body = $(
				'<div class="body">' +
					'<p>'+Craft.t('What do you want to do with any content that is only available in {language}?', { language: name })+'</p>' +
					'<div class="options">' +
						'<label><input type="radio" name="contentAction" value="transfer"/> '+Craft.t('Transfer it to:')+'</label>' +
						'<div id="transferselect" class="select">' +
							'<select/>' +
						'</div>' +
					'</div>' +
					'<div>' +
						'<label><input type="radio" name="contentAction" value="delete"/> '+Craft.t('Delete it')+'</label>' +
					'</div>' +
				'</div>'
			).appendTo($form),
			$buttons = $('<div class="buttons right"/>').appendTo($body),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons);

		this.$deleteActionRadios = $body.find('input[type=radio]');
		this.$transferSelect = $('#transferselect > select');
		this.$deleteSubmitBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Delete {language}', { language: name })+'" />').appendTo($buttons);
		this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);

		for (var i = 0; i < this.manager.selectedLocales.length; i++)
		{
			if (this.manager.selectedLocales[i] != id)
			{
				this.$transferSelect.append('<option value="'+this.manager.selectedLocales[i]+'">'+this.manager.locales[this.manager.selectedLocales[i]].name+'</option>');
			}
		}

		this.confirmDeleteModal = new Garnish.Modal($form);

		this.addListener($cancelBtn, 'click', function() {
			this.confirmDeleteModal.hide();
		});

		this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
		this.addListener($form, 'submit', 'submitDeleteLocale');
	}
});


})(jQuery);
