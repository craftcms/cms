import {browserSupportsWebAuthn} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Mfa = Garnish.Base.extend(
    {
      $mfaLoginFormContainer: null,
      $mfaSetupFormContainer: null,
      $alternativeMfaLink: null,
      $alternativeMfaTypesContainer: null,
      $viewSetupBtns: null,
      $errors: null,

      $slideout: null,
      $removeSetupButton: null,
      $closeButton: null,
      $verifyButton: null,

      init: function (settings) {
        this.$mfaLoginFormContainer = $('#mfa-form');
        this.$mfaSetupFormContainer = $('#mfa-setup');
        this.$alternativeMfaLink = $('#alternative-mfa');
        this.$alternativeMfaTypesContainer = $('#alternative-mfa-types');
        this.$viewSetupBtns = this.$mfaSetupFormContainer.find(
          'button.mfa-view-setup'
        );

        this.setSettings(settings, Craft.Mfa.defaults);

        this.addListener(
          this.$alternativeMfaLink,
          'click',
          'onAlternativeMfaTypeClick'
        );
        this.addListener(this.$viewSetupBtns, 'click', 'onViewSetupBtnClick');
      },

      showMfaForm: function (mfaForm, $loginDiv) {
        this.$mfaLoginFormContainer.html('').append(mfaForm);
        $loginDiv.addClass('mfa');
        $('#login-form-buttons').hide();
        const $submitBtn = this.$mfaLoginFormContainer.find('.submit');
        this.$errors = $('#login-errors');

        this.onSubmitResponse($submitBtn);
      },

      getCurrentMfaType: function ($container) {
        let currentMethod = $container.attr('data-mfa-type');

        if (currentMethod === undefined) {
          currentMethod = null;
        }

        return currentMethod;
      },

      submitMfaCode: function () {
        const $submitBtn = this.$mfaLoginFormContainer.find('.submit');
        $submitBtn.addClass('loading');

        let data = {
          mfaFields: {},
          currentMethod: null,
        };

        data.mfaFields = this._getMfaFields(this.$mfaLoginFormContainer);
        data.currentMethod = this._getCurrentMethodInput(
          this.$mfaLoginFormContainer
        );

        Craft.sendActionRequest('POST', 'users/verify-mfa', {data})
          .then((response) => {
            window.location.href = response.data.returnUrl;
          })
          .catch(({response}) => {
            this.onSubmitResponse($submitBtn);

            // Add the error message
            this.showError(response.data.message);
          });
      },

      onViewSetupBtnClick: function (ev) {
        const $button = $(ev.currentTarget);
        $button.disable();
        ev.preventDefault();

        const data = {
          selectedMethod: this.getCurrentMfaType($button),
        };

        Craft.sendActionRequest('POST', this.settings.setupSlideoutHtml, {data})
          .then((response) => {
            this.slideout = new Craft.Slideout(response.data.html);

            this.$errors = this.slideout.$container.find('.so-notice');
            this.$closeButton = this.slideout.$container.find('button.close');

            // initialise webauthn
            if (
              data.selectedMethod === 'craft\\mfa\\type\\WebAuthn' &&
              browserSupportsWebAuthn()
            ) {
              new Craft.WebAuthn(this.slideout);
            }

            this.$verifyButton = this.slideout.$container.find('#mfa-verify');
            this.$removeSetupButton =
              this.slideout.$container.find('#mfa-remove-setup');

            this.addListener(this.$removeSetupButton, 'click', 'onRemoveSetup');
            this.addListener(this.$closeButton, 'click', 'onClickClose');
            this.addListener(this.$verifyButton, 'click', 'onVerify');

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

        let currentMethod = this.getCurrentMfaType(
          this.slideout.$container.find('#mfa-setup-form')
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
              Craft.cp.displayNotice(Craft.t('app', 'MFA setup removed.'));
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

        const $submitBtn = this.slideout.$container.find('#mfa-verify');

        $submitBtn.addClass('loading');

        let data = {
          mfaFields: {},
          currentMethod: null,
        };

        data.mfaFields = this._getMfaFields(this.slideout.$container);
        data.currentMethod = this._getCurrentMethodInput(
          this.slideout.$container
        );

        Craft.sendActionRequest('POST', this.settings.saveSetup, {data})
          .then((response) => {
            this.onSubmitResponse($submitBtn);
            Craft.cp.displayNotice(Craft.t('app', 'MFA settings saved.'));
            this.slideout.close();
          })
          .catch(({response}) => {
            this.onSubmitResponse($submitBtn);

            // Add the error message
            this.showError(response.data.message);
            Craft.cp.displayError(response.data.message);
          });
      },

      onSubmitResponse: function ($submitBtn) {
        $submitBtn.removeClass('loading');
      },

      showError: function (error) {
        this.clearErrors();

        $('<p class="error" style="display: none;">' + error + '</p>')
          .appendTo(this.$errors)
          .velocity('fadeIn');
      },

      clearErrors: function () {
        this.$errors.empty();
      },

      onAlternativeMfaTypeClick: function (event) {
        // get current authenticator class via data-mfa-type
        let currentMethod = this.getCurrentMfaType(
          this.$mfaLoginFormContainer.find('#verifyContainer')
        );
        if (currentMethod === null) {
          this.$alternativeMfaLink.hide();
          this.showError(
            Craft.t('app', 'No alternative MFA methods available.')
          );
        }

        let data = {
          currentMethod: currentMethod,
        };

        // get available MFA methods, minus the one that's being shown
        this.getAlternativeMfaTypes(data);
      },

      getAlternativeMfaTypes: function (data) {
        Craft.sendActionRequest(
          'POST',
          this.settings.fetchAlternativeMfaTypes,
          {
            data,
          }
        )
          .then((response) => {
            if (response.data.alternativeTypes !== undefined) {
              this.showAlternativeMfaTypes(response.data.alternativeTypes);
            }
          })
          .catch(({response}) => {
            this.showError(response.data.message);
          });
      },

      showAlternativeMfaTypes: function (data) {
        let alternativeTypes = Object.entries(data).map(([key, value]) => ({
          key,
          value,
        }));
        if (alternativeTypes.length > 0) {
          alternativeTypes.forEach((type) => {
            this.$alternativeMfaTypesContainer.append(
              '<li><button ' +
                'class="alternative-mfa-type" ' +
                'type="button" ' +
                'value="' +
                type.key +
                '">' +
                type.value.name +
                '</button></li>'
            );
          });
        }

        // list them by name
        this.$alternativeMfaLink
          .hide()
          .after(this.$alternativeMfaTypesContainer);

        // clicking on a method name swaps the form fields
        this.addListener(
          $('.alternative-mfa-type'),
          'click',
          'onSelectAlternativeMfaType'
        );
      },

      onSelectAlternativeMfaType: function (event) {
        const data = {
          selectedMethod: $(event.currentTarget).attr('value'),
        };

        Craft.sendActionRequest('POST', this.settings.loadAlternativeMfaType, {
          data,
        })
          .then((response) => {
            if (response.data.mfaForm !== undefined) {
              this.$mfaLoginFormContainer
                .html('')
                .append(response.data.mfaForm);
              this.$alternativeMfaTypesContainer.html('');
              this.$alternativeMfaLink.show();
              this.onSubmitResponse();
            }
          })
          .catch(({response}) => {
            //this.showError(response.data.message);
          });
      },

      _getMfaFields: function ($container) {
        let mfaFields = {};

        $container
          .find('input[name^="mfaFields[')
          .each(function (index, element) {
            let name = $(element).attr('id');
            mfaFields[name] = $(element).val();
          });

        return mfaFields;
      },

      _getCurrentMethodInput: function ($container) {
        return $container.find('input[name="currentMethod"').val();
      },
    },
    {
      defaults: {
        fetchAlternativeMfaTypes: 'mfa/fetch-alternative-mfa-types',
        loadAlternativeMfaType: 'mfa/load-alternative-mfa-type',
        setupSlideoutHtml: 'mfa/setup-slideout-html',
        saveSetup: 'mfa/save-setup',
        removeSetup: 'mfa/remove-setup',
      },
    }
  );
})(jQuery);
