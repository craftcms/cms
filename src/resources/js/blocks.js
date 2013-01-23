if (typeof Blocks == 'undefined')
{
	Blocks = {};
}

Blocks = $.extend(Blocks, {

	navHeight: 48,

	/**
	 * Map of high-ASCII codes to their low-ASCII characters.
	 *
	 * @var object
	 */
	asciiCharMap: {
		'223':'ss', '224':'a',  '225':'a',  '226':'a',  '229':'a',  '227':'ae', '230':'ae', '228':'ae', '231':'c',  '232':'e',
		'233':'e',  '234':'e',  '235':'e',  '236':'i',  '237':'i',  '238':'i',  '239':'i',  '241':'n',  '242':'o',  '243':'o',
		'244':'o',  '245':'o',  '246':'oe', '249':'u',  '250':'u',  '251':'u',  '252':'ue', '255':'y',  '257':'aa', '269':'ch',
		'275':'ee', '291':'gj', '299':'ii', '311':'kj', '316':'lj', '326':'nj', '353':'sh', '363':'uu', '382':'zh', '256':'aa',
		'268':'ch', '274':'ee', '290':'gj', '298':'ii', '310':'kj', '315':'lj', '325':'nj', '352':'sh', '362':'uu', '381':'zh'
	},

	/**
	 * Get a translated message.
	 *
	 * @param string message
	 * @param object params
	 * @return string
	 */
	t: function(message, params)
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
	},

	/**
	 * Returns whether a package is included in this Blocks build.
	 *
	 * @param string $package
	 * @return bool
	 */
	hasPackage: function(pkg)
	{
		return ($.inArray(pkg, Blocks.packages) != -1);
	},

	/**
	 * Returns a URL.
	 *
	 * @param string path
	 * @param array|string|null params
	 * @return string
	 */
	getUrl: function(path, params)
	{
		// Return path if it appears to be an absolute URL.
		if (path.search('://') != -1)
		{
			return path;
		}

		path = Blocks.trim(path, '/');

		var anchor = '';

		// Normalize the params
		if (Garnish.isObject(params))
		{
			var aParams = [];

			for (var name in params)
			{
				var value = params[name];

				if (name == '#')
				{
					anchor = value;
				}
				else if (value !== null && value !== '')
				{
					aParams.push(name+'='+value);
				}
			}

			params = aParams;
		}

		if (Garnish.isArray(params))
		{
			params = params.join('&');
		}
		else
		{
			params = Blocks.ltrim(params, '&');
		}

		// Put it all together
		var url = Blocks.baseUrl;

		// Does the base URL already have a query string?
		var qsMarker = url.indexOf('?');
		if (qsMarker != '-1')
		{
			// Append params with the existing query string, and chop it off of the base URL
			var qs = url.substr(qsMarker+1);
			url = url.substr(0, qsMarker);

			if (qs)
			{
				params = qs + (params ? '&'+params : '');
			}
		}

		if (!Blocks.usePathInfo && path)
		{
			// Is the p= param already set?
			if (params && params.substr(0, 2) == 'p=')
			{
				var endPath = params.indexOf('&');
				if (endPath != -1)
				{
					var basePath = params.substring(2, endPath-1);
					params = params.substr(endPath+1);
				}
				else
				{
					var basePath = params.substr(2);
					params = null;
				}

				path = basePath + (path ? '/'+path : '');
			}

			// Now move the path into the params
			params = 'p='+path + (params ? '&'+params : '');
			path = null;
		}

		if (path)
		{
			url += '/'+path;
		}

		if (params)
		{
			url += '?'+params;
		}

		if (anchor)
		{
			url += '#'+anchor;
		}

		return url;
	},

	/**
	 * Returns a resource URL.
	 *
	 * @param string path
	 * @param array|string|null params
	 * @return string
	 */
	getResourceUrl: function(path, params)
	{
		path = Blocks.resourceTrigger+'/'+Blocks.trim(path, '/');
		return Blocks.getUrl(path, params);
	},

	/**
	 * Returns an action URL.
	 *
	 * @param string path
	 * @param array|string|null params
	 * @return string
	 */
	getActionUrl: function(path, params)
	{
		path = Blocks.actionTrigger+'/'+Blocks.trim(path, '/');
		return Blocks.getUrl(path, params);
	},

	/**
	 * Posts an action request to the server.
	 *
	 * @param string action
	 * @param object|null data
	 * @param function|null onSuccess
	 * @param funciton|null onError
	 */
	postActionRequest: function(action, data, onSuccess, onError)
	{
		var url = Blocks.getActionUrl(action);

		// Param mapping
		if (typeof data == 'function')
		{
			// (action, onSuccess, onError)
			onSuccess = data;
			onError = onSuccess;
			data = {};
		}

		return $.ajax(url, {
			type: 'POST',
			data: data,
			success: onSuccess,
			error: onError
		});
	},

	/**
	 * Converts a comma-delimited string into an array.
	 *
	 * @param string str
	 * @return array
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
	 * Takes an array or string of chars, and places a backslash before each one, returning the combined string.
	 *
	 * Userd by ltrim() and rtrim()
	 *
	 * @param string|array chars
	 * @return string
	 */
	escapeChars: function(chars)
	{
		if (!Garnish.isArray(chars))
		{
			chars = chars.split();
		}

		var escaped = '';

		for (var i = 0; i < chars.length; i++)
		{
			escaped += "\\"+chars[i];
		}

		return escaped;
	},

	/**
	 * Trim characters off of the beginning of a string.
	 *
	 * @param string str
	 * @param string|array|null The characters to trim off. Defaults to a space if left blank.
	 * @return string
	 */
	ltrim: function(str, chars)
	{
		if (!str) return str;
		if (chars === undefined) chars = ' ';
		var re = new RegExp('^['+Blocks.escapeChars(chars)+']+');
		return str.replace(re, '');
	},

	/**
	 * Trim characters off of the end of a string.
	 *
	 * @param string str
	 * @param string|array|null The characters to trim off. Defaults to a space if left blank.
	 * @return string
	 */
	rtrim: function(str, chars)
	{
		if (!str) return str;
		if (chars === undefined) chars = ' ';
		var re = new RegExp('['+Blocks.escapeChars(chars)+']+$');
		return str.replace(re, '');
	},

	/**
	 * Trim characters off of the beginning and end of a string.
	 *
	 * @param string str
	 * @param string|array|null The characters to trim off. Defaults to a space if left blank.
	 * @return string
	 */
	trim: function(str, chars)
	{
		str = Blocks.ltrim(str, chars);
		str = Blocks.rtrim(str, chars);
		return str;
	},

	/**
	 * Filters an array.
	 *
	 * @param array    arr
	 * @param function callback A user-defined callback function. If null, we'll just remove any elements that equate to false.
	 * @return array
	 */
	filterArray: function(arr, callback)
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
	},

	/**
	 * Returns whether an element is in an array (unlike jQuery.inArray(), which returns the element's index, or -1).
	 *
	 * @param mixed elem
	 * @param mixed arr
	 * @return bool
	 */
	inArray: function(elem, arr)
	{
		return ($.inArray(elem, arr) != -1);
	},

	/**
	 * Removes an element from an array.
	 *
	 * @param mixed elem
	 * @param array arr
	 * @return bool Whether the element could be found or not.
	 */
	removeFromArray: function(elem, arr)
	{
		var index = $.inArray(elem, arr);
		if (index != -1)
		{
			arr.splice(index, 1);
			return true;
		}
		else
		{
			return false;
		}
	},

	/**
	 * Returns the last element in an array.
	 *
	 * @param array
	 * @return mixed
	 */
	getLast: function(arr)
	{
		if (!arr.length)
			return null;
		else
			return arr[arr.length-1];
	},

	/**
	 * Makes the first character of a string uppercase.
	 *
	 * @param string str
	 * @return string
	 */
	uppercaseFirst: function(str)
	{
		return str.charAt(0).toUpperCase() + str.slice(1);
	},

	/**
	 * Makes the first character of a string lowercase.
	 *
	 * @param string str
	 * @return string
	 */
	lowercaseFirst: function(str)
	{
		return str.charAt(0).toLowerCase() + str.slice(1);
	},

	/**
	 * Converts extended ASCII characters to ASCII.
	 *
	 * @param string str
	 * @return string
	 */
	asciiString: function(str)
	{
		var asciiStr = '';

		for (var c = 0; c < str.length; c++)
		{
			var ascii = str.charCodeAt(c);

			if (ascii >= 32 && ascii < 128)
			{
				asciiStr += str.charAt(c);
			}
			else if (typeof Blocks.asciiCharMap[ascii] != 'undefined')
			{
				asciiStr += Blocks.asciiCharMap[ascii];
			}
		}

		return asciiStr;
	},

	/**
	 * Prevents the outline when an element is focused by the mouse.
	 *
	 * @param mixed elem Either an actual element or a jQuery collection.
	 */
	preventOutlineOnMouseFocus: function(elem)
	{
		var $elem = $(elem),
			namespace = '.preventOutlineOnMouseFocus';

		$elem.on('mousedown'+namespace, function() {
			$elem.addClass('no-outline');
			$elem.focus();
		})
		.on('keydown'+namespace+' blur'+namespace, function(event) {
			if (event.keyCode != Garnish.SHIFT_KEY && event.keyCode != Garnish.CTRL_KEY && event.keyCode != Garnish.CMD_KEY)
				$elem.removeClass('no-outline');
		});
	},

	/**
	 * Creates a validation error list.
	 *
	 * @param array errors
	 * @return jQuery
	 */
	createErrorList: function(errors)
	{
		var $ul = $(document.createElement('ul')).addClass('errors');

		for (var i = 0; i < errors.length; i++)
		{
			var $li = $(document.createElement('li'));
			$li.appendTo($ul);
			$li.html(errors[i]);
		}

		return $ul;
	}
});


