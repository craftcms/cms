(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.AccountSettingsForm = Garnish.Base.extend(
        {
            userId: null,
            isCurrent: null,

            $copyPasswordResetUrlBtn: null,
            $actionSpinner: null,

            confirmDeleteModal: null,
            $deleteBtn: null,

            init: function(userId, isCurrent, settings) {
                this.userId = userId;
                this.isCurrent = isCurrent;

                this.setSettings(settings, Craft.AccountSettingsForm.defaults);

                this.$copyPasswordResetUrlBtn = $('#copy-passwordreset-url');
                this.$actionSpinner = $('#action-spinner');
                this.$deleteBtn = $('#delete-btn');

                this.addListener(this.$copyPasswordResetUrlBtn, 'click', 'handleCopyPasswordResetUrlBtnClick');
                this.addListener(this.$deleteBtn, 'click', 'showConfirmDeleteModal');
            },

            handleCopyPasswordResetUrlBtnClick: function() {
                // Requires an elevated session
                Craft.elevatedSessionManager.requireElevatedSession($.proxy(this, 'getPasswordResetUrl'));
            },

            getPasswordResetUrl: function() {
                this.$actionSpinner.removeClass('hidden');

                var data = {
                    userId: this.userId
                };

                Craft.postActionRequest('users/get-password-reset-url', data, $.proxy(function(response, textStatus) {
                    this.$actionSpinner.addClass('hidden');

                    if (textStatus === 'success') {
                        Craft.ui.createCopyTextPrompt({
                            label: Craft.t('app', 'Copy the activation URL'),
                            value: response.url,
                        });
                    }
                }, this));
            },

            showConfirmDeleteModal: function() {
                if (!this.confirmDeleteModal) {
                    this.$actionSpinner.removeClass('hidden');
                    Craft.postActionRequest('users/user-content-summary', {userId: this.userId}, $.proxy(function(response, textStatus) {
                        this.$actionSpinner.addClass('hidden');
                        if (textStatus === 'success') {
                            this.confirmDeleteModal = new Craft.DeleteUserModal(this.userId, {
                                contentSummary: response,
                                redirect: this.settings.deleteModalRedirect
                            });
                        }
                    }, this));
                } else {
                    this.confirmDeleteModal.show();
                }
            }
        },
        {
            defaults: {
                deleteModalRedirect: null
            }
        });
})(jQuery);
