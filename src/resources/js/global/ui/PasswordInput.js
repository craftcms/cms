(function($) {

/**
 * Password Input
 */
Blocks.ui.PasswordInput = Blocks.Base.extend({

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
			Blocks.log('Double-instantiating a password input on an element');
			this.$passwordInput.data('passwordInput').destroy();
		}

		this.$passwordInput.data('passwordInput', this);

		this.showingCapsIcon = false;

		this.$showPasswordToggle = $('<a/>').hide();
		this.$showPasswordToggle.addClass('password-toggle');
		this.$showPasswordToggle.insertAfter(this.$passwordInput);
		this.addListener(this.$showPasswordToggle, 'mousedown', 'onToggleMouseDown');
		this.hidePassword();
	},

	setCurrentInput: function($input)
	{
		if (this.$currentInput)
		{
			// Swap the inputs, while preventing the focus animation
			$input.addClass('focus');
			this.$currentInput.replaceWith($input);
			$input.focus();
			$input.removeClass('focus');

			// Restore the input value
			$input.val(this.$currentInput.val());
		}

		this.$currentInput = $input;

		this.addListener(this.$currentInput, 'focus', 'onFocus');
		this.addListener(this.$currentInput, 'keypress', 'onKeyPress');
		this.addListener(this.$currentInput, 'keypress,keyup,change,blur', 'onInputChange');
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
		this.updateToggleLabel(Blocks.t('Hide'));
		this.showingPassword = true;
	},

	hidePassword: function()
	{
		// showingPassword could be null, which is acceptable
		if (this.showingPassword === false)
			return;

		this.setCurrentInput(this.$passwordInput);
		this.updateToggleLabel(Blocks.t('Show'));
		this.showingPassword = false;

		// Alt key temporarily shows the password
		this.addListener(this.$passwordInput, 'keydown', 'onKeyDown');
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

	onKeyDown: function(event)
	{
		if (event.keyCode == Blocks.ALT_KEY && this.$currentInput.val())
		{
			this.showPassword();
			this.$showPasswordToggle.hide();
			this.addListener(this.$textInput, 'keyup', 'onKeyUp');
		}
	},

	onKeyUp: function(event)
	{
		event.preventDefault();

		if (event.keyCode == Blocks.ALT_KEY)
		{
			this.hidePassword();
			this.$showPasswordToggle.show();
		}
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

	onInputChange: function()
	{
		if (this.$currentInput.val())
			this.$showPasswordToggle.show();
		else
			this.$showPasswordToggle.hide();
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
			new Blocks.ui.PasswordInput(this);
	});
};

Blocks.$document.ready(function()
{
	$('input.password').passwordInput();
});

})(jQuery);
