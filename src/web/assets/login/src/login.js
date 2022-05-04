import './login.scss';

(function ($) {
  /** global: Craft */
  /** global: Garnish */
  var LoginForm = Garnish.Base.extend({
    $form: null,
    $loginNameInput: null,
    $passwordInput: null,
    $rememberMeCheckbox: null,
    $forgotPasswordLink: null,
    $rememberPasswordLink: null,
    $submitBtn: null,
    $errors: null,

    forgotPassword: false,
    validateOnInput: false,

    init: function () {
      this.$form = $('#login-form');
      this.$loginNameInput = $('#loginName');
      this.$passwordInput = $('#password');
      this.$rememberMeCheckbox = $('#rememberMe');
      this.$forgotPasswordLink = $('#forgot-password');
      this.$rememberPasswordLink = $('#remember-password');
      this.$submitBtn = $('#submit');
      this.$errors = $('#login-errors');

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

      this.$submitBtn.addClass('loading');

      this.clearErrors();

      if (this.forgotPassword) {
        this.submitForgotPassword();
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
        });
    },

    submitLogin: function () {
      var data = {
        loginName: this.$loginNameInput.val(),
        password: this.$passwordInput.val(),
        rememberMe: this.$rememberMeCheckbox.prop('checked') ? 'y' : '',
      };

      Craft.sendActionRequest('POST', 'users/login', {data})
        .then((response) => {
          window.location.href = response.data.returnUrl;
        })
        .catch(({response}) => {
          Garnish.shake(this.$form);
          this.onSubmitResponse();

          // Add the error message
          this.showError(response.data.message);
        });

      return false;
    },

    onSubmitResponse: function () {
      this.$submitBtn.removeClass('loading');
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
      this.$submitBtn.text(
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
