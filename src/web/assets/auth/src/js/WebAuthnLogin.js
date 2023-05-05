import {
  browserSupportsWebAuthn,
  startAuthentication,
} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.WebAuthnLogin = Garnish.Base.extend({
    $submitBtn: null,
    $errors: null,

    init: function () {
      this.$submitBtn = $('#webauthn-login');
      this.$errors = $('#login-errors');

      if (!browserSupportsWebAuthn()) {
        this.$submitBtn.disable();
      } else {
        this.addListener(this.$submitBtn, 'click', 'webauthnLogin');

        this.$submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
          changeButtonText: true,
        });
      }
    },

    webauthnLogin: function (ev) {
      ev.preventDefault();

      this.$submitBtn.busyEvent();
      this.clearErrors();

      this.startWebauthnLogin(false)
        .then((response) => {
          this.$submitBtn.successEvent();
          if (response.returnUrl != undefined) {
            window.location.href = response.returnUrl;
          }
        })
        .catch((response) => {
          this.$submitBtn.failureEvent();
          this.processFailure(response.error);
        });
    },

    startWebauthnLogin: function (inModal = false) {
      return Craft.sendActionRequest('POST', 'users/start-webauthn-login', {})
        .then((response) => {
          const authenticationOptions = response.data.authenticationOptions;

          try {
            return startAuthentication(authenticationOptions)
              .then((authResponse) => {
                return Promise.resolve(
                  this.verifyWebAuthnLogin(
                    authenticationOptions,
                    authResponse,
                    inModal
                  )
                );
              })
              .catch((authResponseError) => {
                return Promise.reject({
                  success: false,
                  error: authResponseError,
                });
              });
          } catch (error) {
            return Promise.reject({success: false, error: error});
          }
        })
        .catch((response) => {
          return Promise.reject({
            success: false,
            error: response.error.message,
          });
        });
    },

    verifyWebAuthnLogin: function (
      authenticationOptions,
      authResponse,
      inModal
    ) {
      let data = {
        authenticationOptions: JSON.stringify(authenticationOptions),
        authResponse: JSON.stringify(authResponse),
      };

      return Craft.sendActionRequest('POST', 'users/webauthn-login', {data})
        .then((response) => {
          if (inModal) {
            return Promise.resolve({success: true});
          } else {
            return Promise.resolve({
              success: true,
              returnUrl: response.data.returnUrl,
            });
          }
        })
        .catch(({response}) => {
          return Promise.reject({success: false, error: response.data.message});
        });
    },

    processFailure: function (error) {
      // Add the error message
      this.showError(error);
      this.$submitBtn.failureEvent();
    },

    showError: function (error) {
      this.clearErrors();

      $('<p style="display: none;">' + error + '</p>')
        .appendTo(this.$errors)
        .velocity('fadeIn');
    },

    clearErrors: function () {
      this.$errors.empty();
    },
  });

  new Craft.WebAuthnLogin();
})(jQuery);
