(function($) {


var LoginForm = blx.Base.extend({

	$form: null,
	$pane: null,
	$nameInput: null,
	$passwordInput: null,
	$rememberMeInput: null,
	$loginBtn: null,

	init: function()
	{
		this.$form = $('#form'),
		this.$pane = $('#pane'),
		this.$nameInput = $('#loginName'),
		this.$passwordInput = $('#password'),
		this.$loginBtn = $('#login'),
		this.$rememberMeInput = $('#remember-me');

		this.$nameInput.focus();

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