// -------------------------------------------
//  Custom jQuery plugins
// -------------------------------------------

$.extend($.fn, {

	/**
	 * Disables elements by adding a .disabled class and preventing them from receiving focus.
	 */
	disable: function()
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
	},

	/**
	 * Enables elements by removing their .disabled class and allowing them to receive focus.
	 */
	enable: function()
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
	},

	/**
	 * Sets the element as a container for a checkbox select.
	 */
	checkboxselect: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'checkboxselect'))
			{
				new Garnish.CheckboxSelect(this);
			}
		});
	},

	/**
	 * Sets the element as a field toggle trigger.
	 */
	fieldtoggle: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'fieldtoggle'))
			{
				new Blocks.FieldToggle(this);
			}
		});
	},

	lightswitch: function(settings, settingName, settingValue)
	{
		// param mapping
		if (settings == 'settings')
		{
			if (typeof settingName == 'string')
			{
				settings = {};
				settings[settingName] = settingValue;
			}
			else
			{
				settings = settingName;
			}

			return this.each(function()
			{
				var obj = $.data(this, 'lightswitch');
				if (obj)
				{
					obj.setSettings(settings);
				}
			});
		}

		return this.each(function()
		{
			if (!$.data(this, 'lightswitch'))
			{
				new Blocks.LightSwitch(this, settings);
			}
		});
	},

	nicetext: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'text'))
			{
				new Garnish.NiceText(this, {hint: this.getAttribute('data-hint')});
			}
		});
	},

	passwordinput: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'passwordinput'))
			{
				new Garnish.PasswordInput(this);
			}
		});
	},

	pill: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'pill'))
			{
				new Garnish.Pill(this);
			}
		});
	},

	menubtn: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'menubtn'))
			{
				new Garnish.MenuBtn(this);
			}
		});
	}
});


Garnish.$doc.ready(function()
{
	$('.checkbox-select').checkboxselect();
	$('.fieldtoggle').fieldtoggle();
	$('.lightswitch').lightswitch();
	$('.nicetext').nicetext();
	$('input.password').passwordinput();
	$('.pill').pill();
	$('.menubtn').menubtn();
});
