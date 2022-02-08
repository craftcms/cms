import './account.scss';

(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.AccountSettingsForm = Garnish.Base.extend({
        userId: null,
        isCurrent: null,

        $copyPasswordResetUrlBtn: null,
        $copyImpersonationUrlBtn: null,
        $actionBtn: null,

        confirmDeleteModal: null,
        $deleteBtn: null,

        init: function(userId, isCurrent, settings) {
            this.userId = userId;
            this.isCurrent = isCurrent;

            this.setSettings(settings, Craft.AccountSettingsForm.defaults);

            this.$copyPasswordResetUrlBtn = $('#copy-passwordreset-url');
            this.$copyImpersonationUrlBtn = $('#copy-impersonation-url')
            this.$actionBtn = $('#action-menubtn');
            this.$deleteBtn = $('#delete-btn');

            this.addListener(this.$copyPasswordResetUrlBtn, 'click', 'handleCopyPasswordResetUrlBtnClick');
            this.addListener(this.$copyImpersonationUrlBtn, 'click', 'handleCopyImpersonationUrlBtnClick');
            this.addListener(this.$deleteBtn, 'click', 'showConfirmDeleteModal');
        },

        handleCopyPasswordResetUrlBtnClick: function() {
            // Requires an elevated session
            Craft.elevatedSessionManager.requireElevatedSession(this.getPasswordResetUrl.bind(this));
        },

        getPasswordResetUrl: function() {
            this.$actionBtn.addClass('loading');

            var data = {
                userId: this.userId
            };

            Craft.postActionRequest('users/get-password-reset-url', data, (response, textStatus) => {
                this.$actionBtn.removeClass('loading');

                if (textStatus === 'success') {
                    Craft.ui.createCopyTextPrompt({
                        label: Craft.t('app', 'Copy the activation URL'),
                        value: response.url,
                    });
                }
            });
        },

        handleCopyImpersonationUrlBtnClick: function() {
            this.$actionBtn.addClass('loading');

            var data = {
                userId: this.userId
            };

            Craft.postActionRequest('users/get-impersonation-url', data, (response, textStatus) => {
                this.$actionBtn.removeClass('loading');

                if (textStatus === 'success') {
                    Craft.ui.createCopyTextPrompt({
                        label: Craft.t('app', 'Copy the impersonation URL, and open it in a new private window.'),
                        value: response.url,
                    });
                }
            });
        },

        showConfirmDeleteModal: function() {
            if (!this.confirmDeleteModal) {
                this.$actionBtn.addClass('loading');
                Craft.postActionRequest('users/user-content-summary', {userId: this.userId}, (response, textStatus) => {
                    this.$actionBtn.removeClass('loading');
                    if (textStatus === 'success') {
                        this.confirmDeleteModal = new Craft.DeleteUserModal(this.userId, {
                            contentSummary: response,
                            redirect: this.settings.deleteModalRedirect
                        });
                    }
                });
            } else {
                this.confirmDeleteModal.show();
            }
        }
    }, {
        defaults: {
            deleteModalRedirect: null
        }
    });
})(jQuery);
