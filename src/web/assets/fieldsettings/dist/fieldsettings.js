(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.FieldSettingsToggle = Garnish.Base.extend({
        $toggle: null,
        $container: null,
        namespace: null,
        currentType: null,
        typeSettings: null,

        _cancelToken: null,
        _ignoreFailedRequest: false,

        init: function(toggle, container, namespace, settings) {
            this.$toggle = $(toggle);
            this.$container = $(container);
            this.namespace = namespace;
            this.currentType = this.$toggle.val();
            this.typeSettings = {};
            this.setSettings(settings, Craft.FieldSettingsToggle.defaults);
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
            this.typeSettings[this.currentType] = this.$container.children().detach();

            this.currentType = this.$toggle.val();

            if (typeof this.typeSettings[this.currentType] !== 'undefined') {
                this.typeSettings[this.currentType].appendTo(this.$container);
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
                let $settings = $(response.data.settingsHtml || '');
                if (this.settings.wrapWithTypeClassDiv) {
                    $settings = $('<div/>', {
                        id: Craft.formatInputId(this.currentType)
                    }).append($settings);
                }
                this.$container.html('').append($settings);
                Craft.initUiElements(this.$container);
                Craft.appendHeadHtml(response.data.headHtml);
                Craft.appendFootHtml(response.data.footHtml);
            }).catch(() => {
                if (!this._ignoreFailedRequest) {
                    Craft.cp.displayError(Craft.t('app', 'A server error occurred.'));
                    this.$container.html('');
                }
            });
        },
    }, {
        defaults: {
            wrapWithTypeClassDiv: false,
        }
    });
})(jQuery);
