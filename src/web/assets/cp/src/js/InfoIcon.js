/** global: Craft */
/** global: Garnish */
/**
 * Info icon class
 */
Craft.InfoIcon = Garnish.Base.extend(
    {
        $icon: null,
        hud: null,

        init: function(icon) {
            this.$icon = $(icon);

            this.addListener(this.$icon, 'click', 'showHud');
        },

        showHud: function() {
            if (!this.hud) {
                this.hud = new Garnish.HUD(this.$icon, this.$icon.html(), {
                    hudClass: 'hud info-hud',
                    closeOtherHUDs: false
                });
            }
            else {
                this.hud.show();
            }
        }
    });
