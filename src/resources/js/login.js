(function($) {

var LoginForm = Garnish.Base.extend({

	$form: null,
	$loginNameInput: null,
	$loginFields: null,
	$passwordPaneItem: null,
	$passwordInput: null,
	$forgotPasswordLink: null,
	$rememberMeCheckbox: null,
	$sslIcon: null,
	$submitBtn: null,
	$spinner: null,
	$error: null,

	forgotPassword: false,
	loading: false,

	init: function()
	{
		this.$form = $('#login-form'),
		this.$loginNameInput = $('#loginName');
		this.$loginFields = $('#login-fields');
		this.$passwordPaneItem = this.$loginFields.children();
		this.$passwordInput = $('#password');
		this.$forgotPasswordLink = $('#forgot-password');
		this.$sslIcon = $('#ssl-icon');
		this.$submitBtn = $('#submit');
		this.$spinner = $('#spinner');
		this.$rememberMeCheckbox = $('#rememberMe');

		new Garnish.PasswordInput(this.$passwordInput, {
			onToggleInput: $.proxy(function($newPasswordInput) {
				this.removeListener(this.$passwordInput, 'textchange');
				this.$passwordInput = $newPasswordInput;
				this.addListener(this.$passwordInput, 'textchange', 'onInputChange');
			}, this)
		});

		this.addListener(this.$loginNameInput, 'textchange', 'onInputChange');
		this.addListener(this.$passwordInput, 'textchange', 'onInputChange');
		this.addListener(this.$forgotPasswordLink, 'click', 'onForgetPassword');
		this.addListener(this.$form, 'submit', 'onSubmit');

		// Super hacky!
		this.addListener(this.$sslIcon, 'mouseover', function() {
			if (this.$sslIcon.hasClass('disabled'))
			{
				return;
			}

			this.$submitBtn.addClass('hover');
		});
		this.addListener(this.$sslIcon, 'mouseout', function() {
			if (this.$sslIcon.hasClass('disabled'))
			{
				return;
			}

			this.$submitBtn.removeClass('hover');
		});
		this.addListener(this.$sslIcon, 'mousedown', function() {
			if (this.$sslIcon.hasClass('disabled'))
			{
				return;
			}

			this.$submitBtn.addClass('active');

			this.addListener(Garnish.$doc, 'mouseup', function() {
				this.$submitBtn.removeClass('active');
				this.removeListener(Garnish.$doc, 'mouseup');
			});
		});
		this.addListener(this.$sslIcon, 'click', function() {
			this.$submitBtn.click();
		});
	},

	validate: function()
	{
		if (this.$loginNameInput.val() && (this.forgotPassword || this.$passwordInput.val().length >= 6))
		{
			this.$sslIcon.enable();
			this.$submitBtn.enable();
			return true;
		}
		else
		{
			this.$sslIcon.disable();
			this.$submitBtn.disable();
			return false;
		}
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
		this.$spinner.removeClass('hidden');
		this.loading = true;

		if (this.$error)
		{
			this.$error.remove();
		}

		if (this.forgotPassword)
		{
			this.submitForgotPassword();
		}
		else
		{
			this.submitLogin();
		}
	},

	submitForgotPassword: function()
	{
		var data = {
			loginName: this.$loginNameInput.val()
		};

		Craft.postActionRequest('users/forgotpassword', data, $.proxy(function(response, textStatus) {

			if (textStatus == 'success')
			{
				if (response.success)
				{
					new MessageSentModal();
				}
				else
				{
					this.showError(response.error);
				}
			}

			this.onSubmitResponse();

		}, this));
	},

	submitLogin: function()
	{
		var data = {
			loginName: this.$loginNameInput.val(),
			password: this.$passwordInput.val(),
			rememberMe: (this.$rememberMeCheckbox.prop('checked') ? 'y' : '')
		};

		Craft.postActionRequest('users/login', data, $.proxy(function(response, textStatus) {

			if (textStatus == 'success')
			{
				if (response.success)
				{
					window.location.href = Craft.getUrl(window.returnUrl);
				}
				else
				{
					Garnish.shake(this.$form);
					this.onSubmitResponse();

					// Add the error message
					this.showError(response.error);
				}
			}
			else
			{
				this.onSubmitResponse();
			}

		}, this));

		return false;
	},

	onSubmitResponse: function()
	{
		this.$submitBtn.removeClass('active');
		this.$spinner.addClass('hidden');
		this.loading = false;
	},

	showError: function(error)
	{
		if (!error)
		{
			error = Craft.t('An unknown error occurred.');
		}

		this.$error = $('<p class="error" style="display:none">'+error+'</p>').appendTo(this.$form);
		this.$error.fadeIn();
	},

	onForgetPassword: function(event)
	{
		event.preventDefault();
		this.$loginNameInput.focus();

		if (this.$error)
		{
			this.$error.remove();
		}

		var formTopMargin = parseInt(this.$form.css('margin-top')),
			loginFieldsHeight = this.$loginFields.height(),
			newFormTopMargin = formTopMargin + Math.round(loginFieldsHeight/2);

		this.$form.animate({marginTop: newFormTopMargin}, 'fast');
		this.$loginFields.animate({height: 0}, 'fast');

		this.$submitBtn.addClass('reset-password');
		this.$submitBtn.attr('value', Craft.t('Reset Password'));
		this.$submitBtn.enable();
		this.$sslIcon.remove();

		this.forgotPassword = true;
		this.validate();
	}
});


var MessageSentModal = Garnish.Modal.extend({

	init: function()
	{
		var $container = $('<div class="pane email-sent">'+Craft.t('Check your email for instructions to reset your password.')+'</div>')
			.appendTo(Garnish.$bod);

		this.base($container);
	},

	hide: function()
	{
	}

});


var loginForm = new LoginForm();

})(jQuery);
