import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
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
        this.$submitBtn.remove();
      } else {
        // "FIDO2 and WebAuthn support two types of authenticators, platform authenticators and roaming authenticators.
        // Platform Authenticators are authentication mechanisms built into devices. This could include things like Windows Hello, Apple's Touch ID or Face ID.
        // Roaming Authenticators are separate authentication hardware keys like Yubikeys or Google's Titan Keys."
        // @link: https://www.twilio.com/blog/detect-browser-support-webauthn
        // e.g. Firefox supports Platform Authenticators on Windows but not on macOS.
        platformAuthenticatorIsAvailable()
          .then((response) => {
            if (!response) {
              this.$submitBtn.remove();
            }
          })
          .catch((error) => {
            this.showError(error);
          })
          .finally(() => {
            if (this.$submitBtn !== null) {
              this.addListener(this.$submitBtn, 'click', 'login');

              this.$submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
                changeButtonText: true,
              });
            }
            if ($('.alternative-login-methods').find('.btn').length == 0) {
              $('.alternative-login-methods').remove();
            }
          });
      }
    },

    login: function (ev) {
      ev.preventDefault();

      this.$submitBtn.busyEvent();
      this.clearErrors();

      this.startAuthentication(true, false, 'login')
        .then((response) => {
          this.$submitBtn.successEvent();
          if (response.returnUrl != undefined) {
            window.location.href = response.returnUrl;
          }
        })
        .catch((response) => {
          this.$submitBtn.failureEvent();
          this.processFailure(response);
        });
    },

    startAuthentication: function (
      usernameless = false,
      inModal = false,
      action = 'login'
    ) {
      let data = {
        usernameless: usernameless,
      };
      return Craft.sendActionRequest('POST', 'users/start-webauthn-login', {
        data,
      })
        .then((response) => {
          const authenticationOptions = response.data.authenticationOptions;

          try {
            return startAuthentication(authenticationOptions)
              .then((authResponse) => {
                return Promise.resolve(
                  this.verifyAuthentication(
                    authenticationOptions,
                    authResponse,
                    inModal,
                    action
                  )
                );
              })
              .catch((authResponseError) => {
                return Promise.reject(authResponseError);
              });
          } catch (error) {
            return Promise.reject(error);
          }
        })
        .catch((response) => {
          return Promise.reject(response);
        });
    },

    verifyAuthentication: function (
      authenticationOptions,
      authResponse,
      inModal,
      action
    ) {
      let data = {
        authenticationOptions: JSON.stringify(authenticationOptions),
        authResponse: JSON.stringify(authResponse),
      };

      let actionUrl = 'users/webauthn-verify';

      if (action == 'elevateSessionWebAuthn') {
        actionUrl = 'users/start-elevated-session';
        data['passwordless'] = true;
        data['passwordlessMethod'] = 'WebAuthn';
      }

      return Craft.sendActionRequest('POST', actionUrl, {data})
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
          return Promise.reject(response.data.message);
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
