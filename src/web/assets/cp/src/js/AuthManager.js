/** global: Craft */
/** global: Garnish */
/**
 * AuthManager class
 */
Craft.AuthManager = Garnish.Base.extend(
    {
        remainingSessionTime: null,
        checkRemainingSessionTimer: null,
        showLoginModalTimer: null,
        decrementLogoutWarningInterval: null,

        showingLogoutWarningModal: false,
        showingLoginModal: false,

        logoutWarningModal: null,
        loginModal: null,

        $logoutWarningPara: null,
        $passwordInput: null,
        $passwordSpinner: null,
        $loginBtn: null,
        $loginErrorPara: null,

        submitLoginIfLoggedOut: false,

        /**
         * Init
         */
        init: function() {
            this.updateRemainingSessionTime(Craft.remainingSessionTime);
        },

        /**
         * Sets a timer for the next time to check the auth timeout.
         */
        setCheckRemainingSessionTimer: function(seconds) {
            if (this.checkRemainingSessionTimer) {
                clearTimeout(this.checkRemainingSessionTimer);
            }

            this.checkRemainingSessionTimer = setTimeout($.proxy(this, 'checkRemainingSessionTime'), seconds * 1000);
        },

        /**
         * Pings the server to see how many seconds are left on the current user session, and handles the response.
         */
        checkRemainingSessionTime: function(extendSession) {
            $.ajax({
                url: Craft.getActionUrl('users/get-remaining-session-time', (extendSession ? null : 'dontExtendSession=1')),
                type: 'GET',
                dataType: 'json',
                complete: $.proxy(function(jqXHR, textStatus) {
                    if (textStatus === 'success') {
                        if (typeof jqXHR.responseJSON.csrfTokenValue !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined') {
                            Craft.csrfTokenValue = jqXHR.responseJSON.csrfTokenValue;
                        }

                        this.updateRemainingSessionTime(jqXHR.responseJSON.timeout);
                        this.submitLoginIfLoggedOut = false;
                    }
                    else {
                        this.updateRemainingSessionTime(-1);
                    }
                }, this)
            });
        },

        /**
         * Updates our record of the auth timeout, and handles it.
         */
        updateRemainingSessionTime: function(remainingSessionTime) {
            this.remainingSessionTime = parseInt(remainingSessionTime);

            // Are we within the warning window?
            if (this.remainingSessionTime !== -1 && this.remainingSessionTime < Craft.AuthManager.minSafeSessiotTime) {
                // Is there still time to renew the session?
                if (this.remainingSessionTime) {
                    if (!this.showingLogoutWarningModal) {
                        // Show the warning modal
                        this.showLogoutWarningModal();
                    }

                    // Will the session expire before the next checkup?
                    if (this.remainingSessionTime < Craft.AuthManager.checkInterval) {
                        if (this.showLoginModalTimer) {
                            clearTimeout(this.showLoginModalTimer);
                        }

                        this.showLoginModalTimer = setTimeout($.proxy(this, 'showLoginModal'), this.remainingSessionTime * 1000);
                    }
                }
                else {
                    if (this.showingLoginModal) {
                        if (this.submitLoginIfLoggedOut) {
                            this.submitLogin();
                        }
                    }
                    else {
                        // Show the login modal
                        this.showLoginModal();
                    }
                }

                this.setCheckRemainingSessionTimer(Craft.AuthManager.checkInterval);
            }
            else {
                // Everything's good!
                this.hideLogoutWarningModal();
                this.hideLoginModal();

                // Will be be within the minSafeSessiotTime before the next update?
                if (this.remainingSessionTime !== -1 && this.remainingSessionTime < (Craft.AuthManager.minSafeSessiotTime + Craft.AuthManager.checkInterval)) {
                    this.setCheckRemainingSessionTimer(this.remainingSessionTime - Craft.AuthManager.minSafeSessiotTime + 1);
                }
                else {
                    this.setCheckRemainingSessionTimer(Craft.AuthManager.checkInterval);
                }
            }
        },

        /**
         * Shows the logout warning modal.
         */
        showLogoutWarningModal: function() {
            var quickShow;

            if (this.showingLoginModal) {
                this.hideLoginModal(true);
                quickShow = true;
            }
            else {
                quickShow = false;
            }

            this.showingLogoutWarningModal = true;

            if (!this.logoutWarningModal) {
                var $form = $('<form id="logoutwarningmodal" class="modal alert fitted"/>'),
                    $body = $('<div class="body"/>').appendTo($form),
                    $buttons = $('<div class="buttons right"/>').appendTo($body),
                    $logoutBtn = $('<div class="btn">' + Craft.t('app', 'Log out now') + '</div>').appendTo($buttons),
                    $renewSessionBtn = $('<input type="submit" class="btn submit" value="' + Craft.t('app', 'Keep me logged in') + '" />').appendTo($buttons);

                this.$logoutWarningPara = $('<p/>').prependTo($body);

                this.logoutWarningModal = new Garnish.Modal($form, {
                    autoShow: false,
                    closeOtherModals: false,
                    hideOnEsc: false,
                    hideOnShadeClick: false,
                    shadeClass: 'modal-shade dark',
                    onFadeIn: function() {
                        if (!Garnish.isMobileBrowser(true)) {
                            // Auto-focus the renew button
                            setTimeout(function() {
                                $renewSessionBtn.trigger('focus');
                            }, 100);
                        }
                    }
                });

                this.addListener($logoutBtn, 'activate', 'logout');
                this.addListener($form, 'submit', 'renewSession');
            }

            if (quickShow) {
                this.logoutWarningModal.quickShow();
            }
            else {
                this.logoutWarningModal.show();
            }

            this.updateLogoutWarningMessage();

            this.decrementLogoutWarningInterval = setInterval($.proxy(this, 'decrementLogoutWarning'), 1000);
        },

        /**
         * Updates the logout warning message indicating that the session is about to expire.
         */
        updateLogoutWarningMessage: function() {
            this.$logoutWarningPara.text(Craft.t('app', 'Your session will expire in {time}.', {
                time: Craft.secondsToHumanTimeDuration(this.remainingSessionTime)
            }));

            this.logoutWarningModal.updateSizeAndPosition();
        },

        decrementLogoutWarning: function() {
            if (this.remainingSessionTime > 0) {
                this.remainingSessionTime--;
                this.updateLogoutWarningMessage();
            }

            if (this.remainingSessionTime === 0) {
                clearInterval(this.decrementLogoutWarningInterval);
            }
        },

        /**
         * Hides the logout warning modal.
         */
        hideLogoutWarningModal: function(quick) {
            this.showingLogoutWarningModal = false;

            if (this.logoutWarningModal) {
                if (quick) {
                    this.logoutWarningModal.quickHide();
                }
                else {
                    this.logoutWarningModal.hide();
                }

                if (this.decrementLogoutWarningInterval) {
                    clearInterval(this.decrementLogoutWarningInterval);
                }
            }
        },

        /**
         * Shows the login modal.
         */
        showLoginModal: function() {
            var quickShow;

            if (this.showingLogoutWarningModal) {
                this.hideLogoutWarningModal(true);
                quickShow = true;
            }
            else {
                quickShow = false;
            }

            this.showingLoginModal = true;

            if (!this.loginModal) {
                var $form = $('<form id="loginmodal" class="modal alert fitted"/>'),
                    $body = $('<div class="body"><h2>' + Craft.t('app', 'Your session has ended.') + '</h2><p>' + Craft.t('app', 'Enter your password to log back in.') + '</p></div>').appendTo($form),
                    $inputContainer = $('<div class="inputcontainer">').appendTo($body),
                    $inputsFlexContainer = $('<div class="flex"/>').appendTo($inputContainer),
                    $passwordContainer = $('<div class="flex-grow"/>').appendTo($inputsFlexContainer),
                    $buttonContainer = $('<div/>').appendTo($inputsFlexContainer),
                    $passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($passwordContainer);

                this.$passwordInput = $('<input type="password" class="text password fullwidth" placeholder="' + Craft.t('app', 'Password') + '"/>').appendTo($passwordWrapper);
                this.$passwordSpinner = $('<div class="spinner hidden"/>').appendTo($inputContainer);
                this.$loginBtn = $('<input type="submit" class="btn submit disabled" value="' + Craft.t('app', 'Login') + '" />').appendTo($buttonContainer);
                this.$loginErrorPara = $('<p class="error"/>').appendTo($body);

                this.loginModal = new Garnish.Modal($form, {
                    autoShow: false,
                    closeOtherModals: false,
                    hideOnEsc: false,
                    hideOnShadeClick: false,
                    shadeClass: 'modal-shade dark',
                    onFadeIn: $.proxy(function() {
                        if (!Garnish.isMobileBrowser(true)) {
                            // Auto-focus the password input
                            setTimeout($.proxy(function() {
                                this.$passwordInput.trigger('focus');
                            }, this), 100);
                        }
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
                this.addListener($form, 'submit', 'login');
            }

            if (quickShow) {
                this.loginModal.quickShow();
            }
            else {
                this.loginModal.show();
            }
        },

        /**
         * Hides the login modal.
         */
        hideLoginModal: function(quick) {
            this.showingLoginModal = false;

            if (this.loginModal) {
                if (quick) {
                    this.loginModal.quickHide();
                }
                else {
                    this.loginModal.hide();
                }
            }
        },

        logout: function() {
            $.get({
                url: Craft.getActionUrl('users/logout'),
                dataType: 'json',
                success: $.proxy(function() {
                    Craft.redirectTo('');
                }, this)
            });
        },

        renewSession: function(ev) {
            if (ev) {
                ev.preventDefault();
            }

            this.hideLogoutWarningModal();
            this.checkRemainingSessionTime(true);
        },

        validatePassword: function() {
            if (this.$passwordInput.val().length >= 6) {
                this.$loginBtn.removeClass('disabled');
                return true;
            }
            else {
                this.$loginBtn.addClass('disabled');
                return false;
            }
        },

        login: function(ev) {
            if (ev) {
                ev.preventDefault();
            }

            if (this.validatePassword()) {
                this.$passwordSpinner.removeClass('hidden');
                this.clearLoginError();

                if (typeof Craft.csrfTokenValue !== 'undefined') {
                    // Check the auth status one last time before sending this off,
                    // in case the user has already logged back in from another window/tab
                    this.submitLoginIfLoggedOut = true;
                    this.checkRemainingSessionTime();
                }
                else {
                    this.submitLogin();
                }
            }
        },

        submitLogin: function() {
            var data = {
                loginName: Craft.username,
                password: this.$passwordInput.val()
            };

            Craft.postActionRequest('users/login', data, $.proxy(function(response, textStatus) {
                this.$passwordSpinner.addClass('hidden');

                if (textStatus === 'success') {
                    if (response.success) {
                        this.hideLoginModal();
                        this.checkRemainingSessionTime();
                    }
                    else {
                        this.showLoginError(response.error);
                        Garnish.shake(this.loginModal.$container);

                        if (!Garnish.isMobileBrowser(true)) {
                            this.$passwordInput.trigger('focus');
                        }
                    }
                }
                else {
                    this.showLoginError();
                }

            }, this));
        },

        showLoginError: function(error) {
            if (error === null || typeof error === 'undefined') {
                error = Craft.t('app', 'An unknown error occurred.');
            }

            this.$loginErrorPara.text(error);
            this.loginModal.updateSizeAndPosition();
        },

        clearLoginError: function() {
            this.showLoginError('');
        }
    },
    {
        checkInterval: 60,
        minSafeSessiotTime: 120
    });
