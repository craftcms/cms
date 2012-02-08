(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Switch
 */
blx.ui.Switch = blx.Base.extend({

	$outerContainer: null,
	$innerContainer: null,
	$input: null,
	on: null,

	dragStartMargin: null,

	init: function(outerContainer)
	{
		this.$outerContainer = $(outerContainer);
		this.$innerContainer = this.$outerContainer.find('.container:first');
		this.$input = this.$outerContainer.find('input:first');

		this.on = this.$outerContainer.hasClass('on');

		blx.utils.preventOutlineOnMouseFocus(this.$outerContainer);
		this.addListener(this.$outerContainer, 'click', 'toggle');
		this.addListener(this.$outerContainer, 'keydown', 'onKeydown');

		this.dragger = new blx.ui.DragCore(this.$innerContainer, {
			axis: 'x',
			onDragStart: $.proxy(this, 'onDragStart'),
			onDrag:      $.proxy(this, 'onDrag'),
			onDragStop:  $.proxy(this, 'onDragStop')
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

	toggle: function()
	{
		if (!this.on)
			this.turnOn();
		else
			this.turnOff();
	},

	onKeydown: function(event)
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

	onDragStart: function()
	{
		this.dragStartMargin = parseInt(this.$innerContainer.css('marginLeft'));
	},

	onDrag: function()
	{
		var margin = this.dragStartMargin + this.dragger.mouseDistX;

		if (margin < -32)
			margin = -32;
		else if (margin > 0)
			margin = 0;

		this.$innerContainer.css('marginLeft', margin);
	},

	onDragStop: function()
	{
		var margin = parseInt(this.$innerContainer.css('marginLeft'));

		if (margin < -16)
			this.turnOn();
		else
			this.turnOff();
	}

});


})(jQuery);
