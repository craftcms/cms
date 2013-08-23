/**
 * Element editor
 */
var x;
Craft.ElementEditor = Garnish.Base.extend({

		hud: null,
		elementId: 0,
		requestId: 0,
		$trigger: null,
		$spinner: null,

		init: function(settings)
		{
			this.setSettings(settings, Craft.ElementEditor.defaults);

			this.elementId = this.settings.elementId;
			this.$trigger = this.settings.$trigger;
		},

		show: function ()
		{
			var params = {
				requestId: ++this.requestId,
				elementId: this.elementId
			};

			this._showSpinner();

			// Create a new HUD
			Craft.postActionRequest(this.settings.loadContentAction, params, $.proxy(function(data, textStatus) {

				this._hideSpinner();

				if (textStatus != 'success' || data.requestId != this.requestId) {
					return;
				}

				$hudHtml = $('<div/>').html((data.headHtml ? data.headHtml : '') + (data.bodyHtml ? data.bodyHtml : '') + (data.footHtml ? data.footHtml : ''));

				this.hud = new Garnish.HUD(this.$trigger, $hudHtml, {
					hudClass: 'hud contenthud',
					triggerSpacing: 10,
					tipWidth: 30,
					closeOtherHUDs: false
				});

				Craft.initUiElements($hudHtml);
				this.addListener($hudHtml.find('form'), 'submit', $.proxy(this, '_saveElementDetails'));
				this.addListener($hudHtml.find('.btn.submit'), 'click', function (ev) {$(ev.currentTarget).parents('form').submit();});
				this.addListener($hudHtml.find('.btn.cancel'), 'click', $.proxy(this, 'removeHud'));


			}, this));
		},

		_saveElementDetails: function (event)
		{
			event.preventDefault();

			this.hud.$body.find('.spinner').removeClass('hidden');

			$form = $(event.currentTarget);
			var params = $form.serialize();

			Craft.postActionRequest(this.settings.saveContentAction, params, $.proxy(function(response, textStatus)
			{
				this.hud.$body.find('.spinner').addClass('hidden');

				if (textStatus == 'success')
				{
					if (textStatus == 'success' && response.success)
					{
						// Update the title
						this.$trigger.find('.label').text(response.title);
						this.removeHud();
					}
					else
					{
						Garnish.shake(this.hud.$hud);
					}
				}
			}, this));
		},

		_showSpinner: function ()
		{
			this.removeHud();

            this.$trigger.find('.delete').addClass('hidden');

            // If the removable class is present, then treat this as an Input Field.
            if (this.$trigger.hasClass('removable'))
            {
                this.$trigger.removeClass('removable').data('elementInputField', true);
            }

			this.$trigger.find('.label').css('padding-right', '20px');
			this.$trigger.find('.label').after('<div class="spinner element-spinner" style="position: absolute; right: 2px; bottom: -1px;"></div>');
		},

		_hideSpinner: function ()
		{
            this.$trigger.find('.delete').removeClass('hidden');
			this.$trigger.find('.label').removeClass('spinner element-spinner inline').html(this.$trigger.find('.label nobr').html());

            if (this.$trigger.data('elementInputField'))
            {
                this.$trigger.addClass('removable');
            }

			this.$trigger.find('.label').css('padding-right', '0');
			this.$trigger.find('.label').siblings('.spinner').remove();

		},

		removeHud: function ()
		{
			if (this.hud !== null)
			{
				this.hud.hide();
				delete this.hud;
			}
		}

	},
	{
		defaults: {
			elementId: null,
			$trigger: null,
			loadContentAction: null,
			saveContentAction: null
		}
	}
);
