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
            if (this.$icon.data('infoicon')) {
                Garnish.log('Double-instantiating an info icon on an element');
                this.$icon.data('infoicon').destroy();
            }
            this.$icon.data('infoicon', this);
            this.addListener(this.$icon, 'click', 'showHud');
        },

        showHud: function(ev) {
            ev.preventDefault();
            ev.stopPropagation();

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
