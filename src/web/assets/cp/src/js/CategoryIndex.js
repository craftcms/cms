/** global: Craft */
/** global: Garnish */
/**
 * Category index class
 */
Craft.CategoryIndex = Craft.BaseElementIndex.extend(
    {
        editableGroups: null,
        $newCategoryBtnGroup: null,
        $newCategoryBtn: null,

        init: function(elementType, $container, settings) {
            this.on('selectSource', $.proxy(this, 'updateButton'));
            this.on('selectSite', $.proxy(this, 'updateButton'));
            this.base(elementType, $container, settings);
        },

        afterInit: function() {
            // Find which of the visible groups the user has permission to create new categories in
            this.editableGroups = [];

            for (var i = 0; i < Craft.editableCategoryGroups.length; i++) {
                var group = Craft.editableCategoryGroups[i];

                if (this.getSourceByKey('group:' + group.uid)) {
                    this.editableGroups.push(group);
                }
            }

            this.base();
        },

        getDefaultSourceKey: function() {
            // Did they request a specific category group in the URL?
            if (this.settings.context === 'index' && typeof defaultGroupHandle !== 'undefined') {
                for (var i = 0; i < this.$sources.length; i++) {
                    var $source = $(this.$sources[i]);

                    if ($source.data('handle') === defaultGroupHandle) {
                        return $source.data('key');
                    }
                }
            }

            return this.base();
        },

        updateButton: function() {
            if (!this.$source) {
                return;
            }

            // Get the handle of the selected source
            var selectedSourceHandle = this.$source.data('handle');

            var i, href, label;

            // Update the New Category button
            // ---------------------------------------------------------------------

            if (this.editableGroups.length) {
                // Remove the old button, if there is one
                if (this.$newCategoryBtnGroup) {
                    this.$newCategoryBtnGroup.remove();
                }

                // Determine if they are viewing a group that they have permission to create categories in
                var selectedGroup;

                if (selectedSourceHandle) {
                    for (i = 0; i < this.editableGroups.length; i++) {
                        if (this.editableGroups[i].handle === selectedSourceHandle) {
                            selectedGroup = this.editableGroups[i];
                            break;
                        }
                    }
                }

                this.$newCategoryBtnGroup = $('<div class="btngroup submit"/>');
                var $menuBtn;

                // If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
                // Otherwise only show a menu button
                if (selectedGroup) {
                    href = this._getGroupTriggerHref(selectedGroup);
                    label = (this.settings.context === 'index' ? Craft.t('app', 'New category') : Craft.t('app', 'New {group} category', {group: selectedGroup.name}));
                    this.$newCategoryBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newCategoryBtnGroup);

                    if (this.settings.context !== 'index') {
                        this.addListener(this.$newCategoryBtn, 'click', function(ev) {
                            this._openCreateCategoryModal(ev.currentTarget.getAttribute('data-id'));
                        });
                    }

                    if (this.editableGroups.length > 1) {
                        $menuBtn = $('<button/>', {
                            type: 'button',
                            class: 'btn submit menubtn',
                        }).appendTo(this.$newCategoryBtnGroup);
                    }
                }
                else {
                    this.$newCategoryBtn = $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit add icon menubtn',
                        text: Craft.t('app', 'New category'),
                    }).appendTo(this.$newCategoryBtnGroup);
                }

                if ($menuBtn) {
                    var menuHtml = '<div class="menu"><ul>';

                    for (i = 0; i < this.editableGroups.length; i++) {
                        var group = this.editableGroups[i];

                        if (this.settings.context === 'index' || group !== selectedGroup) {
                            href = this._getGroupTriggerHref(group);
                            label = (this.settings.context === 'index' ? group.name : Craft.t('app', 'New {group} category', {group: group.name}));
                            menuHtml += '<li><a ' + href + '>' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    $(menuHtml).appendTo(this.$newCategoryBtnGroup);
                    var menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context !== 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateCategoryModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newCategoryBtnGroup);
            }

            // Update the URL if we're on the Categories index
            // ---------------------------------------------------------------------

            if (this.settings.context === 'index' && typeof history !== 'undefined') {
                var uri = 'categories';

                if (selectedSourceHandle) {
                    uri += '/' + selectedSourceHandle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }
        },

        _getGroupTriggerHref: function(group) {
            if (this.settings.context === 'index') {
                var uri = 'categories/' + group.handle + '/new';
                if (this.siteId && this.siteId != Craft.primarySiteId) {
                    for (var i = 0; i < Craft.sites.length; i++) {
                        if (Craft.sites[i].id == this.siteId) {
                            uri += '/'+Craft.sites[i].handle;
                        }
                    }
                }
                return 'href="' + Craft.getUrl(uri) + '"';
            }
            else {
                return 'data-id="' + group.id + '"';
            }
        },

        _openCreateCategoryModal: function(groupId) {
            if (this.$newCategoryBtn.hasClass('loading')) {
                return;
            }

            // Find the group
            var group;

            for (var i = 0; i < this.editableGroups.length; i++) {
                if (this.editableGroups[i].id == groupId) {
                    group = this.editableGroups[i];
                    break;
                }
            }

            if (!group) {
                return;
            }

            this.$newCategoryBtn.addClass('inactive');
            var newCategoryBtnText = this.$newCategoryBtn.text();
            this.$newCategoryBtn.text(Craft.t('app', 'New {group} category', {group: group.name}));

            Craft.createElementEditor(this.elementType, {
                hudTrigger: this.$newCategoryBtnGroup,
                siteId: this.siteId,
                attributes: {
                    groupId: groupId
                },
                onBeginLoading: $.proxy(function() {
                    this.$newCategoryBtn.addClass('loading');
                }, this),
                onEndLoading: $.proxy(function() {
                    this.$newCategoryBtn.removeClass('loading');
                }, this),
                onHideHud: $.proxy(function() {
                    this.$newCategoryBtn.removeClass('inactive').text(newCategoryBtnText);
                }, this),
                onSaveElement: $.proxy(function(response) {
                    // Make sure the right group is selected
                    var groupSourceKey = 'group:' + group.uid;

                    if (this.sourceKey !== groupSourceKey) {
                        this.selectSourceByKey(groupSourceKey);
                    }

                    this.selectElementAfterUpdate(response.id);
                    this.updateElements();
                }, this)
            });
        }
    });

// Register it!
Craft.registerElementIndexClass('craft\\elements\\Category', Craft.CategoryIndex);
