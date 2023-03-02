(function ($) {
  /** global: Craft */
  /** global: Garnish */
  Craft.Mfa = Garnish.Base.extend(
    {
      $mfaLoginFormContainer: null,
      $mfaSetupFormContainer: null,
      $alternativeMfaLink: null,
      $alternativeMfaOptionsContainer: null,
      $removeSetupButtons: null,
      $submitBtns: null,
      $errors: null,

      init: function (settings) {
        this.$mfaLoginFormContainer = $('#mfa-form');
        this.$mfaSetupFormContainer = $('#mfa-setup');
        this.$alternativeMfaLink = $('#alternative-mfa');
        this.$alternativeMfaOptionsContainer = $('#alternative-mfa-options');
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
          'onAlternativeMfaOption'
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

      getCurrentMfaOption: function ($container) {
        let currentMethod = $container.attr('data-mfa-option');

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

        data.currentMethod = this.getCurrentMfaOption(
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

        let selectedMethod = this.getCurrentMfaOption(
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

        data.currentMethod = this.getCurrentMfaOption($form);

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

      onAlternativeMfaOption: function (event) {
        // get current authenticator class via data-mfa-option
        let currentMethod = this.getCurrentMfaOption(
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
        this.getAlternativeMfaOptions(data);
      },

      getAlternativeMfaOptions: function (data) {
        Craft.sendActionRequest('POST', 'mfa/get-alternative-mfa-options', {
          data,
        })
          .then((response) => {
            if (response.data.alternativeOptions !== undefined) {
              this.showAlternativeMfaOptions(response.data.alternativeOptions);
            }
          })
          .catch(({response}) => {
            this.showError(response.data.message);
          });
      },

      showAlternativeMfaOptions: function (data) {
        let alternativeOptions = Object.entries(data).map(([key, value]) => ({
          key,
          value,
        }));
        if (alternativeOptions.length > 0) {
          alternativeOptions.forEach((option) => {
            this.$alternativeMfaOptionsContainer.append(
              '<li><button ' +
                'class="alternative-mfa-option" ' +
                'type="button" ' +
                'value="' +
                option.key +
                '">' +
                option.value.name +
                '</button></li>'
            );
          });
        }

        // list them by name
        this.$alternativeMfaLink
          .hide()
          .after(this.$alternativeMfaOptionsContainer);

        // clicking on a method name swaps the form fields
        this.addListener(
          $('.alternative-mfa-option'),
          'click',
          'onSelectAlternativeMfaOption'
        );
      },

      onSelectAlternativeMfaOption: function (event) {
        const data = {
          selectedMethod: $(event.currentTarget).attr('value'),
        };

        Craft.sendActionRequest('POST', 'mfa/load-alternative-mfa-option', {
          data,
        })
          .then((response) => {
            if (response.data.mfaForm !== undefined) {
              this.$mfaLoginFormContainer
                .html('')
                .append(response.data.mfaForm);
              this.$alternativeMfaOptionsContainer.html('');
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
