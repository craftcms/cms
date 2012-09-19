(function($) {

/**
 * Select Menu
 */
Blocks.ui.SelectMenu = Blocks.ui.Menu.extend({

	/**
	 * Constructor
	 */
	init: function(btn, options, settings, callback)
	{
		// argument mapping
		if (typeof settings == 'function')
		{
			// (btn, options, callback)
			callback = settings;
			settings = {};
		}

		settings = $.extend({}, Blocks.ui.SelectMenu.defaults, settings);

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

	Blocks.ui.SelectMenu.defaults = {
	ulClass: 'menu select'
};

})(jQuery);
