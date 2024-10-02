/** global: Craft */
import {
  browserSupportsWebAuthn,
  platformAuthenticatorIsAvailable,
  startAuthentication,
} from '@simplewebauthn/browser';

/** global: Garnish */
Craft.LoginForm = Garnish.Base.extend(
  {
    $container: null,
    $form: null,
    $spinner: null,
    $usernameInput: null,
    $passwordInput: null,
    $rememberMeCheckbox: null,
    $forgotPasswordLink: null,
    $submitBtn: null,
    submitBtn: null,
    $errors: null,
    $altMethodContainer: null,
    $passkeyBtn: null,
    passkeyBtn: null,

    modal: null,
    resetPasswordForm: null,
    validateOnInput: false,

    async init(container, settings) {
      this.$container = $(container);
      this.$pane = this.$container.find('.login-form-container');
      this.$form = this.$container.find('.login-form');
      this.$usernameInput = this.$form.find('.login-username');
      this.$passwordInput = this.$form.find('.login-password');
      this.$rememberMeCheckbox = this.$form.find('.login-remember-me');
      this.$forgotPasswordLink = this.$form.find('.login-forgot-password');
      this.$submitBtn = this.$form.find('button.submit');
      this.$errors = this.$container.find('.login-errors');
      this.$altMethodContainer = this.$container.find(
        '.alternative-login-methods'
      );
      this.$passkeyBtn = this.$altMethodContainer.find('.login-passkey-btn');

      this.setSettings(settings, Craft.LoginForm.defaults);

      this.modal = this.$container.closest('.modal').data('modal');

      this.submitBtn = new Garnish.MultiFunctionBtn(this.$submitBtn, {
        changeButtonText: true,
      });
      this.passkeyBtn = new Garnish.MultiFunctionBtn(this.$passkeyBtn);

      this.$spinner = document.createElement('craft-spinner');
      this.$spinner.setAttribute('visible', false);
      this.$spinner.classList.add('center-absolute');

      $(this.$spinner).insertAfter(this.$form);

      new Craft.PasswordInput(this.$passwordInput, {
        onToggleInput: ($newPasswordInput) => {
          this.removeListener(this.$passwordInput, 'input');
          this.$passwordInput = $newPasswordInput;
          this.addListener(this.$passwordInput, 'input', 'onInput');
        },
      });

      this.addListener(this.$usernameInput, 'input', 'onInput');
      this.addListener(this.$passwordInput, 'input', 'onInput');
      this.addListener(
        this.$forgotPasswordLink,
        'activate',
        'showResetPasswordForm'
      );
      this.addListener(this.$form, 'submit', 'onSubmit');

      // Focus first empty field in form
      if (!Garnish.isMobileBrowser()) {
        if (this.$usernameInput.val()) {
          this.$passwordInput.focus();
        } else {
          this.$usernameInput.focus();
        }
      }

      if (
        this.settings.showPasskeyBtn &&
        browserSupportsWebAuthn() &&
        (await platformAuthenticatorIsAvailable())
      ) {
        this.$passkeyBtn.removeClass('hidden');
        this.onResize();
        this.addListener(this.$passkeyBtn, 'activate', () => {
          this.loginWithPasskey();
        });
      }

      if (
        this.$altMethodContainer.children().filter('.btn:not(.hidden)').length
      ) {
        this.$altMethodContainer.removeClass('hidden');
      }
    },

    validate() {
      const usernameValidates = Craft.LoginForm.validateUsernameOrEmail(
        this.$usernameInput.val()
      );
      if (usernameValidates !== true) {
        return usernameValidates;
      }

      const passwordLength = this.$passwordInput.val().length;
      if (passwordLength < Craft.minPasswordLength) {
        return Craft.t(
          'yii',
          '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.',
          {
            attribute: Craft.t('app', 'Password'),
            min: Craft.minPasswordLength,
          }
        );
      }
      if (passwordLength > Craft.maxPasswordLength) {
        return Craft.t(
          'yii',
          '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.',
          {
            attribute: Craft.t('app', 'Password'),
            max: Craft.maxPasswordLength,
          }
        );
      }

      return true;
    },

    onInput() {
      if (this.validateOnInput && this.validate() === true) {
        this.clearErrors();
      }
    },

    onSubmit(event) {
      // Prevent full HTTP submits
      event.preventDefault();

      const error = this.validate();
      if (error !== true) {
        this.showError(error);
        this.validateOnInput = true;
        return;
      }

      this.clearErrors();
      this.submitBtn.busyEvent();

      const data = {
        loginName: this.$usernameInput.val(),
        password: this.$passwordInput.val(),
        rememberMe: this.$rememberMeCheckbox.prop('checked') ? 'y' : '',
      };

      Craft.sendActionRequest('POST', 'users/login', {data})
        .then(({data}) => {
          if (data.authMethod) {
            this.show2faForm(data);
          } else {
            this.submitBtn.successEvent();
            this.settings.onLogin(data.returnUrl);
          }
        })
        .catch((e) => {
          this.submitBtn.failureEvent();

          Garnish.shake(this.$form, 'left');

          // Add the error message
          this.showError(
            e?.response?.data?.message ||
              Craft.t('app', 'A server error occurred.')
          );

          this.submitBtn.failureEvent();
        });
    },

    showResetPasswordForm() {
      this.clearErrors();
      this.$form.addClass('hidden');

      if (!this.resetPasswordForm) {
        this.resetPasswordForm = new Craft.LoginForm.ResetPasswordForm(
          this,
          this.$container
        );
      }

      this.resetPasswordForm.$form.removeClass('hidden');
      this.resetPasswordForm.$usernameInput.val(this.$usernameInput.val());

      if (!Garnish.isMobileBrowser()) {
        this.resetPasswordForm.$usernameInput.focus();
      }

      this.onResize();
    },

    async show2faForm(data) {
      this.clearErrors();
      this.$form.addClass('hidden');

      const $authForm = $(data.authForm).insertAfter(this.$form);
      await Craft.appendHeadHtml(data.headHtml);
      await Craft.appendBodyHtml(data.bodyHtml);
      Craft.initUiElements($authForm);

      Craft.createAuthFormHandler(
        data.authMethod,
        $authForm,
        () => {
          this.settings.onLogin(data.returnUrl);
        },
        (error) => {
          this.showError(error);
        }
      );

      if (!Garnish.isMobileBrowser()) {
        setTimeout(() => {
          $authForm.find(':focusable:first').focus();
        }, 100);
      }

      if (data.otherMethods.length) {
        const $hr = $('<hr/>').insertAfter($authForm);
        const $altContainer = $(
          '<div class="login-alt-container"/>'
        ).insertAfter($hr);

        const $menu = $(
          '<div id="login-alt-menu" class="login-alt-menu menu menu--disclosure"/>'
        ).appendTo($altContainer);
        const $ul = $('<ul/>').appendTo($menu);
        for (let method of data.otherMethods) {
          $('<li/>')
            .append(
              $('<button/>', {
                text: method.name,
                'data-method': method.class,
                class: 'menu-item',
              })
            )
            .appendTo($ul);
        }

        const $button = $('<button/>', {
          type: 'button',
          'aria-controls': 'login-alt-menu',
          class: 'menu-toggle',
          html: Craft.t('app', 'Try another way'),
        }).appendTo($altContainer);

        const $methodDisclosure = new Garnish.DisclosureMenu($button);

        $ul.find('button').on('activate', (event) => {
          const tempHeight = this.$pane.outerHeight();
          this.$pane.outerHeight(tempHeight);

          this.$spinner.visible = true;
          this.$spinner.focus();
          $methodDisclosure.hide();
          $authForm.remove();
          $hr.remove();
          $altContainer.remove();

          Craft.sendActionRequest('post', 'users/auth-form', {
            data: {
              method: $(event.target).data('method'),
            },
          })
            .then(({data}) => {
              this.$pane.removeAttr('style');
              this.show2faForm(data);
            })
            .finally(() => {
              this.$spinner.visible = false;
            });
        });
      }

      this.onResize();
    },

    showError(error) {
      this.clearErrors();

      $('<p style="display: none;">' + error + '</p>')
        .appendTo(this.$errors)
        .velocity('fadeIn');

      this.$errors.removeClass('hidden');
      Craft.cp.announce(error);
      this.onResize();
    },

    clearErrors() {
      this.$errors.empty().addClass('hidden');
      this.onResize();
    },

    onResize() {
      if (this.modal) {
        Garnish.requestAnimationFrame(() => {
          this.modal.updateSizeAndPosition();
        });
      }
    },

    async loginWithPasskey() {
      if (this.$passkeyBtn.hasClass('loading')) {
        return;
      }

      this.passkeyBtn.busyEvent();

      try {
        const optionsResponse = await Craft.sendActionRequest(
          'POST',
          'auth/passkey-request-options'
        );
        const authResponse = await startAuthentication(
          optionsResponse.data.options
        );
        const loginResponse = await Craft.sendActionRequest(
          'POST',
          'users/login-with-passkey',
          {
            data: {
              requestOptions: JSON.stringify(optionsResponse.data.options),
              response: JSON.stringify(authResponse),
            },
          }
        );

        this.passkeyBtn.successEvent();
        this.settings.onLogin(loginResponse.data.returnUrl);
      } catch (e) {
        const message = e?.response?.data?.message;

        this.passkeyBtn.failureEvent();

        if (message) {
          this.showError(message);
        }
      } finally {
        this.passkeyBtn.endBusyState();
      }
    },
  },
  {
    validateUsernameOrEmail(val) {
      if (val.length === 0) {
        if (Craft.useEmailAsUsername) {
          return Craft.t('app', 'Invalid email.');
        }
        return Craft.t('app', 'Invalid username or email.');
      }

      if (Craft.useEmailAsUsername && !val.match('.+@.+..+')) {
        return Craft.t('app', 'Invalid email.');
      }

      return true;
    },

    defaults: {
      showPasskeyBtn: true,
      onLogin: (returnUrl) => {
        window.location.href = returnUrl;
      },
    },
  }
);

