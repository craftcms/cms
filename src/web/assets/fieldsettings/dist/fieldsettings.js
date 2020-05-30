(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.FieldSettingsToggle = Garnish.Base.extend({
        $toggle: null,
        $container: null,
        namespace: null,
        currentType: null,
        settings: null,

        _cancelToken: null,
        _ignoreFailedRequest: false,

        init: function(toggle, container, namespace) {
            this.$toggle = $(toggle);
            this.$container = $(container);
            this.namespace = namespace;
            this.currentType = this.$toggle.val();
            this.settings = {};
            this.addListener(this.$toggle, 'change', 'handleToggleChange');
        },

        handleToggleChange: function() {
            // Cancel the current request
            if (this._cancelToken) {
                this._ignoreFailedRequest = true;
                this._cancelToken.cancel();
                Garnish.requestAnimationFrame(() => {
                    this._ignoreFailedRequest = false;
                });
            }

            // Save & detach the current settings
            this.settings[this.currentType] = this.$container.children().detach();

            this.currentType = this.$toggle.val();

            if (typeof this.settings[this.currentType] !== 'undefined') {
                this.settings[this.currentType].appendTo(this.$container);
                return;
            }

            // Show a spinner
            this.$container.html('<div class="zilch"><div class="spinner"></div></div>');

            // Create a cancel token
            this._cancelToken = axios.CancelToken.source();

            let data = {
                type: this.currentType,
            };
            if (this.namespace) {
                data.namespace = this.namespace.replace(/__TYPE__/g, this.currentType);
            }

            Craft.sendActionRequest('POST', 'fields/render-settings', {
                cancelToken: this._cancelToken.token,
                data: data
            }).then(response => {
                this.$container.html(response.data.settingsHtml || '');
                Craft.initUiElements(this.$container);
                Craft.appendHeadHtml(response.data.headHtml);
                Craft.appendFootHtml(response.data.footHtml);
            }).catch(() => {
                if (!this._ignoreFailedRequest) {
                    Craft.cp.displayError(Craft.t('app', 'A server error occurred.'));
                }
            });
        },
    });
})(jQuery);
