(function($) {

/**
 * Nice Text
 */
b.ui.NiceText = b.Base.extend({

	$input: null,
	$hint: null,
	$stage: null,
	isTextarea: null,
	focussed: false,
	showingHint: false,
	val: null,
	stageHeight: null,
	minHeight: null,
	interval: null,

	init: function(input, settings)
	{
		this.$input = $(input);
		this.settings = $.extend({}, b.ui.NiceText.defaults, settings);

		// Is this already a transparent text input?
		if (this.$input.data('nicetext'))
		{
			b.log('Double-instantiating a transparent text input on an element');
			this.$input.data('nicetext').destroy();
		}

		this.$input.data('nicetext', this);
		this.isTextarea = (this.$input[0].nodeName == 'TEXTAREA');

		if (this.isTextarea)
			this.minHeight = this.getStageHeight('');

		this.getVal();

		var hintText = this.$input.attr('data-hint');
		if (hintText)
		{
			this.$hint = $('<div class="nicetext-hint-container"><div class="nicetext-hint">'+hintText+'</div></div>');
			this.$hint.insertBefore(this.$input);

			if (this.val)
				this.$hint.hide();
			else
				this.showingHint = true;
		}

		this.addListener(this.$input, 'focus', 'onFocus');
		this.addListener(this.$input, 'blur', 'onBlur');
		this.addListener(this.$input, 'keydown', 'onKeydown');
	},

	getVal: function()
	{
		this.val = this.$input.val();
		return this.val;
	},

	showHint: function()
	{
		this.$hint.fadeIn(b.ui.NiceText.hintFadeDuration);
		this.showingHint = true;
	},

	hideHint: function()
	{
		this.$hint.fadeOut(b.ui.NiceText.hintFadeDuration);
		this.showingHint = false;
	},

	checkInput: function()
	{
		// Has the value changed?
		var changed = (this.val !== this.getVal());
		if (changed)
		{
			if (this.showingHint && this.val)
				this.hideHint();

			if (this.isTextarea)
				this.setHeight();
		}

		return changed;
	},

	buildStage: function()
	{
		this.$stage = $('<stage/>').insertAfter(this.$input);

		// replicate the textarea's text styles
		this.$stage.css({
			position: 'absolute',
			top: -9999,
			left: -9999,
			width: this.$input.width(),
			lineHeight: this.$input.css('lineHeight'),
			fontSize: this.$input.css('fontSize'),
			fontFamily: this.$input.css('fontFamily'),
			fontWeight: this.$input.css('fontWeight'),
			letterSpacing: this.$input.css('letterSpacing'),
			wordWrap: 'break-word'
		});
	},

	getStageHeight: function(val)
	{
		if (!this.$stage)
			this.buildStage();

		if (!val)
		{
			val = '&nbsp;';
			for (var i = 1; i < this.$input[0].rows; i++)
			{
				val += '<br/>&nbsp;';
			}
		}
		else
		{
			// HTML entities
			val = val.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/[\n\r]$/g, '<br/>&nbsp;').replace(/[\n\r]/g, '<br/>');

			// One extra line for fun
			val += '<br/>&nbsp;';
		}

		this.$stage.html(val);
		this.stageHeight = this.$stage.height();
		return this.stageHeight;
	},

	setHeight: function()
	{
		// has the height changed?
		if (this.stageHeight !== this.getStageHeight(this.val))
		{
			// update the textarea height
			var height = this.stageHeight;
			if (height < this.minHeight)
				height = this.minHeight;
			this.$input.height(height);
		}
	},

	onFocus: function()
	{
		this.focussed = true;
		this.interval = setInterval($.proxy(this, 'checkInput'), b.ui.NiceText.interval);
		this.checkInput();
	},

	onBlur: function()
	{
		this.focussed = false;
		clearInterval(this.interval);

		this.checkInput()

		if (this.$hint && !this.showingHint && !this.val)
			this.showHint();
	},

	onKeydown: function()
	{
		setTimeout($.proxy(this, 'checkInput'), 1);
	}

}, {
	interval: 100,
	hintFadeDuration: 50,
	defaults: {
	}
});


$.fn.nicetext = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'text'))
			new b.ui.NiceText(this);
	});
};

b.$document.ready(function()
{
	$('#body .nicetext').nicetext();
});


})(jQuery);
