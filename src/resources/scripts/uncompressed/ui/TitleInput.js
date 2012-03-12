(function($) {


/**
 * Title Input
 */
blx.ui.TitleInput = blx.Base.extend({

	$container: null,
	$heading: null,
	$hiddenInput: null,
	$input: null,
	val: null,

	init: function(container, settings)
	{
		this.$container = $(container);

		// Is this already a title input?
		if (this.$container.data('titleinput'))
		{
			blx.log('Double-instantiating a title input on an element');
			this.$container.data('titleinput').destroy();
		}
		this.$container.data('titleinput', this);

		this.settings = $.extend({}, blx.ui.TitleInput.defaults, settings);

		this.$heading = this.$container.find('h1');
		this.$hiddenInput = this.$container.find('input');

		this.addListener(this.$container, 'focus,click', 'showInput');
	},

	createInput: function()
	{
		this.$input = $('<input class="title-input" type="text"/>').insertAfter(this.$container);
		this.$input.attr('name', this.$hiddenInput.attr('name'));
		this.val = this.$hiddenInput.val();
		this.$input.val(this.val);
		this.$hiddenInput.remove();
		this.addListener(this.$input, 'keydown', 'onKeydown');
		this.addListener(this.$input, 'blur', 'hideInput');
		this.settings.onCreateInput();
	},

	showInput: function()
	{
		if (!this.$input)
			this.createInput();

		this.$input.show();
		this.$container.hide();
		this.$input.focus();

		var length = this.$input.val().length * 2;
		this.$input[0].setSelectionRange(0, length);
	},

	hideInput: function()
	{
		this.$container.show();
		this.$input.hide();

		// Has the value changed?
		if (this.val != (this.val = this.$input.val()))
		{
			if (this.val)
			{
				this.$heading.removeClass('empty');
				this.$heading.text(this.val);
			}
			else
			{
				this.$heading.addClass('empty');
				this.$heading.text(this.settings.emptyText);
			}
			this.settings.onChange();
		}
	},

	onKeydown: function(event)
	{
		// Ignore if meta key is down
		if (event.metaKey) return;

		if (event.keyCode == blx.RETURN_KEY)
		{
			event.preventDefault();
			this.hideInput();
		}

		this.settings.onKeydown();
	}

}, {
	defaults: {
		onCreateInput: function(){},
		onKeydown: function(){},
		onChange: function(){},
		emptyText: 'Untitled'
	}
});


})(jQuery);
