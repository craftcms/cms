(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.UpdatesUtility = Garnish.Base.extend(
        {
            $body: null,
            totalAvailableUpdates: 0,
            criticalUpdateAvailable: false,
            allowUpdates: null,
            showUpdateAllBtn: true,
            updates: null,

            init: function() {
                this.$body = $('#content');

                var $graphic = $('#graphic'),
                    $status = $('#status');

                this.updates = [];

                var data = {
                    forceRefresh: true,
                    includeDetails: true
                };

                Craft.postActionRequest('app/check-for-updates', data, $.proxy(function(response, textStatus) {
                    if (textStatus !== 'success' || response.error) {
                        var error = Craft.t('app', 'An unknown error occurred.');

                        if (response.errors && response.errors.length) {
                            error = response.errors[0];
                        }
                        else if (response.error) {
                            error = response.error;
                        }

                        $graphic.addClass('error');
                        $status.text(error);
                    }
                    else {
                        this.allowUpdates = response.allowUpdates;

                        // Craft CMS update?
                        if (response.updates.cms) {
                            this.processUpdate(response.updates.cms, false);
                        }

                        // Plugin updates?
                        if (response.updates.plugins && response.updates.plugins.length) {
                            for (var i = 0; i < response.updates.plugins.length; i++) {
                                this.processUpdate(response.updates.plugins[i], true);
                            }
                        }

                        if (this.totalAvailableUpdates) {
                            $graphic.remove();
                            $status.remove();

                            // Add the page title
                            var headingText;

                            if (this.totalAvailableUpdates === 1) {
                                headingText = Craft.t('app', '1 Available Update');
                            }
                            else {
                                headingText = Craft.t('app', '{num} Available Updates', {num: this.totalAvailableUpdates});
                            }

                            $('#page-title').find('h1').text(headingText);

                            if (this.allowUpdates && this.showUpdateAllBtn && this.updates.length > 1) {
                                this.createUpdateForm(Craft.t('app', 'Update all'), this.updates)
                                    .insertAfter($('#header').children('h1'));
                            }
                        } else {
                            $graphic.addClass('success');
                            $status.text(Craft.t('app', 'You’re all up-to-date!'));
                        }
                    }
                }, this));
            },

            processUpdate: function(updateInfo, isPlugin) {
                if (!updateInfo.releases.length && updateInfo.status !== 'expired') {
                    return;
                }

                this.totalAvailableUpdates++;

                this.updates.push(new Update(this, updateInfo, isPlugin));
            },

            createUpdateForm: function(label, updates)
            {
                var $form = $('<form/>', {
                    method: 'post'
                });

                $form.append(Craft.getCsrfInput());
                $form.append($('<input/>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'updater'
                }));
                $form.append($('<input/>', {
                    type: 'hidden',
                    name: 'return',
                    value: 'utilities/updates'
                }));

                for (var i = 0; i < updates.length; i++) {
                    $form.append($('<input/>', {
                        type: 'hidden',
                        name: 'install['+updates[i].updateInfo.handle+']',
                        value: updates[i].updateInfo.latestVersion
                    }));
                }

                $form.append($('<input/>', {
                    type: 'submit',
                    value: label,
                    class: 'btn submit'
                }));

                return $form;
            }
        }
    );

    var Update = Garnish.Base.extend(
        {
            updateInfo: null,
            isPlugin: null,

            $container: null,
            $header: null,
            $contents: null,
            $releaseContainer: null,
            $showAllLink: null,

            licenseHud: null,
            $licenseSubmitBtn: null,
            licenseSubmitAction: null,

            init: function(updatesPage, updateInfo, isPlugin) {
                this.updatesPage = updatesPage;
                this.updateInfo = updateInfo;
                this.isPlugin = isPlugin;

                this.createPane();
                this.initReleases();
                this.createHeading();
                this.createCta();

                // Any ineligible releases?
                if (this.updateInfo.status !== 'eligible') {
                    $('<blockquote class="note ineligible"><p>'+this.updateInfo.statusText+'</p></blockquote>').insertBefore(this.$releaseContainer);

                    if (this.updateInfo.status === 'expired' || this.updateInfo.latestVersion === null) {
                        this.updatesPage.showUpdateAllBtn = false;
                    }
                }
            },

            createPane: function() {
                this.$container = $('<div class="update"/>').appendTo(this.updatesPage.$body);
                this.$header = $('<div class="update-header"/>').appendTo(this.$container);
                this.$contents = $('<div class="readable"/>').appendTo(this.$container);
                this.$releaseContainer = $('<div class="releases"/>').appendTo(this.$contents);
            },

            createHeading: function() {
                $('<div class="readable left"/>').appendTo(this.$header).append(
                    $('<h1/>', {text: this.updateInfo.name})
                );
            },

            createCta: function() {
                if (!this.updatesPage.allowUpdates || !this.updateInfo.latestVersion) {
                    return;
                }

                var $buttonContainer = $('<div class="buttons right"/>').appendTo(this.$header);
                if (typeof this.updateInfo.ctaUrl !== 'undefined') {
                    $('<a/>', {
                        'class': 'btn submit',
                        text: this.updateInfo.ctaText,
                        href: this.updateInfo.ctaUrl
                    }).appendTo($buttonContainer);
                } else {
                    this.updatesPage.createUpdateForm(this.updateInfo.ctaText, [this])
                        .appendTo($buttonContainer);
                }
            },

            initReleases: function() {
                for (var i = 0; i < this.updateInfo.releases.length; i++) {
                    new Release(this, this.updateInfo.releases[i]);
                }
            }
        }
    );

    var Release = Garnish.Base.extend(
        {
            update: null,
            releaseInfo: null,
            notesId: null,

            $container: null,
            $headingContainer: null,

            init: function(update, releaseInfo) {
                this.update = update;
                this.releaseInfo = releaseInfo;
                this.notesId = 'notes-'+Math.floor(Math.random() * 1000000);

                this.createContainer();
                this.createHeading();

                if (this.releaseInfo.notes) {
                    this.createReleaseNotes();
                    new Craft.FieldToggle(this.$headingContainer);
                }
            },

            createContainer: function() {
                this.$container = $('<div class="pane release"/>').appendTo(this.update.$releaseContainer);

                if (this.releaseInfo.critical) {
                    this.$container.addClass('critical');
                }
            },

            createHeading: function() {
                if (this.releaseInfo.notes) {
                    this.$headingContainer = $('<a/>', {'class': 'release-info fieldtoggle', 'data-target': this.notesId});
                } else {
                    this.$headingContainer = $('<div/>', {'class': 'release-info'});
                }
                this.$headingContainer.appendTo(this.$container);
                $('<h2/>', {text: this.releaseInfo.version}).appendTo(this.$headingContainer);
                if (this.releaseInfo.critical) {
                    $('<strong/>', {'class': 'critical', text: Craft.t('app', 'Critical')}).appendTo(this.$headingContainer);
                }
                if (this.releaseInfo.date) {
                    $('<span/>', {'class': 'date', text: Craft.formatDate(this.releaseInfo.date)}).appendTo(this.$headingContainer);
                }
            },

            createReleaseNotes: function() {
                var $notes = $('<div/>', {id: this.notesId})
                    .appendTo(this.$container)
                    .append($('<div/>', {'class': 'release-notes'}).html(this.releaseInfo.notes));

                // Auto-expand if this is a critical release, or there are any tips/warnings in the release notes
                if (this.releaseInfo.critical || $notes.find('blockquote').length) {
                    this.$headingContainer.addClass('expanded');
                } else {
                    $notes.addClass('hidden');
                }
            }
        },
        {
            maxInitialUpdateHeight: 500
        }
    );
})(jQuery);
