(function($) {

/**
 * Light Switch
 */
Blocks.ui.LightSwitch = Blocks.Base.extend({

	settings: null,
	$outerContainer: null,
	$innerContainer: null,
	$input: null,
	$toggleTarget: null,
	on: null,
	dragger: null,

	dragStartMargin: null,

	init: function(outerContainer, settings)
	{
		this.$outerContainer = $(outerContainer);

		// Is this already a switch?
		if (this.$outerContainer.data('lightswitch'))
		{
			Blocks.log('Double-instantiating a switch on an element');
			this.$outerContainer.data('lightswitch').destroy();
		}

		this.$outerContainer.data('lightswitch', this);

		this.setSettings(settings, Blocks.ui.LightSwitch.defaults);

		this.$innerContainer = this.$outerContainer.find('.container:first');
		this.$input = this.$outerContainer.find('input:first');
		this.$toggleTarget = $(this.$outerContainer.attr('data-toggle'));

		this.on = this.$outerContainer.hasClass('on');

		this.addListener(this.$outerContainer, 'mousedown', '_onMouseDown');
		this.addListener(this.$outerContainer, 'keydown', '_onKeyDown');

		this.dragger = new Blocks.ui.BaseDrag(this.$outerContainer, {
			axis: 'x',
			ignoreButtons: false,
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
		this.settings.onChange();

		this.$toggleTarget.show();
		this.$toggleTarget.height('auto');
		var height = this.$toggleTarget.height();
		this.$toggleTarget.height(0);
		this.$toggleTarget.stop().animate({height: height}, 'fast', $.proxy(function() {
			this.$toggleTarget.height('auto');
		}, this));
	},

	turnOff: function()
	{
		this.$innerContainer.stop().animate({marginLeft: Blocks.ui.LightSwitch.offMargin}, 'fast');
		this.$input.val('');
		this.on = false;
		this.settings.onChange();

		this.$toggleTarget.stop().animate({height: 0}, 'fast');
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
		this.addListener(Blocks.$document, 'mouseup', '_onMouseUp')
	},

	_onMouseUp: function()
	{
		this.removeListener(Blocks.$document, 'mouseup');

		// Was this a click?
		if (!this.dragger.dragging)
			this.toggle();
	},

	_onKeyDown: function(event)
	{
		switch (event.keyCode)
		{
			case Blocks.SPACE_KEY:
				this.toggle();
				event.preventDefault();
				break;
			case Blocks.RIGHT_KEY:
				this.turnOn();
				event.preventDefault();
				break;
			case Blocks.LEFT_KEY:
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

		if (margin < Blocks.ui.LightSwitch.offMargin)
			margin = Blocks.ui.LightSwitch.offMargin;
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
	},

	destroy: function()
	{
		this.base();
		this.dragger.destroy();
	}

}, {
	offMargin: -50,
	defaults: {
		onChange: function(){}
	}
});

$.fn.lightswitch = function(settings, settingName, settingValue)
{
	if (settings == 'settings')
	{
		if (typeof settingName == 'string')
		{
			settings = {};
			settings[settingName] = settingValue;
		}
		else
			settings = settingName;

		return this.each(function()
		{
			var obj = $.data(this, 'lightswitch');
			if (obj)
				obj.setSettings(settings);
		});
	}

	return this.each(function()
	{
		if (!$.data(this, 'lightswitch'))
			new Blocks.ui.LightSwitch(this, settings);
	});
};

Blocks.$document.ready(function()
{
	$('.lightswitch').lightswitch();
});

})(jQuery);
