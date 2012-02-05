(function($) {


if (typeof blx == 'undefined')
	blx = {};


blx.$window = $(window);
blx.$document = $(document);
blx.$body = $(document.body);


/**
 * Base class
 */
blx.Base = Base.extend({

	namespace: null,

	constructor: function()
	{
		this.namespace = '.blx'+Math.floor(Math.random()*999999999);
		this.init.apply(this, arguments);
	},

	init: function(){},

	addListener: function(elem, event, func)
	{
		if (typeof func == 'function')
			func = $.proxy(func, this);
		else
			func = $.proxy(this, func);

		$(elem).on(event+this.namespace, func);
	},

	removeListener: function(elem, event)
	{
		$(elem).off(event+this.namespace);
	},

	removeAllListeners: function(elem)
	{
		$(elem).off(this.namespace);
	}

});


/**
 * Blocks class
 */
var CP = blx.Base.extend({

	_windowHeight: null,
	_$sidebar: null,
	_sidebarTop: null,

	init: function()
	{
		var $sidebar = $('#sidebar');
		if ($sidebar.length)
		{
			this._$sidebar = $sidebar;
			this._sidebarTop = parseInt(this._$sidebar.css('top'));

			this.setSidebarHeight();
			this.addListener(blx.$window, 'resize', 'setSidebarHeight');
			this.addListener(blx.$window, 'scroll', 'setSidebarHeight');
		}
	},

	setSidebarHeight: function()
	{
		if (! this._$sidebar)
			return false;

		// has the window height changed?
		if (this._windowHeight !== (this._windowHeight = blx.$window.height()))
		{
			var sidebarHeight = this._windowHeight - this._sidebarTop;
			this._$sidebar.height(sidebarHeight);
		}
	}

});

blx.cp = new CP();


/**
 * Utility functions
 */
blx.utils =
{
	/**
	 * Format a number with commas
	 * ex: 1000 => 1,000
	 */
	numCommas: function(num)
	{
		num = num.toString();

		var regex = /(\d+)(\d{3})/;
		while (regex.test(num)) {
			num = num.replace(regex, '$1'+','+'$2');
		}

		return num;
	},

	/**
	 * Get the distance between two coordinates
	 */
	getDist: function(x1, y1, x2, y2)
	{
		return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
	},

	/**
	 * Check if an element is touching an x/y coordinate
	 */
	hitTest: function(x0, y0, element)
	{
		var $element = $(element),
			offset = $element.offset(),
			x1 = offset.left,
			y1 = offset.top,
			x2 = x1 + $element.width(),
			y2 = y1 + $element.height();

		return (x0 >= x1 && x0 < x2 && y0 >= y1 && y0 < y2);
	},

	/**
	 * Check if the cursor is over an element
	 */
	isCursorOver: function(event, element)
	{
		return blx.utils.hitTest(event.pageX, event.pageY, element);
	},

	/**
	 * Case insensative sort
	 */
	caseInsensativeSort: function(arr)
	{
		return arr.sort(this.caseInsensativeCompare)
	},

	/**
	 * Case insensative string comparison
	 */
	caseInsensativeCompare: function(a, b)
	{
		a = a.toLowerCase();
		b = b.toLowerCase();
		return a < b ? -1 : (a > b ? 1 : 0);
	},

	/**
	 * Returns the body's proper scrollTop, discarding any document banding in Safari
	 */
	getBodyScrollTop: function()
	{
		var scrollTop = document.body.scrollTop;

		if (scrollTop < 0)
			scrollTop = 0;
		else
		{
			var maxScrollTop = blx.$body.outerHeight() - blx.$window.height();
			if (scrollTop > maxScrollTop)
				scrollTop = maxScrollTop;
		}

		return scrollTop;
	}
};


blx.fx = {
	duration: 400,
	delay: 100
};


})(jQuery);
