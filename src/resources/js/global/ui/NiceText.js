(function($) {

/**
 * Nice Text
 */
Blocks.ui.NiceText = Blocks.Base.extend({

	$input: null,
	$hint: null,
	$stage: null,
	autoHeight: null,
	focussed: false,
	showingHint: false,
	val: null,
	stageHeight: null,
	minHeight: null,
	interval: null,

	init: function(input, settings)
	{
		this.$input = $(input);
		this.settings = $.extend({}, Blocks.ui.NiceText.defaults, settings);

		// Is this already a transparent text input?
		if (this.$input.data('nicetext'))
		{
			Blocks.log('Double-instantiating a transparent text input on an element');
			this.$input.data('nicetext').destroy();
		}

		this.$input.data('nicetext', this);

		this.getVal();

		this.autoHeight = (this.settings.autoHeight && this.$input.prop('nodeName') == 'TEXTAREA');
		if (this.autoHeight)
		{
			this.minHeight = this.getStageHeight('');
			this.setHeight();

			this.addListener(Blocks.$window, 'resize', 'setHeight');
		}

		if (this.settings.hint)
		{
			this.$hintContainer = $('<div class="texthint-container"/>').insertBefore(this.$input);
			this.$hint = $('<div class="texthint">'+this.settings.hint+'</div>').appendTo(this.$hintContainer);
			this.$hint.css({
				top:  (parseInt(this.$input.css('borderTopWidth'))  + parseInt(this.$input.css('paddingTop'))),
				left: (parseInt(this.$input.css('borderLeftWidth')) + parseInt(this.$input.css('paddingLeft')) + 1)
			});
			Blocks.copyTextStyles(this.$input, this.$hint);

			if (this.val)
				this.$hint.hide();
			else
				this.showingHint = true;

			// Focus the input when clicking on the hint
			this.addListener(this.$hint, 'mousedown', function(event) {
				event.preventDefault();
				this.$input.focus();
			});
		}

		this.addListener(this.$input, 'focus', 'onFocus');
		this.addListener(this.$input, 'blur', 'onBlur');
		this.addListener(this.$input, 'keydown', 'onKeyDown');
	},

	getVal: function()
	{
		this.val = this.$input.val();
		return this.val;
	},

	showHint: function()
	{
		this.$hint.fadeIn(Blocks.ui.NiceText.hintFadeDuration);
		this.showingHint = true;
	},

	hideHint: function()
	{
		this.$hint.fadeOut(Blocks.ui.NiceText.hintFadeDuration);
		this.showingHint = false;
	},

	checkInput: function()
	{
		// Has the value changed?
		var changed = (this.val !== this.getVal());
		if (changed)
		{
			if (this.$hint)
			{
				if (this.showingHint && this.val)
					this.hideHint();
				else if (!this.showingHint && !this.val)
					this.showHint();
			}

			if (this.autoHeight)
				this.setHeight();
		}

		return changed;
	},

	buildStage: function()
	{
		this.$stage = $('<stage/>').appendTo(Blocks.$body);

		// replicate the textarea's text styles
		this.$stage.css({
			position: 'absolute',
			top: -9999,
			left: -9999,
			wordWrap: 'break-word'
		});

		Blocks.copyTextStyles(this.$input, this.$stage);
	},

	getStageHeight: function(val)
	{
		if (!this.$stage)
			this.buildStage();

		this.$stage.css('width', this.$input.width());

		if (!val)
		{
			val = '&nbsp;';
			for (var i = 1; i < this.$input.prop('rows'); i++)
			{
				val += '<br/>&nbsp;';
			}
		}
		else
		{
			// Ampersand entities
			val = val.replace(/&/g, '&amp;');

			// < and >
			val = val.replace(/</g, '&lt;');
			val = val.replace(/>/g, '&gt;');

			// Spaces
			val = val.replace(/ /g, '&nbsp;');

			// Line breaks
			val = val.replace(/[\n\r]$/g, '<br/>&nbsp;');
			val = val.replace(/[\n\r]/g, '<br/>');

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
		this.interval = setInterval($.proxy(this, 'checkInput'), Blocks.ui.NiceText.interval);
		this.checkInput();
	},

	onBlur: function()
	{
		this.focussed = false;
		clearInterval(this.interval);

		this.checkInput();
	},

	onKeyDown: function()
	{
		setTimeout($.proxy(this, 'checkInput'), 1);
	},

	destroy: function()
	{
		this.base();
		this.$hint.remove();
		this.$stage.remove();
	}

}, {
	interval: 100,
	hintFadeDuration: 50,
	defaults: {
		autoHeight: true
	}
});

$.fn.nicetext = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'text'))
			new Blocks.ui.NiceText(this, {hint: this.getAttribute('data-hint')});
	});
};

Blocks.$document.ready(function()
{
	$('.nicetext').nicetext();
});

})(jQuery);
