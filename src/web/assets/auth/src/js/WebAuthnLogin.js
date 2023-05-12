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
    webAuthnPlatformAuthenticatorSupported: true,

    init: function () {
      this.$submitBtn = $('#webauthn-login');
      this.$errors = $('#login-errors');

      if (!browserSupportsWebAuthn()) {
        this.$submitBtn.disable();
      } else {
        // "FIDO2 and WebAuthn support two types of authenticators, platform authenticators and roaming authenticators.
        // Platform Authenticators are authentication mechanisms built into devices. This could include things like Windows Hello, Apple's Touch ID or Face ID.
        // Roaming Authenticators are separate authentication hardware keys like Yubikeys or Google's Titan Keys."
        // @link: https://www.twilio.com/blog/detect-browser-support-webauthn
        // Firefox supports Platform Authenticators on Windows but not on macOS.
        platformAuthenticatorIsAvailable()
          .then((response) => {
            if (!response) {
              this.webAuthnPlatformAuthenticatorSupported = false;
            }
          })
          .catch((error) => {
            this.showError(error);
          })
          .finally(() => {
            this.addListener(this.$submitBtn, 'click', 'login');

            this.$submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
              changeButtonText: true,
            });
          });
      }
    },

    supportCheck: function () {
      let proceed = true;

      if (!this.webAuthnPlatformAuthenticatorSupported) {
        proceed = confirm(
          Craft.t(
            'app',
            'In this browser, you can only use a security key with an external (roaming) authenticator like Yubikey or Titan Key.'
          )
        );
      }

      return proceed;
    },

    login: function (ev) {
      ev.preventDefault();

      if (this.supportCheck()) {
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
            this.processFailure(response.error);
          });
      }
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
          let error = '';
          if (response.error !== undefined) {
            error = response.error;
          } else {
            error = response.response.data.message;
          }
          return Promise.reject({
            success: false,
            error: error,
          });
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
