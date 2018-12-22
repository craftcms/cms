(function($) {

    Craft.PluginStoreOauthCallback = Garnish.Base.extend(
        {
            $graphic: null,
            $status: null,
            errorDetails: null,
            data: null,
            settings: null,

            init: function(settings) {
                this.setSettings(settings);
                
                this.$graphic = $('#graphic');
                this.$status = $('#status');

                setTimeout($.proxy(function() {
                    this.postActionRequest();
                }, this), 500);
            },

            postActionRequest: function() {
                var fragmentString = window.location.hash.substr(1);
                var fragments = $.parseFragmentString(fragmentString);

                Craft.postActionRequest('plugin-store/save-token', fragments, $.proxy(function(response, textStatus, jqXHR)
                {
                    if (textStatus == 'success') {

                        if(response.error) {
                            this.showError(response.error);
                        } else {

                            this.updateStatus('<p>' + Craft.t('app', 'Connected!') + '</p>');
                            this.$graphic.addClass('success');

                            // Redirect to the Dashboard in half a second
                            setTimeout($.proxy(function() {
                                if(typeof(this.settings.redirectUrl) != 'undefined') {
                                    window.location = this.settings.redirectUrl;
                                } else {
                                    window.location = Craft.getCpUrl('myaccount');
                                }
                            }, this), 500);
                        }
                    } else {
                        this.showFatalError(jqXHR);
                    }
                }, this));
            },

            showFatalError: function(jqXHR) {
                this.$graphic.addClass('error');
                var statusHtml =
                    '<p>' + Craft.t('app', 'A fatal error has occurred:') + '</p>' +
                    '<div id="error" class="code">' +
                    '<p><strong class="code">' + Craft.t('app', 'Status:') + '</strong> ' + Craft.escapeHtml(jqXHR.statusText) + '</p>' +
                    '<p><strong class="code">' + Craft.t('app', 'Response:') + '</strong> ' + Craft.escapeHtml(jqXHR.responseText) + '</p>' +
                    '</div>' +
                    '<a class="btn submit big" href="mailto:support@craftcms.com' +
                    '?subject=' + encodeURIComponent('Craft update failure') +
                    '&body=' + encodeURIComponent(
                        'Describe what happened here.\n\n' +
                        '-----------------------------------------------------------\n\n' +
                        'Status: ' + jqXHR.statusText + '\n\n' +
                        'Response: ' + jqXHR.responseText
                    ) +
                    '">' +
                    Craft.t('app', 'Send for help') +
                    '</a>';

                this.updateStatus(statusHtml);
            },

            updateStatus: function(html) {
                this.$status.html(html);
            },

            showError: function(msg) {
                this.$graphic.addClass('error');
                this.updateStatus('<p>' + msg + '</p>');

                var $buttonContainer = $('<div id="junction-buttons"/>').appendTo(this.$status);

                $cancelBtn = $('<a/>', {
                    'class': 'btn big',
                    'href': Craft.getCpUrl('myaccount'),
                    text: "Cancel",
                }).appendTo($buttonContainer);

                $retryBtn = $('<a/>', {
                    'class': 'btn big',
                    'href': Craft.getActionUrl('plugin-store/connect'),
                    text: "Try again",
                }).appendTo($buttonContainer);
            }
        });

})(jQuery);