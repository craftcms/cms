import {browserSupportsWebAuthn} from '@simplewebauthn/browser';
import {startRegistration} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.WebAuthnSetup = Craft.Auth2fa.extend(
    {
      $addSecurityKeyBtn: null,
      $keysTable: null,

      init: function (slideout, settings) {
        this.setSettings(settings, Craft.WebAuthnSetup.defaults);
        this.slideout = slideout;
        this.initSlideout();

        this.$addSecurityKeyBtn = $('#add-security-key');
        this.$keysTable = this.slideout.$container.find(
          '#webauthn-security-keys'
        );

        if (!browserSupportsWebAuthn()) {
          Craft.cp.displayError(
            Craft.t('app', 'This browser does not support WebAuthn.')
          );
          this.$addSecurityKeyBtn.disable();
        }

        this.addListener(
          this.$addSecurityKeyBtn,
          'click',
          'onAddSecurityKeyBtn'
        );

        if (this.$keysTable !== null) {
          this.addListener(
            this.$keysTable.find('.delete'),
            'click',
            'onDeleteSecurityKey'
          );
        }
      },

      onAddSecurityKeyBtn: function (ev) {
        if (!$(ev.currentTarget).hasClass('disabled')) {
          this.showStatus(Craft.t('app', 'Waiting for elevated session'), '');
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
              this.showStatus(Craft.t('app', 'Starting registration'), '');
              const credentialName = Craft.escapeHtml(
                prompt(
                  Craft.t('app', 'Please enter a name for the security key')
                )
              );
              startRegistration(registrationOptions)
                .then((regResponse) => {
                  this.verifyWebAuthnRegistration(regResponse, credentialName);
                })
                .catch((regResponseError) => {
                  this.showStatus(
                    Craft.t('app', 'Registration failed:') +
                      ' ' +
                      regResponseError.message
                  );
                });
            } catch (error) {
              this.showStatus(error);
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message);
          });
      },

      verifyWebAuthnRegistration: function (
        startRegistrationResponse,
        credentialName
      ) {
        this.showStatus(Craft.t('app', 'Starting verification'), '');
        let data = {
          credentials: JSON.stringify(startRegistrationResponse),
          credentialName: credentialName,
        };

        // POST the response to the endpoint
        Craft.sendActionRequest('POST', this.settings.verifyRegistration, {
          data,
        })
          .then((response) => {
            this.clearStatus();
            // Show UI appropriate for the `verified` status
            if (response.data.verified) {
              Craft.cp.displaySuccess(
                Craft.t('app', 'Security key registered.')
              );
              if (response.data.html) {
                this.slideout.$container.html(response.data.html);
                this.init(this.slideout); //re-initialise slideout
              }
            } else {
              this.showStatus(Craft.t('app', 'Something went wrong!'));
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message);
          });
      },

      onDeleteSecurityKey: function (ev) {
        ev.preventDefault();

        const $uid = $(ev.currentTarget).attr('data-uid');
        const credentialName = $(ev.currentTarget)
          .parents('tr')
          .find('[data-name="credentialName"]')
          .text();

        let data = {
          uid: $uid,
        };

        const confirmed = confirm(
          Craft.t(
            'app',
            'Are you sure you want to delete ‘{credentialName}‘ security key?',
            {credentialName: credentialName}
          )
        );

        if ($uid !== undefined && confirmed) {
          Craft.sendActionRequest('POST', this.settings.deleteSecurityKey, {
            data,
          })
            .then((response) => {
              Craft.cp.displaySuccess(response.data.message);
              if (response.data.html) {
                this.slideout.$container.html(response.data.html);
                this.init(this.slideout); //re-initialise slideout
              }
            })
            .catch(({response}) => {
              this.showStatus(response.data.message);
            });
        }
      },
    },
    {
      defaults: {
        generateRegistrationOptions: 'auth/generate-registration-options',
        verifyRegistration: 'auth/verify-registration',
        deleteSecurityKey: 'auth/delete-security-key',
      },
    }
  );
})(jQuery);
