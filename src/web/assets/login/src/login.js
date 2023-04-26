import './login.scss';
import {browserSupportsWebAuthn} from '@simplewebauthn/browser';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  var LoginForm = Garnish.Base.extend({
    $loginDiv: null,
    $form: null,
    $loginNameInput: null,
    $passwordInput: null,
    $rememberMeCheckbox: null,
    $forgotPasswordLink: null,
    $rememberPasswordLink: null,
    $alternativeLoginLink: null,
    $submitBtn: null,
    $errors: null,

    forgotPassword: false,
    loginWithPassword: false,
    loginWithSecurityKey: false,
    validateOnInput: false,

    mfaFlow: false,
    auth2fa: null,

    init: function () {
      this.$loginDiv = $('#login');
      this.$form = $('#login-form');
      this.$loginNameInput = $('#loginName');
      this.$passwordInput = $('#password');
      this.$rememberMeCheckbox = $('#rememberMe');
      this.$forgotPasswordLink = $('#forgot-password');
      this.$rememberPasswordLink = $('#remember-password');
      this.$submitBtn = $('#submit');
      this.$errors = $('#login-errors');

      this.$submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
        changeButtonText: true,
      });

      new Craft.PasswordInput(this.$passwordInput, {
        onToggleInput: ($newPasswordInput) => {
          this.removeListener(this.$passwordInput, 'input');
          this.$passwordInput = $newPasswordInput;
          this.addListener(this.$passwordInput, 'input', 'onInput');
        },
      });

      if (!browserSupportsWebAuthn()) {
        this.loginWithPassword = true;
      }

      if (!this.loginWithPassword && !this.loginWithSecurityKey) {
        this.$passwordInput.hide();
        this.$forgotPasswordLink.hide();
        this.$rememberMeCheckbox.parents('.field').hide();
        this.$submitBtn.updateMessages(Craft.t('app', 'Continue'));
      }

      this.addListener(this.$loginNameInput, 'input', 'onInput');
      this.addListener(this.$passwordInput, 'input', 'onInput');
      this.addListener(this.$forgotPasswordLink, 'click', 'onSwitchForm');
      this.addListener(this.$rememberPasswordLink, 'click', 'onSwitchForm');
      this.addListener(this.$form, 'submit', 'onSubmit');

      // Focus first empty field in form
      if (!Garnish.isMobileBrowser()) {
        if (this.$loginNameInput.val()) {
          this.$passwordInput.focus();
        } else {
          this.$loginNameInput.focus();
        }
      }
    },

    validate: function () {
      const loginNameVal = this.$loginNameInput.val();
      if (loginNameVal.length === 0) {
        if (window.useEmailAsUsername) {
          return Craft.t('app', 'Invalid email.');
        }
        return Craft.t('app', 'Invalid username or email.');
      }

      if (window.useEmailAsUsername && !loginNameVal.match('.+@.+..+')) {
        return Craft.t('app', 'Invalid email.');
      }

      if (!this.forgotPassword && this.loginWithPassword) {
        const passwordLength = this.$passwordInput.val().length;
        if (passwordLength < window.minPasswordLength) {
          return Craft.t(
            'yii',
            '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.',
            {
              attribute: Craft.t('app', 'Password'),
              min: window.minPasswordLength,
            }
          );
        }
        if (passwordLength > window.maxPasswordLength) {
          return Craft.t(
            'yii',
            '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.',
            {
              attribute: Craft.t('app', 'Password'),
              max: window.maxPasswordLength,
            }
          );
        }
      }

      return true;
    },

    onInput: function (event) {
      if (this.validateOnInput && this.validate() === true) {
        this.clearErrors();
      }
    },

    onSubmit: function (event) {
      // Prevent full HTTP submits
      event.preventDefault();

      const error = this.validate();
      if (error !== true) {
        this.showError(error);
        this.validateOnInput = true;
        return;
      }

      this.$submitBtn.busyEvent();

      this.clearErrors();

      if (this.forgotPassword) {
        this.submitForgotPassword();
      } else if (this.loginWithSecurityKey) {
        this.webauthnLogin();
      } else if (this.mfaFlow) {
        this.submitMfaCode();
      } else if (this.loginWithPassword) {
        this.submitLogin();
      } else {
        this.submitFindUser();
      }
    },

    submitFindUser: function () {
      var data = {
        loginName: this.$loginNameInput.val(),
      };

      Craft.sendActionRequest('POST', 'users/get-user-for-login', {data})
        .then((response) => {
          if (
            browserSupportsWebAuthn() &&
            response.data.hasSecurityKeys !== undefined &&
            response.data.hasSecurityKeys == true
          ) {
            this.loginWithSecurityKey = true;
            this.$rememberMeCheckbox.parents('.field').show();
            this.$submitBtn.updateMessages(
              Craft.t('app', 'Sign in with a security key')
            );

            if (this.$alternativeLoginLink === null) {
              $('#login-form-extra')
                .remove('#alternative-login')
                .prepend(
                  '<button id="alternative-login" type="button" class="btn">' +
                    Craft.t('app', 'Use password to login') +
                    '</button>'
                );
              this.$alternativeLoginLink = $('#alternative-login');
              this.addListener(
                this.$alternativeLoginLink,
                'click',
                'onAlternativeLoginLink'
              );
            }
          } else {
            this.loginWithPassword = true;
            this.$passwordInput.show();
            this.$forgotPasswordLink.show();
            this.$rememberMeCheckbox.parents('.field').show();
            this.$submitBtn.updateMessages(Craft.t('app', 'Sign in'));
            this.auth2fa = new Craft.Auth2fa();
          }
        })
        .catch(({response}) => {
          this.processFailure(response.data.message);
        })
        .finally(() => {
          this.$submitBtn.endBusyState();
        });

      return false;
    },

    submitForgotPassword: function () {
      var data = {
        loginName: this.$loginNameInput.val(),
      };

      Craft.sendActionRequest('POST', 'users/send-password-reset-email', {data})
        .then((response) => {
          new MessageSentModal();
        })
        .catch(({response}) => {
          this.showError(response.data.message);
        });
    },

    webauthnLogin: function () {
      this.clearErrors();
      this.$submitBtn.busyEvent();

      var data = {
        loginName: this.$loginNameInput.val(),
        rememberMe: this.$rememberMeCheckbox.prop('checked') ? 'y' : '',
      };

      new Craft.Auth2faLogin.startWebauthnLogin(data, false)
        .then((response) => {
          this.$submitBtn.successEvent();
          if (response.returnUrl != undefined) {
            window.location.href = response.returnUrl;
          }
        })
        .catch((response) => {
          this.processFailure(response.error);
        });
    },

    submitMfaCode: function () {
      this.clearErrors();
      this.$submitBtn.busyEvent();

      new Craft.Auth2faLogin.submitMfaCode($('#mfa-form'), false)
        .then((response) => {
          this.$submitBtn.successEvent();
          window.location.href = response.returnUrl;
        })
        .catch((response) => {
          this.processFailure(response.message);
        });
    },

    submitLogin: function () {
      this.clearErrors();
      this.$submitBtn.busyEvent();

      var data = {
        loginName: this.$loginNameInput.val(),
        password: this.$passwordInput.val(),
        rememberMe: this.$rememberMeCheckbox.prop('checked') ? 'y' : '',
      };

      Craft.sendActionRequest('POST', 'users/login', {data})
        .then((response) => {
          if (
            response.data.auth2fa !== undefined &&
            response.data.auth2fa == true
          ) {
            this.mfaFlow = true;
            if (this.$alternativeLoginLink !== null) {
              this.$alternativeLoginLink.remove();
            }
            this.auth2fa.showMfaForm(response.data.auth2faForm, this.$loginDiv);
            this.$submitBtn.endBusyState();
          } else {
            this.$submitBtn.successEvent();
            window.location.href = response.data.returnUrl;
          }
        })
        .catch(({response}) => {
          this.processFailure(response.data.message);
        });

      return false;
    },

    processFailure: function (error) {
      Garnish.shake(this.$form, 'left');
      this.onSubmitResponse();

      // Add the error message
      this.showError(error);
      this.$submitBtn.failureEvent();
    },

    onSubmitResponse: function () {
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

    onSwitchForm: function (event) {
      if (!Garnish.isMobileBrowser()) {
        this.$loginNameInput.trigger('focus');
      }

      this.clearErrors();

      this.forgotPassword = !this.forgotPassword;

      this.$form.toggleClass('reset-password', this.forgotPassword);
      this.$submitBtn.updateMessages(
        Craft.t('app', this.forgotPassword ? 'Reset Password' : 'Sign in')
      );
    },

    onAlternativeLoginLink: function (event) {
      if (!Garnish.isMobileBrowser()) {
        this.$loginNameInput.trigger('focus');
      }

      this.clearErrors();

      this.loginWithPassword = !this.loginWithPassword;
      this.loginWithSecurityKey = !this.loginWithSecurityKey;

      this.$passwordInput.toggle();
      this.$forgotPasswordLink.toggle();

      if (this.loginWithPassword) {
        this.$submitBtn.updateMessages(Craft.t('app', 'Sign in'));
        this.$alternativeLoginLink.text(
          Craft.t('app', 'Use a security key to login')
        );
        this.auth2fa = new Craft.Auth2fa();
      } else if (this.loginWithSecurityKey) {
        this.$submitBtn.updateMessages(
          Craft.t('app', 'Sign in using a security key')
        );
        this.$alternativeLoginLink.text(
          Craft.t('app', 'Use a password to login')
        );
      }
    },
  });

  var MessageSentModal = Garnish.Modal.extend({
    init: function () {
      var $container = $(
        '<div class="modal fitted email-sent"><div class="body">' +
          Craft.t(
            'app',
            'Check your email for instructions to reset your password.'
          ) +
          '</div></div>'
      ).appendTo(Garnish.$bod);

      this.base($container);
    },

    hide: function () {},
  });

  new LoginForm();
})(jQuery);
