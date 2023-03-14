import {browserSupportsWebAuthn} from '@simplewebauthn/browser';
import {startRegistration} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.WebAuthn = Garnish.Base.extend(
    {
      $addSecurityKeyBtn: null,
      $errors: null,
      slideout: null,

      init: function (slideout, settings) {
        this.slideout = slideout;
        this.setSettings(settings, Craft.WebAuthn.defaults);
        this.$addSecurityKeyBtn = $('#add-security-key');
        this.$errors = this.slideout.$container.find('.so-notice');

        if (!browserSupportsWebAuthn()) {
          Craft.cp.displayError('This browser does not support WebAuth.');
          this.$addSecurityKeyBtn.disable();
        }

        this.addListener(
          this.$addSecurityKeyBtn,
          'click',
          'onAddSecurityKeyBtn'
        );
      },

      onAddSecurityKeyBtn: function (ev) {
        if (!$(ev.currentTarget).hasClass('disabled')) {
          //this.setStatus(Craft.t('app', 'Waiting for elevated session'));
          Craft.elevatedSessionManager.requireElevatedSession(
            this.startWebAuthRegistration.bind(this),
            this.failedElevation.bind(this)
          );
        }
      },

      failedElevation: function () {
        console.log('not elevated from funct');
      },

      startWebAuthRegistration: function () {
        console.log('elevated funct - start reg');
        // GET registration options from the endpoint that calls
        Craft.sendActionRequest(
          'POST',
          this.settings.generateRegistrationOptions
        )
          .then((response) => {
            const registrationOptions = response.data.registrationOptions;
            let startRegistrationResponse;
            try {
              startRegistrationResponse =
                startRegistration(registrationOptions);

              startRegistrationResponse.then((regResponse) => {
                console.log('start response');
                this.verifyWebAuthnRegistration(regResponse);
              });
            } catch (error) {
              // Some basic error handling
              if (error.name === 'InvalidStateError') {
                Craft.cp.displayError(
                  'Error: Authenticator was probably already registered by user'
                );
              } else {
                Craft.cp.displayError(error);
              }
              throw error;
            }
          })
          .catch(({response}) => {
            // todo: handle me
            console.log(response);
          });
      },

      verifyWebAuthnRegistration: function (startRegistrationResponse) {
        console.log('verifyWebAuthnRegistration');
        console.log(startRegistrationResponse);
        let data = {
          credentials: JSON.stringify(startRegistrationResponse),
        };

        console.log(data);

        // POST the response to the endpoint
        Craft.sendActionRequest('POST', this.settings.verifyRegistration, {
          data,
        })
          .then((response) => {
            // Show UI appropriate for the `verified` status
            if (response.data.verified) {
              Craft.cp.displaySuccess('Success!');
            } else {
              Craft.cp.displayError('Something went wrong!');
              console.log(response);
            }
          })
          .catch(({response}) => {
            // todo: handle me
            console.log(response);
          });
      },
    },
    {
      defaults: {
        generateRegistrationOptions: 'mfa/generate-registration-options',
        verifyRegistration: 'mfa/verify-registration',
      },
    }
  );
})(jQuery);
