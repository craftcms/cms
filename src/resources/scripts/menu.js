(function($) {



/**
 * Menu
 */
blx.Menu = Base.extend({

	/**
	 * Constructor
	 */
	constructor: function(btn, options, settings, callback)
	{
		// argument mapping
		if (typeof settings == 'function')
		{
			// (btn, options, callback)
			callback = settings;
			settings = {};
		}

		if (typeof callback != 'function') {
			callback = function(){};
		}

		this.dom = {};
		this.dom.btn = btn;
		this.dom.$btn = $(btn);
		this.dom.$btnLabel = $('span.label', this.dom.$btn);

		this.options = options;
		this.settings = $.extend({}, blx.Menu.defaults, settings);
		this.callback = callback;

		this.showing = false;

		this.dom.$btn.on('click.menu', $.proxy(this, 'toggle'));
	},

	/**
	 * Build
	 */
	build: function()
	{
		this.dom.ul = document.createElement('ul');
		this.dom.ul.className = this.settings.ulClass;
		document.body.appendChild(this.dom.ul);

		this.dom.options = [];

		for (var i = 0; i < this.options.length; i++)
		{
			var li = document.createElement('li');
			li.innerHTML = this.options[i].label;
			this.dom.ul.appendChild(li);

			$(li).on('click.menu', { option: i }, $.proxy(function(event) {
				this.select(event.data.option);
			}, this));

			this.dom.options[i] = li;
		}

	},

	/**
	 * Show
	 */
	show: function()
	{
		// ignore if already showing
		if (this.showing) return;

		this.dom.$btn.addClass('sel');

		if (! this.dom.ul)
			this.build();

		var btnOffset = this.dom.$btn.offset(),
			btnHeight = this.dom.$btn.outerHeight(),
			btnWidth = this.dom.$btn.outerWidth();

		this.dom.ul.style.top = (btnOffset.top + btnHeight) + 'px';
		this.dom.ul.style.minWidth = (btnWidth - 2) + 'px';

		if (this.settings.align == blx.Menu.ALIGN_LEFT)
		{
			this.dom.ul.style.left = (btnOffset.left + 1) + 'px';
		}
		else
		{
			this.dom.ul.style.right = (btnOffset.right + btnWidth + 1) + 'px';
		}

		this.dom.ul.style.left = (btnOffset.left + 1) + 'px';
		this.dom.ul.style.display = 'block';

		this.showing = true;

		// wait for this event to finish propagating, and then listen for new clicks
		setTimeout($.proxy(function() {
			$(document.body).on('click.menu', $.proxy(this, 'hide'));
		}, this), 1);
	},

	/**
	 * Hide
	 */
	hide: function()
	{
		// ignore if not showing
		if (! this.showing) return;

		this.dom.$btn.removeClass('sel');
		this.dom.ul.style.display = 'none';

		$(document.body).off('click.menu');

		this.showing = false;
	},

	/**
	 * Toggle
	 */
	toggle: function()
	{
		if (this.showing)
			this.hide();
		else
			this.show();
	},

	/**
	 * Select
	 */
	select: function(option)
	{
		this.callback(option);
	}

},
{
	ALIGN_LEFT: 'left',
	ALIGN_RIGHT: 'right'
});



blx.Menu.defaults = { 
	align: blx.Menu.ALIGN_LEFT,
	ulClass: 'menu'
};



/**
 * Select Menu
 */
blx.SelectMenu = blx.Menu.extend({

	/** 
	 * Constructor
	 */
	constructor: function(btn, options, settings, callback)
	{
		// argument mapping
		if (typeof settings == 'function')
		{
			// (btn, options, callback)
			callback = settings;
			settings = {};
		}

		settings = $.extend({}, blx.SelectMenu.defaults, settings);

		this.base(btn, options, settings, callback);

		this.selected = -1;
	},

	/**
	 * Build
	 */
	build: function() {
		this.base();

		if (this.selected != -1)
			this._addSelectedOptionClass(this.selected);
	},

	/**
	 * Select
	 */
	select: function(option)
	{
		// ignore if it's already selected
		if (option == this.selected) return;

		if (this.dom.ul)
		{
			if (this.selected != -1)
				this.dom.options[this.selected].className = '';

			this._addSelectedOptionClass(option);
		}

		this.selected = option;

		// set the button text to the selected option
		this.setBtnText($(this.options[option].label).text());

		this.base(option);
	},

	/**
	 * Add Selected Option Class
	 */
	_addSelectedOptionClass: function(option)
	{
		this.dom.options[option].className = 'sel';
	},

	/**
	 * Set Button Text
	 */
	setBtnText: function(text)
	{
		this.dom.$btnLabel.text(text);
	}

});



blx.SelectMenu.defaults = {
	ulClass: 'menu select'
};



})(jQuery);
