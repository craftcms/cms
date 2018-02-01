/** global: Craft */
/** global: Garnish */
/**
 * Elevated Session Manager
 */
Craft.ElevatedSessionManager = Garnish.Base.extend(
    {
        fetchingTimeout: false,

        passwordModal: null,
        $passwordInput: null,
        $passwordSpinner: null,
        $submitBtn: null,
        $errorPara: null,

        callback: null,

        /**
         * Requires that the user has an elevated session.
         *
         * @param {function} callback The callback function that should be called once the user has an elevated session
         */
        requireElevatedSession: function(callback) {
            this.callback = callback;

            // Check the time remaining on the user's elevated session (if any)
            this.fetchingTimeout = true;

            Craft.postActionRequest('users/get-elevated-session-timeout', $.proxy(function(response, textStatus) {
                this.fetchingTimeout = false;

                if (textStatus === 'success') {
                    // Is there still enough time left or has it been disabled?
                    if (response.timeout === false || response.timeout >= Craft.ElevatedSessionManager.minSafeElevatedSessionTimeout) {
                        this.callback();
                    }
                    else {
                        // Show the password modal
                        this.showPasswordModal();
                    }
                }
            }, this));
        },

        showPasswordModal: function() {
            if (!this.passwordModal) {
                var $passwordModal = $('<form id="elevatedsessionmodal" class="modal secure fitted"/>'),
                    $body = $('<div class="body"><p>' + Craft.t('app', 'Enter your password to continue.') + '</p></div>').appendTo($passwordModal),
                    $inputContainer = $('<div class="inputcontainer">').appendTo($body),
                    $inputsFlexContainer = $('<div class="flex"/>').appendTo($inputContainer),
                    $passwordContainer = $('<div class="flex-grow"/>').appendTo($inputsFlexContainer),
                    $buttonContainer= $('<td/>').appendTo($inputsFlexContainer),
                    $passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($passwordContainer);

                this.$passwordInput = $('<input type="password" class="text password fullwidth" placeholder="' + Craft.t('app', 'Password') + '"/>').appendTo($passwordWrapper);
                this.$passwordSpinner = $('<div class="spinner hidden"/>').appendTo($inputContainer);
                this.$submitBtn = $('<input type="submit" class="btn submit disabled" value="' + Craft.t('app', 'Submit') + '" />').appendTo($buttonContainer);
                this.$errorPara = $('<p class="error"/>').appendTo($body);

                this.passwordModal = new Garnish.Modal($passwordModal, {
                    closeOtherModals: false,
                    onFadeIn: $.proxy(function() {
                        setTimeout($.proxy(this, 'focusPasswordInput'), 100);
                    }, this),
                    onFadeOut: $.proxy(function() {
                        this.$passwordInput.val('');
                    }, this)
                });

                new Craft.PasswordInput(this.$passwordInput, {
                    onToggleInput: $.proxy(function($newPasswordInput) {
                        this.$passwordInput = $newPasswordInput;
                    }, this)
                });

                this.addListener(this.$passwordInput, 'textchange', 'validatePassword');
                this.addListener($passwordModal, 'submit', 'submitPassword');
            }
            else {
                this.passwordModal.show();
            }
        },

        focusPasswordInput: function() {
            if (!Garnish.isMobileBrowser(true)) {
                this.$passwordInput.trigger('focus');
            }
        },

        validatePassword: function() {
            if (this.$passwordInput.val().length >= 6) {
                this.$submitBtn.removeClass('disabled');
                return true;
            }
            else {
                this.$submitBtn.addClass('disabled');
                return false;
            }
        },

        submitPassword: function(ev) {
            if (ev) {
                ev.preventDefault();
            }

            if (!this.validatePassword()) {
                return;
            }

            this.$passwordSpinner.removeClass('hidden');
            this.clearLoginError();

            var data = {
                password: this.$passwordInput.val()
            };

            Craft.postActionRequest('users/start-elevated-session', data, $.proxy(function(response, textStatus) {
                this.$passwordSpinner.addClass('hidden');

                if (textStatus === 'success') {
                    if (response.success) {
                        this.passwordModal.hide();
                        this.callback();
                    }
                    else {
                        this.showPasswordError(Craft.t('app', 'Incorrect password.'));
                        Garnish.shake(this.passwordModal.$container);
                        this.focusPasswordInput();
                    }
                }
                else {
                    this.showPasswordError();
                }

            }, this));
        },

        showPasswordError: function(error) {
            if (error === null || typeof error === 'undefined') {
                error = Craft.t('app', 'An unknown error occurred.');
            }

            this.$errorPara.text(error);
            this.passwordModal.updateSizeAndPosition();
        },

        clearLoginError: function() {
            this.showPasswordError('');
        }
    },
    {
        minSafeElevatedSessionTimeout: 5
    });

// Instantiate it
Craft.elevatedSessionManager = new Craft.ElevatedSessionManager();
