import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
} from '@simplewebauthn/browser';
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
          Craft.cp.displayNotice(
            Craft.t('app', 'This browser does not support WebAuthn.')
          );
          this.$addSecurityKeyBtn.remove();
        } else {
          platformAuthenticatorIsAvailable()
            .then((response) => {
              if (!response) {
                Craft.cp.displayNotice(
                  Craft.t('app', 'This browser does not support WebAuthn.')
                );
                this.$addSecurityKeyBtn.remove();
              }
            })
            .catch((error) => {
              this.showError(error);
            })
            .finally(() => {
              if (this.$addSecurityKeyBtn !== null) {
                this.addListener(
                  this.$addSecurityKeyBtn,
                  'click',
                  'onAddSecurityKeyBtn'
                );
              }
            });
        }

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
          $(ev.currentTarget).disable();
          this.showStatus(Craft.t('app', 'Waiting for elevated session'), '');
          Craft.elevatedSessionManager.requireElevatedSession(
            this.startRegistration.bind(this),
            this.failedElevation.bind(this)
          );
        }
      },

      failedElevation: function () {
        this.$addSecurityKeyBtn.enable();
        this.clearStatus();
      },

      startRegistration: function () {
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
              let defaultName =
                this._getBrowser() + ' on ' + this._getPlatformName();
              const credentialName = Craft.escapeHtml(
                prompt(
                  Craft.t('app', 'Please enter a name for the security key'),
                  defaultName
                )
              );
              startRegistration(registrationOptions)
                .then((regResponse) => {
                  this.verifyRegistration(regResponse, credentialName);
                })
                .catch((regResponseError) => {
                  this.showStatus(
                    Craft.t('app', 'Registration failed:') +
                      ' ' +
                      regResponseError.message
                  );
                  this.$addSecurityKeyBtn.enable();
                });
            } catch (error) {
              this.showStatus(error);
              this.$addSecurityKeyBtn.enable();
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message);
            this.$addSecurityKeyBtn.enable();
          });
      },

      verifyRegistration: function (startRegistrationResponse, credentialName) {
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
          })
          .finally(() => {
            this.$addSecurityKeyBtn.enable();
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

      _getPlatformName: function () {
        let platform = navigator.platform;

        if (platform.indexOf('Mac') != -1) {
          platform = 'Mac';
        } else if (
          platform.indexOf('iPhone') != -1 ||
          platform.indexOf('Pike')
        ) {
          platform = 'iPhone';
        } else if (platform.indexOf('iPad') != -1) {
          platform = 'iPad';
        } else if (platform.indexOf('iPod') != -1) {
          platform = 'iPod';
        } else if (platform.indexOf('FreeBSD') != -1) {
          platform = 'FreeBSD';
        } else if (platform.indexOf('Linux') != -1) {
          platform = 'Linux';
        } else if (platform.indexOf('Win') != -1) {
          platform = 'Windows';
        } else if (platform.indexOf('Nintendo') != -1) {
          platform = 'Nintendo';
        } else if (platform.indexOf('SunOS') != -1) {
          platform = 'Solaris';
        }
        // in other cases - just use the full name returned by navigator.platform

        return platform;
      },

      _getBrowser: function () {
        let userAgent = navigator.userAgent;
        let browser = '';

        if (userAgent.match(/chrome|chromium|crios/i)) {
          browser = 'Chrome';
        } else if (userAgent.match(/firefox|fxios/i)) {
          browser = 'Firefox';
        } else if (userAgent.match(/safari/i)) {
          browser = 'Safari';
        } else if (userAgent.match(/opr\//i)) {
          browser = 'Opera';
        } else if (userAgent.match(/edg/i)) {
          browser = 'Edge';
        } else if (userAgent.match(/trident/i)) {
          browser = 'IE';
        } else {
          browser = 'Browser';
        }

        return browser;
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
