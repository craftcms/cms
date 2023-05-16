import {browserSupportsWebAuthn} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Auth2fa = Garnish.Base.extend(
    {
      $auth2faLoginFormContainer: null,
      $auth2faSetupFormContainer: null,
      $alternative2faLink: null,
      $alternative2faTypesContainer: null,
      $viewSetupBtns: null,
      $errors: null,

      slideout: null,
      $removeSetupButton: null,
      $closeButton: null,
      $verifyButton: null,

      init: function (settings) {
        this.setSettings(settings, Craft.Auth2fa.defaults);

        this.$auth2faLoginFormContainer = $('#auth-2fa-form');
        this.$auth2faSetupFormContainer = $('#auth-2fa-setup');
        this.$viewSetupBtns = this.$auth2faSetupFormContainer.find(
          'button.auth-2fa-view-setup'
        );
        this.$errors = $('#login-errors');

        this.addListener(this.$viewSetupBtns, 'click', 'onViewSetupBtnClick');
      },

      show2faForm: function (auth2faForm, $loginDiv) {
        this.$auth2faLoginFormContainer.html('').append(auth2faForm);
        $loginDiv.addClass('auth-2fa');
        $('#login-form-buttons').hide();

        const $submitBtn = this.$auth2faLoginFormContainer.find('.submit');

        this.connectAlternative2fa();
        this.onSubmitResponse($submitBtn);
      },

      getCurrent2faType: function ($container) {
        let currentMethod = $container.attr('data-2fa-type');

        if (currentMethod === undefined) {
          currentMethod = null;
        }

        return currentMethod;
      },

      initSlideout: function () {
        this.$errors = this.slideout.$container.find('.so-notice');
        this.$closeButton = this.slideout.$container.find('button.close');
        this.$verifyButton = this.slideout.$container.find('#auth2fa-verify');
        this.$removeSetupButton = this.slideout.$container.find(
          '#auth-2fa-remove-setup'
        );

        this.addListener(this.$removeSetupButton, 'click', 'onRemoveSetup');
        this.addListener(this.$closeButton, 'click', 'onClickClose');
        this.addListener(this.$verifyButton, 'click', 'onVerify');
        this.addListener(this.slideout.$container, 'keypress', (ev) => {
          if (ev.keyCode === Garnish.RETURN_KEY) {
            this.$verifyButton.trigger('click');
          }
        });
      },

      onViewSetupBtnClick: function (ev) {
        const $button = $(ev.currentTarget);
        $button.disable();
        ev.preventDefault();

        const data = {
          selectedMethod: this.getCurrent2faType($button),
        };

        Craft.sendActionRequest('POST', this.settings.setupSlideoutHtml, {data})
          .then((response) => {
            this.slideout = new Craft.Slideout(response.data.html);

            if (
              data.selectedMethod === 'craft\\auth\\type\\WebAuthn' &&
              browserSupportsWebAuthn()
            ) {
              new Craft.WebAuthnSetup(this.slideout, this.settings);
            } else if (
              data.selectedMethod === 'craft\\auth\\type\\RecoveryCodes'
            ) {
              new Craft.RecoveryCodesSetup(this.slideout, this.settings);
            } else {
              // for any other auth methods, just make sure we re-initialised the slideout vars
              this.initSlideout();
            }

            this.slideout.on('close', (ev) => {
              this.$removeSetupButton = null;
              this.slideout = null;
              $button.enable();
            });
          })
          .catch(({response}) => {
            // Add the error message
            Craft.cp.displayError(response.data.message);
            $button.enable();
          });
      },

      onClickClose: function (ev) {
        this.slideout.close();
      },

      onRemoveSetup: function (ev) {
        ev.preventDefault();

        let currentMethod = this.getCurrent2faType(
          this.slideout.$container.find('#setup-form-2fa')
        );

        if (currentMethod === undefined) {
          currentMethod = null;
        }

        let data = {
          currentMethod: currentMethod,
        };

        const confirmed = confirm(
          Craft.t('app', 'Are you sure you want to delete this setup?')
        );

        if (confirmed) {
          Craft.sendActionRequest('POST', this.settings.removeSetup, {data})
            .then((response) => {
              $(ev.currentTarget).remove();
              Craft.cp.displayNotice(Craft.t('app', '2FA setup removed.'));
            })
            .catch((e) => {
              Craft.cp.displayError(e.response.data.message);
            })
            .finally(() => {
              this.slideout.close();
            });
        }
      },

      onVerify: function (ev) {
        ev.preventDefault();

        const $submitBtn = this.slideout.$container.find('#auth2fa-verify');

        $submitBtn.addClass('loading');

        let data = {
          auth2faFields: {},
          currentMethod: null,
        };

        data.auth2faFields = this._get2faFields(this.slideout.$container);
        data.currentMethod = this._getCurrentMethodInput(
          this.slideout.$container
        );

        Craft.sendActionRequest('POST', this.settings.saveSetup, {data})
          .then((response) => {
            this.onSubmitResponse($submitBtn);
            Craft.cp.displayNotice(Craft.t('app', '2FA settings saved.'));
            this.slideout.close();
          })
          .catch(({response}) => {
            this.onSubmitResponse($submitBtn);

            // Add the error message
            this.showStatus(response.data.message);
            Craft.cp.displayError(response.data.message);
          });
      },

      onSubmitResponse: function ($submitBtn) {
        $submitBtn.removeClass('loading');
      },

      showStatus: function (message, type = 'error') {
        this.clearStatus();

        if (type == 'error') {
          this.$errors.addClass('error');
        } else {
          this.$errors.removeClass('error');
        }
        this.$errors.text(message);
      },

      clearStatus: function ($errorsContainer = null) {
        if ($errorsContainer !== null) {
          $errorsContainer.empty();
        } else {
          this.$errors.empty();
        }
      },

      verify2faCode: function ($auth2faLoginFormContainer, inModal = false) {
        let data = {
          auth2faFields: {},
          currentMethod: null,
        };

        data.auth2faFields = this._get2faFields($auth2faLoginFormContainer);
        data.currentMethod = this._getCurrentMethodInput(
          $auth2faLoginFormContainer
        );

        return Craft.sendActionRequest('POST', 'users/verify-2fa', {data})
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
            return Promise.reject({
              success: false,
              error: response.data.message,
            });
          });
      },

      onAlternative2faTypeClick: function (event) {
        this.clearStatus();

        let $btn = $(event.currentTarget);
        $btn.attr('disabled', true).disable();

        // get current authenticator class via data-2fa-type
        let currentMethod = this.getCurrent2faType(
          this.$auth2faLoginFormContainer.find('#verifyContainer')
        );
        if (currentMethod === null) {
          this.$alternative2faLink.hide();
          this.showStatus(
            Craft.t('app', 'No alternative 2FA methods available.')
          );
        }

        let data = {
          currentMethod: currentMethod,
        };

        // get available 2FA methods, minus the one that's being shown
        this.getAlternative2faTypes(data, $btn);
      },

      getAlternative2faTypes: function (data, $btn) {
        Craft.sendActionRequest(
          'POST',
          this.settings.fetchAlternative2faTypes,
          {
            data,
          }
        )
          .then((response) => {
            if (response.data.alternativeTypes !== undefined) {
              this.showAlternative2faTypes(response.data.alternativeTypes);
            }
          })
          .catch(({response}) => {
            this.showStatus(response.data.message);
          })
          .finally(() => {
            $btn.attr('disabled', false).enable();
          });
      },

      showAlternative2faTypes: function (data) {
        this.$alternative2faTypesContainer.empty();
        let alternativeTypes = Object.entries(data).map(([key, value]) => ({
          key,
          value,
        }));
        if (alternativeTypes.length > 0) {
          alternativeTypes.forEach((type) => {
            this.$alternative2faTypesContainer.append(
              '<li><button ' +
                'class="alternative-2fa-type" ' +
                'type="button" ' +
                'value="' +
                type.key +
                '">' +
                type.value.name +
                '</button></li>'
            );
          });
        } else {
          this.showStatus(
            Craft.t('app', 'No alternative 2FA methods available.')
          );
        }

        // list them by name
        this.$alternative2faLink
          .hide()
          .after(this.$alternative2faTypesContainer);

        // clicking on a method name swaps the form fields
        this.addListener(
          $('.alternative-2fa-type'),
          'click',
          'onSelectAlternative2faType'
        );
      },

      onSelectAlternative2faType: function (event) {
        let $btn = $(event.currentTarget);
        $btn.attr('disabled', true).disable();

        const data = {
          selectedMethod: $(event.currentTarget).attr('value'),
        };

        Craft.sendActionRequest('POST', this.settings.loadAlternative2faType, {
          data,
        })
          .then((response) => {
            if (response.data.auth2faForm !== undefined) {
              this.$auth2faLoginFormContainer
                .html('')
                .append(response.data.auth2faForm);

              this.connectAlternative2fa();
              this.onSubmitResponse();
            }
          })
          .catch(({response}) => {
            //this.showStatus(response.data.message);
          })
          .finally(() => {
            $btn.attr('disabled', false).enable();
          });
      },

      connectAlternative2fa: function () {
        this.$alternative2faLink =
          this.$auth2faLoginFormContainer.find('#alternative-2fa');
        this.$alternative2faTypesContainer =
          this.$auth2faLoginFormContainer.find('#alternative-2fa-types');
        this.addListener(
          this.$alternative2faLink,
          'click',
          'onAlternative2faTypeClick'
        );
      },

      _get2faFields: function ($container) {
        let auth2faFields = {};

        $container
          .find('input[name^="auth2faFields[')
          .each(function (index, element) {
            let name = $(element).attr('id');
            auth2faFields[name] = $(element).val();
          });

        return auth2faFields;
      },

      _getCurrentMethodInput: function ($container) {
        return $container.find('input[name="currentMethod"').val();
      },
    },
    {
      defaults: {
        fetchAlternative2faTypes: 'auth/fetch-alternative-2fa-types',
        loadAlternative2faType: 'auth/load-alternative-2fa-type',
        setupSlideoutHtml: 'auth/setup-slideout-html',
        saveSetup: 'auth/save-setup',
        removeSetup: 'auth/remove-setup',
      },
    }
  );
})(jQuery);
