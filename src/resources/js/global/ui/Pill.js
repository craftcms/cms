(function($) {

/**
 * Pill
 */
Blocks.ui.Pill = Blocks.Base.extend({

	$outerContainer: null,
	$innerContainer: null,
	$btns: null,
	$selectedBtn: null,
	$input: null,

	init: function(outerContainer)
	{
		this.$outerContainer = $(outerContainer);

		// Is this already a pill?
		if (this.$outerContainer.data('pill'))
		{
			Blocks.log('Double-instantiating a pill on an element');
			this.$outerContainer.data('pill').destroy();
		}

		this.$outerContainer.data('pill', this);

		this.$innerContainer = this.$outerContainer.find('.btngroup:first');
		this.$btns = this.$innerContainer.find('.btn');
		this.$selectedBtn = this.$btns.filter('.active:first');
		this.$input = this.$outerContainer.find('input:first');

		Blocks.preventOutlineOnMouseFocus(this.$innerContainer);
		this.addListener(this.$btns, 'mousedown', 'onMouseDown');
		this.addListener(this.$innerContainer, 'keydown', 'onKeyDown');
	},

	select: function(btn)
	{
		this.$selectedBtn.removeClass('active');
		var $btn = $(btn);
		$btn.addClass('active');
		this.$input.val($btn.attr('data-value'));
		this.$selectedBtn = $btn;
	},

	onMouseDown: function(event)
	{
		this.select(event.currentTarget);
	},

	_getSelectedBtnIndex: function()
	{
		if (typeof this.$selectedBtn[0] != 'undefined')
			return $.inArray(this.$selectedBtn[0], this.$btns);
		else
			return -1;
	},

	onKeyDown: function(event)
	{
		switch (event.keyCode)
		{
			case Blocks.RIGHT_KEY:
				if (!this.$selectedBtn.length)
					this.select(this.$btns[this.$btns.length-1]);
				else
				{
					var nextIndex = this._getSelectedBtnIndex() + 1;
					if (typeof this.$btns[nextIndex] != 'undefined')
						this.select(this.$btns[nextIndex]);
				}
				event.preventDefault();
				break;
			case Blocks.LEFT_KEY:
				if (!this.$selectedBtn.length)
					this.select(this.$btns[0]);
				else
				{
					var prevIndex = this._getSelectedBtnIndex() - 1;
					if (typeof this.$btns[prevIndex] != 'undefined')
						this.select(this.$btns[prevIndex]);
				}
				event.preventDefault();
				break;
		}
	}

});

$.fn.pill = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'pill'))
			new Blocks.ui.Pill(this);
	});
};

Blocks.$document.ready(function()
{
	$('.pill').pill();
});

})(jQuery);
