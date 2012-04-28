(function($) {


if (typeof b == 'undefined') b = {};
if (typeof b.ui == 'undefined') b.ui = {};


// jQuery objects for common elements
b.$window = $(window);
b.$document = $(document);
b.$body = $(document.body);


// Key code constants
b.DELETE_KEY = 8;
b.SHIFT_KEY  = 16;
b.CTRL_KEY   = 17;
b.ALT_KEY    = 18;
b.RETURN_KEY = 13;
b.ESC_KEY    = 27;
b.SPACE_KEY  = 32;
b.LEFT_KEY   = 37;
b.UP_KEY     = 38;
b.RIGHT_KEY  = 39;
b.DOWN_KEY   = 40;
b.CMD_KEY    = 91;


b.navHeight = 48;

b.fx = {
	duration: 400,
	delay: 100
};


/**
 * Log
 */
b.log = function(msg)
{
	if (typeof console != 'undefined' && typeof console.log == 'function')
		console.log(msg);
};


var asciiCharMap = {'223':'ss','224':'a','225':'a','226':'a','229':'a','227':'ae','230':'ae','228':'ae','231':'c','232':'e','233':'e','234':'e','235':'e','236':'i','237':'i','238':'i','239':'i','241':'n','242':'o','243':'o','244':'o','245':'o','246':'oe','249':'u','250':'u','251':'u','252':'ue','255':'y','257':'aa','269':'ch','275':'ee','291':'gj','299':'ii','311':'kj','316':'lj','326':'nj','353':'sh','363':'uu','382':'zh','256':'aa','268':'ch','274':'ee','290':'gj','298':'ii','310':'kj','315':'lj','325':'nj','352':'sh','362':'uu','381':'zh'};



/**
 * Format a number with commas.
 * @param mixed num
 * @return string
 */
b.numCommas = function(num)
{
	num = num.toString();

	var regex = /(\d+)(\d{3})/;
	while (regex.test(num)) {
		num = num.replace(regex, '$1'+','+'$2');
	}

	return num;
};

/**
 * Converts a comma-delimited string into an array.
 * @param string str
 * @return array
 */
b.stringToArray = function(str)
{
	if (typeof str != 'string')
		return str;

	var arr = str.split(',');
	for (var i = 0; i < arr.length; i++)
	{
		arr[i] = $.trim(arr[i]);
	}
	return arr;
};

/**
 * Filters an array.
 * @param array    arr
 * @param function callback A user-defined callback function. If null, we'll just remove any elements that equate to false.
 * @return array
 */
b.filterArray = function(arr, callback)
{
	var filtered = [];

	for (var i = 0; i < arr.length; i++)
	{
		if (typeof callback == 'function')
			var include = callback(arr[i], i);
		else
			var include = arr[i];

		if (include)
			filtered.push(arr[i]);
	}

	return filtered;
};

/**
 * Returns whether an element is in an array (unline jQuery.inArray(), which returns the element's index, or -1).
 * @param mixed elem
 * @param mixed arr
 * @return bool
 */
b.inArray = function(elem, arr)
{
	return ($.inArray(elem, arr) != -1);
};

/**
 * Removes an element from an array.
 * @param mixed elem
 * @param array arr
 * @return bool Whether the element could be found or not.
 */
b.removeFromArray = function(elem, arr)
{
	var index = $.inArray(elem, arr);
	if (index != -1)
	{
		arr.splice(index, 1);
		return true;
	}
	else
		return false;
};

/**
 * Makes the first character of a string uppercase.
 * @param string str
 * @return string
 */
b.uppercaseFirst = function(str)
{
	return str.charAt(0).toUpperCase() + str.slice(1);
};

/**
 * Makes the first character of a string lowerscase.
 * @param string str
 * @return string
 */
b.lowercaseFirst = function(str)
{
	return str.charAt(0).toLowerCase() + str.slice(1);
};

/**
 * Converts extended ASCII characters to ASCII.
 * @param string str
 * @return string
 */
b.asciiString = function(str)
{
	var asciiStr = '';

	for (c = 0; c < str.length; c++) {
		charCode = str.charCodeAt(c);

		if (charCode >= 32 && charCode < 128)
			asciiStr += str.charAt(c);
		else if (typeof asciiCharMap[charCode] != 'undefined')
			asciiStr += asciiCharMap[charCode];
	}

	return asciiStr;
};

/**
 * Get the distance between two coordinates.
 * @param int x1 The first coordinate's position on the X axis.
 * @param int y1 The first coordinate's position on the Y axis.
 * @param int x2 The second coordinate's position on the X axis.
 * @param int y2 The second coordinate's position on the Y axis.
 * @return float
 */
b.getDist = function(x1, y1, x2, y2)
{
	return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
};

/**
 * Check if an element is touching an x/y coordinate.
 * @param int x0 The coordinate's position on the X axis.
 * @param int y0 The coordinate's position on the Y axis.
 * @param mixed elem Either an actual element or a jQuery collection.
 * @return bool
 */
b.hitTest = function(x0, y0, elem)
{
	var $elem = $(elem),
		offset = $elem.offset(),
		x1 = offset.left,
		y1 = offset.top,
		x2 = x1 + $elem.width(),
		y2 = y1 + $elem.height();

	return (x0 >= x1 && x0 < x2 && y0 >= y1 && y0 < y2);
};

/**
 * Check if the cursor is over an element.
 * @param object event The mouse event object containing pageX and pageY properties.
 * @param mixed  elem  Either an actual element or a jQuery collection.
 * @return bool
 */
b.isCursorOver = function(event, elem)
{
	return b.hitTest(event.pageX, event.pageY, elem);
};

/**
 * Prevents the outline when an element is focussed by the mouse.
 * @param mixed elem Either an actual element or a jQuery collection.
 */
b.preventOutlineOnMouseFocus = function(elem)
{
	var $elem = $(elem),
		namespace = '.preventOutlineOnMouseFocus';

	$elem.on('mousedown'+namespace, function() {
		$elem.addClass('no-outline');
		$elem.focus();
	})
	.on('keydown'+namespace+' blur'+namespace, function(event) {
		if (event.keyCode != b.SHIFT_KEY && event.keyCode != b.CTRL_KEY && event.keyCode != b.CMD_KEY)
			$elem.removeClass('no-outline');
	});
};

/**
 * Performs a case-insensative sort on an array of strings.
 * @param array arr
 * @return array
 */
b.caseInsensativeSort = function(arr)
{
	return arr.sort(this.caseInsensativeCompare)
};

/**
 * Performs a case-insensative string comparison.
 * Returns -1 if a is less than b, 1 if a is greater than b, or 0 if they are equal.
 * @param string a
 * @param string b
 * @return int
 */
b.caseInsensativeCompare = function(a, b)
{
	a = a.toLowerCase();
	b = b.toLowerCase();
	return a < b ? -1 : (a > b ? 1 : 0);
};

/**
 * Copies text styles from one element to another, including line-height, font-size, font-family, font-weight, and letter-spacing.
 * @param mixed from The source element. Can be either an actual element or a jQuery collection.
 * @param mixed to   The target element. Can be either an actual element or a jQuery collection.
 */
b.copyTextStyles = function(from, to)
{
	var $from = $(from),
		$to = $(to);

	$to.css({
		lineHeight:    $from.css('lineHeight'),
		fontSize:      $from.css('fontSize'),
		fontFamily:    $from.css('fontFamily'),
		fontWeight:    $from.css('fontWeight'),
		letterSpacing: $from.css('letterSpacing')
	});
};

/**
 * Returns the body's proper scrollTop, discarding any document banding in Safari.
 * @return int
 */
b.getBodyScrollTop = function()
{
	var scrollTop = document.body.scrollTop;

	if (scrollTop < 0)
		scrollTop = 0;
	else
	{
		var maxScrollTop = b.$body.outerHeight() - b.$window.height();
		if (scrollTop > maxScrollTop)
			scrollTop = maxScrollTop;
	}

	return scrollTop;
};

/**
 * Scrolls a container to an element within it.
 * @param mixed container Either an actual element or a jQuery collection.
 * @param mixed elem      Either an actual element or a jQuery collection.
 */
b.scrollContainerToElement = function(container, elem) {
	var $container = $(container),
		$elem = $(elem);

	if (! $container.length || ! $elem.length)
		return;

	var scrollTop = $container.scrollTop(),
		elemOffset = $elem.offset().top,
		containerOffset = $container.offset().top,
		offsetDiff = elemOffset - containerOffset;

	if (offsetDiff < 0) {
		$container.scrollTop(scrollTop + offsetDiff);
	}
	else {
		var elemHeight = $elem.outerHeight(),
			containerHeight = $container[0].clientHeight;

		if (offsetDiff + elemHeight > containerHeight) {
			$container.scrollTop(scrollTop + (offsetDiff - (containerHeight - elemHeight)));
		}
	}
};

/**
 * Returns the first element in an array or jQuery collection.
 * @param mixed elem
 * @return mixed
 */
b.getElement = function(elem)
{
	return $.makeArray(elem)[0];
};

/**
 * Creates a validation error list.
 * @param array errors
 * @return jQuery
 */
b.createErrorList = function(errors)
{
	var $ul = $(document.createElement('ul')).addClass('errors');

	for (var i = 0; i < errors.length; i++)
	{
		var $li = $(document.createElement('li'));
		$li.appendTo($ul);
		$li.html(errors[i]);
	}

	return $ul;
};

/**
 * Returns whether a variable is an array.
 * @param mixed val
 * @return bool
 */
b.isArray = function(val)
{
	return (val instanceof Array);
};

/**
 * Returns whether a variable is a jQuery collection.
 * @param mixed val
 * @return bool
 */
b.isJquery = function(val)
{
	return (val instanceof jQuery);
};

/**
 * Returns whether a variable is a plain object (not an array, element, or jQuery collection).
 * @param mixed val
 * @return bool
 */
b.isObject = function(val)
{
	return (typeof val == 'object' && !b.isArray(val) && !b.isJquery(val) && typeof val.nodeType == 'undefined');
};

/**
 * Animate an element's width.
 * @param mixed    elem     Either an acutal element or a jQuery collection.
 * @param function callback A callback function to call while the element is temporarily set to the target width before the animation begins.
 */
b.animateWidth = function(elem, callback)
{
	var $elem = $(elem),
		oldWidth = $elem.width();
	$elem.width('auto');

	callback();

	var newWidth = $elem.width();
	$elem.width(oldWidth);
	$elem.animate({width: newWidth}, 'fast', function() {
		$elem.width('auto');
	});
};

/**
 * Returns the inputs within a container
 * @param mixed container The container element. Can be either an actual element or a jQuery collection.
 * @return jQuery
 */
b.findInputs = function(container)
{
	return $(container).find('input,text,textarea,select,button');
};

/**
 * Returns an inputs's name, "namespaced" into a basename.
 * So if name="gin" and you pass the namespace "drinks", this will return "drinks[gin]".
 * More useful in the event that the name already has its own brackets, e.g. "gin[tonic]" => "drinks[gin][tonic]".
 * @param string inputName
 * @param string namespace
 * @return string
 */
b.namespaceInputName = function(inputName, namespace)
{
	return inputName.replace(/^([^\[\]]+)(.*)$/, namespace+'[$1]$2');
};

/**
 * Returns the beginning of an input's name= attribute value with any [bracktes] stripped out.
 * @param jQuery $input
 * @return string
 */
b.getInputBasename = function($input)
{
	return $input.attr('name').replace(/\[.*/, '');
};

/**
 * Returns an input's value as it would be POSTed.
 * So unchecked checkboxes and radio buttons return null,
 * and multi-selects whose name don't end in "[]" only return the last selection
 * @param jQuery $input
 * @return mixed
 */
b.getInputPostVal = function($input)
{
	var type = $input.attr('type'),
		val  = $input.val();

	// Is this an unchecked checkbox or radio button?
	if ((type == 'checkbox' || type == 'radio'))
	{
		if ($input.prop('checked'))
			return val;
		else
			return null;
	}

	// How bout a multi-select missing its "[]" at the end of its name?
	else if ($input.prop('nodeName') == 'SELECT' && $input.attr('multiple') && $input.attr('name').substr(-2) != '[]')
	{
		if (val.length)
			return val[val.length-1];
		else
			return null;
	}

	// Just return the value
	else
		return val;
};


/**
 * Base class
 */
b.Base = Base.extend({

	settings: null,

	_namespace: null,
	_$listeners: null,

	constructor: function()
	{
		this._namespace = '.blx'+Math.floor(Math.random()*999999999);
		this._$listeners = $();
		this.init.apply(this, arguments);
	},

	init: function(){},

	setSettings: function(settings, defaults)
	{
		var baseSettings = (typeof this.settings == 'undefined' ? {} : this.settings);
		this.settings = $.extend(baseSettings, defaults, settings);
	},

	_formatEvents: function(events)
	{
		events = b.stringToArray(events);
		for (var i = 0; i < events.length; i++)
		{
			events[i] += this._namespace;
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

		// Remember that we're listening to this element
		this._$listeners = this._$listeners.add(elem);
	},

	removeListener: function(elem, events)
	{
		events = this._formatEvents(events);
		$(elem).off(events);
	},

	removeAllListeners: function(elem)
	{
		$(elem).off(this._namespace);
	},

	destroy: function()
	{
		this.removeAllListeners(this._$listeners);
	}

});


/**
 * Blocks class
 */
var CP = b.Base.extend({

	$nav: null,
	$notification: null,
	fixedNav: false,

	init: function()
	{
		this.$nav = $('#nav');
		this.$notification = $('#notification');
		this.$notification.delay(2000).fadeOut();

		// Don't let the nav scroll out of view
		this.addListener(b.$window, 'scroll', function()
		{
			if (document.body.scrollTop > 50)
			{
				if (!this.fixedNav)
				{
					this.$nav.addClass('fixed');
					this.fixedNav = true;
				}
			}
			else if (this.fixedNav)
			{
				this.$nav.removeClass('fixed');
				this.fixedNav = false;
			}
		});
	},

});


b.cp = new CP();


$('.formsubmit').click(function() {
	var $btn = $(this),
		$form = $btn.closest('form');
	if ($btn.attr('data-action'))
		$('<input type="hidden" name="action" value="'+$btn.attr('data-action')+'"/>').appendTo($form);
	$form.submit();
});

$('.togglefields').change(function() {
	var $toggle = $(this),
		$target = $('#'+$toggle.attr('data-target'));

	if (b.getInputPostVal($toggle) == 'y')
	{
		$target.height('auto');
		var height = $target.height();
		$target.height(0);
		$target.stop().animate({height: height}, 'fast', $.proxy(function() {
			$target.height('auto');
		}, this));
	}
	else
		$target.stop().animate({height: 0}, 'fast');
});


})(jQuery);
