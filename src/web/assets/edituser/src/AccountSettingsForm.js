import './account.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.AccountSettingsForm = Garnish.Base.extend(
    {
      userId: null,
      isCurrent: null,

      $copyPasswordResetUrlBtn: null,
      $copyImpersonationUrlBtn: null,
      $actionBtn: null,

      confirmDeleteModal: null,
      $deleteBtn: null,

      init: function (userId, isCurrent, settings) {
        this.userId = userId;
        this.isCurrent = isCurrent;

        this.setSettings(settings, Craft.AccountSettingsForm.defaults);

        this.$copyPasswordResetUrlBtn = $('#copy-passwordreset-url');
        this.$copyImpersonationUrlBtn = $('#copy-impersonation-url');
        this.$actionBtn = $('#action-menubtn');
        this.$deleteBtn = $('#delete-btn');

        this.addListener(
          this.$copyPasswordResetUrlBtn,
          'click',
          'handleCopyPasswordResetUrlBtnClick'
        );
        this.addListener(
          this.$copyImpersonationUrlBtn,
          'click',
          'handleCopyImpersonationUrlBtnClick'
        );
        this.addListener(this.$deleteBtn, 'click', 'showConfirmDeleteModal');
      },

      handleCopyPasswordResetUrlBtnClick: function () {
        // Requires an elevated session
        Craft.elevatedSessionManager.requireElevatedSession(
          this.getPasswordResetUrl.bind(this)
        );
      },

      getPasswordResetUrl: function () {
        this.$actionBtn.addClass('loading');

        var data = {
          userId: this.userId,
        };

        Craft.sendActionRequest('POST', 'users/get-password-reset-url', {data})
          .then((response) => {
            this.$actionBtn.removeClass('loading');
            Craft.ui.createCopyTextPrompt({
              label: Craft.t('app', 'Copy the activation URL'),
              value: response.data.url,
            });
          })
          .catch(({response}) => {
            this.$actionBtn.removeClass('loading');
          });
      },

      handleCopyImpersonationUrlBtnClick: function () {
        this.$actionBtn.addClass('loading');

        var data = {
          userId: this.userId,
        };

        Craft.sendActionRequest('POST', 'users/get-impersonation-url', {data})
          .then((response) => {
            this.$actionBtn.removeClass('loading');
            Craft.ui.createCopyTextPrompt({
              label: Craft.t(
                'app',
                'Copy the impersonation URL, and open it in a new private window.'
              ),
              value: response.data.url,
            });
          })
          .catch(({response}) => {
            this.$actionBtn.removeClass('loading');
          });
      },

      showConfirmDeleteModal: function () {
        if (!this.confirmDeleteModal) {
          this.$actionBtn.addClass('loading');

          let data = {userId: this.userId};
          Craft.sendActionRequest('POST', 'users/user-content-summary', {data})
            .then((response) => {
              this.$actionBtn.removeClass('loading');
              this.confirmDeleteModal = new Craft.DeleteUserModal(this.userId, {
                contentSummary: response.data,
                redirect: this.settings.deleteModalRedirect,
              });
            })
            .catch(({response}) => {
              this.$actionBtn.removeClass('loading');
            });
        } else {
          this.confirmDeleteModal.show();
        }
      },
    },
    {
      defaults: {
        deleteModalRedirect: null,
      },
    }
  );
})(jQuery);
