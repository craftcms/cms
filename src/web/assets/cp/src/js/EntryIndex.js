/** global: Craft */
/** global: Garnish */
/**
 * Entry index class
 */
Craft.EntryIndex = Craft.BaseElementIndex.extend(
    {
        publishableSections: null,
        $newEntryBtnGroup: null,
        $newEntryBtn: null,

        init: function(elementType, $container, settings) {
            this.on('selectSource', $.proxy(this, 'updateButton'));
            this.on('selectSite', $.proxy(this, 'updateButton'));
            this.base(elementType, $container, settings);
        },

        afterInit: function() {
            // Find which of the visible sections the user has permission to create new entries in
            this.publishableSections = [];

            for (var i = 0; i < Craft.publishableSections.length; i++) {
                var section = Craft.publishableSections[i];

                if (this.getSourceByKey('section:' + section.uid)) {
                    this.publishableSections.push(section);
                }
            }

            this.base();
        },

        getDefaultSourceKey: function() {
            // Did they request a specific section in the URL?
            if (this.settings.context === 'index' && typeof defaultSectionHandle !== 'undefined') {
                if (defaultSectionHandle === 'singles') {
                    return 'singles';
                }
                else {
                    for (var i = 0; i < this.$sources.length; i++) {
                        var $source = $(this.$sources[i]);

                        if ($source.data('handle') === defaultSectionHandle) {
                            return $source.data('key');
                        }
                    }
                }
            }

            return this.base();
        },

        updateButton: function() {
            if (!this.$source) {
                return;
            }

            var handle;

            // Get the handle of the selected source
            if (this.$source.data('key') === 'singles') {
                handle = 'singles';
            }
            else {
                handle = this.$source.data('handle');
            }

            // Update the New Entry button
            // ---------------------------------------------------------------------

            var i, href, label;

            if (this.publishableSections.length) {
                // Remove the old button, if there is one
                if (this.$newEntryBtnGroup) {
                    this.$newEntryBtnGroup.remove();
                }

                // Determine if they are viewing a section that they have permission to create entries in
                var selectedSection;

                if (handle) {
                    for (i = 0; i < this.publishableSections.length; i++) {
                        if (this.publishableSections[i].handle === handle) {
                            selectedSection = this.publishableSections[i];
                            break;
                        }
                    }
                }

                this.$newEntryBtnGroup = $('<div class="btngroup submit"/>');
                var $menuBtn;

                // If they are, show a primary "New entry" button, and a dropdown of the other sections (if any).
                // Otherwise only show a menu button
                if (selectedSection) {
                    href = this._getSectionTriggerHref(selectedSection);
                    label = (this.settings.context === 'index' ? Craft.t('app', 'New entry') : Craft.t('app', 'New {section} entry', {section: selectedSection.name}));
                    this.$newEntryBtn = $('<a class="btn submit add icon" ' + href + ' role="button" tabindex="0">' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newEntryBtnGroup);

                    if (this.settings.context !== 'index') {
                        this.addListener(this.$newEntryBtn, 'click', function(ev) {
                            this._openCreateEntryModal(ev.currentTarget.getAttribute('data-id'));
                        });
                    }

                    if (this.publishableSections.length > 1) {
                        $menuBtn = $('<button/>', {
                            type: 'button',
                            class: 'btn submit menubtn',
                        }).appendTo(this.$newEntryBtnGroup);
                    }
                }
                else {
                    this.$newEntryBtn = $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit add icon menubtn',
                        text: Craft.t('app', 'New entry'),
                    }).appendTo(this.$newEntryBtnGroup);
                }

                if ($menuBtn) {
                    var menuHtml = '<div class="menu"><ul>';

                    for (i = 0; i < this.publishableSections.length; i++) {
                        var section = this.publishableSections[i];

                        if (
                            (this.settings.context === 'index' && $.inArray(this.siteId, section.sites) !== -1) ||
                            (this.settings.context !== 'index' && section !== selectedSection)
                        ) {
                            href = this._getSectionTriggerHref(section);
                            label = (this.settings.context === 'index' ? section.name : Craft.t('app', 'New {section} entry', {section: section.name}));
                            menuHtml += '<li><a ' + href + '>' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    $(menuHtml).appendTo(this.$newEntryBtnGroup);
                    var menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context !== 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateEntryModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newEntryBtnGroup);
            }

            // Update the URL if we're on the Entries index
            // ---------------------------------------------------------------------

            if (this.settings.context === 'index' && typeof history !== 'undefined') {
                var uri = 'entries';

                if (handle) {
                    uri += '/' + handle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }
        },

        _getSectionTriggerHref: function(section) {
            if (this.settings.context === 'index') {
                var uri = 'entries/' + section.handle + '/new';
                let params = {};
                if (this.siteId) {
                    for (var i = 0; i < Craft.sites.length; i++) {
                        if (Craft.sites[i].id == this.siteId) {
                            params.site = Craft.sites[i].handle;
                        }
                    }
                }
                return 'href="' + Craft.getUrl(uri, params) + '"';
            } else {
                return 'data-id="' + section.id + '"';
            }
        },

        _openCreateEntryModal: function(sectionId) {
            if (this.$newEntryBtn.hasClass('loading')) {
                return;
            }

            // Find the section
            var section;

            for (var i = 0; i < this.publishableSections.length; i++) {
                if (this.publishableSections[i].id == sectionId) {
                    section = this.publishableSections[i];
                    break;
                }
            }

            if (!section) {
                return;
            }

            this.$newEntryBtn.addClass('inactive');
            var newEntryBtnText = this.$newEntryBtn.text();
            this.$newEntryBtn.text(Craft.t('app', 'New {section} entry', {section: section.name}));

            Craft.createElementEditor(this.elementType, {
                hudTrigger: this.$newEntryBtnGroup,
                siteId: this.siteId,
                attributes: {
                    sectionId: sectionId,
                    typeId: section.entryTypes[0].id,
                    enabled: section.canPublish ? 1 : 0,
                },
                onBeginLoading: $.proxy(function() {
                    this.$newEntryBtn.addClass('loading');
                }, this),
                onEndLoading: $.proxy(function() {
                    this.$newEntryBtn.removeClass('loading');
                }, this),
                onHideHud: $.proxy(function() {
                    this.$newEntryBtn.removeClass('inactive').text(newEntryBtnText);
                }, this),
                onSaveElement: $.proxy(function(response) {
                    // Make sure the right section is selected
                    var sectionSourceKey = 'section:' + section.uid;

                    if (this.sourceKey !== sectionSourceKey) {
                        this.selectSourceByKey(sectionSourceKey);
                    }

                    this.selectElementAfterUpdate(response.id);
                    this.updateElements();
                }, this)
            });
        }
    });

// Register it!
Craft.registerElementIndexClass('craft\\elements\\Entry', Craft.EntryIndex);
