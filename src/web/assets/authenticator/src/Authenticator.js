(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Authenticator = Garnish.Base.extend(
    {
      $qrSecretContainer: null,
      $enableMfaCheckbox: null,
      $verificationForm: null,
      $verificationCodeInput: null,

      init: function (settings) {
        this.$qrSecretContainer = $('#qrContainer');
        this.$enableMfaCheckbox = $('#requireMfa');
        this.$verificationForm = $('#verifyMfaSetup');
        this.$verifyButton = this.$verificationForm.find('button');
        this.$verificationCodeInput = this.$verificationForm.find(
          'input[name="verificationCode"]'
        );

        this.setSettings(settings, Craft.Authenticator.defaults);

        this.addListener(this.$enableMfaCheckbox, 'change', 'getQrCode');
        this.addListener(this.$verifyButton, 'click', 'verifyCode');
      },

      verifyCode: function (ev) {
        const verificationCode = this.$verificationCodeInput.val();
        if (verificationCode !== undefined && verificationCode.length > 0) {
          Craft.sendActionRequest('POST', this.settings.verifyAction, {
            data: {
              code: verificationCode,
            },
          })
            .then((response) => {
              this.$qrSecretContainer.html('');
              this.$verificationForm.html('').append('Verified');
              Craft.cp.displayNotice(response.data.message);
            })
            .catch((e) => {
              Craft.cp.displayError(e.response.data.message);
            });
        }
      },

      getQrCode: function (ev) {
        let isChecked = ev.currentTarget.checked;

        if (isChecked) {
          // get qr and secret
          Craft.sendActionRequest('POST', this.settings.getQrCodeAction)
            .then((response) => {
              let secret =
                '<div class="secret">Secret Key (input without spaces): ' +
                response.data.secret +
                '</div>';
              let qrCode =
                '<div class="qrCode">QR Code: ' +
                response.data.qrCode +
                '</div>';
              this.$qrSecretContainer.html('').append(secret).append(qrCode);
            })
            .catch((e) => {
              Craft.cp.displayError(e.response.data.message);
            });
        } else {
          // clear out container
          this.$qrSecretContainer.html('');
        }
      },
    },
    {
      defaults: {
        getQrCodeAction: 'authentication/get-qr-code',
        verifyAction: 'authentication/verify',
      },
    }
  );

  new Craft.Authenticator();
})(jQuery);
