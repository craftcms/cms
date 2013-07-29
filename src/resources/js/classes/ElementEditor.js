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

				if (data.requestId != this.requestId) {
					return;
				}

				$hudHtml = $('<div/>').html((data.headHtml ? data.headHtml : '') + (data.bodyHtml ? data.bodyHtml : '') + (data.footHtml ? data.footHtml : ''));

				this.hud = new Garnish.HUD(this.$trigger, $hudHtml, {
					hudClass: 'hud assetshud',
					triggerSpacing: 10,
					tipWidth: 30,
					closeOtherHUDs: true
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
				if (response.success)
				{
					// Update the title
					this.$trigger.find('.label').text(response.title);
					this.hud.$body.find('.spinner').hide();
					this.removeHud();
				}
				else
				{
					this.hud.$body.find('.spinner').addClass('hidden');
				}
			}, this));
		},

		_showSpinner: function ()
		{
			this.removeHud();

            this.$trigger.find('.delete').addClass('hidden');
            this.$trigger.find('.label').addClass('spinner element-spinner inline');
            this.$trigger.removeClass('removable');
		},

		_hideSpinner: function ()
		{
            this.$trigger.find('.delete').removeClass('hidden');
			this.$trigger.find('.label').removeClass('spinner element-spinner inline');
            this.$trigger.addClass('removable');
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
