import {browserSupportsWebAuthn} from '@simplewebauthn/browser';
import {startRegistration} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.WebAuthn = Garnish.Base.extend(
    {
      $addSecurityKeyBtn: null,
      $errors: null,
      $statusContainer: null,
      slideout: null,

      init: function (slideout, settings) {
        console.log('init');
        this.slideout = slideout;
        this.setSettings(settings, Craft.WebAuthn.defaults);
        this.$addSecurityKeyBtn = $('#add-security-key');
        this.$errors = this.slideout.$container.find('.so-notice');
        this.$statusContainer =
          this.slideout.$container.find('#webauthn-status');

        if (!browserSupportsWebAuthn()) {
          Craft.cp.displayError(
            Craft.t('app', 'This browser does not support WebAuth.')
          );
          this.$addSecurityKeyBtn.disable();
        }

        this.addListener(
          this.$addSecurityKeyBtn,
          'click',
          'onAddSecurityKeyBtn'
        );
      },

      onAddSecurityKeyBtn: function (ev) {
        console.log('clicked btn');
        if (!$(ev.currentTarget).hasClass('disabled')) {
          this.showStatus(Craft.t('app', 'Waiting for elevated session'));
          Craft.elevatedSessionManager.requireElevatedSession(
            this.startWebAuthRegistration.bind(this),
            this.failedElevation.bind(this)
          );
        }
      },

      failedElevation: function () {
        this.clearStatus();
      },

      startWebAuthRegistration: function () {
        this.clearStatus();

        // GET registration options from the endpoint that calls
        Craft.sendActionRequest(
          'POST',
          this.settings.generateRegistrationOptions
        )
          .then((response) => {
            const registrationOptions = response.data.registrationOptions;
            try {
              this.showStatus(Craft.t('app', 'Starting registration'));
              startRegistration(registrationOptions)
                .then((regResponse) => {
                  this.verifyWebAuthnRegistration(regResponse);
                })
                .catch((regResponseError) => {
                  this.showStatus(
                    Craft.t('app', 'Registration failed:') +
                      ' ' +
                      regResponseError.message,
                    'error'
                  );
                });
            } catch (error) {
              this.showStatus(error, 'error');
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message, 'error');
          });
      },

      verifyWebAuthnRegistration: function (startRegistrationResponse) {
        this.showStatus(Craft.t('app', 'Starting verification'));
        let data = {
          credentials: JSON.stringify(startRegistrationResponse),
        };

        // POST the response to the endpoint
        Craft.sendActionRequest('POST', this.settings.verifyRegistration, {
          data,
        })
          .then((response) => {
            this.clearStatus();
            // Show UI appropriate for the `verified` status
            if (response.data.verified) {
              Craft.cp.displaySuccess('Success!');
              if (response.data.html) {
                this.slideout.$container.html(response.data.html);
                this.init(this.slideout); //reinitialise
              }
            } else {
              this.showStatus('Something went wrong!', 'error');
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message, 'error');
          });
      },

      showStatus: function (message, type) {
        //Craft.cp.displayError(message);
        if (type == 'error') {
          this.$statusContainer.addClass('error');
        } else {
          this.$statusContainer.removeClass('error');
        }
        this.$statusContainer.text(message);
      },

      clearStatus: function () {
        this.$statusContainer.text('');
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
