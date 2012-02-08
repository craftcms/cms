(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Switch
 */
blx.ui.Switch = blx.Base.extend({

	$outerContainer: null,
	$innerContainer: null,
	$btn: null,
	$input: null,
	on: null,

	dragStartMargin: null,

	init: function(outerContainer)
	{
		this.$outerContainer = $(outerContainer);
		this.$innerContainer = this.$outerContainer.find('.container:first');
		this.$btn = this.$innerContainer.find('.btn:first');
		this.$input = this.$outerContainer.find('input:first');

		this.on = this.$outerContainer.hasClass('on');

		blx.utils.preventOutlineOnMouseFocus(this.$outerContainer);
		this.addListener(this.$innerContainer, 'mousedown', '_onMouseDown');
		this.addListener(this.$outerContainer, 'keydown', '_onKeyDown');

		this.dragger = new blx.ui.DragCore(this.$innerContainer, {
			axis: 'x',
			onDragStart: $.proxy(this, '_onDragStart'),
			onDrag:      $.proxy(this, '_onDrag'),
			onDragStop:  $.proxy(this, '_onDragStop')
		});
	},

	turnOn: function()
	{
		this.$innerContainer.stop().animate({marginLeft: 0}, 'fast');
		this.$input.val('y');
		this.on = true;
	},

	turnOff: function()
	{
		this.$innerContainer.stop().animate({marginLeft: -32}, 'fast');
		this.$input.val('');
		this.on = false;
	},

	toggle: function(event)
	{
		if (!this.on)
			this.turnOn();
		else
			this.turnOff();
	},

	_onMouseDown: function()
	{
		this.$btn.addClass('sel');
		this.addListener(blx.$document, 'mouseup', '_onMouseUp')
	},

	_onMouseUp: function()
	{
		this.$btn.removeClass('sel');
		this.removeListener(blx.$document, 'mouseup');

		// Was this a click?
		if (!this.dragger.dragging)
			this.toggle();
	},

	_onKeyDown: function(event)
	{
		switch (event.keyCode)
		{
			case blx.SPACE_KEY:
				this.toggle();
				event.preventDefault();
				break;
			case blx.RIGHT_KEY:
				this.turnOn();
				event.preventDefault();
				break;
			case blx.LEFT_KEY:
				this.turnOff();
				event.preventDefault();
				break;
		}
	},

	_getMargin: function()
	{
		return parseInt(this.$innerContainer.css('marginLeft'))
	},

	_onDragStart: function()
	{
		this.dragStartMargin = this._getMargin();
	},

	_onDrag: function()
	{
		var margin = this.dragStartMargin + this.dragger.mouseDistX;

		if (margin < -32)
			margin = -32;
		else if (margin > 0)
			margin = 0;

		this.$innerContainer.css('marginLeft', margin);
	},

	_onDragStop: function()
	{
		var margin = this._getMargin();

		if (margin > -16)
			this.turnOn();
		else
			this.turnOff();
	}

});


})(jQuery);
