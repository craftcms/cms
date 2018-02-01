/**
 * AuthManager class
 */
Craft.AuthManager = Garnish.Base.extend(
{
	checkAuthTimeoutTimer: null,
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
	init: function()
	{
		this.updateAuthTimeout(Craft.authTimeout);
	},

	/**
	 * Sets a timer for the next time to check the auth timeout.
	 */
	setCheckAuthTimeoutTimer: function(seconds)
	{
		if (this.checkAuthTimeoutTimer)
		{
			clearTimeout(this.checkAuthTimeoutTimer);
		}

		this.checkAuthTimeoutTimer = setTimeout($.proxy(this, 'checkAuthTimeout'), seconds*1000);
	},

	/**
	 * Pings the server to see how many seconds are left on the current user session, and handles the response.
	 */
	checkAuthTimeout: function(extendSession)
	{
		$.ajax({
			url: Craft.getActionUrl('users/getAuthTimeout', (extendSession ? null : 'dontExtendSession=1')),
			type: 'GET',
			complete: $.proxy(function(jqXHR, textStatus)
			{
				if (textStatus == 'success')
				{
					if (typeof jqXHR.responseJSON.csrfTokenValue !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined')
					{
						Craft.csrfTokenValue = jqXHR.responseJSON.csrfTokenValue;
					}

					this.updateAuthTimeout(jqXHR.responseJSON.timeout);
					this.submitLoginIfLoggedOut = false;
				}
				else
				{
					this.updateAuthTimeout(-1);
				}
			}, this)
		});
	},

	/**
	 * Updates our record of the auth timeout, and handles it.
	 */
	updateAuthTimeout: function(authTimeout)
	{
		this.authTimeout = parseInt(authTimeout);

		// Are we within the warning window?
		if (this.authTimeout != -1 && this.authTimeout < Craft.AuthManager.minSafeAuthTimeout)
		{
			// Is there still time to renew the session?
			if (this.authTimeout)
			{
				if (!this.showingLogoutWarningModal)
				{
					// Show the warning modal
					this.showLogoutWarningModal();
				}

				// Will the session expire before the next checkup?
				if (this.authTimeout < Craft.AuthManager.checkInterval)
				{
					if (this.showLoginModalTimer)
					{
						clearTimeout(this.showLoginModalTimer);
					}

					this.showLoginModalTimer = setTimeout($.proxy(this, 'showLoginModal'), this.authTimeout*1000);
				}
			}
			else
			{
				if (this.showingLoginModal)
				{
					if (this.submitLoginIfLoggedOut)
					{
						this.submitLogin();
					}
				}
				else
				{
					// Show the login modal
					this.showLoginModal();
				}
			}

			this.setCheckAuthTimeoutTimer(Craft.AuthManager.checkInterval);
		}
		else
		{
			// Everything's good!
			this.hideLogoutWarningModal();
			this.hideLoginModal();

			// Will be be within the minSafeAuthTimeout before the next update?
			if (this.authTimeout != -1 && this.authTimeout < (Craft.AuthManager.minSafeAuthTimeout + Craft.AuthManager.checkInterval))
			{
				this.setCheckAuthTimeoutTimer(this.authTimeout - Craft.AuthManager.minSafeAuthTimeout + 1);
			}
			else
			{
				this.setCheckAuthTimeoutTimer(Craft.AuthManager.checkInterval);
			}
		}
	},

	/**
	 * Shows the logout warning modal.
	 */
	showLogoutWarningModal: function()
	{
		var quickShow;

		if (this.showingLoginModal)
		{
			this.hideLoginModal(true);
			quickShow = true;
		}
		else
		{
			quickShow = false;
		}

		this.showingLogoutWarningModal = true;

		if (!this.logoutWarningModal)
		{
			var $form = $('<form id="logoutwarningmodal" class="modal alert fitted"/>'),
				$body = $('<div class="body"/>').appendTo($form),
				$buttons = $('<div class="buttons right"/>').appendTo($body),
				$logoutBtn = $('<div class="btn">'+Craft.t('Log out now')+'</div>').appendTo($buttons),
				$renewSessionBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('Keep me logged in')+'" />').appendTo($buttons);

			this.$logoutWarningPara = $('<p/>').prependTo($body);

			this.logoutWarningModal = new Garnish.Modal($form, {
				autoShow: false,
				closeOtherModals: false,
				hideOnEsc: false,
				hideOnShadeClick: false,
				shadeClass: 'modal-shade dark',
				onFadeIn: function()
				{
					if (!Garnish.isMobileBrowser(true))
					{
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

		if (quickShow)
		{
			this.logoutWarningModal.quickShow();
		}
		else
		{
			this.logoutWarningModal.show();
		}

		this.updateLogoutWarningMessage();

		this.decrementLogoutWarningInterval = setInterval($.proxy(this, 'decrementLogoutWarning'), 1000);
	},

	/**
	 * Updates the logout warning message indicating that the session is about to expire.
	 */
	updateLogoutWarningMessage: function()
	{
		this.$logoutWarningPara.text(Craft.t('Your session will expire in {time}.', {
			time: Craft.secondsToHumanTimeDuration(this.authTimeout)
		}));

		this.logoutWarningModal.updateSizeAndPosition();
	},

	decrementLogoutWarning: function()
	{
		if (this.authTimeout > 0)
		{
			this.authTimeout--;
			this.updateLogoutWarningMessage();
		}

		if (this.authTimeout == 0)
		{
			clearInterval(this.decrementLogoutWarningInterval);
		}
	},

	/**
	 * Hides the logout warning modal.
	 */
	hideLogoutWarningModal: function(quick)
	{
		this.showingLogoutWarningModal = false;

		if (this.logoutWarningModal)
		{
			if (quick)
			{
				this.logoutWarningModal.quickHide();
			}
			else
			{
				this.logoutWarningModal.hide();
			}

			if (this.decrementLogoutWarningInterval)
			{
				clearInterval(this.decrementLogoutWarningInterval);
			}
		}
	},

	/**
	 * Shows the login modal.
	 */
	showLoginModal: function()
	{
		var quickShow;

		if (this.showingLogoutWarningModal)
		{
			this.hideLogoutWarningModal(true);
			quickShow = true;
		}
		else
		{
			quickShow = false;
		}

		this.showingLoginModal = true;

		if (!this.loginModal)
		{
			var $form = $('<form id="loginmodal" class="modal alert fitted"/>'),
				$body = $('<div class="body"><h2>'+Craft.t('Your session has ended.')+'</h2><p>'+Craft.t('Enter your password to log back in.')+'</p></div>').appendTo($form),
				$inputContainer = $('<div class="inputcontainer">').appendTo($body),
				$inputsTable = $('<table class="inputs fullwidth"/>').appendTo($inputContainer),
				$inputsRow = $('<tr/>').appendTo($inputsTable),
				$passwordCell = $('<td/>').appendTo($inputsRow),
				$buttonCell = $('<td class="thin"/>').appendTo($inputsRow),
				$passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($passwordCell);

			this.$passwordInput = $('<input type="password" class="text password fullwidth" placeholder="'+Craft.t('Password')+'"/>').appendTo($passwordWrapper);
			this.$passwordSpinner = $('<div class="spinner hidden"/>').appendTo($inputContainer);
			this.$loginBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Login')+'" />').appendTo($buttonCell);
			this.$loginErrorPara = $('<p class="error"/>').appendTo($body);

			this.loginModal = new Garnish.Modal($form, {
				autoShow: false,
				closeOtherModals: false,
				hideOnEsc: false,
				hideOnShadeClick: false,
				shadeClass: 'modal-shade dark',
				onFadeIn: $.proxy(function()
				{
					if (!Garnish.isMobileBrowser(true))
					{
						// Auto-focus the password input
						setTimeout($.proxy(function() {
							this.$passwordInput.trigger('focus');
						}, this), 100);
					}
				}, this),
				onFadeOut: $.proxy(function()
				{
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

		if (quickShow)
		{
			this.loginModal.quickShow();
		}
		else
		{
			this.loginModal.show();
		}
	},

	/**
	 * Hides the login modal.
	 */
	hideLoginModal: function(quick)
	{
		this.showingLoginModal = false;

		if (this.loginModal)
		{
			if (quick)
			{
				this.loginModal.quickHide();
			}
			else
			{
				this.loginModal.hide();
			}
		}
	},

	logout: function()
	{
		var url = Craft.getActionUrl('users/logout');

		$.get(url, $.proxy(function()
		{
			Craft.redirectTo('');
		}, this));
	},

	renewSession: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		this.hideLogoutWarningModal();
		this.checkAuthTimeout(true);
	},

	validatePassword: function()
	{
		if (this.$passwordInput.val().length >= 6)
		{
			this.$loginBtn.removeClass('disabled');
			return true;
		}
		else
		{
			this.$loginBtn.addClass('disabled');
			return false;
		}
	},

	login: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		if (this.validatePassword())
		{
			this.$passwordSpinner.removeClass('hidden');
			this.clearLoginError();

			if (typeof Craft.csrfTokenValue != 'undefined')
			{
				// Check the auth status one last time before sending this off,
				// in case the user has already logged back in from another window/tab
				this.submitLoginIfLoggedOut = true;
				this.checkAuthTimeout();
			}
			else
			{
				this.submitLogin();
			}
		}
	},

	submitLogin: function()
	{
		var data = {
			loginName: Craft.username,
			password: this.$passwordInput.val()
		};

		Craft.postActionRequest('users/login', data, $.proxy(function(response, textStatus)
		{
			this.$passwordSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.hideLoginModal();
					this.checkAuthTimeout();
				}
				else
				{
					this.showLoginError(response.error);
					Garnish.shake(this.loginModal.$container);

					if (!Garnish.isMobileBrowser(true))
					{
						this.$passwordInput.trigger('focus');
					}
				}
			}
			else
			{
				this.showLoginError();
			}

		}, this));
	},

	showLoginError: function(error)
	{
		if (error === null || typeof error == 'undefined')
		{
			error = Craft.t('An unknown error occurred.');
		}

		this.$loginErrorPara.text(error);
		this.loginModal.updateSizeAndPosition();
	},

	clearLoginError: function()
	{
		this.showLoginError('');
	}
},
{
	checkInterval: 60,
	minSafeAuthTimeout: 120
});
