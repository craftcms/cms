/**
 * Password Input
 */
Craft.PasswordInput = Garnish.Base.extend(
{
	$passwordInput: null,
	$textInput: null,
	$currentInput: null,

	$showPasswordToggle: null,
	showingPassword: null,

	init: function(passwordInput, settings)
	{
		this.$passwordInput = $(passwordInput);
		this.settings = $.extend({}, Craft.PasswordInput.defaults, settings);

		// Is this already a password input?
		if (this.$passwordInput.data('passwordInput'))
		{
			Garnish.log('Double-instantiating a password input on an element');
			this.$passwordInput.data('passwordInput').destroy();
		}

		this.$passwordInput.data('passwordInput', this);

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
			$input.trigger('focus');
			$input.removeClass('focus');

			// Restore the input value
			$input.val(this.$currentInput.val());
		}

		this.$currentInput = $input;

		this.addListener(this.$currentInput, 'keypress,keyup,change,blur', 'onInputChange');
	},

	updateToggleLabel: function(label)
	{
		this.$showPasswordToggle.text(label);
	},

	showPassword: function()
	{
		if (this.showingPassword)
		{
			return;
		}

		if (!this.$textInput)
		{
			this.$textInput = this.$passwordInput.clone(true);
			this.$textInput.attr('type', 'text');
		}

		this.setCurrentInput(this.$textInput);
		this.updateToggleLabel(Craft.t('Hide'));
		this.showingPassword = true;
	},

	hidePassword: function()
	{
		// showingPassword could be null, which is acceptable
		if (this.showingPassword === false)
		{
			return;
		}

		this.setCurrentInput(this.$passwordInput);
		this.updateToggleLabel(Craft.t('Show'));
		this.showingPassword = false;
	},

	togglePassword: function()
	{
		if (this.showingPassword)
		{
			this.hidePassword();
		}
		else
		{
			this.showPassword();
		}

		this.settings.onToggleInput(this.$currentInput);
	},

	onInputChange: function()
	{
		if (this.$currentInput.val())
		{
			this.$showPasswordToggle.show();
		}
		else
		{
			this.$showPasswordToggle.hide();
		}
	},

	onToggleMouseDown: function(ev)
	{
		// Prevent focus change
		ev.preventDefault();

		var selectionStart,
			selectionEnd;

		if (this.$currentInput[0].setSelectionRange)
		{
			selectionStart = this.$currentInput[0].selectionStart;
			selectionEnd   = this.$currentInput[0].selectionEnd;
		}

		this.togglePassword();

		if (this.$currentInput[0].setSelectionRange)
		{
			this.$currentInput[0].setSelectionRange(selectionStart, selectionEnd);
		}
	}
},
{
	defaults: {
		onToggleInput: $.noop
	}
});
