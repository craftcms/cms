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
		{
			this.hideMenu();
		}
		else
		{
			this.showMenu();
		}
	},

	showMenu: function()
	{
		this.menu.setPosition(this.$btn);
		this.menu.show();
		this.$btn.addClass('active');
		this.showingMenu = true;

		setTimeout($.proxy(function() {
			this.addListener(Blocks.$document, 'mousedown', 'onMouseDown');
		}, this), 1);
	},

	hideMenu: function()
	{
		this.menu.hide();
		this.$btn.removeClass('active');
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
		var btnOffset = $btn.offset(),
			btnWidth = $btn.outerWidth(),
			css = {
				top: btnOffset.top + $btn.outerHeight(),
				minWidth: (btnWidth - 32)
			};

		if (this.$container.attr('data-align') == 'right')
		{
			css.right = 1 + Blocks.$window.width() - (btnOffset.left + btnWidth);
		}
		else
		{
			css.left = 1 + btnOffset.left;
		}

		this.$container.css(css);
	},

	show: function()
	{
		this.$container.fadeIn(50);
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


$.fn.menubtn = function()
{
	return this.each(function()
	{
		if (!$.data(this, 'menubtn'))
		{
			new Blocks.ui.MenuBtn(this);
		}
	});
};


Blocks.$document.ready(function()
{
	$('.menubtn').menubtn();
});


})(jQuery);
