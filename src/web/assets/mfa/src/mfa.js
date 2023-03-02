(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Mfa = Garnish.Base.extend(
    {
      $mfaLoginFormContainer: null,
      $mfaSetupFormContainer: null,
      $alternativeMfaLink: null,
      $alternativeMfaTypesContainer: null,
      $removeSetupButtons: null,
      $submitBtns: null,
      $errors: null,

      init: function (settings) {
        this.$mfaLoginFormContainer = $('#mfa-form');
        this.$mfaSetupFormContainer = $('#mfa-setup');
        this.$alternativeMfaLink = $('#alternative-mfa');
        this.$alternativeMfaTypesContainer = $('#alternative-mfa-types');
        this.$errors = $('#login-errors');

        this.$submitBtns =
          this.$mfaSetupFormContainer.find('button.mfa-verify');
        this.$removeSetupButtons = this.$mfaSetupFormContainer.find(
          'button.remove-setup'
        );

        this.setSettings(settings, Craft.Mfa.defaults);

        this.addListener(
          this.$alternativeMfaLink,
          'click',
          'onAlternativeMfaType'
        );
        this.addListener(this.$removeSetupButtons, 'click', 'onRemoveSetup');
        this.addListener(this.$submitBtns, 'click', 'onSetupBtnClick');
      },

      showMfaForm: function (mfaForm, $loginDiv) {
        this.$mfaLoginFormContainer.html('').append(mfaForm);
        $loginDiv.addClass('mfa');
        $('#login-form-buttons').hide();
        const $submitBtn = this.$mfaLoginFormContainer.find('.submit');

        this.onSubmitResponse($submitBtn);
      },

      getCurrentMfaType: function ($container) {
        let currentMethod = $container.attr('data-mfa-type');

        if (currentMethod === undefined) {
          currentMethod = null;
        }

        return currentMethod;
      },

      submitLoginMfa: function () {
        const $submitBtn = this.$mfaLoginFormContainer.find('.submit');
        $submitBtn.addClass('loading');

        let data = {
          mfaFields: {},
        };

        this.$mfaLoginFormContainer
          .find('input')
          .each(function (index, element) {
            data.mfaFields[$(element).attr('name')] = $(element).val();
          });

        data.currentMethod = this.getCurrentMfaType(
          this.$mfaLoginFormContainer.find('#verifyContainer')
        );

        Craft.sendActionRequest('POST', 'users/verify-mfa', {data})
          .then((response) => {
            window.location.href = response.data.returnUrl;
          })
          .catch(({response}) => {
            Garnish.shake(this.$form, 'left');
            this.onSubmitResponse($submitBtn);

            // Add the error message
            this.showError(response.data.message);
          });
      },

      onRemoveSetup: function (ev) {
        ev.preventDefault();

        let selectedMethod = this.getCurrentMfaType(
          $(ev.currentTarget).parents('.mfa-setup-form')
        );

        if (selectedMethod === undefined) {
          selectedMethod = null;
        }

        let data = {
          selectedMethod: selectedMethod,
        };

        Craft.sendActionRequest('POST', this.settings.removeSetup, {data})
          .then((response) => {
            $(ev.currentTarget).remove();
            Craft.cp.displayNotice('MFA setup removed.');
          })
          .catch((e) => {
            Craft.cp.displayError(e.response.data.message);
          });
      },

      onSetupBtnClick: function (ev) {
        ev.preventDefault();

        const $form = $(ev.currentTarget).parents('.mfa-setup-form');
        const $submitBtn = $form.find('.submit');

        $submitBtn.addClass('loading');

        let data = {
          mfaFields: {},
        };

        $form.find('input').each(function (index, element) {
          data.mfaFields[$(element).attr('name')] = $(element).val();
        });

        data.currentMethod = this.getCurrentMfaType($form);

        console.log(data);

        Craft.sendActionRequest('POST', 'users/verify-mfa', {data})
          .then((response) => {
            $form.remove();
            this.onSubmitResponse();
          })
          .catch(({response}) => {
            this.onSubmitResponse();

            // Add the error message
            this.showError(response.data.message);
          });
      },

      onSubmitResponse: function ($submitBtn) {
        $submitBtn.removeClass('loading');
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

      onAlternativeMfaType: function (event) {
        // get current authenticator class via data-mfa-type
        let currentMethod = this.getCurrentMfaType(
          this.$mfaLoginFormContainer.find('#verifyContainer')
        );
        if (currentMethod === null) {
          this.$alternativeMfaLink.hide();
          this.showError('No alternative MFA methods available.');
        }

        let data = {
          currentMethod: currentMethod,
        };

        // get available MFA methods, minus the one that's being shown
        this.getAlternativeMfaTypes(data);
      },

      getAlternativeMfaTypes: function (data) {
        Craft.sendActionRequest('POST', 'mfa/get-alternative-mfa-types', {
          data,
        })
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

        Craft.sendActionRequest('POST', 'mfa/load-alternative-mfa-type', {
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
            // console.log(response);
            // this.showError(response.data.message);
          });
      },
    },
    {
      defaults: {
        removeSetup: 'mfa/remove-setup',
      },
    }
  );
})(jQuery);
