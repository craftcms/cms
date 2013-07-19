/**
 * Element editor
 */
Craft.ElementEditor = Garnish.Base.extend({

        hud: null,
        elementId: 0,
        requestId: 0,
        $trigger: null,

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

                this.removeHud();

                if (data.requestId != this.requestId) {
                    return;
                }

                $hudHtml = $('<div/>').html((data.headHtml ? data.headHtml : '') + (data.bodyHtml ? data.bodyHtml : '') + (data.footHtml ? data.footHtml : ''));

                this.hud = new Garnish.HUD(this.$trigger, $hudHtml, {
                    hudClass: 'hud assetshud',
                    triggerSpacing: 10,
                    tipWidth: 30
                });

                Craft.initUiElements($hudHtml);
                this.addListener($hudHtml.find('form'), 'submit', $.proxy(this, '_saveElementDetails'));


            }, this));
        },

        _saveElementDetails: function (event)
        {
            event.preventDefault();

            $form = $(event.currentTarget);

            var params = $form.serialize();

            this._showSpinner();

            Craft.postActionRequest(this.settings.saveContentAction, params, $.proxy(function(response, textStatus)
            {
                if (response.success)
                {
                    // Update the title
                    this.$trigger.find('.label').text(response.title);

                    this.removeHud();
                }
            }, this));
        },

        _showSpinner: function ()
        {
            this.removeHud();
            this.hud = new Garnish.HUD(this.$trigger, $('<div class="body"><div class="spinner big"></div></div>'));
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
