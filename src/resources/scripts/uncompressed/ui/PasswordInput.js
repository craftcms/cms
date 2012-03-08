(function($) {


/**
 * Password Input
 */
blx.ui.PasswordInput = blx.Base.extend({

	$passwordInput: null,
	$textInput: null,
	$currentInput: null,

	$showPasswordToggle: null,
	showingPassword: null,
	showingCapsIcon: null,

	init: function(passwordInput)
	{
		this.$passwordInput = $(passwordInput);

		// Is this already a password input?
		if (this.$passwordInput.data('passwordInput'))
		{
			blx.log('Double-instantiating a password input on an element');
			this.$passwordInput.data('passwordInput').destroy();
		}

		this.$passwordInput.data('passwordInput', this);

		this.showingCapsIcon = false;

		this.$showPasswordToggle = $(document.createElement('a'));
		this.$showPasswordToggle.addClass('password-toggle');
		this.$showPasswordToggle.insertAfter(this.$passwordInput);
		this.addListener(this.$showPasswordToggle, 'mousedown', 'onToggleMouseDown');
		this.hidePassword();
	},

	setCurrentInput: function($input)
	{
		if (this.$currentInput)
		{
			this.$currentInput.replaceWith($input);
			$input.focus();
			$input.val(this.$currentInput.val());
		}

		this.$currentInput = $input;

		this.addListener(this.$currentInput, 'focus', 'onFocus');
		this.addListener(this.$currentInput, 'keypress', 'onKeyPress');
	},

	updateToggleLabel: function(label)
	{
		this.$showPasswordToggle.text(label);
	},

	showPassword: function()
	{
		if (this.showingPassword)
			return;

		this.hideCapsIcon();

		if (!this.$textInput)
		{
			this.$textInput = this.$passwordInput.clone(true);
			this.$textInput.attr('type', 'text');
		}

		this.setCurrentInput(this.$textInput);
		this.updateToggleLabel('Hide');
		this.showingPassword = true;
	},

	hidePassword: function()
	{
		// showingPassword could be null, which is acceptable
		if (this.showingPassword === false)
			return;

		this.setCurrentInput(this.$passwordInput);
		this.updateToggleLabel('Show');
		this.showingPassword = false;
	},

	togglePassword: function()
	{
		if (this.showingPassword)
			this.hidePassword();
		else
			this.showPassword();
	},

	showCapsIcon: function()
	{
		if (this.showingCapsIcon)
			return;

		this.$currentInput.addClass('capslock');
		this.showingCapsIcon = true;
	},

	hideCapsIcon: function()
	{
		if (!this.showingCapsIcon)
			return;

		this.$currentInput.removeClass('capslock');
		this.showingCapsIcon = false;
	},

	onFocus: function()
	{
		this.hideCapsIcon();
	},

	onKeyPress: function(ev)
	{
		// No need to show the caps lock indicator if we're showing the password
		if (this.showingPassword)
			return;

		if (!ev.shiftKey && !ev.metaKey)
		{
			var str = String.fromCharCode(ev.which);

			if (str.toUpperCase() === str && str.toLowerCase() !== str)
				this.showCapsIcon();
			else if (str.toLowerCase() === str && str.toUpperCase() !== str)
				this.hideCapsIcon();
		}
	},

	onToggleMouseDown: function(ev)
	{
		// Prevent focus change
		ev.preventDefault();

		if (this.$currentInput[0].setSelectionRange)
			var selectionStart = this.$currentInput[0].selectionStart,
				selectionEnd   = this.$currentInput[0].selectionEnd;

		this.togglePassword();

		if (this.$currentInput[0].setSelectionRange)
			this.$currentInput[0].setSelectionRange(selectionStart, selectionEnd);
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
