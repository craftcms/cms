(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.RecoveryCodesSetup = Craft.Auth2fa.extend(
    {
      $generateRecoveryCodesBtn: null,
      $downloadRecoveryCodes: null,

      init: function (slideout, settings) {
        this.setSettings(settings, Craft.RecoveryCodesSetup.defaults);
        this.slideout = slideout;
        this.initSlideout();

        this.$generateRecoveryCodesBtn = this.slideout.$container.find(
          '#generate-recovery-codes'
        );
        this.$downloadRecoveryCodes = this.slideout.$container.find(
          '#download-recovery-codes'
        );

        this.addListener(
          this.$generateRecoveryCodesBtn,
          'click',
          'onGenerateRecoveryCodesBtn'
        );

        if (this.$downloadRecoveryCodes.length > 0) {
          this.addListener(
            this.$downloadRecoveryCodes,
            'submit',
            'onDownloadRecoveryCodesBtn'
          );
        }
      },

      onGenerateRecoveryCodesBtn: function (ev) {
        if (!$(ev.currentTarget).hasClass('disabled')) {
          const confirmed = confirm(
            Craft.t(
              'app',
              'Are you sure you want to generate new recovery codes? All current codes will stop working.'
            )
          );

          if (confirmed) {
            this.showStatus(Craft.t('app', 'Waiting for elevated session'), '');
            Craft.elevatedSessionManager.requireElevatedSession(
              this.generateRecoveryCodes.bind(this),
              this.failedElevation.bind(this)
            );
          }
        }
      },

      onDownloadRecoveryCodesBtn: function (ev) {
        ev.preventDefault();

        if (!$(ev.currentTarget).hasClass('disabled')) {
          this.showStatus(Craft.t('app', 'Waiting for elevated session'), '');
          Craft.elevatedSessionManager.requireElevatedSession(
            this.downloadRecoveryCodes.bind(this),
            this.failedElevation.bind(this)
          );
        }
      },

      failedElevation: function () {
        this.clearStatus();
      },

      generateRecoveryCodes: function () {
        this.clearStatus();

        // GET registration options from the endpoint that calls
        Craft.sendActionRequest('POST', this.settings.generateRecoveryCodes)
          .then((response) => {
            this.clearStatus();
            // Show UI appropriate for the `verified` status
            if (response.data.verified) {
              Craft.cp.displaySuccess(
                Craft.t('app', 'Recovery codes generated.')
              );
              if (response.data.html) {
                this.slideout.$container.html(response.data.html);
                this.init(this.slideout); //re-initialise the slideout
              }
            } else {
              this.showStatus('Something went wrong!');
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message);
          });
      },

      downloadRecoveryCodes: function () {
        this.clearStatus();

        Craft.downloadFromUrl(
          'POST',
          Craft.getActionUrl(this.settings.downloadRecoveryCodes),
          this.$downloadRecoveryCodes.serialize()
        ).then((response) => {
          this.clearStatus();

          // Show UI message
          Craft.cp.displaySuccess(Craft.t('app', 'Recovery codes downloaded.'));
        });
      },
    },
    {
      defaults: {
        downloadRecoveryCodes: 'auth/download-recovery-codes',
        generateRecoveryCodes: 'auth/generate-recovery-codes',
      },
    }
  );
})(jQuery);
