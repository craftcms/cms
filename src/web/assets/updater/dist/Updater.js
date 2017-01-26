(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.Updater = Garnish.Base.extend(
        {
            $graphic: null,
            $status: null,
            errorDetails: null,
            data: null,

            init: function(data) {
                this.$graphic = $('#graphic');
                this.$status = $('#status');

                if (!data || !data.handle) {
                    this.showError(Craft.t('app', 'Unable to determine what to update.'));
                    return;
                }

                this.data = data;

                this.postActionRequest('update/prepare');
            },

            updateStatus: function(html) {
                this.$status.html(html);
            },

            showError: function(msg) {
                this.$graphic.addClass('error');
                this.updateStatus('<p>' + msg + '</p>');
            },

            postActionRequest: function(action) {
                var data = {
                    data: this.data
                };

                Craft.postActionRequest(action, data, $.proxy(function(response, textStatus, jqXHR) {
                    if (textStatus == 'success') {
                        this.processResponse(response);
                    }
                    else {
                        this.showFatalError(jqXHR);
                    }

                }, this), {
                    complete: $.noop
                });
            },

            processResponse: function(response) {
                if (response.data) {
                    this.data = response.data;
                }

                if (response.errorDetails) {
                    this.errorDetails = response.errorDetails;
                }

                if (response.nextStatus) {
                    this.updateStatus('<p>' + response.nextStatus + '</p>');
                }

                if (response.junction) {
                    this.showJunction(response.junction);
                }

                if (response.nextAction) {
                    this.postActionRequest(response.nextAction);
                }

                if (response.finished) {
                    this.onFinish(response.returnUrl, !!response.rollBack);
                }
            },

            showJunction: function(options) {
                this.$graphic.addClass('error');
                var $buttonContainer = $('<div id="junction-buttons"/>').appendTo(this.$status);

                for (var i = 0; i < options.length; i++) {
                    var option = options[i],
                        $button = $('<div/>', {
                            'class': 'btn big',
                            text: option.label
                        }).appendTo($buttonContainer);

                    this.addListener($button, 'click', option, 'onJunctionSelection');
                }
            },

            onJunctionSelection: function(ev) {
                this.$graphic.removeClass('error');
                this.processResponse(ev.data);
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

            onFinish: function(returnUrl, rollBack) {
                if (this.errorDetails) {
                    this.$graphic.addClass('error');
                    var errorText = Craft.t('app', 'Craft CMS was unable to install this update :(') + '<br /><p>';

                    if (rollBack) {
                        errorText += Craft.t('app', 'The site has been restored to the state it was in before the attempted update.') + '</p><br /><p>';
                    }
                    else {
                        errorText += Craft.t('app', 'No files have been updated and the database has not been touched.') + '</p><br /><p>';
                    }

                    errorText += this.errorDetails + '</p>';
                    this.updateStatus('<p>' + errorText + '</p>');
                }
                else {
                    this.updateStatus('<p>' + Craft.t('app', 'All done!') + '</p>');
                    this.$graphic.addClass('success');

                    // Redirect to the Dashboard in half a second
                    setTimeout(function() {
                        if (returnUrl) {
                            window.location = Craft.getUrl(returnUrl);
                        }
                        else {
                            window.location = Craft.getUrl('dashboard');
                        }
                    }, 500);
                }
            }
        });
})(jQuery);
