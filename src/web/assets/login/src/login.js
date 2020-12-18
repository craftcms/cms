(function($) {
    /** global: Craft */
    /** global: Garnish */
    var LoginForm = Garnish.Base.extend(
        {
            $form: null,
            $loginNameInput: null,
            $passwordInput: null,
            $rememberMeCheckbox: null,
            $forgotPasswordLink: null,
            $rememberPasswordLink: null,
            $submitBtn: null,
            $spinner: null,
            $error: null,

            forgotPassword: false,
            validateOnInput: false,

            init: function() {
                this.$form = $('#login-form');
                this.$loginNameInput = $('#loginName');
                this.$passwordInput = $('#password');
                this.$forgotPasswordLink = $('#forgot-password');
                this.$rememberPasswordLink = $('#remember-password');
                this.$submitBtn = $('#submit');
                this.$spinner = $('#spinner');
                this.$rememberMeCheckbox = $('#rememberMe');

                new Craft.PasswordInput(this.$passwordInput, {
                    onToggleInput: $.proxy(function($newPasswordInput) {
                        this.removeListener(this.$passwordInput, 'input');
                        this.$passwordInput = $newPasswordInput;
                        this.addListener(this.$passwordInput, 'input', 'onInput');
                    }, this)
                });

                this.addListener(this.$loginNameInput, 'input', 'onInput')
                this.addListener(this.$passwordInput, 'input', 'onInput');
                this.addListener(this.$forgotPasswordLink, 'click', 'onSwitchForm');
                this.addListener(this.$rememberPasswordLink, 'click', 'onSwitchForm');
                this.addListener(this.$form, 'submit', 'onSubmit');
            },

            validate: function() {
                if (this.$loginNameInput.val() && (this.forgotPassword || this.$passwordInput.val().length >= 6)) {
                    this.removeError();
                    return true;
                }
                else {
                    this.showError(Craft.t('app', 'Couldn’t log in. Please check your username or email and password.'));
                    return false;
                }
            },

            onInput: function(event) {
                if (this.validateOnInput) {
                    this.validate();
                }
            },

            onSubmit: function(event) {
                // Prevent full HTTP submits
                event.preventDefault();

                if (!this.validate()) {
                    this.validateOnInput = true;
                    return;
                }

                this.$submitBtn.addClass('active');
                this.$spinner.removeClass('hidden');

                this.removeError();

                if (this.forgotPassword) {
                    this.submitForgotPassword();
                }
                else {
                    this.submitLogin();
                }
            },

            submitForgotPassword: function() {
                var data = {
                    loginName: this.$loginNameInput.val()
                };

                Craft.postActionRequest('users/send-password-reset-email', data, $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        if (response.success) {
                            new MessageSentModal();
                        }
                        else {
                            this.showError(response.error);
                        }
                    }

                    this.onSubmitResponse();
                }, this));
            },

            submitLogin: function() {
                var data = {
                    loginName: this.$loginNameInput.val(),
                    password: this.$passwordInput.val(),
                    rememberMe: (this.$rememberMeCheckbox.prop('checked') ? 'y' : '')
                };

                Craft.postActionRequest('users/login', data, $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        if (response.success) {
                            window.location.href = response.returnUrl;
                        }
                        else {
                            Garnish.shake(this.$form);
                            this.onSubmitResponse();

                            // Add the error message
                            this.showError(response.error);
                        }
                    }
                    else {
                        this.onSubmitResponse();
                    }
                }, this));

                return false;
            },

            onSubmitResponse: function() {
                this.$submitBtn.removeClass('active');
                this.$spinner.addClass('hidden');
            },

            showError: function(error) {
                this.removeError();

                this.$error = $('<p class="error" style="display:none">' + error + '</p>').insertAfter($('.buttons', this.$form));
                this.$error.velocity('fadeIn');
            },

            removeError: function() {
                if (this.$error) {
                    this.$error.remove();
                }
            },

            onSwitchForm: function(event) {
                if (!Garnish.isMobileBrowser()) {
                    this.$loginNameInput.trigger('focus');
                }

                this.removeError();

                this.forgotPassword = !this.forgotPassword;

                this.$form.toggleClass('reset-password', this.forgotPassword);
                this.$submitBtn.text(Craft.t('app', this.forgotPassword ? 'Reset Password': 'Login'));
            },
        });


    var MessageSentModal = Garnish.Modal.extend(
        {
            init: function() {
                var $container = $('<div class="modal fitted email-sent"><div class="body">' + Craft.t('app', 'Check your email for instructions to reset your password.') + '</div></div>')
                    .appendTo(Garnish.$bod);

                this.base($container);
            },

            hide: function() {
            }
        });


    new LoginForm();
})(jQuery);
