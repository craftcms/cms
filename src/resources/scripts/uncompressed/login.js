(function($) {

var LoginForm = blx.Base.extend({

	$form: null,
	$usernameInput: null,
	$loginFields: null,
	$passwordPaneItem: null,
	$passwordInput: null,
	$rememberMeInput: null,
	$submitBtn: null,
	$spinner: null,
	$error: null,

	forgotPassword: false,
	loading: false,

	init: function()
	{
		this.$form = $('#login-form'),
		this.$usernameInput = $('#username'),
		this.$loginFields = $('#login-fields');
		this.$passwordPaneItem = this.$loginFields.children();
		this.$passwordInput = $('#password'),
		this.$submitBtn = $('#submit'),
		this.$spinner = $('#spinner');
		this.$rememberMeInput = $('#remember-me');

		this.addListener(this.$usernameInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$passwordInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$submitBtn, 'click', 'onSubmit');
		this.addListener(this.$form, 'submit', 'onSubmit');
	},

	validate: function()
	{
		if (this.$usernameInput.val() && (this.forgotPassword || this.$passwordInput.val().length >= 6))
		{
			this.$submitBtn.removeClass('disabled');
			return true;
		}

		this.$submitBtn.addClass('disabled');
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

		this.$submitBtn.addClass('active');
		this.$spinner.show();
		this.loading = true;

		if (this.$error)
			this.$error.remove();

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

		$.post(blx.actionUrl+'account/forgotPassword', data, $.proxy(function(response) {
			if (response.success)
			{
				new MessageSentModal();
			}
			else
			{
				this.showError(response.error);
			}

			this.onSubmitResponse();
		}, this));
	},

	submitLogin: function()
	{
		var data = {
			username: this.$usernameInput.val(),
			password: this.$passwordInput.val(),
			rememberMe: (this.$rememberMeInput.attr('checked') ? 'y' : '')
		};

		$.post(blx.actionUrl+'session/login', data, $.proxy(function(response) {
			if (response.success)
			{
				window.location = response.redirectUrl;
			}
			else
			{
				blx.shake(this.$form);
				this.onSubmitResponse();

				// Add the error message
				this.showError(response.error);

				var $forgotPasswordLink = this.$error.find('a');
				if ($forgotPasswordLink.length)
					this.addListener($forgotPasswordLink, 'mousedown', 'onForgetPassword');
			}
		}, this));

		return false;
	},

	onSubmitResponse: function()
	{
		this.$submitBtn.removeClass('active');
		this.$spinner.hide();
		this.loading = false;
	},

	showError: function(error)
	{
		if (!error)
			error = blx.t('An unknown error occurred.');

		this.$error = $('<p class="error" style="display:none">'+error+'</p>').appendTo(this.$form);
		this.$error.fadeIn();
	},

	onForgetPassword: function(event)
	{
		event.preventDefault();
		this.$usernameInput.focus();

		this.$error.remove();

		var formTopMargin = parseInt(this.$form.css('margin-top')),
			loginFieldsHeight = this.$loginFields.height(),
			newFormTopMargin = formTopMargin + Math.round(loginFieldsHeight/2);

		this.$form.animate({marginTop: newFormTopMargin}, 'fast');
		this.$loginFields.animate({height: 0}, 'fast');

		this.$submitBtn.find('span').html(blx.t('Reset Password'));
		this.$submitBtn.removeClass('disabled');
		this.$submitBtn.removeAttr('data-icon');

		this.forgotPassword = true;
		this.validate();
	}
});


var MessageSentModal = blx.ui.Modal.extend({

	init: function()
	{
		var $container = $('<div class="pane email-sent">'+blx.t('We’ve sent you an email with instructions to reset your password.')+'</div>')
			.appendTo(blx.$body);

		this.base($container);
	},

	hide: function()
	{
	}

})


var loginForm = new LoginForm();

})(jQuery);
