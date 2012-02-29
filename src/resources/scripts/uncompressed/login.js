(function($) {


var LoginForm = blx.Base.extend({

	$form: null,
	$pane: null,
	$nameInput: null,
	$passwordInput: null,
	$rememberMeInput: null,
	$loginBtn: null,
	$error: null,

	init: function()
	{
		this.$form = $('#login-form'),
		this.$pane = $('#login-pane'),
		this.$nameInput = $('#loginName'),
		this.$passwordInput = $('#password'),
		this.$loginBtn = $('#login-btn'),
		this.$rememberMeInput = $('#remember-me');

		if (!this.$nameInput.val())
			this.$nameInput.focus();
		else
			this.$passwordInput.focus();

		this.addListener(this.$nameInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$passwordInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$form, 'submit', 'onSubmit');
	},

	validate: function()
	{
		if (this.$nameInput.val() && this.$passwordInput.val().length >= 6)
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

		var data = {
			loginName: this.$nameInput.val(),
			password: this.$passwordInput.val(),
			rememberMe: (this.$rememberMeInput.attr('checked') ? 'y' : '')
		};

		$.post(actionUrl+'session/login', data, $.proxy(function(response) {
			if (response.success)
			{
				window.location = response.redirectUrl;
			}
			else
			{
				// Add the error message
				if (!this.$error)
				{
					this.$errorContainer = $(document.createElement('div'));
					this.$errorContainer.attr('id', 'error');
					this.$errorContainer.appendTo(this.$form);
					this.$error = $(document.createElement('p'));
					this.$error.addClass('error');
					this.$error.appendTo(this.$errorContainer);
					this.$error.hide().fadeIn();
				}

				var error = response.error || 'An unknown error occurred.';
				this.$error.html(error);

				// Shake it like it's hot
				for (var i = 10; i > 0; i--)
				{
					var left = (i % 2 ? -1 : 1) * i;
					this.$pane.animate({left: left}, {
						duration: 50,
						queue: true
					});
				}
			}
		}, this));

		return false;
	}

});


var loginForm = new LoginForm();


})(jQuery);
