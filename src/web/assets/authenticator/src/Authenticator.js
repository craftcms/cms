(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Authenticator = Garnish.Base.extend(
    {
      $qrSecretContainer: null,
      $enable2faCheckbox: null,
      $verificationForm: null,
      $verificationCodeInput: null,

      init: function (settings) {
        this.$qrSecretContainer = $('#qrSecret');
        this.$enable2faCheckbox = $('#require2fa');
        this.$verificationForm = $('#verify2faSetup');
        this.$verifyButton = this.$verificationForm.find('button');
        this.$verificationCodeInput = this.$verificationForm.find(
          'input[name="verificationCode"]'
        );

        this.setSettings(settings, Craft.Authenticator.defaults);

        this.addListener(this.$enable2faCheckbox, 'change', 'getQrSecret');
        this.addListener(this.$verifyButton, 'click', 'verifyCode');
      },

      verifyCode: function (ev) {
        const verificationCode = this.$verificationCodeInput.val();
        if (verificationCode !== undefined && verificationCode.length > 0) {
          Craft.sendActionRequest('POST', this.settings.verifyCodeAction, {
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

      getQrSecret: function (ev) {
        let isChecked = ev.currentTarget.checked;

        if (isChecked) {
          // get qr and secret
          Craft.sendActionRequest('POST', this.settings.getQrSecretAction)
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
        getQrSecretAction: 'authenticator/enable2fa-step1',
        verifyCodeAction: 'authenticator/enable2fa-step2',
      },
    }
  );

  new Craft.Authenticator();
})(jQuery);
