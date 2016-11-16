/**
 * Element editor
 */
Craft.ElementEditor = Garnish.Base.extend(
{
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

	init: function($element, settings)
	{
		// Param mapping
		if (typeof settings == typeof undefined && $.isPlainObject($element))
		{
			// (settings)
			settings = $element;
			$element = null;
		}

		this.$element = $element;
		this.setSettings(settings, Craft.ElementEditor.defaults);

		this.loadHud();
	},

	setElementAttribute: function(name, value)
	{
		if (!this.settings.attributes)
		{
			this.settings.attributes = {};
		}

		if (value === null)
		{
			delete this.settings.attributes[name];
		}
		else
		{
			this.settings.attributes[name] = value;
		}
	},

	getBaseData: function()
	{
		var data = $.extend({}, this.settings.params);

		if (this.settings.locale)
		{
			data.locale = this.settings.locale;
		}
		else if (this.$element && this.$element.data('locale'))
		{
			data.locale = this.$element.data('locale');
		}

		if (this.settings.elementId)
		{
			data.elementId = this.settings.elementId;
		}
		else if (this.$element && this.$element.data('id'))
		{
			data.elementId = this.$element.data('id');
		}

		if (this.settings.elementType)
		{
			data.elementType = this.settings.elementType;
		}

		if (this.settings.attributes)
		{
			data.attributes = this.settings.attributes;
		}

		return data;
	},

	loadHud: function()
	{
		this.onBeginLoading();
		var data = this.getBaseData();
		data.includeLocales = this.settings.showLocaleSwitcher;
		Craft.postActionRequest('elements/getEditorHtml', data, $.proxy(this, 'showHud'));
	},

	showHud: function(response, textStatus)
	{
		this.onEndLoading();

		if (textStatus == 'success')
		{
			var $hudContents = $();

			if (response.locales)
			{
				var $header = $('<div class="hud-header"/>'),
					$localeSelectContainer = $('<div class="select"/>').appendTo($header);

				this.$localeSelect = $('<select/>').appendTo($localeSelectContainer);
				this.$localeSpinner = $('<div class="spinner hidden"/>').appendTo($header);

				for (var i = 0; i < response.locales.length; i++)
				{
					var locale = response.locales[i];
					$('<option value="'+locale.id+'"'+(locale.id == response.locale ? ' selected="selected"' : '')+'>'+locale.name+'</option>').appendTo(this.$localeSelect);
				}

				this.addListener(this.$localeSelect, 'change', 'switchLocale');

				$hudContents = $hudContents.add($header);
			}

			this.$form = $('<div/>');
			this.$fieldsContainer = $('<div class="fields"/>').appendTo(this.$form);

			this.updateForm(response);

			this.onCreateForm(this.$form);

			var $footer = $('<div class="hud-footer"/>').appendTo(this.$form),
				$buttonsContainer = $('<div class="buttons right"/>').appendTo($footer);
			this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttonsContainer);
			this.$saveBtn = $('<input class="btn submit" type="submit" value="'+Craft.t('Save')+'"/>').appendTo($buttonsContainer);
			this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsContainer);

			$hudContents = $hudContents.add(this.$form);

			if (!this.hud)
			{
				var hudTrigger = (this.settings.hudTrigger || this.$element);

				this.hud = new Garnish.HUD(hudTrigger, $hudContents, {
					bodyClass: 'body elementeditor',
					closeOtherHUDs: false,
					onShow: $.proxy(this, 'onShowHud'),
					onHide: $.proxy(this, 'onHideHud'),
					onSubmit: $.proxy(this, 'saveElement')
				});

				this.hud.$hud.data('elementEditor', this);

				this.hud.on('hide', $.proxy(function() {
					delete this.hud;
				}, this));
			}
			else
			{
				this.hud.updateBody($hudContents);
				this.hud.updateSizeAndPosition();
			}

			// Focus on the first text input
			$hudContents.find('.text:first').focus();

			this.addListener(this.$cancelBtn, 'click', function() {
				this.hud.hide();
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


		var data = this.getBaseData();
		data.locale = newLocale;

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

		this.$fieldsContainer.html(response.html);

		// Swap any instruction text with info icons
		var $instructions = this.$fieldsContainer.find('> .meta > .field > .heading > .instructions');

		for (var i = 0; i < $instructions.length; i++)
		{

			$instructions.eq(i)
				.replaceWith($('<span/>', {
					'class': 'info',
					'html': $instructions.eq(i).children().html()
				}))
				.infoicon();
		}

		Garnish.requestAnimationFrame($.proxy(function()
		{
			Craft.appendHeadHtml(response.headHtml);
			Craft.appendFootHtml(response.footHtml);
			Craft.initUiElements(this.$fieldsContainer);
		}, this));
	},

	saveElement: function()
	{
		var validators = this.settings.validators;

		if ($.isArray(validators))
		{
			for (var i = 0; i < validators.length; i++)
			{
				if ($.isFunction(validators[i]) && !validators[i].call())
				{
					return false;
				}
			}
		}

		this.$spinner.removeClass('hidden');

		var data = $.param(this.getBaseData())+'&'+this.hud.$body.serialize();
		Craft.postActionRequest('elements/saveElement', data, $.proxy(function(response, textStatus)
		{
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (textStatus == 'success' && response.success)
				{
					if (this.$element && this.locale == this.$element.data('locale'))
					{
						// Update the label
						var $title = this.$element.find('.title'),
							$a = $title.find('a');

						if ($a.length && response.cpEditUrl)
						{
							$a.attr('href', response.cpEditUrl);
							$a.text(response.newTitle);
						}
						else
						{
							$title.text(response.newTitle);
						}
					}

					// Update Live Preview
					if (typeof Craft.livePreview != 'undefined')
					{
						Craft.livePreview.updateIframe(true);
					}

					this.closeHud();
					this.onSaveElement(response);
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
	},

	// Events
	// -------------------------------------------------------------------------

	onShowHud: function()
	{
		this.settings.onShowHud();
		this.trigger('showHud');
	},

	onHideHud: function()
	{
		this.settings.onHideHud();
		this.trigger('hideHud');
	},

	onBeginLoading: function()
	{
		if (this.$element)
		{
			this.$element.addClass('loading');
		}

		this.settings.onBeginLoading();
		this.trigger('beginLoading');
	},

	onEndLoading: function()
	{
		if (this.$element)
		{
			this.$element.removeClass('loading');
		}

		this.settings.onEndLoading();
		this.trigger('endLoading');
	},

	onSaveElement: function(response)
	{
		this.settings.onSaveElement(response);
		this.trigger('saveElement', {
			response: response
		});
	},

	onCreateForm: function ($form)
	{
		this.settings.onCreateForm($form);
	}
},
{
	defaults: {
		hudTrigger: null,
		showLocaleSwitcher: true,
		elementId: null,
		elementType: null,
		locale: null,
		attributes: null,
		params: null,

		onShowHud: $.noop,
		onHideHud: $.noop,
		onBeginLoading: $.noop,
		onEndLoading: $.noop,
		onCreateForm: $.noop,
		onSaveElement: $.noop,

		validators: []
	}
});
