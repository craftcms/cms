(function($) {


var LoginForm = b.Base.extend({

	$form: null,
	$pane: null,
	$usernameInput: null,
	$passwordContainer: null,
	$passwordPaneItem: null,
	$forgotPasswordLink: null,
	$passwordInput: null,
	$rememberMeInput: null,
	$loginBtn: null,
	$notice: null,

	forgotPassword: false,

	init: function()
	{
		this.$form = $('#login-form'),
		this.$pane = $('#login-pane'),
		this.$usernameInput = $('#username'),
		this.$passwordContainer = $('#password-container');
		this.$passwordPaneItem = this.$passwordContainer.children();
		this.$forgotPasswordLink = $('#forgot-password');
		this.$passwordInput = $('#password'),
		this.$loginBtn = $('#login-btn'),
		this.$rememberMeLabel = $('#remember-me-label');
		this.$rememberMeInput = $('#remember-me');

		this.addListener(this.$usernameInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$passwordInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$forgotPasswordLink, 'click', 'onForgetPassword');
		this.addListener(this.$form, 'submit', 'onSubmit');
	},

	validate: function()
	{
		if (this.$usernameInput.val() && (this.forgotPassword || this.$passwordInput.val().length >= 6))
		{
			this.$loginBtn.removeClass('disabled');
			return true;
		}

		this.$loginBtn.addClass('disabled');
		return false;
	},

	onInputChange: function()
	{
		this.validate();
	},

	onSubmit: function(event)
	{
		// Prevent full HTTP submits
		event.preventDefault();

		if (!this.validate())
			return;

		if (this.forgotPassword)
			this.submitForgotPassword();
		else
			this.submitLogin();
	},

	submitForgotPassword: function()
	{
		var data = {
			username: this.$usernameInput.val()
		};

		$.post(b.actionUrl+'account/forgot', data, $.proxy(function(response) {
			if (response.success)
			{
				// Add the notice
				if (this.$notice)
					this.$notice.attr('className', 'notice');
				else
					this.createNoticeElem('notice');

				var notice = 'Check your email for instructions to reset your password.';
			}
			else
			{
				// Add the error message
				if (!this.$notice)
					this.createNoticeElem('error');

				var notice = response.error || 'An unknown error occurred.';
			}

			this.$notice.html(notice);
		}, this));
	},

	submitLogin: function()
	{
		var data = {
			username: this.$usernameInput.val(),
			password: this.$passwordInput.val(),
			rememberMe: (this.$rememberMeInput.attr('checked') ? 'y' : '')
		};

		$.post(b.actionUrl+'session/login', data, $.proxy(function(response) {
			if (response.success)
			{
				window.location = response.redirectUrl;
			}
			else
			{
				// Add the error message
				if (!this.$notice)
					this.createNoticeElem('error');

				var error = response.error || 'An unknown error occurred.';
				this.$notice.html(error);

				b.shake(this.$pane);
			}
		}, this));

		return false;
	},

	onForgetPassword: function()
	{
		var passwordContainerHeight = this.$passwordContainer.height();
		this.$pane.animate({marginTop: (passwordContainerHeight/2)}, 'fast');
		this.$passwordContainer.animate({height: 0}, 'fast', $.proxy(function() {
			this.$usernameInput.focus();
		}, this));

		this.$loginBtn.attr('value', 'Reset Password');
		this.$loginBtn.removeClass('disabled');
		this.$rememberMeLabel.hide();

		this.forgotPassword = true;
		this.validate();
	},

	createNoticeElem: function(className)
	{
		this.$noticeContainer = $('<div id="notice"/>').appendTo(this.$form);
		this.$notice = $('<p class="'+className+'"/>').appendTo(this.$noticeContainer);
		this.$notice.hide().fadeIn();
	}

});


var loginForm = new LoginForm();


})(jQuery);
