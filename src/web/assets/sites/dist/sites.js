(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.SitesAdmin = Garnish.Base.extend(
        {
            $groups: null,
            $selectedGroup: null,

            init: function() {
                this.$groups = $('#groups');
                this.$selectedGroup = this.$groups.find('a.sel:first');
                this.addListener($('#newgroupbtn'), 'activate', 'addNewGroup');

                var $groupSettingsBtn = $('#groupsettingsbtn');

                if ($groupSettingsBtn.length) {
                    var menuBtn = $groupSettingsBtn.data('menubtn');

                    menuBtn.settings.onOptionSelect = $.proxy(function(elem) {
                        var $elem = $(elem);

                        if ($elem.hasClass('disabled')) {
                            return;
                        }

                        switch ($elem.data('action')) {
                            case 'rename': {
                                this.renameSelectedGroup();
                                break;
                            }
                            case 'delete': {
                                this.deleteSelectedGroup();
                                break;
                            }
                        }
                    }, this);
                }
            },

            addNewGroup: function() {
                var name = this.promptForGroupName('');

                if (name) {
                    var data = {
                        name: name
                    };

                    Craft.postActionRequest('sites/save-group', data, $.proxy(function(response, textStatus) {
                        if (textStatus === 'success') {
                            if (response.success) {
                                location.href = Craft.getUrl('settings/sites', {groupId: response.group.id});
                            }
                            else if (response.errors) {
                                var errors = this.flattenErrors(response.errors);
                                alert(Craft.t('app', 'Could not create the group:') + "\n\n" + errors.join("\n"));
                            }
                            else {
                                Craft.cp.displayError();
                            }
                        }

                    }, this));
                }
            },

            renameSelectedGroup: function() {
                var oldName = this.$selectedGroup.text(),
                    newName = this.promptForGroupName(oldName);

                if (newName && newName !== oldName) {
                    var data = {
                        id: this.$selectedGroup.data('id'),
                        name: newName
                    };

                    Craft.postActionRequest('sites/save-group', data, $.proxy(function(response, textStatus) {
                        if (textStatus === 'success') {
                            if (response.success) {
                                this.$selectedGroup.text(response.group.name);
                                Craft.cp.displayNotice(Craft.t('app', 'Group renamed.'));
                            }
                            else if (response.errors) {
                                var errors = this.flattenErrors(response.errors);
                                alert(Craft.t('app', 'Could not rename the group:') + "\n\n" + errors.join("\n"));
                            }
                            else {
                                Craft.cp.displayError();
                            }
                        }

                    }, this));
                }
            },

            promptForGroupName: function(oldName) {
                return prompt(Craft.t('app', 'What do you want to name the group?'), oldName);
            },

            deleteSelectedGroup: function() {
                if (confirm(Craft.t('app', 'Are you sure you want to delete this group?'))) {
                    var data = {
                        id: this.$selectedGroup.data('id')
                    };

                    Craft.postActionRequest('sites/delete-group', data, $.proxy(function(response, textStatus) {
                        if (textStatus === 'success') {
                            if (response.success) {
                                location.href = Craft.getUrl('settings/sites');
                            }
                            else {
                                Craft.cp.displayError();
                            }
                        }
                    }, this));
                }
            },

            flattenErrors: function(responseErrors) {
                var errors = [];

                for (var attribute in responseErrors) {
                    if (!responseErrors.hasOwnProperty(attribute)) {
                        continue;
                    }

                    errors = errors.concat(responseErrors[attribute]);
                }

                return errors;
            }
        });

    Craft.SiteAdminTable = Craft.AdminTable.extend(
        {
            confirmDeleteModal: null,

            $rowToDelete: null,
            $deleteActionRadios: null,
            $deleteSubmitBtn: null,
            $deleteSpinner: null,

            _deleting: false,

            confirmDeleteItem: function($row) {
                if (this.confirmDeleteModal) {
                    this.confirmDeleteModal.destroy();
                    delete this.confirmDeleteModal;
                }

                this._createConfirmDeleteModal($row);

                // Auto-focus the first radio
                if (!Garnish.isMobileBrowser(true)) {
                    setTimeout($.proxy(function() {
                        this.$deleteActionRadios.first().trigger('focus');
                    }, this), 100);
                }

                return false;
            },

            validateDeleteInputs: function() {
                var validates = (
                    this.$deleteActionRadios.eq(0).prop('checked') ||
                    this.$deleteActionRadios.eq(1).prop('checked')
                );

                if (validates) {
                    this.$deleteSubmitBtn.removeClass('disabled');
                }
                else {
                    this.$deleteSubmitBtn.addClass('disabled');
                }

                return validates;
            },

            submitDeleteLocale: function(ev) {
                ev.preventDefault();

                if (this._deleting || !this.validateDeleteInputs()) {
                    return;
                }

                this.$deleteSubmitBtn.addClass('active');
                this.$deleteSpinner.removeClass('hidden');
                this.disable();
                this._deleting = true;

                var data = {
                    id: this.getItemId(this.$rowToDelete)
                };

                // Are we transferring content?
                if (this.$deleteActionRadios.eq(0).prop('checked')) {
                    data.transferContentTo = this.$transferSelect.val();
                }

                Craft.postActionRequest(this.settings.deleteAction, data, $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        this._deleting = false;
                        this.enable();
                        this.confirmDeleteModal.hide();
                        this.handleDeleteItemResponse(response, this.$rowToDelete);
                    }
                }, this));
            },

            // Private Methods
            // =========================================================================

            _createConfirmDeleteModal: function($row) {
                this.$rowToDelete = $row;

                var id = this.getItemId($row),
                    name = this.getItemName($row);

                var $form = $(
                        '<form id="confirmdeletemodal" class="modal fitted" method="post" accept-charset="UTF-8">' +
                        Craft.getCsrfInput() +
                        '<input type="hidden" name="action" value="localization/deleteLocale"/>' +
                        '<input type="hidden" name="id" value="' + id + '"/>' +
                        '</form>'
                    ).appendTo(Garnish.$bod),
                    $body = $(
                        '<div class="body">' +
                        '<p>' + Craft.t('app', 'What do you want to do with any content that is only available in {language}?', {language: name}) + '</p>' +
                        '<div class="options">' +
                        '<label><input type="radio" name="contentAction" value="transfer"/> ' + Craft.t('app', 'Transfer it to:') + '</label> ' +
                        '<div id="transferselect" class="select">' +
                        '<select/>' +
                        '</div>' +
                        '</div>' +
                        '<div>' +
                        '<label><input type="radio" name="contentAction" value="delete"/> ' + Craft.t('app', 'Delete it') + '</label>' +
                        '</div>' +
                        '</div>'
                    ).appendTo($form),
                    $buttons = $('<div class="buttons right"/>').appendTo($body),
                    $cancelBtn = $('<div class="btn">' + Craft.t('app', 'Cancel') + '</div>').appendTo($buttons);

                this.$deleteActionRadios = $body.find('input[type=radio]');
                this.$transferSelect = $('#transferselect').find('> select');
                this.$deleteSubmitBtn = $('<input type="submit" class="btn submit disabled" value="' + Craft.t('app', 'Delete {site}', {site: name}) + '" />').appendTo($buttons);
                this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);

                for (var i = 0; i < Craft.sites.length; i++) {
                    if (Craft.sites[i].id != id) {
                        this.$transferSelect.append('<option value="' + Craft.sites[i].id + '">' + Craft.sites[i].name + '</option>');
                    }
                }

                this.confirmDeleteModal = new Garnish.Modal($form);

                this.addListener($cancelBtn, 'click', function() {
                    this.confirmDeleteModal.hide();
                });

                this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
                this.addListener($form, 'submit', 'submitDeleteLocale');
            }
        });
})(jQuery);
