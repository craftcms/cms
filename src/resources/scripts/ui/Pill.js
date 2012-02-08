(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Switch
 */
blx.ui.Pill = blx.Base.extend({

	$outerContainer: null,
	$innerContainer: null,
	$btns: null,
	$selectedBtn: null,
	$input: null,

	init: function(outerContainer)
	{
		this.$outerContainer = $(outerContainer);
		this.$innerContainer = this.$outerContainer.find('.btn-group:first');
		this.$btns = this.$innerContainer.find('.btn');
		this.$selectedBtn = this.$btns.filter('.sel:first');
		this.$input = this.$outerContainer.find('input:first');

		blx.utils.preventOutlineOnMouseFocus(this.$innerContainer);
		this.addListener(this.$btns, 'click', '_onClick');
		this.addListener(this.$innerContainer, 'keydown', '_onKeyDown');
	},

	select: function(btn)
	{
		this.$selectedBtn.removeClass('sel');
		var $btn = $(btn);
		$btn.addClass('sel');
		this.$input.val($btn.attr('data-value'));
		this.$selectedBtn = $btn;
	},

	_onClick: function(event)
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

	_onKeyDown: function(event)
	{
		switch (event.keyCode)
		{
			case blx.RIGHT_KEY:
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
			case blx.LEFT_KEY:
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


})(jQuery);
