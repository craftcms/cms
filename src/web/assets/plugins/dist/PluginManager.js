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
                                if (!response[handle].isComposerInstalled) {
                                    this.addUninstalledPluginRow(handle, response[handle]);
                                } else {
                                    (new Plugin($('#plugin-' + handle))).update(response[handle], handle);
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
                                                        $('<div />', {'class': 'pane'})
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
                if (typeof key !== 'string') {
                    return '';
                }
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
            $buyBtn: null,
            handle: null,
            updateTimeout: null,

            init: function($row) {
                this.$row = $row;
                this.$keyContainer = $row.find('.license-key')
                this.$keyInput = this.$keyContainer.find('input.text').removeAttr('readonly');
                this.$buyBtn = this.$keyContainer.find('.btn');
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

            update: function(info, handle) {
                // update the status icon
                var $oldIcon = this.$row.find('.license-key-status');
                if (info.licenseKeyStatus == 'valid' || info.licenseIssues.length) {
                    var $newIcon = $('<span/>', {'class': 'license-key-status ' + (info.licenseIssues.length === 0 ? 'valid' : '')});
                    if ($oldIcon.length) {
                        $oldIcon.replaceWith($newIcon);
                    } else {
                        $newIcon.appendTo(this.$row.find('.icon'));
                    }
                } else if ($oldIcon.length) {
                    $oldIcon.remove();
                }

                // add the edition/trial badge
                var $oldEdition = this.$row.find('.edition');
                if (info.hasMultipleEditions || info.isTrial) {
                    var $newEdition = info.upgradeAvailable
                        ? $('<a/>', {href: Craft.getUrl('plugin-store/' + handle), 'class': 'edition'})
                        : $('<div/>', {'class': 'edition'});
                    if (info.hasMultipleEditions) {
                        $('<div/>', {'class': 'edition-name', text: info.edition}).appendTo($newEdition);
                    }
                    if (info.isTrial) {
                        $('<div/>', {'class': 'edition-trial', text: Craft.t('app', 'Trial')}).appendTo($newEdition);
                    }
                    if ($oldEdition.length) {
                        $oldEdition.replaceWith($newEdition);
                    } else {
                        $newEdition.insertBefore(this.$row.find('.version'));
                    }
                } else if ($oldEdition.length) {
                    $oldEdition.remove();
                }

                // show the license key?
                var showLicenseKey = info.licenseKey || info.licenseKeyStatus !== 'unknown';
                if (showLicenseKey) {
                    this.$keyContainer.removeClass('hidden');
                } else {
                    this.$keyContainer.addClass('hidden');
                }

                // update the license key input class
                if (showLicenseKey && info.licenseIssues.length) {
                    this.$keyInput.addClass('error');
                } else {
                    this.$keyInput.removeClass('error');
                }

                // add the error message
                this.$row.find('p.error').remove();
                if (info.licenseIssues.length) {
                    var $issues = $();
                    var $p, $form, message;
                    for (var i = 0; i < info.licenseIssues.length; i++) {
                        switch (info.licenseIssues[i]) {
                            case 'wrong_edition':
                                message = Craft.t('app', 'This license is for the {name} edition.', {
                                    name: info.licensedEdition.charAt(0).toUpperCase() + info.licensedEdition.substring(1)
                                }) + ' <a class="btn submit small formsubmit">' + Craft.t('app', 'Switch') + '</a>';
                                break;
                            case 'mismatched':
                                message = Craft.t('app', 'This license is tied to another Craft install. Visit {url} to resolve.', {
                                    url: '<a href="https://id.craftcms.com" rel="noopener" target="_blank">id.craftcms.com</a>'
                                });
                                break;
                            case 'astray':
                                message = Craft.t('app', 'This license isn’t allowed to run version {version}.', {
                                    version: info.version
                                });
                                break;
                            case 'required':
                                message = Craft.t('app', 'A license key is required.');
                                break;
                            default:
                                message = Craft.t('app', 'Your license key is invalid.');
                        }

                        $p = $('<p/>', {'class': 'error', html: message});
                        if (info.licenseIssues[i] === 'wrong_edition') {
                            $form = $('<form/>', {
                                    method: 'post',
                                    'accept-charset': 'UTF-8',
                                })
                                .append(Craft.getCsrfInput())
                                .append(
                                    $('<input/>', {
                                        type: 'hidden',
                                        name: 'action',
                                        value: 'plugins/switch-edition'
                                    })
                                )
                                .append(
                                    $('<input/>', {
                                        type: 'hidden',
                                        name: 'pluginHandle',
                                        value: handle
                                    })
                                )
                                .append(
                                    $('<input/>', {
                                        type: 'hidden',
                                        name: 'edition',
                                        value: info.licensedEdition
                                    })
                                )
                                .append($p);

                            Craft.initUiElements($form);
                            $issues = $issues.add($form);
                        } else {
                            $issues = $issues.add($p);
                        }
                    }
                    $issues.insertAfter(this.$row.find('.license-key'));
                    Craft.initUiElements()
                }

                // show/hide the Buy button
                if (showLicenseKey && !info.licenseKey) {
                    this.$buyBtn.removeClass('hidden');
                    if (info.licenseIssues.length) {
                        this.$buyBtn.addClass('submit');
                    } else {
                        this.$buyBtn.removeClass('submit');
                    }
                } else {
                    this.$buyBtn.addClass('hidden');
                }
            }
        }
    )
})(jQuery);
