(function($) {

/**
 * Menu Button
 */
Blocks.ui.MenuBtn = Blocks.Base.extend({

	$btn: null,
	menu: null,
	showingMenu: false,

	/**
	 * Constructor
	 */
	init: function(btn, settings)
	{
		this.$btn = $(btn);

		// Is this already a menu button?
		if (this.$btn.data('menubtn'))
		{
			Blocks.log('Double-instantiating a menu button on an element');
			this.$btn.data('menubtn').destroy();
		}

		this.$btn.data('menubtn', this);

		this.setSettings(settings, Blocks.ui.MenuBtn.defaults);

		var $menu = this.$btn.next('.menu');
		this.menu = new Blocks.ui.Menu($menu, {
			onOptionSelect: $.proxy(this, 'onOptionSelect')
		});

		this.addListener(this.$btn, 'mousedown', 'onMouseDown');
	},

	onMouseDown: function(event)
	{
		if (event.button != 0 || event.metaKey)
			return;

		event.preventDefault();

		if (this.showingMenu)
			this.hideMenu();
		else
			this.showMenu();
	},

	showMenu: function()
	{
		this.menu.setPosition(this.$btn);
		this.menu.show();
		this.$btn.addClass('sel');
		this.showingMenu = true;

		setTimeout($.proxy(function() {
			this.addListener(Blocks.$document, 'mousedown', 'onMouseDown');
		}, this), 1);
	},

	hideMenu: function()
	{
		this.menu.hide();
		this.$btn.removeClass('sel');
		this.showingMenu = false;

		this.removeListener(Blocks.$document, 'mousedown');
	},

	onOptionSelect: function(option)
	{
		this.settings.onOptionSelect(option);
	}

}, {
	defaults: {
		onOptionSelect: function() {}
	}
});

/**
 * Menu
 */
Blocks.ui.Menu = Blocks.Base.extend({

	settings: null,

	$container: null,
	$options: null,

	/**
	 * Constructor
	 */
	init: function(container, settings)
	{
		this.setSettings(settings, Blocks.ui.Menu.defaults);

		this.$container = $(container).appendTo(Blocks.$body);
		this.$options = this.$container.find('li');

		this.addListener(this.$options, 'mousedown', 'selectOption');
	},

	setPosition: function($btn)
	{
		var offset = $btn.offset();
		this.$container.css({
			top: offset.top + $btn.outerHeight(),
			left: offset.left,
			minWidth: $btn.outerWidth()
		});
	},

	show: function()
	{
		this.$container.fadeIn(75);
	},

	hide: function()
	{
		this.$container.fadeOut('fast');
	},

	selectOption: function(event)
	{
		this.settings.onOptionSelect(event.currentTarget);
	}

}, {
	defaults: {
		onOptionSelect: function() {}
	}
});

})(jQuery);
