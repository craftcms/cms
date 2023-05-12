import './login.scss';

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
    validateOnInput: false,

    auth2faFlow: false,
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

      if (!this.forgotPassword) {
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
      } else if (this.auth2faFlow) {
        this.submit2faCode();
      } else {
        this.submitLogin();
      }
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
        })
        .finally(() => {
          this.$submitBtn.successEvent();
          this.$submitBtn.updateMessages(Craft.t('app', 'Reset Password'));
        });
    },

    submit2faCode: function () {
      this.clearErrors();

      let $btn = $('#auth-2fa-form').find('button');
      $btn = new Garnish.MultiFunctionBtn($btn);
      $btn.busyEvent();

      const auth2fa = new Craft.Auth2fa();
      auth2fa
        .verify2faCode($('#auth-2fa-form'), false)
        .then((response) => {
          $btn.successEvent();
          window.location.href = response.returnUrl;
        })
        .catch((response) => {
          $btn.failureEvent();
          this.processFailure(response.error);
        });
    },

    submitLogin: function () {
      this.clearErrors();
      this.$submitBtn.busyEvent();
      this.auth2fa = new Craft.Auth2fa();

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
            this.auth2faFlow = true;
            if (this.$alternativeLoginLink !== null) {
              this.$alternativeLoginLink.remove();
            }
            this.auth2fa.show2faForm(response.data.auth2faForm, this.$loginDiv);
            this.$submitBtn.endBusyState();
          } else {
            this.$submitBtn.successEvent();
            window.location.href = response.data.returnUrl;
          }
        })
        .catch(({response}) => {
          this.$submitBtn.failureEvent();
          this.processFailure(response.data.message);
        });

      return false;
    },

    processFailure: function (error) {
      Garnish.shake(this.$form, 'left');

      // Add the error message
      this.showError(error);
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
