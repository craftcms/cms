(function($) {


if (typeof blx == 'undefined')
	blx = {};


blx.$window = $(window);
blx.$document = $(document);
blx.$body = $(document.body);


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
	 * Converts a comma-delimited string into an array
	 */
	stringToArray: function(str)
	{
		if (typeof str != 'string')
			return str;

		var arr = str.split(',');
		for (var i = 0; i < arr.length; i++)
		{
			arr[i] = $.trim(arr[i]);
		}
		return arr;
	},

	/**
	 * Converts extended ASCII characters to ASCII
	 */
	asciiCharMap: {'223':'ss','224':'a','225':'a','226':'a','229':'a','227':'ae','230':'ae','228':'ae','231':'c','232':'e','233':'e','234':'e','235':'e','236':'i','237':'i','238':'i','239':'i','241':'n','242':'o','243':'o','244':'o','245':'o','246':'oe','249':'u','250':'u','251':'u','252':'ue','255':'y','257':'aa','269':'ch','275':'ee','291':'gj','299':'ii','311':'kj','316':'lj','326':'nj','353':'sh','363':'uu','382':'zh','256':'aa','268':'ch','274':'ee','290':'gj','298':'ii','310':'kj','315':'lj','325':'nj','352':'sh','362':'uu','381':'zh'},

	asciiString: function(str)
	{
		var asciiStr = '';

		for (c = 0; c < str.length; c++) {
			charCode = str.charCodeAt(c);

			if (charCode >= 32 && charCode < 128)
				asciiStr += str.charAt(c);
			else if (typeof this.asciiCharMap[charCode] != 'undefined')
				asciiStr += this.asciiCharMap[charCode];
		}

		return asciiStr;
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

	_formatEvents: function(events)
	{
		events = blx.utils.stringToArray(events);
		for (var i = 0; i < events.length; i++)
		{
			events[i] += this.namespace;
		}
		return events.join(' ');
	},

	addListener: function(elem, events, func)
	{
		events = this._formatEvents(events);

		if (typeof func == 'function')
			func = $.proxy(func, this);
		else
			func = $.proxy(this, func);

		$(elem).on(events, func);
	},

	removeListener: function(elem, events)
	{
		events = this._formatEvents(events);
		$(elem).off(events);
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


})(jQuery);