Craft.LoginForm.ResetPasswordForm = Garnish.Base.extend({
  loginForm: null,
  $container: null,
  $form: null,
  $usernameInput: null,
  $submitBtn: null,
  $backBtn: null,
  validateOnInput: false,

  init(loginForm, container) {
    this.loginForm = loginForm;
    this.$container = $(container);
    this.$form = this.$container
      .find('.login-reset-password')
      .removeClass('hidden');
    this.$usernameInput = this.$form.find('.login-username');
    this.$submitBtn = this.$form.find('button.submit');
    this.$backBtn = this.$form.find('.login-reset-back-btn');

    this.addListener(this.$usernameInput, 'input', 'onInput');
    this.addListener(this.$form, 'submit', 'onSubmit');
    this.addListener(this.$backBtn, 'activate', 'showLoginForm');
  },

  validate() {
    return Craft.LoginForm.validateUsernameOrEmail(this.$usernameInput.val());
  },

  onInput() {
    if (this.validateOnInput && this.validate() === true) {
      this.loginForm.clearErrors();
    }
  },

  onSubmit(event) {
    // Prevent full HTTP submits
    event.preventDefault();

    const error = this.validate();
    if (error !== true) {
      this.loginForm.showError(error);
      this.validateOnInput = true;
      return;
    }

    this.loginForm.clearErrors();
    this.$submitBtn.addClass('loading');
    Craft.cp.announce(Craft.t('app', 'Loading'));

    const data = {
      loginName: this.$usernameInput.val(),
    };

    Craft.sendActionRequest('POST', 'users/send-password-reset-email', {data})
      .then((response) => {
        new Craft.LoginForm.ResetPasswordForm.MessageSentModal();
      })
      .catch((error) => {
        this.showError(
          (error &&
            error.response &&
            error.response.data &&
            error.response.data.message) ||
            Craft.t('app', 'A server error occurred.')
        );
      })
      .finally(() => {
        this.$submitBtn.removeClass('loading');
      });
  },

  showLoginForm() {
    this.loginForm.clearErrors();
    this.$form.addClass('hidden');
    this.loginForm.$form.removeClass('hidden');
    this.loginForm.$usernameInput.val(this.$usernameInput.val());

    if (!Garnish.isMobileBrowser()) {
      this.loginForm.$usernameInput.focus();
    }

    this.loginForm.onResize();
  },
});

Craft.LoginForm.ResetPasswordForm.MessageSentModal = Garnish.Modal.extend({
  init() {
    const $container = $(
      '<div class="modal fitted email-sent"><div class="body">' +
        Craft.t(
          'app',
          'Check your email for instructions to reset your password.'
        ) +
        '</div></div>'
    ).appendTo(Garnish.$bod);

    this.base($container);
  },

  hide() {},
});
