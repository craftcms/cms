// define the Assets global
if (typeof Assets == 'undefined')
{
    Assets = {};
}


/**
 * File Manager.
 */
Assets.AssetEditor = Garnish.Base.extend({

        hud: null,
        asetId: 0,
        requestId: 0,
        $trigger: null,

        init: function(assetId, $trigger)
        {
            this.assetId = assetId;
            this.$trigger = $trigger;
        },

        show: function ()
        {
            var params = {
                requestId: ++this.requestId,
                fileId: this.assetId
            };

            this._showSpinner();

            // Create a new HUD
            Craft.postActionRequest('assets/viewFile', params, $.proxy(function(data, textStatus) {

                this._removeHud();

                if (data.requestId != this.requestId) {
                    return;
                }

                $hudHtml = $(data.headHtml + data.bodyHtml + data.footHtml);

                this.hud = new Garnish.HUD(this.$trigger, $hudHtml, {
                    hudClass: 'hud assetshud',
                    triggerSpacing: 10,
                    tipWidth: 30
                });

                Craft.initUiElements($hudHtml);
                this.addListener($hudHtml.find('form'), 'submit', $.proxy(this, '_saveAssetDetails'));


            }, this));
        },

        _saveAssetDetails: function (event)
        {
            event.preventDefault();

            $form = $(event.currentTarget);

            var params = $form.serialize();

            this._showSpinner();

            Craft.postActionRequest('assets/saveFileContent', params, $.proxy(function(data, textStatus) {
                this._removeHud();
            }, this));
        },

        _showSpinner: function ()
        {
            this._removeHud();
            this.hud = new Garnish.HUD(this.$trigger, $('<div class="body"><div class="spinner big"></div></div>'));
        },

        _removeHud: function ()
        {
            if (this.hud !== null)
            {
                this.hud.hide();
                delete this.hud;
            }
        }

    }
);
