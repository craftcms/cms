(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


blx.ui.PasswordInput = blx.Base.extend({

	$input: null,

	init: function(input)
	{
		this.$input = $(input);

		// Is this already a password input?
		if (this.$input.data('passwordInput'))
		{
			blx.log('Double-instantiating a password input on an element');
			this.$input.data('passwordInput').destroy();
		}

		this.$input.data('passwordInput', this);

		this.$input.addClass('password');

		this.addListener(this.$input, 'focus', 'onFocus');
		this.addListener(this.$input, 'keypress', 'onKeyPress');
	},

	showCapsIcon: function()
	{
		this.$input.addClass('capslock');
	},

	hideCapsIcon: function()
	{
		this.$input.removeClass('capslock');
	},

	onFocus: function()
	{
		this.hideCapsIcon();
	},

	onKeyPress: function(ev)
	{
		if (!ev.shiftKey && !ev.metaKey)
		{
			var str = String.fromCharCode(ev.which);

			if (str.toUpperCase() === str && str.toLowerCase() !== str)
				this.showCapsIcon();
			else if (str.toLowerCase() === str && str.toUpperCase() !== str)
				this.hideCapsIcon();
		}
	}

});


$.fn.passwordInput = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'passwordInput'))
			new blx.ui.PasswordInput(this);
	});
};

blx.$document.ready(function()
{
	$('input.password').passwordInput();
});


})(jQuery);