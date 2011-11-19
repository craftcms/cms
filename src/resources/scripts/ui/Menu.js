(function($) {


if (typeof blx.ui == 'undefined')
	blx.ui = {};


/**
 * Menu
 */
blx.ui.Menu = Base.extend({

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
		this.settings = $.extend({}, blx.ui.Menu.defaults, settings);
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

		if (!this.dom.ul)
			this.build();

		var btnOffset = this.dom.$btn.offset(),
			btnHeight = this.dom.$btn.outerHeight(),
			btnWidth = this.dom.$btn.outerWidth();

		this.dom.ul.style.top = (btnOffset.top + btnHeight) + 'px';
		this.dom.ul.style.minWidth = (btnWidth - 2) + 'px';

		if (this.settings.align == blx.ui.Menu.ALIGN_LEFT)
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
		if (!this.showing) return;

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
	defaults: { 
		align: 'left',
		ulClass: 'menu'
	}
});


})(jQuery);
