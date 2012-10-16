(function($) {


if (typeof Blocks == 'undefined') Blocks = {};
if (typeof Blocks.ui == 'undefined') Blocks.ui = {};

// jQuery objects for common elements
Blocks.$window = $(window);
Blocks.$document = $(document);
Blocks.$body = $(document.body);

// Key code constants
Blocks.DELETE_KEY = 8;
Blocks.SHIFT_KEY  = 16;
Blocks.CTRL_KEY   = 17;
Blocks.ALT_KEY    = 18;
Blocks.RETURN_KEY = 13;
Blocks.ESC_KEY    = 27;
Blocks.SPACE_KEY  = 32;
Blocks.LEFT_KEY   = 37;
Blocks.UP_KEY     = 38;
Blocks.RIGHT_KEY  = 39;
Blocks.DOWN_KEY   = 40;
Blocks.CMD_KEY    = 91;

Blocks.navHeight = 48;

Blocks.fx = {
	duration: 400,
	delay: 100
};

/**
 * Log
 */
Blocks.log = function(msg)
{
	if (typeof console != 'undefined' && typeof console.log == 'function')
		console.log(msg);
};

var asciiCharMap = {
	'223':'ss', '224':'a',  '225':'a',  '226':'a',  '229':'a',  '227':'ae', '230':'ae', '228':'ae', '231':'c',  '232':'e',
	'233':'e',  '234':'e',  '235':'e',  '236':'i',  '237':'i',  '238':'i',  '239':'i',  '241':'n',  '242':'o',  '243':'o',
	'244':'o',  '245':'o',  '246':'oe', '249':'u',  '250':'u',  '251':'u',  '252':'ue', '255':'y',  '257':'aa', '269':'ch',
	'275':'ee', '291':'gj', '299':'ii', '311':'kj', '316':'lj', '326':'nj', '353':'sh', '363':'uu', '382':'zh', '256':'aa',
	'268':'ch', '274':'ee', '290':'gj', '298':'ii', '310':'kj', '315':'lj', '325':'nj', '352':'sh', '362':'uu', '381':'zh'
};


/**
 * Get a translated message.
 *
 * @param string message
 * @param object params
 * @return string
 */
Blocks.t = function(message, params)
{
	if (typeof Blocks.translations[message] != 'undefined')
		message = Blocks.translations[message];

	if (params)
	{
		for (var key in params)
		{
			message = message.replace('{'+key+'}', params[key])
		}
	}

	return message;
};

/**
 * Returns whether a package is included in this Blocks build.
 *
 * @param string $package
 * @return bool
 */
Blocks.hasPackage = function(pkg)
{
	return ($.inArray(pkg, Blocks.packages) != -1);
};

/**
 * Format a number with commas.
 *
 * @param mixed num
 * @return string
 */
Blocks.numCommas = function(num)
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
 *
 * @param string str
 * @return array
 */
Blocks.stringToArray = function(str)
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
 *
 * @param array    arr
 * @param function callback A user-defined callback function. If null, we'll just remove any elements that equate to false.
 * @return array
 */
Blocks.filterArray = function(arr, callback)
{
	var filtered = [];

	for (var i = 0; i < arr.length; i++)
	{
		if (typeof callback == 'function')
		{
			var include = callback(arr[i], i);
		}
		else
		{
			var include = arr[i];
		}

		if (include)
		{
			filtered.push(arr[i]);
		}
	}

	return filtered;
};

/**
 * Returns whether an element is in an array (unlike jQuery.inArray(), which returns the element's index, or -1).
 *
 * @param mixed elem
 * @param mixed arr
 * @return bool
 */
Blocks.inArray = function(elem, arr)
{
	return ($.inArray(elem, arr) != -1);
};

/**
 * Removes an element from an array.
 *
 * @param mixed elem
 * @param array arr
 * @return bool Whether the element could be found or not.
 */
Blocks.removeFromArray = function(elem, arr)
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
 * Returns the last element in an array.
 *
 * @param array
 * @return mixed
 */
Blocks.getLast = function(arr)
{
	if (!arr.length)
		return null;
	else
		return arr[arr.length-1];
};

/**
 * Makes the first character of a string uppercase.
 *
 * @param string str
 * @return string
 */
Blocks.uppercaseFirst = function(str)
{
	return str.charAt(0).toUpperCase() + str.slice(1);
};

/**
 * Makes the first character of a string lowercase.
 *
 * @param string str
 * @return string
 */
Blocks.lowercaseFirst = function(str)
{
	return str.charAt(0).toLowerCase() + str.slice(1);
};

/**
 * Converts extended ASCII characters to ASCII.
 *
 * @param string str
 * @return string
 */
Blocks.asciiString = function(str)
{
	var asciiStr = '';

	for (var c = 0; c < str.length; c++)
	{
		var ascii = str.charCodeAt(c);

		if (ascii >= 32 && ascii < 128)
		{
			asciiStr += str.charAt(c);
		}
		else if (typeof asciiCharMap[ascii] != 'undefined')
		{
			asciiStr += asciiCharMap[ascii];
		}
	}

	return asciiStr;
};

/**
 * Get the distance between two coordinates.
 *
 * @param int x1 The first coordinate's position on the X axis.
 * @param int y1 The first coordinate's position on the Y axis.
 * @param int x2 The second coordinate's position on the X axis.
 * @param int y2 The second coordinate's position on the Y axis.
 * @return float
 */
Blocks.getDist = function(x1, y1, x2, y2)
{
	return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
};

/**
 * Check if an element is touching an x/y coordinate.
 *
 * @param int x0 The coordinate's position on the X axis.
 * @param int y0 The coordinate's position on the Y axis.
 * @param mixed elem Either an actual element or a jQuery collection.
 * @return bool
 */
Blocks.hitTest = function(x0, y0, elem)
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
 *
 * @param object event The mouse event object containing pageX and pageY properties.
 * @param mixed  elem  Either an actual element or a jQuery collection.
 * @return bool
 */
Blocks.isCursorOver = function(event, elem)
{
	return Blocks.hitTest(event.pageX, event.pageY, elem);
};

/**
 * Prevents the outline when an element is focused by the mouse.
 *
 * @param mixed elem Either an actual element or a jQuery collection.
 */
Blocks.preventOutlineOnMouseFocus = function(elem)
{
	var $elem = $(elem),
		namespace = '.preventOutlineOnMouseFocus';

	$elem.on('mousedown'+namespace, function() {
		$elem.addClass('no-outline');
		$elem.focus();
	})
	.on('keydown'+namespace+' blur'+namespace, function(event) {
		if (event.keyCode != Blocks.SHIFT_KEY && event.keyCode != Blocks.CTRL_KEY && event.keyCode != Blocks.CMD_KEY)
			$elem.removeClass('no-outline');
	});
};

/**
 * Performs a case-insensitive sort on an array of strings.
 *
 * @param array arr
 * @return array
 */
Blocks.caseInsensitiveSort = function(arr)
{
	return arr.sort(this.caseInsensitiveCompare)
};

/**
 * Performs a case-insensitive string comparison.
 * Returns -1 if a is less than b, 1 if a is greater than b, or 0 if they are equal.
 *
 * @param string a
 * @param string b
 * @return int
 */
Blocks.caseInsensitiveCompare = function(a, b)
{
	a = a.toLowerCase();
	b = b.toLowerCase();
	return a < b ? -1 : (a > b ? 1 : 0);
};

/**
 * Copies text styles from one element to another, including line-height, font-size, font-family, font-weight, and letter-spacing.
 *
 * @param mixed from The source element. Can be either an actual element or a jQuery collection.
 * @param mixed to   The target element. Can be either an actual element or a jQuery collection.
 */
Blocks.copyTextStyles = function(from, to)
{
	var $from = $(from),
		$to = $(to);

	$to.css({
		lineHeight:    $from.css('lineHeight'),
		fontSize:      $from.css('fontSize'),
		fontFamily:    $from.css('fontFamily'),
		fontWeight:    $from.css('fontWeight'),
		letterSpacing: $from.css('letterSpacing'),
		textAlign:     $from.css('textAlign')
	});
};

/**
 * Returns the body's proper scrollTop, discarding any document banding in Safari.
 *
 * @return int
 */
Blocks.getBodyScrollTop = function()
{
	var scrollTop = document.body.scrollTop;

	if (scrollTop < 0)
		scrollTop = 0;
	else
	{
		var maxScrollTop = Blocks.$body.outerHeight() - Blocks.$window.height();
		if (scrollTop > maxScrollTop)
			scrollTop = maxScrollTop;
	}

	return scrollTop;
};

/**
 * Scrolls a container to an element within it.
 *
 * @param mixed container Either an actual element or a jQuery collection.
 * @param mixed elem      Either an actual element or a jQuery collection.
 */
Blocks.scrollContainerToElement = function(container, elem) {
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
 *
 * @param mixed elem
 * @return mixed
 */
Blocks.getElement = function(elem)
{
	return $.makeArray(elem)[0];
};

/**
 * Creates a validation error list.
 *
 * @param array errors
 * @return jQuery
 */
Blocks.createErrorList = function(errors)
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
 * Returns whether something is a text node.
 *
 * @param mixed elem
 * @return bool
 */
Blocks.isTextNode = function(elem)
{
	return (elem.nodeType == 3);
};

/**
 * Returns whether a variable is an array.
 *
 * @param mixed val
 * @return bool
 */
Blocks.isArray = function(val)
{
	return (val instanceof Array);
};

/**
 * Returns whether a variable is a jQuery collection.
 *
 * @param mixed val
 * @return bool
 */
Blocks.isJquery = function(val)
{
	return (val instanceof jQuery);
};

/**
 * Returns whether a variable is a plain object (not an array, element, or jQuery collection).
 *
 * @param mixed val
 * @return bool
 */
Blocks.isObject = function(val)
{
	return (typeof val == 'object' && !Blocks.isArray(val) && !Blocks.isJquery(val) && typeof val.nodeType == 'undefined');
};

/**
 * Animate an element's width.
 *
 * @param mixed    elem     Either an actual element or a jQuery collection.
 * @param function callback A callback function to call while the element is temporarily set to the target width before the animation begins.
 */
Blocks.animateWidth = function(elem, callback)
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
 * Shakes an element.
 *
 * @param mixed elem Either an actual element or a jQuery collection.
 */
Blocks.shake = function(elem, property)
{
	var $elem = $(elem);

	if (!property)
		property = 'margin-left';

	var startingPoint = parseInt($elem.css(property));
	if (isNaN(startingPoint))
		startingPoint = 0;

	for (var i = 10; i > 0; i--)
	{
		var value = startingPoint + (i % 2 ? -1 : 1) * i;
		$elem.animate({property: value}, {
			duration: 50,
			queue: true
		});
	}
};

/**
 * Returns the inputs within a container
 *
 * @param mixed container The container element. Can be either an actual element or a jQuery collection.
 * @return jQuery
 */
Blocks.findInputs = function(container)
{
	return $(container).find('input,text,textarea,select,button');
};

/**
 * Returns the post data within a container.
 *
 * @param mixed container
 * @return array
 */
Blocks.getPostData = function(container)
{
	var postData = {},
		arrayInputCounters = {},
		$inputs = Blocks.findInputs(container);

	for (var i = 0; i < $inputs.length; i++)
	{
		var $input = $($inputs[i]);

		var inputName = $input.attr('name');
		if (!inputName) continue;

		var inputVal = Blocks.getInputPostVal($input);
		if (inputVal === null) continue;

		var isArrayInput = (inputName.substr(-2) == '[]');

		if (isArrayInput)
		{
			// Get the cropped input name
			var croppedName = inputName.substring(0, inputName.length-2);

			// Prep the input counter
			if (typeof arrayInputCounters[croppedName] == 'undefined')
			{
				arrayInputCounters[croppedName] = 0;
			}
		}

		if (!Blocks.isArray(inputVal))
		{
			inputVal = [inputVal];
		}

		for (var j = 0; j < inputVal.length; j++)
		{
			if (isArrayInput)
			{
				var inputName = croppedName+'['+arrayInputCounters[croppedName]+']';
				arrayInputCounters[croppedName]++;
			}

			postData[inputName] = inputVal[j];
		}
	}

	return postData;
};

/**
 * Returns an inputs's name, "namespaced" into a basename.
 * So if name="gin" and you pass the namespace "drinks", this will return "drinks[gin]".
 * More useful in the event that the name already has its own brackets, e.g. "gin[tonic]" => "drinks[gin][tonic]".
 *
 * @param string inputName
 * @param string namespace
 * @return string
 */
Blocks.namespaceInputName = function(inputName, namespace)
{
	return inputName.replace(/^([^\[\]]+)(.*)$/, namespace+'[$1]$2');
};

/**
 * Returns the beginning of an input's name= attribute value with any [bracktes] stripped out.
 *
 * @param jQuery $input
 * @return string
 */
Blocks.getInputBasename = function($input)
{
	return $input.attr('name').replace(/\[.*/, '');
};

/**
 * Returns an input's value as it would be POSTed.
 * So unchecked checkboxes and radio buttons return null,
 * and multi-selects whose name don't end in "[]" only return the last selection
 *
 * @param jQuery $input
 * @return mixed
 */
Blocks.getInputPostVal = function($input)
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

	// Flatten any array values whose input name doesn't end in "[]"
	//  - e.g. a multi-select
	else if (Blocks.isArray(val) && $input.attr('name').substr(-2) != '[]')
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
 * Disable jQuery plugin
 */
$.fn.enable = function()
{
	return this.each(function()
	{
		var $elem = $(this);
		$elem.removeClass('disabled');

		if ($elem.data('activatable'))
		{
			$elem.attr('tabindex', '0');
		}
	});
};


/**
 * Enable jQuery plugin
 */
$.fn.disable = function()
{
	return this.each(function()
	{
		var $elem = $(this);
		$elem.addClass('disabled');

		if ($elem.data('activatable'))
		{
			$elem.removeAttr('tabindex');
		}
	});
};



/**
 * Base class
 */
Blocks.Base = Base.extend({

	settings: null,

	_namespace: null,
	_$listeners: null,

	constructor: function()
	{
		this._namespace = '.Blocks'+Math.floor(Math.random()*999999999);
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
		events = Blocks.stringToArray(events);
		for (var i = 0; i < events.length; i++)
		{
			events[i] += this._namespace;
		}
		return events.join(' ');
	},

	addListener: function(elem, events, func)
	{
		var $elem = $(elem);
		events = this._formatEvents(events);

		if (typeof func == 'function')
		{
			func = $.proxy(func, this);
		}
		else
		{
			func = $.proxy(this, func);
		}

		$elem.on(events, func);

		// Remember that we're listening to this element
		this._$listeners = this._$listeners.add(elem);

		// Prep for activate event?
		if (events.search(/\bactivate\b/) != -1)
		{
			if (!$elem.data('activatable'))
			{
				var activateNamespace = this._namespace+'-activate';

				// Prevent buttons from getting focus on click
				$elem.on('mousedown'+activateNamespace, function(event) {
					event.preventDefault();
				});

				$elem.on('click'+activateNamespace, function(event) {
					event.preventDefault();

					if (!$elem.hasClass('disabled'))
					{
						$elem.trigger('activate');
					}
				});

				$elem.on('keydown'+activateNamespace, function(event) {
					if (event.target == $elem[0] && event.keyCode == Blocks.SPACE_KEY)
					{
						event.preventDefault();

						if (!$elem.hasClass('disabled'))
						{
							$elem.addClass('active');

							Blocks.$document.on('keyup'+activateNamespace, function(event) {
								$elem.removeClass('active');
								if (event.target == $elem[0] && event.keyCode == Blocks.SPACE_KEY)
								{
									event.preventDefault();
									$elem.trigger('activate');
								}
								Blocks.$document.off('keyup'+activateNamespace);
							});
						}
					}
				});

				if (!$elem.hasClass('disabled'))
				{
					$elem.attr('tabindex', '0');
				}
				else
				{
					$elem.removeAttr('tabindex');
				}

				$elem.data('activatable', true);
			}

		}
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


})(jQuery);
