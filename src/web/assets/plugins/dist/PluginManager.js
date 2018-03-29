(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.PluginManager = Garnish.Base.extend(
        {
            init: function() {
                Craft.postActionRequest('app/get-plugin-license-info', function(response, textStatus) {
                    if (textStatus === 'success') {
                        for (var handle in response) {
                            if (response.hasOwnProperty(handle)) {
                                (new Plugin($('#plugin-'+handle))).update(response[handle]);
                            }
                        }
                    }
                });
            }
        }
    );

    var Plugin = Garnish.Base.extend(
        {
            $row: null,
            $keyContainer: null,
            $keyInput: null,
            $spinner: null,
            handle: null,
            updateTimeout: null,

            init: function($row) {
                this.$row = $row;
                this.$keyContainer = $row.find('.license-key')
                this.$keyInput = this.$keyContainer.find('input.text').removeAttr('readonly');
                this.$spinner = $row.find('.spinner');
                this.handle = this.$row.data('handle');
                this.addListener(this.$keyInput, 'focus', 'onKeyFocus')
                this.addListener(this.$keyInput, 'textchange', 'onKeyChange');
            },

            getKey: function() {
                return this.$keyInput.val().replace(/\-/g, '').toUpperCase();
            },

            onKeyFocus: function() {
                this.$keyInput.select();
            },

            onKeyChange: function() {
                if (this.updateTimeout) {
                    clearTimeout(this.updateTimeout);
                }
                var key = this.getKey();
                if (key.length === 0 || key.length === 24) {
                    // normalize
                    var userKey = key.replace(/.{4}/g, '$&-').substr(0, 29).toUpperCase();
                    this.$keyInput
                        .val(userKey)
                        .data('garnish-textchange-value', userKey);
                    this.updateTimeout = setTimeout($.proxy(this, 'updateLicenseStatus'), 100);
                }
            },

            updateLicenseStatus: function() {
                this.$spinner.removeClass('hidden');
                Craft.postActionRequest('app/update-plugin-license', {handle: this.handle, key: this.getKey()}, $.proxy(function(response, textStatus) {
                    this.$spinner.addClass('hidden');
                    if (textStatus === 'success') {
                        this.update(response);
                    }
                }, this))
            },

            update: function(info) {
                // update the status icon
                var $oldIcon = this.$row.find('.license-key-status');
                if (info.licenseKeyStatus == 'valid' || info.hasIssues) {
                    var $newIcon = $('<span/>', {'class': 'license-key-status '+info.licenseKeyStatus});
                    if ($oldIcon.length) {
                        $oldIcon.replaceWith($newIcon);
                    } else {
                        $newIcon.appendTo(this.$row.find('.icon'));
                    }
                } else if ($oldIcon.length) {
                    $oldIcon.remove();
                }

                // show the license key?
                var showLicenseKey = info.licenseKey || info.licenseKeyStatus !== 'unknown';
                if (showLicenseKey) {
                    this.$keyContainer.removeClass('hidden');
                } else {
                    this.$keyContainer.addClass('hidden');
                }

                // update the license key input class
                if (showLicenseKey && info.hasIssues) {
                    this.$keyInput.addClass('error');
                } else {
                    this.$keyInput.removeClass('error');
                }

                // add the error message
                var $oldError = this.$row.find('p.error');
                if (showLicenseKey && info.licenseStatusMessage) {
                    var $newError = $('<p/>', {'class': 'error', html: info.licenseStatusMessage});
                    if ($oldError.length) {
                        $oldError.replaceWith($newError);
                    } else {
                        $newError.insertAfter(this.$row.find('.license-key'));
                    }
                } else {
                    $oldError.remove();
                }
            }
        }
    )
})(jQuery);
