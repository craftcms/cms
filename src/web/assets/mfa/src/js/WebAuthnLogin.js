import {browserSupportsWebAuthn} from '@simplewebauthn/browser';
import {startRegistration} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.WebAuthnLogin = Garnish.Base.extend(
    {
      init: function (settings) {
        this.setSettings(settings, Craft.WebAuthnLogin.defaults);

        if (!browserSupportsWebAuthn()) {
          Craft.cp.displayError(
            Craft.t('app', 'This browser does not support WebAuth.')
          );
        }
      },

      submitLogin: function () {
        Craft.sendActionRequest('POST', 'users/webauthn-login')
          .then((response) => {
            window.location.href = response.data.returnUrl;
          })
          .catch(({response}) => {
            //this.onSubmitResponse($submitBtn);

            // Add the error message
            //this.showError(response.data.message);
            console.log(response.data.message);
          });
      },

      showStatus: function (message, type) {
        Craft.cp.displayError(message);
      },

      clearStatus: function () {},
    },
    {
      defaults: {
        login: 'mfa/webauthn-login',
      },
    }
  );
})(jQuery);
