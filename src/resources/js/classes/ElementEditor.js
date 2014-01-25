/**
 * Element editor
 */
Craft.ElementEditor = Garnish.Base.extend({

	$element: null,

	$form: null,
	$fieldsContainer: null,
	$buttonsContainer: null,
	$cancelBtn: null,
	$saveBtn: null,
	$spinner: null,

	hud: null,

	init: function($element)
	{
		this.$element = $element;

		this.$element.addClass('loading');

		var data = {
			elementId: $element.data('id')
		};

		Craft.postActionRequest('elements/getEditorHtml', data, $.proxy(this, 'showHud'));
	},

	showHud: function(response, textStatus)
	{
		this.$element.removeClass('loading');

		if (textStatus == 'success')
		{
			this.$form = $('<form class="elementeditor"/>');
			this.$fieldsContainer = $('<div/>').appendTo(this.$form);

			this.setFieldsHtml(response.html);

			this.$buttonsContainer = $('<div class="buttons right"/>').appendTo(this.$form);
			this.$spinner = $('<div class="spinner hidden"/>').appendTo(this.$buttonsContainer);
			this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo(this.$buttonsContainer);
			this.$saveBtn = $('<input class="btn submit" type="submit" value="'+Craft.t('Save')+'"/>').appendTo(this.$buttonsContainer);

			this.hud = new Garnish.HUD(this.$element, this.$form, {
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

	setFieldsHtml: function(html)
	{
		this.$fieldsContainer.html(html)
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
					// Update the label
					this.$element.find('.label').text(response.newLabel);

					// Update Live Preview
					if (typeof Craft.entryPreviewMode != 'undefined')
					{
						Craft.entryPreviewMode.updateIframe(true);
					}

					this.closeHud();
				}
				else
				{
					this.setFieldsHtml(response.html);
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
