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
                                var $updateAllBtn = $('<div/>', {'class': 'btn submit', text: Craft.t('app', 'Update all')})
                                    .appendTo($('<div/>', {id: 'extra-headers'}).appendTo(Craft.cp.$pageHeader));
                                this.addListener($updateAllBtn, 'click', 'updateAll');
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

            updateAll: function()
            {
                this.redirectToUpdate(this.updates);
            },

            redirectToUpdate: function(updates)
            {
                window.location.href = this.buildUpdateUrl(updates);
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
                var $btn = $('<div/>', {
                    'class': 'btn submit',
                    text: this.updateInfo.ctaText
                }).appendTo($buttonContainer);

                this.addListener($btn, 'click', function() {
                    if (typeof this.updateInfo.ctaUrl !== 'undefined') {
                        window.location.href = this.updateInfo.ctaUrl;
                    } else {
                        this.updatesPage.redirectToUpdate([this]);
                    }
                });
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
            $toggle: null,

            init: function(update, releaseInfo) {
                this.update = update;
                this.releaseInfo = releaseInfo;
                this.notesId = 'notes-'+Math.floor(Math.random() * 1000000);

                this.createContainer();
                this.createHeading();
                this.createReleaseNotes();

                new Craft.FieldToggle(this.$toggle);
            },

            createContainer: function() {
                this.$container = $('<div class="pane release"/>').appendTo(this.update.$releaseContainer);

                if (this.releaseInfo.critical) {
                    this.$container.addClass('critical');
                }
            },

            createHeading: function() {
                this.$toggle = $('<a/>', {'class': 'fieldtoggle', 'data-target': this.notesId}).appendTo(this.$container);
                $('<h2/>', {text: this.releaseInfo.version}).appendTo(this.$toggle);
                if (this.releaseInfo.critical) {
                    $('<strong/>', {'class': 'critical', text: Craft.t('app', 'Critical')}).appendTo(this.$toggle);
                }
                $('<span/>', {'class': 'date', text: Craft.formatDate(this.releaseInfo.date)}).appendTo(this.$toggle);
            },

            createReleaseNotes: function() {
                $('<div/>', {id: this.notesId, 'class': 'hidden'})
                    .appendTo(this.$container)
                    .append($('<div/>', {'class': 'release-notes'}).html(this.releaseInfo.notes));
            }
        },
        {
            maxInitialUpdateHeight: 500
        }
    );
})(jQuery);
