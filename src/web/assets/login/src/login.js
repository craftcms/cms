(function($) {
    /** global: Craft */
    /** global: Garnish */
    var LoginForm = Garnish.Base.extend(
        {
            $form: null,
            $loginNameInput: null,
            $passwordFields: null,
            $passwordField: null,
            $passwordInput: null,
            $forgotPasswordLink: null,
            $rememberMeCheckbox: null,
            $sslIcon: null,
            $submitBtn: null,
            $spinner: null,
            $error: null,

            passwordInputInterval: null,
            forgotPassword: false,
            loading: false,

            init: function() {
                this.$form = $('#login-form');
                this.$loginNameInput = $('#loginName');
                this.$passwordFields = $('#password-fields');
                this.$passwordField = $('#password-field');
                this.$passwordInput = $('#password');
                this.$forgotPasswordLink = $('#forgot-password');
                this.$sslIcon = $('#ssl-icon');
                this.$submitBtn = $('#submit');
                this.$spinner = $('#spinner');
                this.$rememberMeCheckbox = $('#rememberMe');

                new Craft.PasswordInput(this.$passwordInput, {
                    onToggleInput: $.proxy(function($newPasswordInput) {
                        this.removeListener(this.$passwordInput, 'input');
                        this.$passwordInput = $newPasswordInput;
                        this.addListener(this.$passwordInput, 'input', 'validate');
                    }, this)
                });

                this.addListener(this.$loginNameInput, 'input', 'validate');
                this.addListener(this.$passwordInput, 'input', 'validate');
                this.addListener(this.$forgotPasswordLink, 'click', 'onForgetPassword');
                this.addListener(this.$form, 'submit', 'onSubmit');

                // Super hacky!
                this.addListener(this.$sslIcon, 'mouseover', function() {
                    if (this.$sslIcon.hasClass('disabled')) {
                        return;
                    }

                    this.$submitBtn.addClass('hover');
                });
                this.addListener(this.$sslIcon, 'mouseout', function() {
                    if (this.$sslIcon.hasClass('disabled')) {
                        return;
                    }

                    this.$submitBtn.removeClass('hover');
                });
                this.addListener(this.$sslIcon, 'mousedown', function() {
                    if (this.$sslIcon.hasClass('disabled')) {
                        return;
                    }

                    this.$submitBtn.addClass('active');

                    this.addListener(Garnish.$doc, 'mouseup', function() {
                        this.$submitBtn.removeClass('active');
                        this.removeListener(Garnish.$doc, 'mouseup');
                    });
                });

                // Manually validate the inputs every 250ms since some browsers don't fire events when autofill is used
                // http://stackoverflow.com/questions/11708092/detecting-browser-autofill
                this.passwordInputInterval = setInterval($.proxy(this, 'validate'), 250);

                this.addListener(this.$sslIcon, 'click', function() {
                    this.$submitBtn.trigger('click');
                });
            },

            validate: function() {
                if (this.$loginNameInput.val() && (this.forgotPassword || this.$passwordInput.val().length >= 6)) {
                    this.$sslIcon.enable();
                    this.$submitBtn.enable();
                    return true;
                }
                else {
                    this.$sslIcon.disable();
                    this.$submitBtn.disable();
                    return false;
                }
            },

            onSubmit: function(event) {
                // Prevent full HTTP submits
                event.preventDefault();

                if (!this.validate()) {
                    return;
                }

                this.$submitBtn.addClass('active');
                this.$spinner.removeClass('hidden');
                this.loading = true;

                if (this.$error) {
                    this.$error.remove();
                }

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
                this.loading = false;
            },

            showError: function(error) {
                if (!error) {
                    error = Craft.t('app', 'A server error occurred.');
                }

                this.$error = $('<p class="error" style="display:none">' + error + '</p>').insertAfter($('.buttons', this.$form));
                this.$error.velocity('fadeIn');
            },

            onForgetPassword: function(event) {
                event.preventDefault();

                if (!Garnish.isMobileBrowser()) {
                    this.$loginNameInput.trigger('focus');
                }

                if (this.$error) {
                    this.$error.remove();
                }

                this.$form.addClass('reset-password');
                this.$passwordField.remove();
                this.$passwordFields.remove();
                this.$submitBtn.addClass('reset-password');
                this.$submitBtn.attr('value', Craft.t('app', 'Reset Password'));
                this.$submitBtn.enable();
                this.$sslIcon.remove();

                this.forgotPassword = true;
                this.validate();
            }
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
