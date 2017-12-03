(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.UpdatesUtility = Garnish.Base.extend(
        {
            $body: null,
            totalAvailableUpdates: 0,
            criticalUpdateAvailable: false,
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

                            if (this.showUpdateAllBtn && this.updates.length > 1) {
                                $('<a/>', {
                                    'class': 'btn submit',
                                    text: Craft.t('app', 'Update all'),
                                    href: this.buildUpdateUrl(this.updates)
                                }).insertAfter($('#header').children('h1'));
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

            buildUpdateUrl: function(updates)
            {
                return Craft.getUrl('update', {
                    install: this.buildRequirements(updates)
                });
            },

            buildRequirements: function(updates)
            {
                var requirements = [];

                for (var i = 0; i < updates.length; i++) {
                    requirements.push(updates[i].updateInfo.handle+':'+updates[i].updateInfo.latestAllowedVersion);
                }

                return requirements.join(',');
            }
        }
    );

    var Update = Garnish.Base.extend(
        {
            updateInfo: null,
            isPlugin: null,
            latestVersion: null,

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
                    $('<blockquote class="note ineligible"><p>'+this.updateInfo.statusText+'</p>').insertBefore(this.$releaseContainer);

                    if (this.updateInfo.status === 'expired' || this.updateInfo.latestAllowedVersion === null) {
                        this.updatesPage.showUpdateAllBtn = false;
                    }
                }
            },

            canUpdateToLatest: function()
            {
                return this.updateInfo.releases.length && this.updateInfo.latestAllowedVersion === this.updateInfo.releases[0].version;
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
                if (this.latestAllowedVersion === null) {
                    return;
                }

                var $buttonContainer = $('<div class="buttons right"/>').appendTo(this.$header);
                $('<a/>', {
                    'class': 'btn submit',
                    text: this.updateInfo.ctaText,
                    href: typeof this.updateInfo.ctaUrl !== 'undefined' ? this.updateInfo.ctaUrl : this.updatesPage.buildUpdateUrl([this])
                }).appendTo($buttonContainer);
            },

            initReleases: function() {
                for (var i = 0; i < this.updateInfo.releases.length; i++) {
                    if (this.latestAllowedVersion === null && this.updateInfo.releases[i].allowed) {
                        this.latestAllowedVersion = this.updateInfo.releases[i].version;
                    }

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
