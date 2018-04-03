(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.PluginManager = Garnish.Base.extend(
        {
            init: function() {
                Craft.postActionRequest('app/get-plugin-license-info', $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        for (var handle in response) {
                            if (response.hasOwnProperty(handle)) {
                                if (!response[handle].isInstalled) {
                                    this.addUninstalledPluginRow(handle, response[handle]);
                                } else {
                                    (new Plugin($('#plugin-' + handle))).update(response[handle]);
                                }
                            }
                        }
                    }
                }, this));
            },

            addUninstalledPluginRow: function(handle, info) {
                var $table = $('#plugins');
                if (!$table.length) {
                    $table = $('<table/>', {
                        id: 'plugins',
                        'class': 'data fullwidth collapsible',
                        html: '<tbody></tbody>'
                    });
                    $('#no-plugins').replaceWith($table);
                }

                var $row = $('<tr/>')
                    .appendTo($table.children('tbody'))
                    .append(
                        $('<th/>')
                            .append(
                                $('<div/>', {'class': 'plugin-infos'})
                                    .append(
                                        $('<div/>', {'class': 'icon'})
                                            .append(
                                                $('<img/>', {src: info.iconUrl})
                                            )
                                    )
                                    .append(
                                        $('<div/>', {'class': 'details'})
                                            .append(
                                                $('<h2/>', {text: info.name})
                                            )
                                            .append(
                                                info.description
                                                    ? $('<p/>', {text: info.description})
                                                    : $()
                                            )
                                            .append(
                                                info.documentationUrl
                                                    ? $('<p/>', {'class': 'links'})
                                                    .append(
                                                        $('<a/>', {
                                                            href: info.documentationUrl,
                                                            target: '_blank',
                                                            text: Craft.t('app', 'Documentation')
                                                        })
                                                    )
                                                    : $()
                                            )
                                            .append(
                                                $('<div/>', {'class': 'flex license-key'})
                                                    .append(
                                                        $('<div/>')
                                                            .append(
                                                                $('<input/>', {
                                                                    'class': 'text code',
                                                                    size: 29,
                                                                    maxlength: 29,
                                                                    value: Craft.PluginManager.normalizeUserKey(info.licenseKey),
                                                                    readonly: true,
                                                                    disabled: true
                                                                })
                                                            )
                                                    )
                                            )
                                    )
                            )
                    )
                    .append(
                        $('<td/>', {
                            'class': 'nowrap',
                            'data-title': Craft.t('app', 'Status')
                        })
                            .append(
                                $('<span/>', {'class': 'status'})
                            )
                            .append(
                                $('<span/>', {'class': 'light', text: Craft.t('app', 'Missing')})
                            )
                    )
                    .append(
                        info.latestVersion
                            ? $('<td/>', {
                                'class': 'nowrap thin',
                                'data-title': Craft.t('app', 'Action')
                            })
                            .append(
                                $('<form/>', {
                                    method: 'post',
                                    'accept-charset': 'UTF-8',
                                })
                                    .append(
                                        $('<input/>', {
                                            type: 'hidden',
                                            name: 'action',
                                            value: 'pluginstore/install'
                                        })
                                    )
                                    .append(
                                        $('<input/>', {
                                            type: 'hidden',
                                            name: 'packageName',
                                            value: info.packageName
                                        })
                                    )
                                    .append(
                                        $('<input/>', {
                                            type: 'hidden',
                                            name: 'handle',
                                            value: handle
                                        })
                                    )
                                    .append(
                                        $('<input/>', {
                                            type: 'hidden',
                                            name: 'version',
                                            value: info.latestVersion
                                        })
                                    )
                                    .append(
                                        $('<input/>', {
                                            type: 'hidden',
                                            name: 'licenseKey',
                                            value: info.licenseKey
                                        })
                                    )
                                    .append(
                                        $('<input/>', {
                                            type: 'hidden',
                                            name: 'return',
                                            value: 'settings/plugins'
                                        })
                                    )
                                    .append(Craft.getCsrfInput())
                                    .append(
                                        $('<div/>', {'class': 'btngroup'})
                                            .append(
                                                $('<div/>', {
                                                    'class': 'btn menubtn',
                                                    'data-icon': 'settings'
                                                })
                                            )
                                            .append(
                                                $('<div/>', {
                                                    'class': 'menu',
                                                    'data-align': 'right',
                                                })
                                                    .append(
                                                        $('<ul/>')
                                                            .append(
                                                                $('<li/>')
                                                                    .append(
                                                                        $('<a/>', {
                                                                            'class': 'formsubmit',
                                                                            text: Craft.t('app', 'Install')
                                                                        })
                                                                    )
                                                            )
                                                    )
                                            )
                                    )
                            )
                            : $()
                    )
                ;

                Craft.initUiElements($row);
            }
        }, {
            normalizeUserKey: function(key) {
                return key.replace(/.{4}/g, '$&-').substr(0, 29).toUpperCase();
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
                    var userKey = Craft.PluginManager.normalizeUserKey(key);
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
                    var $newIcon = $('<span/>', {'class': 'license-key-status ' + info.licenseKeyStatus});
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
