/**
 * Light Switch
 */
Craft.LightSwitch = Garnish.Base.extend(
{
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

		// Is this already a lightswitch?
		if (this.$outerContainer.data('lightswitch'))
		{
			Garnish.log('Double-instantiating a lightswitch on an element');
			this.$outerContainer.data('lightswitch').destroy();
		}

		this.$outerContainer.data('lightswitch', this);

		this.setSettings(settings, Craft.LightSwitch.defaults);

		this.$innerContainer = this.$outerContainer.find('.lightswitch-container:first');
		this.$input = this.$outerContainer.find('input:first');
		this.$toggleTarget = $(this.$outerContainer.attr('data-toggle'));

		this.on = this.$outerContainer.hasClass('on');

		this.addListener(this.$outerContainer, 'mousedown', '_onMouseDown');
		this.addListener(this.$outerContainer, 'keydown', '_onKeyDown');

		this.dragger = new Garnish.BaseDrag(this.$outerContainer, {
			axis:          Garnish.X_AXIS,
			ignoreButtons: false,
			onDragStart:   $.proxy(this, '_onDragStart'),
			onDrag:        $.proxy(this, '_onDrag'),
			onDragStop:    $.proxy(this, '_onDragStop')
		});
	},

	turnOn: function()
	{
		this.$outerContainer.addClass('dragging');
		this.$innerContainer.stop().animate({marginLeft: 0}, Craft.LightSwitch.animationDuration, $.proxy(this, '_onSettle'));
		this.$input.val('1');
		this.$outerContainer.addClass('on');
		this.on = true;
		this.settings.onChange();

		this.$toggleTarget.show();
		this.$toggleTarget.height('auto');
		var height = this.$toggleTarget.height();
		this.$toggleTarget.height(0);
		this.$toggleTarget.stop().animate({height: height}, Craft.LightSwitch.animationDuration, $.proxy(function() {
			this.$toggleTarget.height('auto');
		}, this));
	},

	turnOff: function()
	{
		this.$outerContainer.addClass('dragging');
		this.$innerContainer.stop().animate({marginLeft: Craft.LightSwitch.offMargin}, Craft.LightSwitch.animationDuration, $.proxy(this, '_onSettle'));
		this.$input.val('');
		this.$outerContainer.removeClass('on');
		this.on = false;
		this.settings.onChange();

		this.$toggleTarget.stop().animate({height: 0}, Craft.LightSwitch.animationDuration);
	},

	toggle: function(event)
	{
		if (!this.on)
		{
			this.turnOn();
		}
		else
		{
			this.turnOff();
		}
	},

	_onMouseDown: function()
	{
		this.addListener(Garnish.$doc, 'mouseup', '_onMouseUp')
	},

	_onMouseUp: function()
	{
		this.removeListener(Garnish.$doc, 'mouseup');

		// Was this a click?
		if (!this.dragger.dragging)
			this.toggle();
	},

	_onKeyDown: function(event)
	{
		switch (event.keyCode)
		{
			case Garnish.SPACE_KEY:
				this.toggle();
				event.preventDefault();
				break;
			case Garnish.RIGHT_KEY:
				this.turnOn();
				event.preventDefault();
				break;
			case Garnish.LEFT_KEY:
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
		this.$outerContainer.addClass('dragging');
		this.dragStartMargin = this._getMargin();
	},

	_onDrag: function()
	{
		var margin = this.dragStartMargin + this.dragger.mouseDistX;

		if (margin < Craft.LightSwitch.offMargin)
		{
			margin = Craft.LightSwitch.offMargin;
		}
		else if (margin > 0)
		{
			margin = 0;
		}

		this.$innerContainer.css('marginLeft', margin);
	},

	_onDragStop: function()
	{
		var margin = this._getMargin();

		if (margin > (Craft.LightSwitch.offMargin / 2))
		{
			this.turnOn();
		}
		else
		{
			this.turnOff();
		}
	},

	_onSettle: function()
	{
		this.$outerContainer.removeClass('dragging');
	},

	destroy: function()
	{
		this.base();
		this.dragger.destroy();
	}

}, {
	offMargin: -9,
	animationDuration: 100,
	defaults: {
		onChange: $.noop
	}
});
