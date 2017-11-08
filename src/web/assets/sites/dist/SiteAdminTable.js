(function($) {
    /** global: Craft */
    /** global: Garnish */
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
                        this.$deleteActionRadios.first().focus();
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
