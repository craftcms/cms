/**
 * Element editor
 */
Craft.ElementEditor = Garnish.Base.extend({

	$element: null,
	elementId: null,
	locale: null,

	$form: null,
	$fieldsContainer: null,
	$cancelBtn: null,
	$saveBtn: null,
	$spinner: null,

	$localeSelect: null,
	$localeSpinner: null,

	hud: null,

	init: function($element)
	{
		this.$element = $element;
		this.elementId = $element.data('id');

		this.$element.addClass('loading');

		var data = {
			elementId:      this.elementId,
			locale:         this.$element.data('locale'),
			includeLocales: true
		};

		Craft.postActionRequest('elements/getEditorHtml', data, $.proxy(this, 'showHud'));
	},

	showHud: function(response, textStatus)
	{
		this.$element.removeClass('loading');

		if (textStatus == 'success')
		{
			var $hudContents = $();

			if (response.locales)
			{
				var $localesContainer = $('<div class="hud-header"/>'),
					$localeSelectContainer = $('<div class="select"/>').appendTo($localesContainer);

				this.$localeSelect = $('<select/>').appendTo($localeSelectContainer);
				this.$localeSpinner = $('<div class="spinner hidden"/>').appendTo($localesContainer);

				for (var i = 0; i < response.locales.length; i++)
				{
					var locale = response.locales[i];
					$('<option value="'+locale.id+'"'+(locale.id == response.locale ? ' selected="selected"' : '')+'>'+locale.name+'</option>').appendTo(this.$localeSelect);
				}

				this.addListener(this.$localeSelect, 'change', 'switchLocale');

				$hudContents = $hudContents.add($localesContainer);
			}

			this.$form = $('<form/>');
			this.$fieldsContainer = $('<div class="fields"/>').appendTo(this.$form);

			this.updateForm(response);

			var $buttonsOuterContainer = $('<div class="hud-footer"/>').appendTo(this.$form);

			this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsOuterContainer);

			var $buttonsContainer = $('<div class="buttons right"/>').appendTo($buttonsOuterContainer);
			this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttonsContainer);
			this.$saveBtn = $('<input class="btn submit" type="submit" value="'+Craft.t('Save')+'"/>').appendTo($buttonsContainer);

			$hudContents = $hudContents.add(this.$form);

			this.hud = new Garnish.HUD(this.$element, $hudContents, {
				bodyClass: 'body elementeditor',
				closeOtherHUDs: false
			});

			this.hud.on('hide', $.proxy(function() {
				delete this.hud;
			}, this));

			this.addListener(this.$form, 'submit', 'saveElement');
			this.addListener(this.$cancelBtn, 'click', function() {
				this.hud.hide()
			});
		}
	},

	switchLocale: function()
	{
		var newLocale = this.$localeSelect.val();

		if (newLocale == this.locale)
		{
			return;
		}

		this.$localeSpinner.removeClass('hidden');

		var data = {
			elementId: this.elementId,
			locale:    newLocale
		};

		Craft.postActionRequest('elements/getEditorHtml', data, $.proxy(function(response, textStatus)
		{
			this.$localeSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				this.updateForm(response);
			}
			else
			{
				this.$localeSelect.val(this.locale);
			}
		}, this));
	},

	updateForm: function(response)
	{
		this.locale = response.locale;

		this.$fieldsContainer.html(response.html)
		Craft.initUiElements(this.$fieldsContainer);
	},

	saveElement: function(ev)
	{
		ev.preventDefault();

		this.$spinner.removeClass('hidden');

		var data = this.$form.serialize();

		Craft.postActionRequest('elements/saveElement', data, $.proxy(function(response, textStatus)
		{
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (textStatus == 'success' && response.success)
				{
					if (this.locale == this.$element.data('locale'))
					{
						// Update the label
						this.$element.find('.title').text(response.newTitle);
					}

					// Update Live Preview
					if (typeof Craft.entryPreviewMode != 'undefined')
					{
						Craft.entryPreviewMode.updateIframe(true);
					}

					this.closeHud();
				}
				else
				{
					this.updateForm(response);
					Garnish.shake(this.hud.$hud);
				}
			}
		}, this));
	},

	closeHud: function()
	{
		this.hud.hide();
		delete this.hud;
	}
});
