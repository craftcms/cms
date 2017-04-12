/*! Craft  - 2017-04-12 */
(function($){

// Set all the standard Craft.* stuff
$.extend(Craft,
{
	navHeight: 48,

	/**
	 * Map of high-ASCII codes to their low-ASCII characters.
	 *
	 * @var object
	 */
	asciiCharMap: {
		'216':'O',  '223':'ss', '224':'a',  '225':'a',  '226':'a',  '229':'a',  '227':'ae', '230':'ae', '228':'ae', '231':'c',
		'232':'e',  '233':'e',  '234':'e',  '235':'e',  '236':'i',  '237':'i',  '238':'i',  '239':'i',  '241':'n',  '242':'o',
		'243':'o',  '244':'o',  '245':'o',  '246':'oe', '248':'o',  '249':'u',  '250':'u',  '251':'u',  '252':'ue', '255':'y',
		'257':'aa', '269':'ch', '275':'ee', '291':'gj', '299':'ii', '311':'kj', '316':'lj', '326':'nj', '353':'sh', '363':'uu',
		'382':'zh', '256':'aa', '268':'ch', '274':'ee', '290':'gj', '298':'ii', '310':'kj', '315':'lj', '325':'nj', '337':'o',
		'352':'sh', '362':'uu', '369':'u',  '381':'zh', '260':'A',  '261':'a',  '262':'C',  '263':'c',  '280':'E',  '281':'e',
		'321':'L',  '322':'l',  '323':'N',  '324':'n',  '211':'O',  '346':'S',  '347':'s',  '377':'Z',  '378':'z',  '379':'Z',
		'380':'z',  '388':'z',
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
		if (typeof Craft.translations[message] != 'undefined')
			message = Craft.translations[message];

		if (params)
		{
			for (var key in params)
			{
				message = message.replace('{'+key+'}', params[key]);
			}
		}

		return message;
	},

	formatDate: function(date)
	{
		if (typeof date != 'object')
		{
			date = new Date(date);
		}

		return $.datepicker.formatDate(Craft.datepickerOptions.dateFormat, date);
	},

	/**
	 * Escapes some HTML.
	 *
	 * @param string str
	 * @return string
	 */
	escapeHtml: function(str)
	{
		return $('<div/>').text(str).html();
	},

	/**
	 * Returns the text in a string that might contain HTML tags.
	 *
	 * @param string str
	 * @return string
	 */
	getText: function(str)
	{
		return $('<div/>').html(str).text();
	},

	/**
	 * Encodes a URI copmonent. Mirrors PHP's rawurlencode().
	 *
	 * @param string str
	 * @return string
	 * @see http://stackoverflow.com/questions/1734250/what-is-the-equivalent-of-javascripts-encodeuricomponent-in-php
	 */
	encodeUriComponent: function(str)
	{
		str = encodeURIComponent(str);

		var differences = {
			'!': '%21',
			'*': '%2A',
			"'": '%27',
			'(': '%28',
			')': '%29'
		};

		for (var chr in differences)
		{
			var re = new RegExp('\\'+chr, 'g');
			str = str.replace(re, differences[chr]);
		}

		return str;
	},

	/**
	 * Formats an ID out of an input name.
	 *
	 * @param string inputName
	 * @return string
	 */
	formatInputId: function(inputName)
	{
		return this.rtrim(inputName.replace(/[\[\]]+/g, '-'), '-');
	},

	/**
	 * @return string
	 * @param path
	 * @param params
	 */
	getUrl: function(path, params, baseUrl)
	{
		if (typeof path != 'string')
		{
			path = '';
		}

		// Return path if it appears to be an absolute URL.
		if (path.search('://') != -1 || path.substr(0, 2) == '//')
		{
			return path;
		}

		path = Craft.trim(path, '/');

		var anchor = '';

		// Normalize the params
		if ($.isPlainObject(params))
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
			params = Craft.trim(params, '&?');
		}

		// Were there already any query string params in the path?
		var qpos = path.indexOf('?');
		if (qpos != -1)
		{
			params = path.substr(qpos+1)+(params ? '&'+params : '');
			path = path.substr(0, qpos);
		}

		// Put it all together
		var url;

		if (baseUrl)
		{
			url = baseUrl;

			if (path)
			{
				// Does baseUrl already contain a path?
				var pathMatch = url.match(/[&\?]p=[^&]+/);
				if (pathMatch)
				{
					url = url.replace(pathMatch[0], pathMatch[0]+'/'+path);
					path = '';
				}
			}
		}
		else
		{
			url = Craft.baseUrl;
		}

		// Does the base URL already have a query string?
		var qpos = url.indexOf('?');
		if (qpos != '-1')
		{
			params = url.substr(qpos+1)+(params ? '&'+params : '');
			url = url.substr(0, qpos);
		}

		if (!Craft.omitScriptNameInUrls && path)
		{
			if (Craft.usePathInfo)
			{
				// Make sure that the script name is in the URL
				if (url.search(Craft.scriptName) == -1)
				{
					url = Craft.rtrim(url, '/') + '/' + Craft.scriptName;
				}
			}
			else
			{
				// Move the path into the query string params

				// Is the p= param already set?
				if (params && params.substr(0, 2) == 'p=')
				{
					var endPath = params.indexOf('&'),
						basePath;

					if (endPath != -1)
					{
						basePath = params.substring(2, endPath);
						params = params.substr(endPath+1);
					}
					else
					{
						basePath = params.substr(2);
						params = null;
					}

					// Just in case
					basePath = Craft.rtrim(basePath);

					path = basePath + (path ? '/'+path : '');
				}

				// Now move the path into the params
				params = 'p='+path + (params ? '&'+params : '');
				path = null;
			}
		}

		if (path)
		{
			url = Craft.rtrim(url, '/') + '/' + path;
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
	 * @return string
	 * @param path
	 * @param params
	 */
	getCpUrl: function(path, params)
	{
		return this.getUrl(path, params, Craft.baseCpUrl);
	},

	/**
	 * @return string
	 * @param path
	 * @param params
	 */
	getSiteUrl: function(path, params)
	{
		return this.getUrl(path, params, Craft.baseSiteUrl);
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
		return Craft.getUrl(path, params, Craft.resourceUrl);
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
		return Craft.getUrl(path, params, Craft.actionUrl);
	},

	/**
	 * Redirects the window to a given URL.
	 *
	 * @param string url
	 */
	redirectTo: function(url)
	{
		document.location.href = this.getUrl(url);
	},

	/**
	 * Returns a hidden CSRF token input, if CSRF protection is enabled.
	 *
	 * @return string
	 */
	getCsrfInput: function()
	{
		if (Craft.csrfTokenName)
		{
			return '<input type="hidden" name="'+Craft.csrfTokenName+'" value="'+Craft.csrfTokenValue+'"/>';
		}
		else
		{
			return '';
		}
	},

	/**
	 * Posts an action request to the server.
	 *
	 * @param string action
	 * @param object|null data
	 * @param function|null callback
	 * @param object|null options
	 * @return jqXHR
	 */
	postActionRequest: function(action, data, callback, options)
	{
		// Make 'data' optional
		if (typeof data == 'function')
		{
			options = callback;
			callback = data;
			data = {};
		}

		if (Craft.csrfTokenValue && Craft.csrfTokenName)
		{
			if (typeof data == 'string')
			{
				if (data) {
					data += '&';
				}
				data += Craft.csrfTokenName + '=' + Craft.csrfTokenValue;
			}
			else
			{
				if (data === null || typeof data !== 'object')
				{
					data = {};
				}
				else
				{
					// Don't modify the passed-in object
					data = $.extend({}, data);
				}

				data[Craft.csrfTokenName] = Craft.csrfTokenValue;
			}
		}

		var jqXHR = $.ajax($.extend({
			url:      Craft.getActionUrl(action),
			type:     'POST',
			data:     data,
			success:  callback,
			error:    function(jqXHR, textStatus, errorThrown)
			{
				if (callback)
				{
					callback(null, textStatus, jqXHR);
				}
			},
			complete: function(jqXHR, textStatus)
			{
				if (textStatus != 'success')
				{
					if (typeof Craft.cp != 'undefined')
					{
						Craft.cp.displayError();
					}
					else
					{
						alert(Craft.t('An unknown error occurred.'));
					}
				}
			}
		}, options));

		// Call the 'send' callback
		if (options && typeof options.send == 'function')
		{
			options.send(jqXHR);
		}

		return jqXHR;
	},

	_waitingOnAjax: false,
	_ajaxQueue: [],

	/**
	 * Queues up an action request to be posted to the server.
	 */
	queueActionRequest: function(action, data, callback, options)
	{
		// Make 'data' optional
		if (typeof data == 'function')
		{
			options = callback;
			callback = data;
			data = undefined;
		}

		Craft._ajaxQueue.push([action, data, callback, options]);

		if (!Craft._waitingOnAjax)
		{
			Craft._postNextActionRequestInQueue();
		}
	},

	_postNextActionRequestInQueue: function()
	{
		Craft._waitingOnAjax = true;

		var args = Craft._ajaxQueue.shift();

		Craft.postActionRequest(args[0], args[1], function(data, textStatus, jqXHR)
		{
			if (args[2] && typeof args[2] == 'function')
			{
				args[2](data, textStatus, jqXHR);
			}

			if (Craft._ajaxQueue.length)
			{
				Craft._postNextActionRequestInQueue();
			}
			else
			{
				Craft._waitingOnAjax = false;
			}
		}, args[3]);
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
	 * Expands an array of POST array-style strings into an actual array.
	 *
	 * @param array arr
	 * @return array
	 */
	expandPostArray: function(arr)
	{
		var expanded = {};

		for (var key in arr)
		{
			var keys;

			var value = arr[key],
				m = key.match(/^(\w+)(\[.*)?/);

			if (m[2])
			{
				// Get all of the nested keys
				keys = m[2].match(/\[[^\[\]]*\]/g);

				// Chop off the brackets
				for (var i = 0; i < keys.length; i++)
				{
					keys[i] = keys[i].substring(1, keys[i].length-1);
				}
			}
			else
			{
				keys = [];
			}

			keys.unshift(m[1]);

			var parentElem = expanded;

			for (var i = 0; i < keys.length; i++)
			{
				if (i < keys.length-1)
				{
					if (typeof parentElem[keys[i]] != 'object')
					{
						// Figure out what this will be by looking at the next key
						if (!keys[i+1] || parseInt(keys[i+1]) == keys[i+1])
						{
							parentElem[keys[i]] = [];
						}
						else
						{
							parentElem[keys[i]] = {};
						}
					}

					parentElem = parentElem[keys[i]];
				}
				else
				{
					// Last one. Set the value
					if (!keys[i])
					{
						keys[i] = parentElem.length;
					}

					parentElem[keys[i]] = value;
				}
			}
		}

		return expanded;
	},

	/**
	 * Compares two variables and returns whether they are equal in value.
	 * Recursively compares array and object values.
	 *
	 * @param mixed obj1
	 * @param mixed obj2
	 * @param bool preserveObjectKeys Whether object keys should be sorted before being compared. Default is true.
	 * @return bool
	 */
	compare: function(obj1, obj2, sortObjectKeys)
	{
		// Compare the types
		if (typeof obj1 != typeof obj2)
		{
			return false;
		}

		if (typeof obj1 == 'object')
		{
			// Compare the lengths
			if (obj1.length != obj2.length)
			{
				return false;
			}

			// Is one of them an array but the other is not?
			if ((obj1 instanceof Array) != (obj2 instanceof Array))
			{
				return false;
			}

			// If they're actual objects (not arrays), compare the keys
			if (!(obj1 instanceof Array))
			{
				if (typeof sortObjectKeys === typeof undefined || sortObjectKeys == true)
				{
					if (!Craft.compare(Craft.getObjectKeys(obj1).sort(), Craft.getObjectKeys(obj2).sort()))
					{
						return false;
					}
				}
				else
				{
					if (!Craft.compare(Craft.getObjectKeys(obj1), Craft.getObjectKeys(obj2)))
					{
						return false;
					}
				}
			}

			// Compare each value
			for (var i in obj1)
			{
				if (!Craft.compare(obj1[i], obj2[i]))
				{
					return false;
				}
			}

			// All clear
			return true;
		}
		else
		{
			return (obj1 === obj2);
		}
	},

	/**
	 * Returns an array of an object's keys.
	 *
	 * @param object obj
	 * @return string
	 */
	getObjectKeys: function(obj)
	{
		var keys = [];

		for (var key in obj)
		{
			if (!obj.hasOwnProperty(key)) {
				continue;
			}

			keys.push(key);
		}

		return keys;
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
		if (chars === undefined) chars = ' \t\n\r\0\x0B';
		var re = new RegExp('^['+Craft.escapeChars(chars)+']+');
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
		if (chars === undefined) chars = ' \t\n\r\0\x0B';
		var re = new RegExp('['+Craft.escapeChars(chars)+']+$');
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
		str = Craft.ltrim(str, chars);
		str = Craft.rtrim(str, chars);
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
			var include;

			if (typeof callback == 'function')
			{
				include = callback(arr[i], i);
			}
			else
			{
				include = arr[i];
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
	 * Converts a number of seconds into a human-facing time duration.
	 */
	secondsToHumanTimeDuration: function(seconds, showSeconds)
	{
		if (typeof showSeconds == 'undefined')
		{
			showSeconds = true;
		}

		var secondsInWeek   = 604800,
			secondsInDay    = 86400,
			secondsInHour   = 3600,
			secondsInMinute = 60;

		var weeks = Math.floor(seconds / secondsInWeek);
		seconds = seconds % secondsInWeek;

		var days = Math.floor(seconds / secondsInDay);
		seconds = seconds % secondsInDay;

		var hours = Math.floor(seconds / secondsInHour);
		seconds = seconds % secondsInHour;

		var minutes;

		if (showSeconds)
		{
			minutes = Math.floor(seconds / secondsInMinute);
			seconds = seconds % secondsInMinute;
		}
		else
		{
			minutes = Math.round(seconds / secondsInMinute);
			seconds = 0;
		}

		timeComponents = [];

		if (weeks)
		{
			timeComponents.push(weeks+' '+(weeks == 1 ? Craft.t('week') : Craft.t('weeks')));
		}

		if (days)
		{
			timeComponents.push(days+' '+(days == 1 ? Craft.t('day') : Craft.t('days')));
		}

		if (hours)
		{
			timeComponents.push(hours+' '+(hours == 1 ? Craft.t('hour') : Craft.t('hours')));
		}

		if (minutes || (!showSeconds && !weeks && !days && !hours))
		{
			timeComponents.push(minutes+' '+(minutes == 1 ? Craft.t('minute') : Craft.t('minutes')));
		}

		if (seconds || (showSeconds && !weeks && !days && !hours && !minutes))
		{
			timeComponents.push(seconds+' '+(seconds == 1 ? Craft.t('second') : Craft.t('seconds')));
		}

		return timeComponents.join(', ');
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
			else if (typeof Craft.asciiCharMap[ascii] != 'undefined')
			{
				asciiStr += Craft.asciiCharMap[ascii];
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
	},

	appendHeadHtml: function(html)
	{
		if (!html)
		{
			return;
		}

		// Prune out any link tags that are already included
		var $existingCss = $('link[href]');

		if ($existingCss.length)
		{
			var existingCss = [];

			for (var i = 0; i < $existingCss.length; i++)
			{
				var href = $existingCss.eq(i).attr('href');
				existingCss.push(href.replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&"));
			}

			var regexp = new RegExp('<link\\s[^>]*href="(?:'+existingCss.join('|')+')".*?></script>', 'g');

			html = html.replace(regexp, '');
		}

		$('head').append(html);
	},

	appendFootHtml: function(html)
	{
		if (!html)
		{
			return;
		}

		// Prune out any script tags that are already included
		var $existingJs = $('script[src]');

		if ($existingJs.length)
		{
			var existingJs = [];

			for (var i = 0; i < $existingJs.length; i++)
			{
				var src = $existingJs.eq(i).attr('src');
				existingJs.push(src.replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&"));
			}

			var regexp = new RegExp('<script\\s[^>]*src="(?:'+existingJs.join('|')+')".*?></script>', 'g');

			html = html.replace(regexp, '');
		}

		Garnish.$bod.append(html);
	},

	/**
	 * Initializes any common UI elements in a given container.
	 *
	 * @param jQuery $container
	 */
	initUiElements: function($container)
	{
		$('.grid', $container).grid();
		$('.pane', $container).pane();
		$('.info', $container).infoicon();
		$('.checkbox-select', $container).checkboxselect();
		$('.fieldtoggle', $container).fieldtoggle();
		$('.lightswitch', $container).lightswitch();
		$('.nicetext', $container).nicetext();
		$('.pill', $container).pill();
		$('.formsubmit', $container).formsubmit();
		$('.menubtn', $container).menubtn();
	},

	_elementIndexClasses: {},
	_elementSelectorModalClasses: {},

	/**
	 * Registers an element index class for a given element type.
	 *
	 * @param string elementType
	 * @param function func
	 */
	registerElementIndexClass: function(elementType, func)
	{
		if (typeof this._elementIndexClasses[elementType] != 'undefined')
		{
			throw 'An element index class has already been registered for the element type “'+elementType+'”.';
		}

		this._elementIndexClasses[elementType] = func;
	},


	/**
	 * Registers an element selector modal class for a given element type.
	 *
	 * @param string elementType
	 * @param function func
	 */
	registerElementSelectorModalClass: function(elementType, func)
	{
		if (typeof this._elementSelectorModalClasses[elementType] != 'undefined')
		{
			throw 'An element selector modal class has already been registered for the element type “'+elementType+'”.';
		}

		this._elementSelectorModalClasses[elementType] = func;
	},

	/**
	 * Creates a new element index for a given element type.
	 *
	 * @param string elementType
	 * @param mixed  $container
	 * @param object settings
	 * @return BaseElementIndex
	 */
	createElementIndex: function(elementType, $container, settings)
	{
		var func;

		if (typeof this._elementIndexClasses[elementType] != 'undefined')
		{
			func = this._elementIndexClasses[elementType];
		}
		else
		{
			func = Craft.BaseElementIndex;
		}

		return new func(elementType, $container, settings);
	},

	/**
	 * Creates a new element selector modal for a given element type.
	 *
	 * @param string elementType
	 * @param object settings
	 */
	createElementSelectorModal: function(elementType, settings)
	{
		var func;

		if (typeof this._elementSelectorModalClasses[elementType] != 'undefined')
		{
			func = this._elementSelectorModalClasses[elementType];
		}
		else
		{
			func = Craft.BaseElementSelectorModal;
		}

		return new func(elementType, settings);
	},

	/**
	 * Retrieves a value from localStorage if it exists.
	 *
	 * @param string key
	 * @param mixed defaultValue
	 */
	getLocalStorage: function(key, defaultValue)
	{
		key = 'Craft-'+Craft.siteUid+'.'+key;

		if (typeof localStorage != 'undefined' && typeof localStorage[key] != 'undefined')
		{
			return JSON.parse(localStorage[key]);
		}
		else
		{
			return defaultValue;
		}
	},

	/**
	 * Saves a value to localStorage.
	 *
	 * @param string key
	 * @param mixed value
	 */
	setLocalStorage: function(key, value)
	{
		if (typeof localStorage != 'undefined')
		{
			key = 'Craft-'+Craft.siteUid+'.'+key;

			// localStorage might be filled all the way up.
			// Especially likely if this is a private window in Safari 8+, where localStorage technically exists,
			// but has a max size of 0 bytes.
			try
			{
				localStorage[key] = JSON.stringify(value);
			}
			catch(e) {}
		}
	},

	/**
	 * Returns element information from it's HTML.
	 *
	 * @param element
	 * @returns object
	 */
	getElementInfo: function(element)
	{
		var $element = $(element);

		if (!$element.hasClass('element'))
		{
			$element = $element.find('.element:first');
		}

		var info = {
			id:       $element.data('id'),
			locale:   $element.data('locale'),
			label:    $element.data('label'),
			status:   $element.data('status'),
			url:      $element.data('url'),
			hasThumb: $element.hasClass('hasthumb'),
			$element: $element
		};

		return info;
	},

	/**
	 * Changes an element to the requested size.
	 *
	 * @param element
	 * @param size
	 */
	setElementSize: function(element, size)
	{
		var $element = $(element);

		if (size != 'small' && size != 'large')
		{
			size = 'small';
		}

		if ($element.hasClass(size))
		{
			return;
		}

		var otherSize = (size == 'small' ? 'large' : 'small');

		$element
			.addClass(size)
			.removeClass(otherSize);

		if ($element.hasClass('hasthumb'))
		{
			var $oldImg = $element.find('> .elementthumb > img'),
				imgSize = (size == 'small' ? '30' : '100');
				$newImg = $('<img/>', {
					sizes: imgSize+'px',
					srcset: $oldImg.attr('srcset') || $oldImg.attr('data-pfsrcset')
				});

			$oldImg.replaceWith($newImg);

			picturefill({
				elements: [$newImg[0]]
			});
		}
	},

	/**
	 * Shows an element editor HUD.
	 *
	 * @param object $element
	 * @param object settings
	 */
	showElementEditor: function($element, settings)
	{
		if (Garnish.hasAttr($element, 'data-editable') && !$element.hasClass('disabled') && !$element.hasClass('loading'))
		{
			return new Craft.ElementEditor($element, settings);
		}
	}
});


// -------------------------------------------
//  Custom jQuery plugins
// -------------------------------------------

$.extend($.fn,
{
	animateLeft: function(pos, duration, easing, complete)
	{
		if (Craft.orientation == 'ltr')
		{
			return this.velocity({ left: pos }, duration, easing, complete);
		}
		else
		{
			return this.velocity({ right: pos }, duration, easing, complete);
		}
	},

	animateRight: function(pos, duration, easing, complete)
	{
		if (Craft.orientation == 'ltr')
		{
			return this.velocity({ right: pos }, duration, easing, complete);
		}
		else
		{
			return this.velocity({ left: pos }, duration, easing, complete);
		}
	},

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
	 * Sets the element as the container of a grid.
	 */
	grid: function()
	{
		return this.each(function()
		{
			var $container = $(this),
				settings = {};

			if ($container.data('item-selector')) settings.itemSelector = $container.data('item-selector');
			if ($container.data('cols'))          settings.cols = parseInt($container.data('cols'));
			if ($container.data('max-cols'))      settings.maxCols = parseInt($container.data('max-cols'));
			if ($container.data('min-col-width')) settings.minColWidth = parseInt($container.data('min-col-width'));
			if ($container.data('mode'))          settings.mode = $container.data('mode');
			if ($container.data('fill-mode'))     settings.fillMode = $container.data('fill-mode');
			if ($container.data('col-class'))     settings.colClass = $container.data('col-class');
			if ($container.data('snap-to-grid'))  settings.snapToGrid = !!$container.data('snap-to-grid');

			new Craft.Grid(this, settings);
		});
	},

	infoicon: function()
	{
		return this.each(function()
		{
			new Craft.InfoIcon(this);
		});
	},

	pane: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'pane'))
			{
				new Craft.Pane(this);
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
				new Craft.FieldToggle(this);
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
				new Craft.LightSwitch(this, settings);
			}
		});
	},

	nicetext: function()
	{
		return this.each(function()
		{
			if (!$.data(this, 'nicetext'))
			{
				new Garnish.NiceText(this);
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

	formsubmit: function()
	{
		// Secondary form submit buttons
		this.on('click', function(ev)
		{
			var $btn = $(ev.currentTarget);

			if ($btn.attr('data-confirm'))
			{
				if (!confirm($btn.attr('data-confirm')))
				{
					return;
				}
			}

			var $form;

			// Is this a menu item?
			if ($btn.data('menu'))
			{
				$form = $btn.data('menu').$anchor.closest('form');
			}
			else
			{
				$form = $btn.closest('form');
			}

			if ($btn.attr('data-action'))
			{
				$('<input type="hidden" name="action"/>')
					.val($btn.attr('data-action'))
					.appendTo($form);
			}

			if ($btn.attr('data-redirect'))
			{
				$('<input type="hidden" name="redirect"/>')
					.val($btn.attr('data-redirect'))
					.appendTo($form);
			}

			if ($btn.attr('data-param'))
			{
				$('<input type="hidden"/>')
					.attr({
						name: $btn.attr('data-param'),
						value: $btn.attr('data-value')
					})
					.appendTo($form);
			}

			$form.submit();
		});
	},

	menubtn: function()
	{
		return this.each(function()
		{
			var $btn = $(this);

			if (!$btn.data('menubtn') && $btn.next().hasClass('menu'))
			{
				var settings = {};

				if ($btn.data('menu-anchor')) settings.menuAnchor = $btn.data('menu-anchor');

				new Garnish.MenuBtn($btn, settings);
			}
		});
	}
});


Garnish.$doc.ready(function()
{
	Craft.initUiElements();
});

/**
 * Element index class
 */
Craft.BaseElementIndex = Garnish.Base.extend(
{
	// Properties
	// =========================================================================

	initialized: false,
	elementType: null,

	instanceState: null,
	sourceStates: null,
	sourceStatesStorageKey: null,

	searchTimeout: null,
	sourceSelect: null,

	$container: null,
	$main: null,
	$mainSpinner: null,
	isIndexBusy: false,

	$sidebar: null,
	showingSidebar: null,
	sourceKey: null,
	sourceViewModes: null,
	$source: null,

	$customizeSourcesBtn: null,
	customizeSourcesModal: null,

	$toolbar: null,
	$toolbarTableRow: null,
	toolbarOffset: null,

	$search: null,
	searching: false,
	searchText: null,
	$clearSearchBtn: null,

	$statusMenuBtn: null,
	statusMenu: null,
	status: null,

	$localeMenuBtn: null,
	localeMenu: null,
	locale: null,

	$sortMenuBtn: null,
	sortMenu: null,
	$sortAttributesList: null,
	$sortDirectionsList: null,
	$scoreSortAttribute: null,
	$structureSortAttribute: null,

	$elements: null,
	$viewModeBtnTd: null,
	$viewModeBtnContainer: null,
	viewModeBtns: null,
	viewMode: null,
	view: null,
	_autoSelectElements: null,

	actions: null,
	actionsHeadHtml: null,
	actionsFootHtml: null,
	$selectAllContainer: null,
	$selectAllCheckbox: null,
	showingActionTriggers: false,
	_$triggers: null,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 */
	init: function(elementType, $container, settings)
	{
		this.elementType = elementType;
		this.$container = $container;
		this.setSettings(settings, Craft.BaseElementIndex.defaults);

		// Set the state objects
		// ---------------------------------------------------------------------

		this.instanceState = {
			selectedSource: null
		};

		this.sourceStates = {};

		// Instance states (selected source) are stored by a custom storage key defined in the settings
		if (this.settings.storageKey)
		{
			$.extend(this.instanceState, Craft.getLocalStorage(this.settings.storageKey), {});
		}

		// Source states (view mode, etc.) are stored by the element type and context
		this.sourceStatesStorageKey = 'BaseElementIndex.'+this.elementType+'.'+this.settings.context;
		$.extend(this.sourceStates, Craft.getLocalStorage(this.sourceStatesStorageKey, {}));

		// Find the DOM elements
		// ---------------------------------------------------------------------

		this.$main = this.$container.find('.main');
		this.$toolbar = this.$container.find('.toolbar:first');
		this.$toolbarTableRow = this.$toolbar.children('table').children('tbody').children('tr');
		this.$statusMenuBtn = this.$toolbarTableRow.find('.statusmenubtn:first');
		this.$localeMenuBtn = this.$toolbarTableRow.find('.localemenubtn:first');
		this.$sortMenuBtn = this.$toolbarTableRow.find('.sortmenubtn:first');
		this.$search = this.$toolbarTableRow.find('.search:first input:first');
		this.$clearSearchBtn = this.$toolbarTableRow.find('.search:first > .clear');
		this.$mainSpinner = this.$toolbar.find('.spinner:first');
		this.$sidebar = this.$container.find('.sidebar:first');
		this.$customizeSourcesBtn = this.$sidebar.children('.customize-sources');
		this.$elements = this.$container.find('.elements:first');
		this.$viewModeBtnTd = this.$toolbarTableRow.find('.viewbtns:first');
		this.$viewModeBtnContainer = $('<div class="btngroup fullwidth"/>').appendTo(this.$viewModeBtnTd);

		// Keep the toolbar at the top of the window
		if (this.settings.context == 'index' && !Garnish.isMobileBrowser(true))
		{
			this.addListener(Garnish.$win, 'resize,scroll', 'updateFixedToolbar');
		}

		// Initialize the sources
		// ---------------------------------------------------------------------

		var $sources = this._getSourcesInList(this.$sidebar.children('nav').children('ul'));

		// No source, no party.
		if ($sources.length == 0)
		{
			return;
		}

		// The source selector
		this.sourceSelect = new Garnish.Select(this.$sidebar.find('nav'), {
			multi:             false,
			allowEmpty:        false,
			vertical:          true,
			onSelectionChange: $.proxy(this, '_handleSourceSelectionChange')
		});

		this._initSources($sources);

		// Customize button
		if (this.$customizeSourcesBtn.length)
		{
			this.addListener(this.$customizeSourcesBtn, 'click', 'createCustomizeSourcesModal');
		}

		// Initialize the status menu
		// ---------------------------------------------------------------------

		if (this.$statusMenuBtn.length)
		{
			this.statusMenu = this.$statusMenuBtn.menubtn().data('menubtn').menu;
			this.statusMenu.on('optionselect', $.proxy(this, '_handleStatusChange'));
		}

		// Initialize the locale menu
		// ---------------------------------------------------------------------

		// Is there a locale menu?
		if (this.$localeMenuBtn.length)
		{
			this.localeMenu = this.$localeMenuBtn.menubtn().data('menubtn').menu;

			// Figure out the initial locale
			var $option = this.localeMenu.$options.filter('.sel:first');

			if (!$option.length)
			{
				$option = this.localeMenu.$options.first();
			}

			if ($option.length)
			{
				this.locale = $option.data('locale');
			}
			else
			{
				// No locale options -- they must not have any locale permissions
				this.settings.criteria = { id: '0' };
			}

			this.localeMenu.on('optionselect', $.proxy(this, '_handleLocaleChange'));

			if (this.locale)
			{
				// Do we have a different locale stored in localStorage?
				var storedLocale = Craft.getLocalStorage('BaseElementIndex.locale');

				if (storedLocale && storedLocale != this.locale)
				{
					// Is that one available here?
					var $storedLocaleOption = this.localeMenu.$options.filter('[data-locale="'+storedLocale+'"]:first');

					if ($storedLocaleOption.length)
					{
						// Todo: switch this to localeMenu.selectOption($storedLocaleOption) once Menu is updated to support that
						$storedLocaleOption.trigger('click');
					}
				}
			}
		}
		else if (this.settings.criteria && this.settings.criteria.locale)
		{
			this.locale = this.settings.criteria.locale;
		}

		// Initialize the search input
		// ---------------------------------------------------------------------

		// Automatically update the elements after new search text has been sitting for a 1/2 second
		this.addListener(this.$search, 'textchange', $.proxy(function()
		{
			if (!this.searching && this.$search.val())
			{
				this.startSearching();
			}
			else if (this.searching && !this.$search.val())
			{
				this.stopSearching();
			}

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout($.proxy(this, 'updateElementsIfSearchTextChanged'), 500);
		}, this));

		// Update the elements when the Return key is pressed
		this.addListener(this.$search, 'keypress', $.proxy(function(ev)
		{
			if (ev.keyCode == Garnish.RETURN_KEY)
			{
				ev.preventDefault();

				if (this.searchTimeout)
				{
					clearTimeout(this.searchTimeout);
				}

				this.updateElementsIfSearchTextChanged();
			}
		}, this));

		// Clear the search when the X button is clicked
		this.addListener(this.$clearSearchBtn, 'click', $.proxy(function()
		{
			this.$search.val('');

			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			if (!Garnish.isMobileBrowser(true))
			{
				this.$search.focus();
			}

			this.stopSearching();

			this.updateElementsIfSearchTextChanged();

		}, this));

		// Auto-focus the Search box
		if (!Garnish.isMobileBrowser(true))
		{
			this.$search.focus();
		}

		// Initialize the sort menu
		// ---------------------------------------------------------------------

		// Is there a sort menu?
		if (this.$sortMenuBtn.length)
		{
			this.sortMenu = this.$sortMenuBtn.menubtn().data('menubtn').menu;
			this.$sortAttributesList = this.sortMenu.$container.children('.sort-attributes');
			this.$sortDirectionsList = this.sortMenu.$container.children('.sort-directions');

			this.sortMenu.on('optionselect', $.proxy(this, '_handleSortChange'));
		}

		// Let everyone know that the UI is initialized
		// ---------------------------------------------------------------------

		this.initialized = true;
		this.afterInit();

		// Select the initial source
		// ---------------------------------------------------------------------

		var sourceKey = this.getDefaultSourceKey(),
			$source;

		if (sourceKey)
		{
			$source = this.getSourceByKey(sourceKey);

			if ($source)
			{
				// Expand any parent sources
				var $parentSources = $source.parentsUntil('.sidebar', 'li');
				$parentSources.not(':first').addClass('expanded');
			}
		}

		if (!sourceKey || !$source)
		{
			// Select the first source by default
			$source = this.$sources.first();
		}

		if ($source.length)
		{
			this.selectSource($source);
		}

		// Load the first batch of elements!
		// ---------------------------------------------------------------------

		this.updateElements();
	},

	afterInit: function()
	{
		this.onAfterInit();
	},

	get $sources()
	{
		if (!this.sourceSelect)
		{
			return undefined;
		}

		return this.sourceSelect.$items;
	},

	updateFixedToolbar: function()
	{
		if (!this.toolbarOffset)
		{
			this.toolbarOffset = this.$toolbar.offset().top;

			if (!this.toolbarOffset)
			{
				return;
			}
		}

		this.updateFixedToolbar._scrollTop = Garnish.$win.scrollTop();

		if (Garnish.$win.width() > 992 && this.updateFixedToolbar._scrollTop > this.toolbarOffset - 7)
		{
			if (!this.$toolbar.hasClass('fixed'))
			{
				this.$elements.css('padding-top', (this.$toolbar.outerHeight() + 24));
				this.$toolbar.addClass('fixed');
			}

			this.$toolbar.css('width', this.$main.width());
		}
		else
		{
			if (this.$toolbar.hasClass('fixed'))
			{
				this.$toolbar.removeClass('fixed');
				this.$toolbar.css('width', '');
				this.$elements.css('padding-top', '');
			}
		}
	},

	initSource: function($source)
	{
		this.sourceSelect.addItems($source);
		this.initSourceToggle($source);
	},

	initSourceToggle: function($source)
	{
		var $toggle = this._getSourceToggle($source);

		if ($toggle.length)
		{
			this.addListener($toggle, 'click', '_handleSourceToggleClick');
		}
	},

	deinitSource: function($source)
	{
		this.sourceSelect.removeItems($source);
		this.deinitSourceToggle($source);
	},

	deinitSourceToggle: function($source)
	{
		var $toggle = this._getSourceToggle($source);

		if ($toggle.length)
		{
			this.removeListener($toggle, 'click');
		}
	},

	getDefaultSourceKey: function()
	{
		return this.instanceState.selectedSource;
	},

	startSearching: function()
	{
		// Show the clear button and add/select the Score sort option
		this.$clearSearchBtn.removeClass('hidden');

		if (!this.$scoreSortAttribute)
		{
			this.$scoreSortAttribute = $('<li><a data-attr="score">'+Craft.t('Score')+'</a></li>');
			this.sortMenu.addOptions(this.$scoreSortAttribute.children());
		}

		this.$scoreSortAttribute.prependTo(this.$sortAttributesList);
		this.setSortAttribute('score');
		this.getSortAttributeOption('structure').addClass('disabled');

		this.searching = true;
	},

	stopSearching: function()
	{
		// Hide the clear button and Score sort option
		this.$clearSearchBtn.addClass('hidden');

		this.$scoreSortAttribute.detach();
		this.getSortAttributeOption('structure').removeClass('disabled');
		this.setStoredSortOptionsForSource();

		this.searching = false;
	},

	setInstanceState: function(key, value)
	{
		if (typeof key == 'object')
		{
			$.extend(this.instanceState, key);
		}
		else
		{
			this.instanceState[key] = value;
		}

		// Store it in localStorage too?
		if (this.settings.storageKey)
		{
			Craft.setLocalStorage(this.settings.storageKey, this.instanceState);
		}
	},

	getSourceState: function(source, key, defaultValue)
	{
		if (typeof this.sourceStates[source] == 'undefined')
		{
			// Set it now so any modifications to it by whoever's calling this will be stored.
			this.sourceStates[source] = {};
		}

		if (typeof key == 'undefined')
		{
			return this.sourceStates[source];
		}
		else if (typeof this.sourceStates[source][key] != 'undefined')
		{
			return this.sourceStates[source][key];
		}
		else
		{
			return (typeof defaultValue != 'undefined' ? defaultValue : null);
		}
	},

	getSelectedSourceState: function(key, defaultValue)
	{
		return this.getSourceState(this.instanceState.selectedSource, key, defaultValue);
	},

	setSelecetedSourceState: function(key, value)
	{
		var viewState = this.getSelectedSourceState();

		if (typeof key == 'object')
		{
			$.extend(viewState, key);
		}
		else
		{
			viewState[key] = value;
		}

		this.sourceStates[this.instanceState.selectedSource] = viewState;

		// Store it in localStorage too
		Craft.setLocalStorage(this.sourceStatesStorageKey, this.sourceStates);
	},

	storeSortAttributeAndDirection: function()
	{
		var attr = this.getSelectedSortAttribute();

		if (attr != 'score')
		{
			this.setSelecetedSourceState({
				order: attr,
				sort: this.getSelectedSortDirection()
			});
		}
	},

	/**
	 * Returns the data that should be passed to the elementIndex/getElements controller action
	 * when loading elements.
	 */
	getViewParams: function()
	{
		var criteria = $.extend({
			status: this.status,
			locale: this.locale,
			search: this.searchText,
			limit: this.settings.batchSize
		}, this.settings.criteria);

		var params = {
			context:             this.settings.context,
			elementType:         this.elementType,
			source:              this.instanceState.selectedSource,
			criteria:            criteria,
			disabledElementIds:  this.settings.disabledElementIds,
			viewState:           this.getSelectedSourceState()
		};

		// Possible that the order/sort isn't entirely accurate if we're sorting by Score
		params.viewState.order = this.getSelectedSortAttribute();
		params.viewState.sort = this.getSelectedSortDirection();

		if (this.getSelectedSortAttribute() == 'structure')
		{
			params.collapsedElementIds = this.instanceState.collapsedElementIds;
		}

		return params;
	},

	updateElements: function()
	{
		// Ignore if we're not fully initialized yet
		if (!this.initialized)
		{
			return;
		}

		this.setIndexBusy();

		var params = this.getViewParams();

		Craft.postActionRequest('elementIndex/getElements', params, $.proxy(function(response, textStatus)
		{
			this.setIndexAvailable();

			if (textStatus == 'success')
			{
				this._updateView(params, response);
			}
			else
			{
				Craft.cp.displayError(Craft.t('An unknown error occurred.'));
			}

		}, this));
	},

	updateElementsIfSearchTextChanged: function()
	{
		if (this.searchText !== (this.searchText = this.searching ? this.$search.val() : null))
		{
			this.updateElements();
		}
	},

	showActionTriggers: function()
	{
		// Ignore if they're already shown
		if (this.showingActionTriggers)
		{
			return;
		}

		// Hard-code the min toolbar height in case it was taller than the actions toolbar
		// (prevents the elements from jumping if this ends up being a double-click)
		this.$toolbar.css('min-height', this.$toolbar.height());

		// Hide any toolbar inputs
		this.$toolbarTableRow.children().not(this.$selectAllContainer).addClass('hidden');

		if (!this._$triggers)
		{
			this._createTriggers();
		}
		else
		{
			this._$triggers.insertAfter(this.$selectAllContainer);
		}

		this.showingActionTriggers = true;
	},

	submitAction: function(actionHandle, actionParams)
	{
		// Make sure something's selected
		var selectedElementIds = this.view.getSelectedElementIds(),
			totalSelected = selectedElementIds.length,
			totalItems = this.view.getEnabledElements.length,
			action;

		if (totalSelected == 0)
		{
			return;
		}

		// Find the action
		for (var i = 0; i < this.actions.length; i++)
		{
			if (this.actions[i].handle == actionHandle)
			{
				action = this.actions[i];
				break;
			}
		}

		if (!action || (action.confirm && !confirm(action.confirm)))
		{
			return;
		}

		// Get ready to submit
		var viewParams = this.getViewParams();

		var params = $.extend(viewParams, actionParams, {
			elementAction: actionHandle,
			elementIds: selectedElementIds
		});

		// Do it
		this.setIndexBusy();
		this._autoSelectElements = selectedElementIds;

		Craft.postActionRequest('elementIndex/performAction', params, $.proxy(function(response, textStatus)
		{
			this.setIndexAvailable();

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this._updateView(viewParams, response);

					if (response.message)
					{
						Craft.cp.displayNotice(response.message);
					}

					// There may be a new background task that needs to be run
					Craft.cp.runPendingTasks();
				}
				else
				{
					Craft.cp.displayError(response.message);
				}
			}
		}, this));
	},

	hideActionTriggers: function()
	{
		// Ignore if there aren't any
		if (!this.showingActionTriggers)
		{
			return;
		}

		this._$triggers.detach();

		this.$toolbarTableRow.children().not(this.$selectAllContainer).removeClass('hidden');

		// Unset the min toolbar height
		this.$toolbar.css('min-height', '');

		this.showingActionTriggers = false;
	},

	updateActionTriggers: function()
	{
		// Do we have an action UI to update?
		if (this.actions)
		{
			var totalSelected = this.view.getSelectedElements().length;

			if (totalSelected != 0)
			{
				if (totalSelected == this.view.getEnabledElements().length)
				{
					this.$selectAllCheckbox.removeClass('indeterminate');
					this.$selectAllCheckbox.addClass('checked');
					this.$selectAllBtn.attr('aria-checked', 'true');
				}
				else
				{
					this.$selectAllCheckbox.addClass('indeterminate');
					this.$selectAllCheckbox.removeClass('checked');
					this.$selectAllBtn.attr('aria-checked', 'mixed');
				}

				this.showActionTriggers();
			}
			else
			{
				this.$selectAllCheckbox.removeClass('indeterminate checked');
				this.$selectAllBtn.attr('aria-checked', 'false');
				this.hideActionTriggers();
			}
		}
	},

	getSelectedElements: function()
	{
		return this.view ? this.view.getSelectedElements() : $();
	},

	getSelectedElementIds: function()
	{
		return this.view ? this.view.getSelectedElementIds() : [];
	},

	getSortAttributeOption: function(attr)
	{
		return this.$sortAttributesList.find('a[data-attr="'+attr+'"]:first');
	},

	getSelectedSortAttribute: function()
	{
		return this.$sortAttributesList.find('a.sel:first').data('attr');
	},

	setSortAttribute: function(attr)
	{
		// Find the option (and make sure it actually exists)
		var $option = this.getSortAttributeOption(attr);

		if ($option.length)
		{
			this.$sortAttributesList.find('a.sel').removeClass('sel');
			$option.addClass('sel');

			var label = $option.text();
			this.$sortMenuBtn.attr('title', Craft.t('Sort by {attribute}', { attribute: label }));
			this.$sortMenuBtn.text(label);

			this.setSortDirection('asc');

			if (attr == 'score' || attr == 'structure')
			{
				this.$sortDirectionsList.find('a').addClass('disabled');
			}
			else
			{
				this.$sortDirectionsList.find('a').removeClass('disabled');
			}
		}
	},

	getSortDirectionOption: function(dir)
	{
		return this.$sortDirectionsList.find('a[data-dir='+dir+']:first');
	},

	getSelectedSortDirection: function()
	{
		return this.$sortDirectionsList.find('a.sel:first').data('dir');
	},

	getSelectedViewMode: function()
	{
		return this.getSelectedSourceState('mode');
	},

	setSortDirection: function(dir)
	{
		if (dir != 'desc')
		{
			dir = 'asc';
		}

		this.$sortMenuBtn.attr('data-icon', dir);
		this.$sortDirectionsList.find('a.sel').removeClass('sel');
		this.getSortDirectionOption(dir).addClass('sel');
	},

	getSourceByKey: function(key)
	{
		if (this.$sources)
		{
			var $source = this.$sources.filter('[data-key="'+key+'"]:first');

			if ($source.length)
			{
				return $source;
			}
		}
	},

	selectSource: function($source)
	{
		if (!$source || !$source.length)
		{
			return false;
		}

		if (this.$source && this.$source[0] && this.$source[0] == $source[0] && $source.data('key') == this.sourceKey)
		{
			return false;
		}

		this.$source = $source;
		this.sourceKey = $source.data('key');
		this.setInstanceState('selectedSource', this.sourceKey);
		this.sourceSelect.selectItem($source);

		Craft.cp.updateSidebarMenuLabel();

		if (this.searching)
		{
			// Clear the search value without causing it to update elements
			this.searchText = null;
			this.$search.val('');
			this.stopSearching();
		}

		// Sort menu
		// ----------------------------------------------------------------------

		// Does this source have a structure?
		if (Garnish.hasAttr(this.$source, 'data-has-structure'))
		{
			if (!this.$structureSortAttribute)
			{
				this.$structureSortAttribute = $('<li><a data-attr="structure">'+Craft.t('Structure')+'</a></li>');
				this.sortMenu.addOptions(this.$structureSortAttribute.children());
			}

			this.$structureSortAttribute.prependTo(this.$sortAttributesList);
		}
		else if (this.$structureSortAttribute)
		{
			this.$structureSortAttribute.removeClass('sel').detach();
		}

		this.setStoredSortOptionsForSource();

		// View mode buttons
		// ----------------------------------------------------------------------

		// Clear out any previous view mode data
		this.$viewModeBtnContainer.empty();
		this.viewModeBtns = {};
		this.viewMode = null;

		// Get the new list of view modes
		this.sourceViewModes = this.getViewModesForSource();

		// Create the buttons if there's more than one mode available to this source
		if (this.sourceViewModes.length > 1)
		{
			this.$viewModeBtnTd.removeClass('hidden');

			for (var i = 0; i < this.sourceViewModes.length; i++)
			{
				var viewMode = this.sourceViewModes[i];

				var $viewModeBtn = $('<div data-view="'+viewMode.mode+'" role="button"' +
					' class="btn'+(typeof viewMode.className != 'undefined' ? ' '+viewMode.className : '')+'"' +
					' title="'+viewMode.title+'"' +
					(typeof viewMode.icon != 'undefined' ? ' data-icon="'+viewMode.icon+'"' : '') +
					'/>'
				).appendTo(this.$viewModeBtnContainer);

				this.viewModeBtns[viewMode.mode] = $viewModeBtn;

				this.addListener($viewModeBtn, 'click', { mode: viewMode.mode }, function(ev) {
					this.selectViewMode(ev.data.mode);
					this.updateElements();
				});
			}
		}
		else
		{
			this.$viewModeBtnTd.addClass('hidden');
		}

		// Figure out which mode we should start with
		var viewMode = this.getSelectedViewMode();

		if (!viewMode || !this.doesSourceHaveViewMode(viewMode))
		{
			// Try to keep using the current view mode
			if (this.viewMode && this.doesSourceHaveViewMode(this.viewMode))
			{
				viewMode = this.viewMode;
			}
			// Just use the first one
			else
			{
				viewMode = this.sourceViewModes[0].mode;
			}
		}

		this.selectViewMode(viewMode);

		this.onSelectSource();

		return true;
	},

	selectSourceByKey: function(key)
	{
		var $source = this.getSourceByKey(key);

		if ($source)
		{
			return this.selectSource($source);
		}
		else
		{
			return false;
		}
	},

	setStoredSortOptionsForSource: function()
	{
		// Default to whatever's first
		this.setSortAttribute();
		this.setSortDirection('asc');

		var sortAttr = this.getSelectedSourceState('order'),
			sortDir = this.getSelectedSourceState('sort');

		if (!sortAttr)
		{
			// Get the default
			sortAttr = this.getDefaultSort();

			if (Garnish.isArray(sortAttr))
			{
				sortDir = sortAttr[1];
				sortAttr = sortAttr[0];
			}
		}

		if (sortDir != 'asc' && sortDir != 'desc')
		{
			sortDir = 'asc';
		}

		this.setSortAttribute(sortAttr);
		this.setSortDirection(sortDir);
	},

	getDefaultSort: function()
	{
		// Does the source specify what to do?
		if (this.$source && Garnish.hasAttr(this.$source, 'data-default-sort'))
		{
			return this.$source.attr('data-default-sort').split(':');
		}
		else
		{
			// Default to whatever's first
			return [this.$sortAttributesList.find('a:first').data('attr'), 'asc'];
		}
	},

	getViewModesForSource: function()
	{
		var viewModes = [
			{ mode: 'table', title: Craft.t('Display in a table'), icon: 'list' }
		];

		if (this.$source && Garnish.hasAttr(this.$source, 'data-has-thumbs'))
		{
			viewModes.push({ mode: 'thumbs', title: Craft.t('Display as thumbnails'), icon: 'grid' });
		}

		return viewModes;
	},

	doesSourceHaveViewMode: function(viewMode)
	{
		for (var i = 0; i < this.sourceViewModes.length; i++)
		{
			if (this.sourceViewModes[i].mode == viewMode)
			{
				return true;
			}
		}

		return false;
	},

	selectViewMode: function(viewMode, force)
	{
		// Make sure that the current source supports it
		if (!force && !this.doesSourceHaveViewMode(viewMode))
		{
			viewMode = this.sourceViewModes[0].mode;
		}

		// Has anything changed?
		if (viewMode == this.viewMode)
		{
			return;
		}

		// Deselect the previous view mode
		if (this.viewMode && typeof this.viewModeBtns[this.viewMode] != 'undefined')
		{
			this.viewModeBtns[this.viewMode].removeClass('active');
		}

		this.viewMode = viewMode;
		this.setSelecetedSourceState('mode', this.viewMode);

		if (typeof this.viewModeBtns[this.viewMode] != 'undefined')
		{
			this.viewModeBtns[this.viewMode].addClass('active');
		}
	},

	createView: function(mode, settings)
	{
		var viewClass = this.getViewClass(mode);
		return new viewClass(this, this.$elements, settings);
	},

	getViewClass: function(mode)
	{
		switch (mode)
		{
			case 'table':
				return Craft.TableElementIndexView;
			case 'thumbs':
				return Craft.ThumbsElementIndexView;
			default:
				throw 'View mode "'+mode+'" not supported.';
		}
	},

	rememberDisabledElementId: function(id)
	{
		var index = $.inArray(id, this.settings.disabledElementIds);

		if (index == -1)
		{
			this.settings.disabledElementIds.push(id);
		}
	},

	forgetDisabledElementId: function(id)
	{
		var index = $.inArray(id, this.settings.disabledElementIds);

		if (index != -1)
		{
			this.settings.disabledElementIds.splice(index, 1);
		}
	},

	enableElements: function($elements)
	{
		$elements.removeClass('disabled').parents('.disabled').removeClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var id = $($elements[i]).data('id');
			this.forgetDisabledElementId(id);
		}

		this.onEnableElements($elements);
	},

	disableElements: function($elements)
	{
		$elements.removeClass('sel').addClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var id = $($elements[i]).data('id');
			this.rememberDisabledElementId(id);
		}

		this.onDisableElements($elements);
	},

	getElementById: function(id)
	{
		return this.view.getElementById(id);
	},

	enableElementsById: function(ids)
	{
		ids = $.makeArray(ids);

		for (var i = 0; i < ids.length; i++)
		{
			var id = ids[i],
				$element = this.getElementById(id);

			if ($element && $element.length)
			{
				this.enableElements($element);
			}
			else
			{
				this.forgetDisabledElementId(id);
			}
		}
	},

	disableElementsById: function(ids)
	{
		ids = $.makeArray(ids);

		for (var i = 0; i < ids.length; i++)
		{
			var id = ids[i],
				$element = this.getElementById(id);

			if ($element && $element.length)
			{
				this.disableElements($element);
			}
			else
			{
				this.rememberDisabledElementId(id);
			}
		}
	},

	selectElementAfterUpdate: function(id)
	{
		if (this._autoSelectElements === null)
		{
			this._autoSelectElements = [];
		}

		this._autoSelectElements.push(id);
	},

	addButton: function($button)
	{
		this.getButtonContainer().append($button);
	},

	isShowingSidebar: function()
	{
		if (this.showingSidebar === null)
		{
			this.showingSidebar = (this.$sidebar.length && !this.$sidebar.hasClass('hidden'));
		}

		return this.showingSidebar;
	},

	getButtonContainer: function()
	{
		// Is there a predesignated place where buttons should go?
		if (this.settings.buttonContainer)
		{
			return $(this.settings.buttonContainer);
		}
		else
		{
			// Add it to the page header
			var $container = $('#extra-headers > .buttons:first');

			if (!$container.length)
			{
				var $extraHeadersContainer = $('#extra-headers');

				if (!$extraHeadersContainer.length)
				{
					$extraHeadersContainer = $('<div id="extra-headers"/>').appendTo($('#page-header'));
				}

				$container = $('<div class="buttons right"/>').appendTo($extraHeadersContainer);
			}

			return $container;
		}
	},

	setIndexBusy: function()
	{
		this.$mainSpinner.removeClass('hidden');
		this.isIndexBusy = true;
	},

	setIndexAvailable: function()
	{
		this.$mainSpinner.addClass('hidden');
		this.isIndexBusy = false;
	},

	createCustomizeSourcesModal: function()
	{
		// Recreate it each time
		var modal = new Craft.CustomizeSourcesModal(this, {
			onHide: function() {
				modal.destroy();
			}
		});

		return modal;
	},

	disable: function()
	{
		if (this.sourceSelect)
		{
			this.sourceSelect.disable();
		}

		if (this.view)
		{
			this.view.disable();
		}

		this.base();
	},

	enable: function()
	{
		if (this.sourceSelect)
		{
			this.sourceSelect.enable();
		}

		if (this.view)
		{
			this.view.enable();
		}

		this.base();
	},

	// Events
	// =========================================================================

	onAfterInit: function()
	{
		this.settings.onAfterInit();
		this.trigger('afterInit');
	},

	onSelectSource: function()
	{
		this.settings.onSelectSource(this.sourceKey);
		this.trigger('selectSource', {sourceKey: this.sourceKey});
	},

	onUpdateElements: function()
	{
		this.settings.onUpdateElements();
		this.trigger('updateElements');
	},

	onSelectionChange: function()
	{
		this.settings.onSelectionChange();
		this.trigger('selectionChange');
	},

	onEnableElements: function($elements)
	{
		this.settings.onEnableElements($elements);
		this.trigger('enableElements', {elements: $elements});
	},

	onDisableElements: function($elements)
	{
		this.settings.onDisableElements($elements);
		this.trigger('disableElements', {elements: $elements});
	},

	// Private methods
	// =========================================================================

	// UI state handlers
	// -------------------------------------------------------------------------

	_handleSourceSelectionChange: function()
	{
		// If the selected source was just removed (maybe because its parent was collapsed),
		// there won't be a selected source
		if (!this.sourceSelect.totalSelected)
		{
			this.sourceSelect.selectItem(this.$sources.first());
			return;
		}

		if (this.selectSource(this.sourceSelect.$selectedItems))
		{
			this.updateElements();
		}
	},

	_handleActionTriggerSubmit: function(ev)
	{
		ev.preventDefault();

		var $form = $(ev.currentTarget);

		// Make sure Craft.ElementActionTrigger isn't overriding this
		if ($form.hasClass('disabled') || $form.data('custom-handler'))
		{
			return;
		}

		var actionHandle = $form.data('action'),
			params = Garnish.getPostData($form);

		this.submitAction(actionHandle, params);
	},

	_handleMenuActionTriggerSubmit: function(ev)
	{
		var $option = $(ev.option);

		// Make sure Craft.ElementActionTrigger isn't overriding this
		if ($option.hasClass('disabled') || $option.data('custom-handler'))
		{
			return;
		}

		var actionHandle = $option.data('action');
		this.submitAction(actionHandle);
	},

	_handleStatusChange: function(ev)
	{
		this.statusMenu.$options.removeClass('sel');
		var $option = $(ev.selectedOption).addClass('sel');
		this.$statusMenuBtn.html($option.html());

		this.status = $option.data('status');
		this.updateElements();
	},

	_handleLocaleChange: function(ev)
	{
		this.localeMenu.$options.removeClass('sel');
		var $option = $(ev.selectedOption).addClass('sel');
		this.$localeMenuBtn.html($option.html());

		this.locale = $option.data('locale');

		if (this.initialized)
		{
			// Remember this locale for later
			Craft.setLocalStorage('BaseElementIndex.locale', this.locale);

			// Update the elements
			this.updateElements();
		}
	},

	_handleSortChange: function(ev)
	{
		var $option = $(ev.selectedOption);

		if ($option.hasClass('disabled') || $option.hasClass('sel'))
		{
			return;
		}

		// Is this an attribute or a direction?
		if ($option.parent().parent().is(this.$sortAttributesList))
		{
			this.setSortAttribute($option.data('attr'));
		}
		else
		{
			this.setSortDirection($option.data('dir'));
		}

		this.storeSortAttributeAndDirection();
		this.updateElements();
	},

	_handleSelectionChange: function()
	{
		this.updateActionTriggers();
		this.onSelectionChange();
	},

	_handleSourceToggleClick: function(ev)
	{
		this._toggleSource($(ev.currentTarget).prev('a'));
		ev.stopPropagation();
	},

	// Source managemnet
	// -------------------------------------------------------------------------

	_getSourcesInList: function($list)
	{
		return $list.children('li').children('a');
	},

	_getChildSources: function($source)
	{
		var $list = $source.siblings('ul');
		return this._getSourcesInList($list);
	},

	_getSourceToggle: function($source)
	{
		return $source.siblings('.toggle');
	},

	_initSources: function($sources)
	{
		for (var i = 0; i < $sources.length; i++)
		{
			this.initSource($($sources[i]));
		}
	},

	_deinitSources: function($sources)
	{
		for (var i = 0; i < $sources.length; i++)
		{
			this.deinitSource($($sources[i]));
		}
	},

	_toggleSource: function($source)
	{
		if ($source.parent('li').hasClass('expanded'))
		{
			this._collapseSource($source);
		}
		else
		{
			this._expandSource($source);
		}
	},

	_expandSource: function($source)
	{
		$source.parent('li').addClass('expanded');

		var $childSources = this._getChildSources($source);
		this._initSources($childSources);
	},

	_collapseSource: function($source)
	{
		$source.parent('li').removeClass('expanded');

		var $childSources = this._getChildSources($source);
		this._deinitSources($childSources);
	},

	// View
	// -------------------------------------------------------------------------

	_updateView: function(params, response)
	{
		// Cleanup
		// -------------------------------------------------------------

		// Kill the old view class
		if (this.view)
		{
			this.view.destroy();
			delete this.view;
		}

		// Get rid of the old action triggers regardless of whether the new batch has actions or not
		if (this.actions)
		{
			this.hideActionTriggers();
			this.actions = this.actionsHeadHtml = this.actionsFootHtml = this._$triggers = null;
		}

		if (this.$selectAllContainer)
		{
			// Git rid of the old select all button
			this.$selectAllContainer.detach();
		}

		// Batch actions setup
		// -------------------------------------------------------------

		if (this.settings.context == 'index' && response.actions && response.actions.length)
		{
			this.actions = response.actions;
			this.actionsHeadHtml = response.actionsHeadHtml;
			this.actionsFootHtml = response.actionsFootHtml;

			// First time?
			if (!this.$selectAllContainer)
			{
				// Create the select all button
				this.$selectAllContainer = $('<td class="selectallcontainer thin"/>');
				this.$selectAllBtn = $('<div class="btn" />').appendTo(this.$selectAllContainer);
				this.$selectAllCheckbox = $('<div class="checkbox"/>').appendTo(this.$selectAllBtn);

				this.$selectAllBtn.attr({
					'role': 'checkbox',
					'tabindex': '0',
					'aria-checked': 'false',
				});

				this.addListener(this.$selectAllBtn, 'click', function()
				{
					if (this.view.getSelectedElements().length == 0)
					{
						this.view.selectAllElements();
					}
					else
					{
						this.view.deselectAllElements();
					}
				});

				this.addListener(this.$selectAllBtn, 'keydown', function(ev)
				{
					if(ev.keyCode == Garnish.SPACE_KEY)
					{
						ev.preventDefault();

						$(ev.currentTarget).trigger('click');
					}
				});
			}
			else
			{
				// Reset the select all button
				this.$selectAllCheckbox.removeClass('indeterminate checked');

				this.$selectAllBtn.attr('aria-checked', 'false');
			}

			// Place the select all button at the beginning of the toolbar
			this.$selectAllContainer.prependTo(this.$toolbarTableRow);
		}

		// Update the view with the new container + elements HTML
		// -------------------------------------------------------------

		this.$elements.html(response.html);
		Craft.appendHeadHtml(response.headHtml);
		Craft.appendFootHtml(response.footHtml);
		picturefill();

		// Create the view
		// -------------------------------------------------------------

		// Should we make the view selectable?
		var selectable = (this.actions || this.settings.selectable);

		this.view = this.createView(this.getSelectedViewMode(), {
			context: this.settings.context,
			batchSize: this.settings.batchSize,
			params: params,
			selectable: selectable,
			multiSelect: (this.actions || this.settings.multiSelect),
			checkboxMode: (this.settings.context == 'index' && this.actions),
			onSelectionChange: $.proxy(this, '_handleSelectionChange')
		});

		// Auto-select elements
		// -------------------------------------------------------------

		if (this._autoSelectElements)
		{
			if (selectable)
			{
				for (var i = 0; i < this._autoSelectElements.length; i++)
				{
					this.view.selectElementById(this._autoSelectElements[i]);
				}
			}

			this._autoSelectElements = null;
		}

		// Trigger the event
		// -------------------------------------------------------------

		this.onUpdateElements();
	},

	_createTriggers: function()
	{
		var triggers = [],
			safeMenuActions = [],
			destructiveMenuActions = [];

		for (var i = 0; i < this.actions.length; i++)
		{
			var action = this.actions[i];

			if (action.trigger)
			{
				var $form = $('<form id="'+action.handle+'-actiontrigger"/>')
					.data('action', action.handle)
					.append(action.trigger);

				this.addListener($form, 'submit', '_handleActionTriggerSubmit');
				triggers.push($form);
			}
			else
			{
				if (!action.destructive)
				{
					safeMenuActions.push(action);
				}
				else
				{
					destructiveMenuActions.push(action);
				}
			}
		}

		var $btn;

		if (safeMenuActions.length || destructiveMenuActions.length)
		{
			var $menuTrigger = $('<form/>');
			$btn = $('<div class="btn menubtn" data-icon="settings" title="'+Craft.t('Actions')+'"/>').appendTo($menuTrigger);
			var $menu = $('<ul class="menu"/>').appendTo($menuTrigger),
				$safeList = this._createMenuTriggerList(safeMenuActions),
				$destructiveList = this._createMenuTriggerList(destructiveMenuActions);

			if ($safeList)
			{
				$safeList.appendTo($menu);
			}

			if ($safeList && $destructiveList)
			{
				$('<hr/>').appendTo($menu);
			}

			if ($destructiveList)
			{
				$destructiveList.appendTo($menu);
			}

			triggers.push($menuTrigger);
		}

		// Add a filler TD
		triggers.push('');

		this._$triggers = $();

		for (var i = 0; i < triggers.length; i++)
		{
			var $td = $('<td class="'+(i < triggers.length - 1 ? 'thin' : '')+'"/>').append(triggers[i]);
			this._$triggers = this._$triggers.add($td);
		}

		this._$triggers.insertAfter(this.$selectAllContainer);
		Craft.appendHeadHtml(this.actionsHeadHtml);
		Craft.appendFootHtml(this.actionsFootHtml);

		Craft.initUiElements(this._$triggers);

		if ($btn)
		{
			$btn.data('menubtn').on('optionSelect', $.proxy(this, '_handleMenuActionTriggerSubmit'));
		}
	},

	_createMenuTriggerList: function(actions)
	{
		if (actions && actions.length)
		{
			var $ul = $('<ul/>');

			for (var i = 0; i < actions.length; i++)
			{
				var handle = actions[i].handle;
				$('<li><a id="'+handle+'-actiontrigger" data-action="'+handle+'">'+actions[i].name+'</a></li>').appendTo($ul);
			}

			return $ul;
		}
	}
},

// Static Properties
// =============================================================================

{
	defaults: {
		context: 'index',
		modal: null,
		storageKey: null,
		criteria: null,
		batchSize: 50,
		disabledElementIds: [],
		selectable: false,
		multiSelect: false,
		buttonContainer: null,

		onAfterInit: $.noop,
		onSelectSource: $.noop,
		onUpdateElements: $.noop,
		onSelectionChange: $.noop,
		onEnableElements: $.noop,
		onDisableElements: $.noop
	}
});


/**
 * Base Element Index View
 */
Craft.BaseElementIndexView = Garnish.Base.extend(
{
	$container: null,
	$loadingMoreSpinner: null,
	$elementContainer: null,
	$scroller: null,

	elementIndex: null,
	elementSelect: null,

	loadingMore: false,

	_totalVisible: null,
	_morePending: null,
	_handleEnableElements: null,
	_handleDisableElements: null,

	init: function(elementIndex, container, settings)
	{
		this.elementIndex = elementIndex;
		this.$container = $(container);
		this.setSettings(settings, Craft.BaseElementIndexView.defaults);

		// Create a "loading-more" spinner
		this.$loadingMoreSpinner = $(
			'<div class="centeralign hidden">' +
				'<div class="spinner loadingmore"></div>' +
			'</div>'
		).insertAfter(this.$container);

		// Get the actual elements container and its child elements
		this.$elementContainer = this.getElementContainer();
		var $elements = this.$elementContainer.children();

		this.setTotalVisible($elements.length);
		this.setMorePending(this.settings.batchSize && $elements.length == this.settings.batchSize);

		if (this.settings.selectable)
		{
			this.elementSelect = new Garnish.Select(
				this.$elementContainer,
				$elements.filter(':not(.disabled)'),
				{
					multi:             this.settings.multiSelect,
					vertical:          this.isVerticalList(),
					handle:            (this.settings.context == 'index' ? '.checkbox, .element:first' : null),
					filter:            ':not(a):not(.toggle)',
					checkboxMode:      this.settings.checkboxMode,
					onSelectionChange: $.proxy(this, 'onSelectionChange')
				}
			);

			this._handleEnableElements = $.proxy(function(ev)
			{
				this.elementSelect.addItems(ev.elements);
			}, this);

			this._handleDisableElements = $.proxy(function(ev)
			{
				this.elementSelect.removeItems(ev.elements);
			}, this);

			this.elementIndex.on('enableElements', this._handleEnableElements);
			this.elementIndex.on('disableElements', this._handleDisableElements);
		}

		// Enable inline element editing if this is an index page
		if (this.settings.context == 'index')
		{
			this._handleElementEditing = $.proxy(function(ev)
			{
				var $target = $(ev.target);

				if ($target.prop('nodeName') == 'A')
				{
					// Let the link do its thing
					return;
				}

				var $element;

				if ($target.hasClass('element'))
				{
					$element = $target;
				}
				else
				{
					$element = $target.closest('.element');

					if (!$element.length)
					{
						return;
					}
				}

				if (Garnish.hasAttr($element, 'data-editable'))
				{
					this.createElementEditor($element);
				}
			}, this);

			this.addListener(this.$elementContainer, 'dblclick', this._handleElementEditing);

			if($.isTouchCapable())
			{
				this.addListener(this.$elementContainer, 'taphold', this._handleElementEditing);
			}
		}

		// Give sub-classes a chance to do post-initialization stuff here
		this.afterInit();

		// Set up lazy-loading
		if (this.settings.batchSize)
		{
			if (this.settings.context == 'index')
			{
				this.$scroller = Garnish.$win;
			}
			else
			{
				this.$scroller = this.elementIndex.$main;
			}

			this.$scroller.scrollTop(0);
			this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
			this.maybeLoadMore();
		}
	},

	getElementContainer: function()
	{
		throw 'Classes that extend Craft.BaseElementIndexView must supply a getElementContainer() method.';
	},

	afterInit: function()
	{
	},

	getAllElements: function()
	{
		return this.$elementContainer.children();
	},

	getEnabledElements: function()
	{
		return this.$elementContainer.children(':not(.disabled)');
	},

	getElementById: function(id)
	{
		var $element = this.$elementContainer.children('[data-id="'+id+'"]:first');

		if ($element.length)
		{
			return $element;
		}
		else
		{
			return null;
		}
	},

	getSelectedElements: function()
	{
		if (!this.elementSelect)
		{
			throw 'This view is not selectable.';
		}

		return this.elementSelect.$selectedItems;
	},

	getSelectedElementIds: function()
	{
		var $selectedElements = this.getSelectedElements(),
			ids = [];

		if ($selectedElements)
		{
			for (var i = 0; i < $selectedElements.length; i++)
			{
				ids.push($selectedElements.eq(i).data('id'));
			}
		}

		return ids;
	},

	selectElement: function($element)
	{
		if (!this.elementSelect)
		{
			throw 'This view is not selectable.';
		}

		this.elementSelect.selectItem($element, true);
		return true;
	},

	selectElementById: function(id)
	{
		if (!this.elementSelect)
		{
			throw 'This view is not selectable.';
		}

		var $element = this.getElementById(id);

		if ($element)
		{
			this.elementSelect.selectItem($element, true);
			return true;
		}
		else
		{
			return false;
		}
	},

	selectAllElements: function()
	{
		this.elementSelect.selectAll();
	},

	deselectAllElements: function()
	{
		this.elementSelect.deselectAll();
	},

	isVerticalList: function()
	{
		return false;
	},

	getTotalVisible: function()
	{
		return this._totalVisible;
	},

	setTotalVisible: function(totalVisible)
	{
		this._totalVisible = totalVisible;
	},

	getMorePending: function()
	{
		return this._morePending;
	},

	setMorePending: function(morePending)
	{
		this._morePending = morePending;
	},

	/**
	 * Checks if the user has reached the bottom of the scroll area, and if so, loads the next batch of elemets.
	 */
	maybeLoadMore: function()
	{
		if (this.canLoadMore())
		{
			this.loadMore();
		}
	},

	/**
	 * Returns whether the user has reached the bottom of the scroll area.
	 */
	canLoadMore: function()
	{
		if (!this.getMorePending() || !this.settings.batchSize)
		{
			return false;
		}

		// Check if the user has reached the bottom of the scroll area
		if (this.$scroller[0] == Garnish.$win[0])
		{
			var winHeight = Garnish.$win.innerHeight(),
				winScrollTop = Garnish.$win.scrollTop(),
				containerOffset = this.$container.offset().top,
				containerHeight = this.$container.height();

			return (winHeight + winScrollTop >= containerOffset + containerHeight);
		}
		else
		{
			var containerScrollHeight = this.$scroller.prop('scrollHeight'),
				containerScrollTop = this.$scroller.scrollTop(),
				containerHeight = this.$scroller.outerHeight();

			return (containerScrollHeight - containerScrollTop <= containerHeight + 15);
		}
	},

	/**
	 * Loads the next batch of elements.
	 */
	loadMore: function()
	{
		if (!this.getMorePending() || this.loadingMore || !this.settings.batchSize)
		{
			return;
		}

		this.loadingMore = true;
		this.$loadingMoreSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		var data = this.getLoadMoreParams();

		Craft.postActionRequest('elementIndex/getMoreElements', data, $.proxy(function(response, textStatus)
		{
			this.loadingMore = false;
			this.$loadingMoreSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				var $newElements = $(response.html);

				this.appendElements($newElements);
				Craft.appendHeadHtml(response.headHtml);
				Craft.appendFootHtml(response.footHtml);

				if (this.elementSelect)
				{
					this.elementSelect.addItems($newElements.filter(':not(.disabled)'));
					this.elementIndex.updateActionTriggers();
				}

				this.setTotalVisible(this.getTotalVisible() + $newElements.length);
				this.setMorePending($newElements.length == this.settings.batchSize);

				// Is there room to load more right now?
				this.addListener(this.$scroller, 'scroll', 'maybeLoadMore');
				this.maybeLoadMore();
			}

		}, this));
	},

	getLoadMoreParams: function()
	{
		// Use the same params that were passed when initializing this view
		var params = $.extend(true, {}, this.settings.params);
		params.criteria.offset = this.getTotalVisible();
		return params;
	},

	appendElements: function($newElements)
	{
		$newElements.appendTo(this.$elementContainer);
		this.onAppendElements($newElements);
	},

	onAppendElements: function($newElements)
	{
		this.settings.onAppendElements($newElements);
		this.trigger('appendElements', {
			newElements: $newElements
		});
	},

	onSelectionChange: function()
	{
		this.settings.onSelectionChange();
		this.trigger('selectionChange');
	},

	createElementEditor: function($element)
	{
		new Craft.ElementEditor($element);
	},

	disable: function()
	{
		if (this.elementSelect)
		{
			this.elementSelect.disable();
		}
	},

	enable: function()
	{
		if (this.elementSelect)
		{
			this.elementSelect.enable();
		}
	},

	destroy: function()
	{
		// Remove the "loading-more" spinner, since we added that outside of the view container
		this.$loadingMoreSpinner.remove();

		// Delete the element select
		if (this.elementSelect)
		{
			this.elementIndex.off('enableElements', this._handleEnableElements);
			this.elementIndex.off('disableElements', this._handleDisableElements);

			this.elementSelect.destroy();
			delete this.elementSelect;
		}

		this.base();
	}
},
{
	defaults: {
		context: 'index',
		batchSize: null,
		params: null,
		selectable: false,
		multiSelect: false,
		checkboxMode: false,
		onAppendElements: $.noop,
		onSelectionChange: $.noop
	},
});

/**
 * Element Select input
 */
Craft.BaseElementSelectInput = Garnish.Base.extend(
{
	elementSelect: null,
	elementSort: null,
	modal: null,
	elementEditor: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addElementBtn: null,

	_initialized: false,

	init: function(settings)
	{
		// Normalize the settings and set them
		// ---------------------------------------------------------------------

		// Are they still passing in a bunch of arguments?
		if (!$.isPlainObject(settings))
		{
			// Loop through all of the old arguments and apply them to the settings
			var normalizedSettings = {},
				args = ['id', 'name', 'elementType', 'sources', 'criteria', 'sourceElementId', 'limit', 'modalStorageKey', 'fieldId'];

			for (var i = 0; i < args.length; i++)
			{
				if (typeof arguments[i] != typeof undefined)
				{
					normalizedSettings[args[i]] = arguments[i];
				}
				else
				{
					break;
				}
			}

			settings = normalizedSettings;
		}

		this.setSettings(settings, Craft.BaseElementSelectInput.defaults);

		// Apply the storage key prefix
		if (this.settings.modalStorageKey)
		{
			this.modalStorageKey = 'BaseElementSelectInput.'+this.settings.modalStorageKey;
		}

		// No reason for this to be sortable if we're only allowing 1 selection
		if (this.settings.limit == 1)
		{
			this.settings.sortable = false;
		}

		this.$container = this.getContainer();

		// Store a reference to this class
		this.$container.data('elementSelect', this);

		this.$elementsContainer = this.getElementsContainer();
		this.$addElementBtn = this.getAddElementsBtn();

		if (this.$addElementBtn && this.settings.limit == 1)
		{
			this.$addElementBtn
				.css('position', 'absolute')
				.css('top', 0)
				.css(Craft.left, 0);
		}

		this.initElementSelect();
		this.initElementSort();
		this.resetElements();

		if (this.$addElementBtn)
		{
			this.addListener(this.$addElementBtn, 'activate', 'showModal');
		}

		this._initialized = true;
	},

	get totalSelected()
	{
		return this.$elements.length;
	},

	getContainer: function()
	{
		return $('#'+this.settings.id);
	},

	getElementsContainer: function()
	{
		return this.$container.children('.elements');
	},

	getElements: function()
	{
		return this.$elementsContainer.children();
	},

	getAddElementsBtn: function()
	{
		return this.$container.children('.btn.add');
	},

	initElementSelect: function()
	{
		if (this.settings.selectable)
		{
			this.elementSelect = new Garnish.Select({
				multi: this.settings.sortable,
				filter: ':not(.delete)'
			});
		}
	},

	initElementSort: function()
	{
		if (this.settings.sortable)
		{
			this.elementSort = new Garnish.DragSort({
				container: this.$elementsContainer,
				filter: (this.settings.selectable ? $.proxy(function()
				{
					// Only return all the selected items if the target item is selected
					if (this.elementSort.$targetItem.hasClass('sel'))
					{
						return this.elementSelect.getSelectedItems();
					}
					else
					{
						return this.elementSort.$targetItem;
					}
				}, this) : null),
				ignoreHandleSelector: '.delete',
				axis: this.getElementSortAxis(),
				collapseDraggees: true,
				magnetStrength: 4,
				helperLagBase: 1.5,
				onSortChange: (this.settings.selectable ? $.proxy(function() {
					this.elementSelect.resetItemOrder();
				}, this) : null)
			});
		}
	},

	getElementSortAxis: function()
	{
		return (this.settings.viewMode == 'list' ? 'y' : null);
	},

	canAddMoreElements: function()
	{
		return (!this.settings.limit || this.$elements.length < this.settings.limit);
	},

	updateAddElementsBtn: function()
	{
		if (this.canAddMoreElements())
		{
			this.enableAddElementsBtn();
		}
		else
		{
			this.disableAddElementsBtn();
		}
	},

	disableAddElementsBtn: function()
	{
		if (this.$addElementBtn && !this.$addElementBtn.hasClass('disabled'))
		{
			this.$addElementBtn.addClass('disabled');

			if (this.settings.limit == 1)
			{
				if (this._initialized)
				{
					this.$addElementBtn.velocity('fadeOut', Craft.BaseElementSelectInput.ADD_FX_DURATION);
				}
				else
				{
					this.$addElementBtn.hide();
				}
			}
		}
	},

	enableAddElementsBtn: function()
	{
		if (this.$addElementBtn && this.$addElementBtn.hasClass('disabled'))
		{
			this.$addElementBtn.removeClass('disabled');

			if (this.settings.limit == 1)
			{
				if (this._initialized)
				{
					this.$addElementBtn.velocity('fadeIn', Craft.BaseElementSelectInput.REMOVE_FX_DURATION);
				}
				else
				{
					this.$addElementBtn.show();
				}
			}
		}
	},

	resetElements: function()
	{
		this.$elements = $();
		this.addElements(this.getElements());
	},

	addElements: function($elements)
	{
		if (this.settings.selectable)
		{
			this.elementSelect.addItems($elements);
		}

		if (this.settings.sortable)
		{
			this.elementSort.addItems($elements);
		}

		if (this.settings.editable)
		{
			this._handleShowElementEditor = $.proxy(function(ev) {
				this.elementEditor = Craft.showElementEditor($(ev.currentTarget), this.settings.editorSettings);
			}, this);

			this.addListener($elements, 'dblclick', this._handleShowElementEditor);

			if($.isTouchCapable())
			{
				this.addListener($elements, 'taphold', this._handleShowElementEditor);
			}
		}

		$elements.find('.delete').on('click', $.proxy(function(ev)
		{
			this.removeElement($(ev.currentTarget).closest('.element'));
		}, this));

		this.$elements = this.$elements.add($elements);
		this.updateAddElementsBtn();
	},

	removeElements: function($elements)
	{
		if (this.settings.selectable)
		{
			this.elementSelect.removeItems($elements);
		}

		if (this.modal)
		{
			var ids = [];

			for (var i = 0; i < $elements.length; i++)
			{
				var id = $elements.eq(i).data('id');

				if (id)
				{
					ids.push(id);
				}
			}

			if (ids.length)
			{
				this.modal.elementIndex.enableElementsById(ids);
			}
		}

		// Disable the hidden input in case the form is submitted before this element gets removed from the DOM
		$elements.children('input').prop('disabled', true);

		this.$elements = this.$elements.not($elements);
		this.updateAddElementsBtn();

		this.onRemoveElements();
	},

	removeElement: function($element)
	{
		this.removeElements($element);
		this.animateElementAway($element, function() {
			$element.remove();
		});
	},

	animateElementAway: function($element, callback)
	{
		$element.css('z-index', 0);

		var animateCss = {
			opacity: -1
		};
		animateCss['margin-'+Craft.left] = -($element.outerWidth() + parseInt($element.css('margin-'+Craft.right)));

		if (this.settings.viewMode == 'list' || this.$elements.length == 0)
		{
			animateCss['margin-bottom'] = -($element.outerHeight() + parseInt($element.css('margin-bottom')));
		}

		$element.velocity(animateCss, Craft.BaseElementSelectInput.REMOVE_FX_DURATION, callback);
	},

	showModal: function()
	{
		// Make sure we haven't reached the limit
		if (!this.canAddMoreElements())
		{
			return;
		}

		if (!this.modal)
		{
			this.modal = this.createModal();
		}
		else
		{
			this.modal.show();
		}
	},

	createModal: function()
	{
		return Craft.createElementSelectorModal(this.settings.elementType, this.getModalSettings());
	},

	getModalSettings: function()
	{
		return $.extend({
			closeOtherModals:   false,
			storageKey:         this.modalStorageKey,
			sources:            this.settings.sources,
			criteria:           this.settings.criteria,
			multiSelect:        (this.settings.limit != 1),
			showLocaleMenu:     this.settings.showLocaleMenu,
			disabledElementIds: this.getDisabledElementIds(),
			onSelect:           $.proxy(this, 'onModalSelect')
		}, this.settings.modalSettings);
	},

	getSelectedElementIds: function()
	{
		var ids = [];

		for (var i = 0; i < this.$elements.length; i++)
		{
			ids.push(this.$elements.eq(i).data('id'));
		}

		return ids;
	},

	getDisabledElementIds: function()
	{
		var ids = this.getSelectedElementIds();

		if (this.settings.sourceElementId)
		{
			ids.push(this.settings.sourceElementId);
		}

		return ids;
	},

	onModalSelect: function(elements)
	{
		if (this.settings.limit)
		{
			// Cut off any excess elements
			var slotsLeft = this.settings.limit - this.$elements.length;

			if (elements.length > slotsLeft)
			{
				elements = elements.slice(0, slotsLeft);
			}
		}

		this.selectElements(elements);
		this.updateDisabledElementsInModal();
	},

	selectElements: function(elements)
	{
		for (var i = 0; i < elements.length; i++)
		{
			var elementInfo = elements[i],
				$element = this.createNewElement(elementInfo);

			this.appendElement($element);
			this.addElements($element);
			this.animateElementIntoPlace(elementInfo.$element, $element);
		}

		this.onSelectElements(elements);
	},

	createNewElement: function(elementInfo)
	{
		var $element = elementInfo.$element.clone();

		// Make a couple tweaks
		Craft.setElementSize($element, (this.settings.viewMode == 'large' ? 'large' : 'small'));
		$element.addClass('removable');
		$element.prepend('<input type="hidden" name="'+this.settings.name+'[]" value="'+elementInfo.id+'">' +
			'<a class="delete icon" title="'+Craft.t('Remove')+'"></a>');

		return $element;
	},

	appendElement: function($element)
	{
		$element.appendTo(this.$elementsContainer);
	},

	animateElementIntoPlace: function($modalElement, $inputElement)
	{
		var origOffset = $modalElement.offset(),
			destOffset = $inputElement.offset(),
			$helper = $inputElement.clone().appendTo(Garnish.$bod);

		$inputElement.css('visibility', 'hidden');

		$helper.css({
			position: 'absolute',
			zIndex: 10000,
			top: origOffset.top,
			left: origOffset.left
		});

		var animateCss = {
			top: destOffset.top,
			left: destOffset.left
		};

		$helper.velocity(animateCss, Craft.BaseElementSelectInput.ADD_FX_DURATION, function() {
			$helper.remove();
			$inputElement.css('visibility', 'visible');
		});
	},

	updateDisabledElementsInModal: function()
	{
		if (this.modal.elementIndex)
		{
			this.modal.elementIndex.disableElementsById(this.getDisabledElementIds());
		}
	},

	getElementById: function(id)
	{
		for (var i = 0; i < this.$elements.length; i++)
		{
			var $element = this.$elements.eq(i);

			if ($element.data('id') == id)
			{
				return $element;
			}
		}
	},

	onSelectElements: function(elements)
	{
		this.trigger('selectElements', { elements: elements });
		this.settings.onSelectElements(elements);
	},

	onRemoveElements: function()
	{
		this.trigger('removeElements');
		this.settings.onRemoveElements();
	}
},
{
	ADD_FX_DURATION: 200,
	REMOVE_FX_DURATION: 200,

	defaults: {
		id: null,
		name: null,
		fieldId: null,
		elementType: null,
		sources: null,
		criteria: {},
		sourceElementId: null,
		viewMode: 'list',
		limit: null,
		showLocaleMenu: false,
		modalStorageKey: null,
		modalSettings: {},
		onSelectElements: $.noop,
		onRemoveElements: $.noop,
		sortable: true,
		selectable: true,
		editable: true,
		editorSettings: {}
	}
});

/**
 * Element selector modal class
 */
Craft.BaseElementSelectorModal = Garnish.Modal.extend(
{
	elementType: null,
	elementIndex: null,

	$body: null,
	$sidebar: null,
	$sources: null,
	$sourceToggles: null,
	$main: null,
	$search: null,
	$elements: null,
	$tbody: null,
	$primaryButtons: null,
	$secondaryButtons: null,
	$cancelBtn: null,
	$selectBtn: null,
	$footerSpinner: null,

	init: function(elementType, settings)
	{
		this.elementType = elementType;
		this.setSettings(settings, Craft.BaseElementSelectorModal.defaults);

		// Build the modal
		var $container = $('<div class="modal elementselectormodal"></div>').appendTo(Garnish.$bod),
			$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo($container),
			$footer = $('<div class="footer"/>').appendTo($container);

		this.base($container, this.settings);

		this.$footerSpinner = $('<div class="spinner hidden"/>').appendTo($footer);
		this.$primaryButtons = $('<div class="buttons right"/>').appendTo($footer);
		this.$secondaryButtons = $('<div class="buttons left secondary-buttons"/>').appendTo($footer);
		this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo(this.$primaryButtons);
		this.$selectBtn = $('<div class="btn disabled submit">'+Craft.t('Select')+'</div>').appendTo(this.$primaryButtons);

		this.$body = $body;

		this.addListener(this.$cancelBtn, 'activate', 'cancel');
		this.addListener(this.$selectBtn, 'activate', 'selectElements');
	},

	onFadeIn: function()
	{
		if (!this.elementIndex)
		{
			this._createElementIndex();
		}
		else
		{
			// Auto-focus the Search box
			if (!Garnish.isMobileBrowser(true))
			{
				this.elementIndex.$search.focus();
			}
		}

		this.base();
	},

	onSelectionChange: function()
	{
		this.updateSelectBtnState();
	},

	updateSelectBtnState: function()
	{
		if (this.$selectBtn)
		{
			if (this.elementIndex.getSelectedElements().length)
			{
				this.enableSelectBtn();
			}
			else
			{
				this.disableSelectBtn();
			}
		}
	},

	enableSelectBtn: function()
	{
		this.$selectBtn.removeClass('disabled');
	},

	disableSelectBtn: function()
	{
		this.$selectBtn.addClass('disabled');
	},

	enableCancelBtn: function()
	{
		this.$cancelBtn.removeClass('disabled');
	},

	disableCancelBtn: function()
	{
		this.$cancelBtn.addClass('disabled');
	},

	showFooterSpinner: function()
	{
		this.$footerSpinner.removeClass('hidden');
	},

	hideFooterSpinner: function()
	{
		this.$footerSpinner.addClass('hidden');
	},

	cancel: function()
	{
		if (!this.$cancelBtn.hasClass('disabled'))
		{
			this.hide();
		}
	},

	selectElements: function()
	{
		if (this.elementIndex && this.elementIndex.getSelectedElements().length)
		{
			// TODO: This code shouldn't know about views' elementSelect objects
			this.elementIndex.view.elementSelect.clearMouseUpTimeout();

			var $selectedElements = this.elementIndex.getSelectedElements(),
				elementInfo = this.getElementInfo($selectedElements);

			this.onSelect(elementInfo);

			if (this.settings.disableElementsOnSelect)
			{
				this.elementIndex.disableElements(this.elementIndex.getSelectedElements());
			}

			if (this.settings.hideOnSelect)
			{
				this.hide();
			}
		}
	},

	getElementInfo: function($selectedElements)
	{
		var info = [];

		for (var i = 0; i < $selectedElements.length; i++)
		{
			var $element = $($selectedElements[i]);

			info.push(Craft.getElementInfo($element));
		}

		return info;
	},

	show: function()
	{
		this.updateSelectBtnState();
		this.base();
	},

	onSelect: function(elementInfo)
	{
		this.settings.onSelect(elementInfo);
	},

	disable: function()
	{
		if (this.elementIndex)
		{
			this.elementIndex.disable();
		}

		this.base();
	},

	enable: function()
	{
		if (this.elementIndex)
		{
			this.elementIndex.enable();
		}

		this.base();
	},

	_createElementIndex: function()
	{
		// Get the modal body HTML based on the settings
		var data = {
			context:     'modal',
			elementType: this.elementType,
			sources:     this.settings.sources
		};

		if (this.settings.showLocaleMenu !== null && this.settings.showLocaleMenu != 'auto') {
			data.showLocaleMenu = this.settings.showLocaleMenu ? '1' : '0';
		}

		Craft.postActionRequest('elements/getModalBody', data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				this.$body.html(response);

				if (this.$body.has('.sidebar:not(.hidden)').length)
				{
					this.$body.addClass('has-sidebar');
				}

				// Initialize the element index
				this.elementIndex = Craft.createElementIndex(this.elementType, this.$body, {
					context:            'modal',
					modal:              this,
					storageKey:         this.settings.storageKey,
					criteria:           this.settings.criteria,
					disabledElementIds: this.settings.disabledElementIds,
					selectable:         true,
					multiSelect:        this.settings.multiSelect,
					buttonContainer:    this.$secondaryButtons,
					onSelectionChange:  $.proxy(this, 'onSelectionChange')
				});

				// Double-clicking or double-tapping should select the elements
				this.addListener(this.elementIndex.$elements, 'doubletap', function(ev, touchData) {
					// Make sure the touch targets are the same
					// (they may be different if Command/Ctrl/Shift-clicking on multiple elements quickly)
					if (touchData.firstTap.target === touchData.secondTap.target)
					{
						this.selectElements();
					}
				});
			}

		}, this));
	}
},
{
	defaults: {
		resizable: true,
		storageKey: null,
		sources: null,
		criteria: null,
		multiSelect: false,
		showLocaleMenu: null,
		disabledElementIds: [],
		disableElementsOnSelect: false,
		hideOnSelect: true,
		onCancel: $.noop,
		onSelect: $.noop
	}
});

/**
 * Input Generator
 */
Craft.BaseInputGenerator = Garnish.Base.extend(
{
	$source: null,
	$target: null,
	$form: null,
	settings: null,

	listening: null,
	timeout: null,

	init: function(source, target, settings)
	{
		this.$source = $(source);
		this.$target = $(target);
		this.$form = this.$source.closest('form');

		this.setSettings(settings);

		this.startListening();
	},

	setNewSource: function(source)
	{
		var listening = this.listening;
		this.stopListening();

		this.$source = $(source);

		if (listening)
		{
			this.startListening();
		}
	},

	startListening: function()
	{
		if (this.listening)
		{
			return;
		}

		this.listening = true;

		this.addListener(this.$source, 'textchange', 'onTextChange');
		this.addListener(this.$form, 'submit', 'onFormSubmit');

		this.addListener(this.$target, 'focus', function() {
			this.addListener(this.$target, 'textchange', 'stopListening');
			this.addListener(this.$target, 'blur', function() {
				this.removeListener(this.$target, 'textchange,blur');
			});
		});
	},

	stopListening: function()
	{
		if (!this.listening)
		{
			return;
		}

		this.listening = false;

		this.removeAllListeners(this.$source);
		this.removeAllListeners(this.$target);
		this.removeAllListeners(this.$form);
	},

	onTextChange: function()
	{
		if (this.timeout)
		{
			clearTimeout(this.timeout);
		}

		this.timeout = setTimeout($.proxy(this, 'updateTarget'), 250);
	},

	onFormSubmit: function()
	{
		if (this.timeout)
		{
			clearTimeout(this.timeout);
		}

		this.updateTarget();
	},

	updateTarget: function()
	{
		var sourceVal = this.$source.val(),
			targetVal = this.generateTargetValue(sourceVal);

		this.$target.val(targetVal);
		this.$target.trigger('textchange');
	},

	generateTargetValue: function(sourceVal)
	{
		return sourceVal;
	}
});

/**
 * Admin table class
 */
Craft.AdminTable = Garnish.Base.extend(
{
	settings: null,
	totalObjects: null,
	sorter: null,

	$noObjects: null,
	$table: null,
	$tbody: null,
	$deleteBtns: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.AdminTable.defaults);

		if (!this.settings.allowDeleteAll)
		{
			this.settings.minObjects = 1;
		}

		this.$noObjects = $(this.settings.noObjectsSelector);
		this.$table = $(this.settings.tableSelector);
		this.$tbody  = this.$table.children('tbody');
		this.totalObjects = this.$tbody.children().length;

		if (this.settings.sortable)
		{
			this.sorter = new Craft.DataTableSorter(this.$table, {
				onSortChange: $.proxy(this, 'reorderObjects')
			});
		}

		this.$deleteBtns = this.$table.find('.delete');
		this.addListener(this.$deleteBtns, 'click', 'handleDeleteBtnClick');

		this.updateUI();
	},

	addRow: function(row)
	{
		if (this.settings.maxObjects && this.totalObjects >= this.settings.maxObjects)
		{
			// Sorry pal.
			return;
		}

		var $row = $(row).appendTo(this.$tbody),
			$deleteBtn = $row.find('.delete');

		if (this.settings.sortable)
		{
			this.sorter.addItems($row);
		}

		this.$deleteBtns = this.$deleteBtns.add($deleteBtn);

		this.addListener($deleteBtn, 'click', 'handleDeleteBtnClick');
		this.totalObjects++;

		this.updateUI();
	},

	reorderObjects: function()
	{
		if (!this.settings.sortable)
		{
			return false;
		}

		// Get the new field order
		var ids = [];

		for (var i = 0; i < this.sorter.$items.length; i++)
		{
			var id = $(this.sorter.$items[i]).attr(this.settings.idAttribute);
			ids.push(id);
		}

		// Send it to the server
		var data = {
			ids: JSON.stringify(ids)
		};

		Craft.postActionRequest(this.settings.reorderAction, data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.onReorderObjects(ids);
					Craft.cp.displayNotice(Craft.t(this.settings.reorderSuccessMessage));
				}
				else
				{
					Craft.cp.displayError(Craft.t(this.settings.reorderFailMessage));
				}
			}

		}, this));
	},

	handleDeleteBtnClick: function(event)
	{
		if (this.settings.minObjects && this.totalObjects <= this.settings.minObjects)
		{
			// Sorry pal.
			return;
		}

		var $row = $(event.target).closest('tr');

		if (this.confirmDeleteObject($row))
		{
			this.deleteObject($row);
		}
	},

	confirmDeleteObject: function($row)
	{
		var name = this.getObjectName($row);
		return confirm(Craft.t(this.settings.confirmDeleteMessage, { name: name }));
	},

	deleteObject: function($row)
	{
		var data = {
			id: this.getObjectId($row)
		};

		Craft.postActionRequest(this.settings.deleteAction, data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				this.handleDeleteObjectResponse(response, $row);
			}
		}, this));
	},

	handleDeleteObjectResponse: function(response, $row)
	{
		var id = this.getObjectId($row),
			name = this.getObjectName($row);

		if (response.success)
		{
			if (this.sorter)
			{
				this.sorter.removeItems($row);
			}

			$row.remove();
			this.totalObjects--;
			this.updateUI();
			this.onDeleteObject(id);

			Craft.cp.displayNotice(Craft.t(this.settings.deleteSuccessMessage, { name: name }));
		}
		else
		{
			Craft.cp.displayError(Craft.t(this.settings.deleteFailMessage, { name: name }));
		}
	},

	onReorderObjects: function(ids)
	{
		this.settings.onReorderObjects(ids);
	},

	onDeleteObject: function(id)
	{
		this.settings.onDeleteObject(id);
	},

	getObjectId: function($row)
	{
		return $row.attr(this.settings.idAttribute);
	},

	getObjectName: function($row)
	{
		return $row.attr(this.settings.nameAttribute);
	},

	updateUI: function()
	{
		// Show the "No Whatever Exists" message if there aren't any
		if (this.totalObjects == 0)
		{
			this.$table.hide();
			this.$noObjects.removeClass('hidden');
		}
		else
		{
			this.$table.show();
			this.$noObjects.addClass('hidden');
		}

		// Disable the sort buttons if there's only one row
		if (this.settings.sortable)
		{
			var $moveButtons = this.$table.find('.move');

			if (this.totalObjects == 1)
			{
				$moveButtons.addClass('disabled');
			}
			else
			{
				$moveButtons.removeClass('disabled');
			}
		}

		// Disable the delete buttons if we've reached the minimum objects
		if (this.settings.minObjects && this.totalObjects <= this.settings.minObjects)
		{
			this.$deleteBtns.addClass('disabled');
		}
		else
		{
			this.$deleteBtns.removeClass('disabled');
		}

		// Hide the New Whatever button if we've reached the maximum objects
		if (this.settings.newObjectBtnSelector)
		{
			if (this.settings.maxObjects && this.totalObjects >= this.settings.maxObjects)
			{
				$(this.settings.newObjectBtnSelector).addClass('hidden');
			}
			else
			{
				$(this.settings.newObjectBtnSelector).removeClass('hidden');
			}
		}
	}
},
{
	defaults: {
		tableSelector: null,
		noObjectsSelector: null,
		newObjectBtnSelector: null,
		idAttribute: 'data-id',
		nameAttribute: 'data-name',
		sortable: false,
		allowDeleteAll: true,
		minObjects: 0,
		maxObjects: null,
		reorderAction: null,
		deleteAction: null,
		reorderSuccessMessage: Craft.t('New order saved.'),
		reorderFailMessage:    Craft.t('Couldn’t save new order.'),
		confirmDeleteMessage:  Craft.t('Are you sure you want to delete “{name}”?'),
		deleteSuccessMessage:  Craft.t('“{name}” deleted.'),
		deleteFailMessage:     Craft.t('Couldn’t delete “{name}”.'),
		onReorderObjects: $.noop,
		onDeleteObject: $.noop
	}
});

/**
 * Asset index class
 */
Craft.AssetIndex = Craft.BaseElementIndex.extend(
{
	$includeSubfoldersContainer: null,
	$includeSubfoldersCheckbox: null,
	showingIncludeSubfoldersCheckbox: false,

	$uploadButton: null,
	$uploadInput: null,
	$progressBar: null,
	$folders: null,

	uploader: null,
	promptHandler: null,
	progressBar: null,

	_uploadTotalFiles: 0,
	_uploadFileProgress: {},
	_uploadedFileIds: [],
	_currentUploaderSettings: {},

	_fileDrag: null,
	_folderDrag: null,
	_expandDropTargetFolderTimeout: null,
	_tempExpandedFolders: [],

	init: function(elementType, $container, settings)
	{
		this.base(elementType, $container, settings);

		if (this.settings.context == 'index')
		{
			this._initIndexPageMode();
			this.addListener(Garnish.$win, 'resize,scroll', '_positionProgressBar');
		}
		else
		{
			this.addListener(this.$main, 'scroll', '_positionProgressBar');

			if (this.settings.modal) {
				this.settings.modal.on('updateSizeAndPosition', $.proxy(this, '_positionProgressBar'));
			}
		}
	},

	initSource: function($source)
	{
		this.base($source);

		this._createFolderContextMenu($source);

		if (this.settings.context == 'index')
		{
			if (this._folderDrag && this._getSourceLevel($source) > 1)
			{
				if (this._getFolderIdFromSourceKey($source.data('key'))) {
					this._folderDrag.addItems($source.parent());
				}
			}

			if (this._fileDrag)
			{
				this._fileDrag.updateDropTargets();
			}
		}
	},

	deinitSource: function($source)
	{
		this.base($source);

		// Does this source have a context menu?
		var contextMenu = $source.data('contextmenu');

		if (contextMenu)
		{
			contextMenu.destroy();
		}

		if (this.settings.context == 'index')
		{
			if (this._folderDrag && this._getSourceLevel($source) > 1)
			{
				this._folderDrag.removeItems($source.parent());
			}

			if (this._fileDrag)
			{
				this._fileDrag.updateDropTargets();
			}
		}
	},

	_getSourceLevel: function($source)
	{
		return $source.parentsUntil('nav', 'ul').length;
	},

	/**
	 * Initialize the index page-specific features
	 */
	_initIndexPageMode: function()
	{
		// Make the elements selectable
		this.settings.selectable = true;
		this.settings.multiSelect = true;

		var onDragStartProxy = $.proxy(this, '_onDragStart'),
			onDropTargetChangeProxy = $.proxy(this, '_onDropTargetChange');

		// File dragging
		// ---------------------------------------------------------------------

		this._fileDrag = new Garnish.DragDrop({
			activeDropTargetClass: 'sel',
			helperOpacity: 0.75,

			filter: $.proxy(function()
			{
				return this.view.getSelectedElements();
			}, this),

			helper: $.proxy(function($file)
			{
				return this._getFileDragHelper($file);
			}, this),

			dropTargets: $.proxy(function()
			{
				var targets = [];

				for (var i = 0; i < this.$sources.length; i++)
				{
					if (!this._getFolderIdFromSourceKey(this.$sources.eq(i).data('key'))) {
						continue;
					}
					targets.push($(this.$sources[i]));
				}

				return targets;
			}, this),

			onDragStart: onDragStartProxy,
			onDropTargetChange: onDropTargetChangeProxy,
			onDragStop: $.proxy(this, '_onFileDragStop')
		});

		// Folder dragging
		// ---------------------------------------------------------------------

		this._folderDrag = new Garnish.DragDrop(
		{
			activeDropTargetClass: 'sel',
			helperOpacity: 0.75,

			filter: $.proxy(function()
			{
				// Return each of the selected <a>'s parent <li>s, except for top level drag attempts.
				var $selected = this.sourceSelect.getSelectedItems(),
					draggees = [];

				for (var i = 0; i < $selected.length; i++)
				{
					var	$source = $selected.eq(i);

					if (!this._getFolderIdFromSourceKey($source.data('key'))) {
						continue;
					}

					if ($source.hasClass('sel') && this._getSourceLevel($source) > 1)
					{
						draggees.push($source.parent()[0]);
					}
				}

				return $(draggees);
			}, this),

			helper: $.proxy(function($draggeeHelper)
			{
				var $helperSidebar = $('<div class="sidebar" style="padding-top: 0; padding-bottom: 0;"/>'),
					$helperNav = $('<nav/>').appendTo($helperSidebar),
					$helperUl = $('<ul/>').appendTo($helperNav);

				$draggeeHelper.appendTo($helperUl).removeClass('expanded');
				$draggeeHelper.children('a').addClass('sel');

				// Match the style
				$draggeeHelper.css({
					'padding-top':    this._folderDrag.$draggee.css('padding-top'),
					'padding-right':  this._folderDrag.$draggee.css('padding-right'),
					'padding-bottom': this._folderDrag.$draggee.css('padding-bottom'),
					'padding-left':   this._folderDrag.$draggee.css('padding-left')
				});

				return $helperSidebar;
			}, this),

			dropTargets: $.proxy(function()
			{
				var targets = [];

				// Tag the dragged folder and it's subfolders
				var draggedSourceIds = [];
				this._folderDrag.$draggee.find('a[data-key]').each(function()
				{
					draggedSourceIds.push($(this).data('key'));
				});

				for (var i = 0; i < this.$sources.length; i++)
				{
					var $source = this.$sources.eq(i);

					if (!this._getFolderIdFromSourceKey($source.data('key'))) {
						continue;
					}

					if (!Craft.inArray($source.data('key'), draggedSourceIds))
					{
						targets.push($source);
					}
				}

				return targets;
			}, this),

			onDragStart: onDragStartProxy,
			onDropTargetChange: onDropTargetChangeProxy,
			onDragStop: $.proxy(this, '_onFolderDragStop')
		});
	},

	/**
	 * On file drag stop
	 */
	_onFileDragStop: function()
	{
		if (this._fileDrag.$activeDropTarget && this._fileDrag.$activeDropTarget[0] != this.$source[0])
		{
			// Keep it selected
			var originatingSource = this.$source;

			var targetFolderId = this._getFolderIdFromSourceKey(this._fileDrag.$activeDropTarget.data('key')),
				originalFileIds = [],
				newFileNames = [];

			// For each file, prepare array data.
			for (var i = 0; i < this._fileDrag.$draggee.length; i++)
			{
				var originalFileId = Craft.getElementInfo(this._fileDrag.$draggee[i]).id,
					fileName = Craft.getElementInfo(this._fileDrag.$draggee[i]).url.split('/').pop();

				if (fileName.indexOf('?') !== -1)
				{
					fileName = fileName.split('?').shift();
				}

				originalFileIds.push(originalFileId);
				newFileNames.push(fileName);
			}

			// Are any files actually getting moved?
			if (originalFileIds.length)
			{
				this.setIndexBusy();

				this._positionProgressBar();
				this.progressBar.resetProgressBar();
				this.progressBar.setItemCount(originalFileIds.length);
				this.progressBar.showProgressBar();


				// For each file to move a separate request
				var parameterArray = [];
				for (i = 0; i < originalFileIds.length; i++)
				{
					parameterArray.push({
						fileId: originalFileIds[i],
						folderId: targetFolderId,
						fileName: newFileNames[i]
					});
				}

				// Define the callback for when all file moves are complete
				var onMoveFinish = $.proxy(function(responseArray)
				{
					this.promptHandler.resetPrompts();

					// Loop trough all the responses
					for (var i = 0; i < responseArray.length; i++)
					{
						var data = responseArray[i];

						// Push prompt into prompt array
						if (data.prompt)
						{
							this.promptHandler.addPrompt(data);
						}

						if (data.error)
						{
							alert(data.error);
						}
					}

					this.setIndexAvailable();
					this.progressBar.hideProgressBar();
					var reloadIndex = false;

					var performAfterMoveActions = function ()
					{
						// Select original source
						this.sourceSelect.selectItem(originatingSource);

						// Make sure we use the correct offset when fetching the next page
						this._totalVisible -= this._fileDrag.$draggee.length;

						// And remove the elements that have been moved away
						for (var i = 0; i < originalFileIds.length; i++)
						{
							$('[data-id=' + originalFileIds[i] + ']').remove();
						}

						this.view.deselectAllElements();
						this._collapseExtraExpandedFolders(targetFolderId);

						if (reloadIndex)
						{
							this.updateElements();
						}
					};

					if (this.promptHandler.getPromptCount())
					{
						// Define callback for completing all prompts
						var promptCallback = $.proxy(function(returnData)
						{
							var newParameterArray = [];

							// Loop trough all returned data and prepare a new request array
							for (var i = 0; i < returnData.length; i++)
							{
								if (returnData[i].choice == 'cancel')
								{
									reloadIndex = true;
									continue;
								}

								// Find the matching request parameters for this file and modify them slightly
								for (var ii = 0; ii < parameterArray.length; ii++)
								{
									if (parameterArray[ii].fileName == returnData[i].fileName)
									{
										parameterArray[ii].action = returnData[i].choice;
										newParameterArray.push(parameterArray[ii]);
									}
								}
							}

							// Nothing to do, carry on
							if (newParameterArray.length == 0)
							{
								performAfterMoveActions.apply(this);
							}
							else
							{
								// Start working
								this.setIndexBusy();
								this.progressBar.resetProgressBar();
								this.progressBar.setItemCount(this.promptHandler.getPromptCount());
								this.progressBar.showProgressBar();

								// Move conflicting files again with resolutions now
								this._moveFile(newParameterArray, 0, onMoveFinish);
							}
						}, this);

						this._fileDrag.fadeOutHelpers();
						this.promptHandler.showBatchPrompts(promptCallback);
					}
					else
					{
						performAfterMoveActions.apply(this);
						this._fileDrag.fadeOutHelpers();
					}
				}, this);

				// Initiate the file move with the built array, index of 0 and callback to use when done
				this._moveFile(parameterArray, 0, onMoveFinish);

				// Skip returning dragees
				return;
			}
		}
		else
		{
			// Add the .sel class back on the selected source
			this.$source.addClass('sel');

			this._collapseExtraExpandedFolders();
		}

		this._fileDrag.returnHelpersToDraggees();
	},

	/**
	 * On folder drag stop
	 */
	_onFolderDragStop: function()
	{
		// Only move if we have a valid target and we're not trying to move into our direct parent
		if (
			this._folderDrag.$activeDropTarget &&
			this._folderDrag.$activeDropTarget.siblings('ul').children('li').filter(this._folderDrag.$draggee).length == 0
		)
		{
			var targetFolderId = this._getFolderIdFromSourceKey(this._folderDrag.$activeDropTarget.data('key'));

			this._collapseExtraExpandedFolders(targetFolderId);

			// Get the old folder IDs, and sort them so that we're moving the most-nested folders first
			var folderIds = [];

			for (var i = 0; i < this._folderDrag.$draggee.length; i++)
			{
				var $a = this._folderDrag.$draggee.eq(i).children('a'),
					folderId = this._getFolderIdFromSourceKey($a.data('key')),
					$source = this._getSourceByFolderId(folderId);

				// Make sure it's not already in the target folder
				if (this._getFolderIdFromSourceKey(this._getParentSource($source).data('key')) != targetFolderId)
				{
					folderIds.push(folderId);
				}
			}

			if (folderIds.length)
			{
				folderIds.sort();
				folderIds.reverse();

				this.setIndexBusy();
				this._positionProgressBar();
				this.progressBar.resetProgressBar();
				this.progressBar.setItemCount(folderIds.length);
				this.progressBar.showProgressBar();

				var responseArray = [];
				var parameterArray = [];

				for (var i = 0; i < folderIds.length; i++)
				{
					parameterArray.push({
						folderId: folderIds[i],
						parentId: targetFolderId
					});
				}

				// Increment, so to avoid displaying folder files that are being moved
				this.requestId++;

				/*
				 Here's the rundown:
				 1) Send all the folders being moved
				 2) Get results:
				   a) For all conflicting, receive prompts and resolve them to get:
				   b) For all valid move operations: by now server has created the needed folders
					  in target destination. Server returns an array of file move operations
				   c) server also returns a list of all the folder id changes
				   d) and the data-id of node to be removed, in case of conflict
				   e) and a list of folders to delete after the move
				 3) From data in 2) build a large file move operation array
				 4) Create a request loop based on this, so we can display progress bar
				 5) when done, delete all the folders and perform other maintenance
				 6) Champagne
				 */

				// This will hold the final list of files to move
				var fileMoveList = [];

				// These folders have to be deleted at the end
				var folderDeleteList = [];

				// This one tracks the changed folder ids
				var changedFolderIds = {};

				var removeFromTree = [];

				var onMoveFinish = $.proxy(function(responseArray)
				{
					this.promptHandler.resetPrompts();

					// Loop trough all the responses
					for (var i = 0; i < responseArray.length; i++)
					{
						var data = responseArray[i];

						// If succesful and have data, then update
						if (data.success)
						{
							if (data.transferList && data.deleteList && data.changedFolderIds)
							{
								for (var ii = 0; ii < data.transferList.length; ii++)
								{
									fileMoveList.push(data.transferList[ii]);
								}

								for (var ii = 0; ii < data.deleteList.length; ii++)
								{
									folderDeleteList.push(data.deleteList[ii]);
								}

								for (var oldFolderId in data.changedFolderIds)
								{
									changedFolderIds[oldFolderId] = data.changedFolderIds[oldFolderId];
								}

								removeFromTree.push(data.removeFromTree);
							}
						}

						// Push prompt into prompt array
						if (data.prompt)
						{
							this.promptHandler.addPrompt(data);
						}

						if (data.error)
						{
							alert(data.error);
						}
					}

					if (this.promptHandler.getPromptCount())
					{
						// Define callback for completing all prompts
						var promptCallback = $.proxy(function(returnData)
						{
							this.promptHandler.resetPrompts();
							this.setNewElementDataHtml('');

							var newParameterArray = [];

							// Loop trough all returned data and prepare a new request array
							for (var i = 0; i < returnData.length; i++)
							{
								if (returnData[i].choice == 'cancel')
								{
									continue;
								}

								parameterArray[0].action = returnData[i].choice;
								newParameterArray.push(parameterArray[0]);
							}

							// Start working on them lists, baby
							if (newParameterArray.length == 0)
							{
								$.proxy(this, '_performActualFolderMove', fileMoveList, folderDeleteList, changedFolderIds, removeFromTree)();
							}
							else
							{
								// Start working
								this.setIndexBusy();
								this.progressBar.resetProgressBar();
								this.progressBar.setItemCount(this.promptHandler.getPromptCount());
								this.progressBar.showProgressBar();

								// Move conflicting files again with resolutions now
								moveFolder(newParameterArray, 0, onMoveFinish);
							}
						}, this);

						this.promptHandler.showBatchPrompts(promptCallback);

						this.setIndexAvailable();
						this.progressBar.hideProgressBar();
					}
					else
					{
						$.proxy(this, '_performActualFolderMove', fileMoveList, folderDeleteList, changedFolderIds, removeFromTree, targetFolderId)();
					}
				}, this);

				var moveFolder = $.proxy(function(parameterArray, parameterIndex, callback)
				{
					if (parameterIndex == 0)
					{
						responseArray = [];
					}

					Craft.postActionRequest('assets/moveFolder', parameterArray[parameterIndex], $.proxy(function(data, textStatus)
					{
						parameterIndex++;
						this.progressBar.incrementProcessedItemCount(1);
						this.progressBar.updateProgressBar();

						if (textStatus == 'success')
						{
							responseArray.push(data);
						}

						if (parameterIndex >= parameterArray.length)
						{
							callback(responseArray);
						}
						else
						{
							moveFolder(parameterArray, parameterIndex, callback);
						}
					}, this));
				}, this);

				// Initiate the folder move with the built array, index of 0 and callback to use when done
				moveFolder(parameterArray, 0, onMoveFinish);

				// Skip returning dragees until we get the Ajax response
				return;
			}
		}
		else
		{
			// Add the .sel class back on the selected source
			this.$source.addClass('sel');

			this._collapseExtraExpandedFolders();
		}

		this._folderDrag.returnHelpersToDraggees();
	},

	/**
	 * Really move the folder. Like really. For real.
	 */
	_performActualFolderMove: function(fileMoveList, folderDeleteList, changedFolderIds, removeFromTree, targetFolderId)
	{
		this.setIndexBusy();
		this.progressBar.resetProgressBar();
		this.progressBar.setItemCount(1);
		this.progressBar.showProgressBar();

		var moveCallback = $.proxy(function(folderDeleteList, changedFolderIds, removeFromTree)
		{
			//Move the folders around in the tree
			var $topFolderLi,
				$folderToMove;

			// Change the folder ids
			for (var previousFolderId in changedFolderIds)
			{
				$folderToMove = this._getSourceByFolderId(previousFolderId);

				// Change the folder ID
				$folderToMove
					.attr('data-key', 'folder:' + changedFolderIds[previousFolderId].newId)
					.data('key', 'folder:' + changedFolderIds[previousFolderId].newId);

				// Select the containing element as the folder element
				$folderToMove = $folderToMove.parent();

				if (!$topFolderLi || $topFolderLi.parents().filter($folderToMove).length > 0)
				{
					$topFolderLi = $folderToMove;
					topFolderMovedId = changedFolderIds[previousFolderId].newId;
				}
			}

			if ($topFolderLi.length == 0)
			{
				this.setIndexAvailable();
				this.progressBar.hideProgressBar();
				this._folderDrag.returnHelpersToDraggees();

				return;
			}

			var topFolder = $topFolderLi.children('a');

			// Now move the uppermost node.
			var siblings = $topFolderLi.siblings('ul, .toggle');
			var parentSource = this._getParentSource(topFolder);

			var newParent = this._getSourceByFolderId(targetFolderId);
			this._prepareParentForChildren(newParent);
			this._appendSubfolder(newParent, $topFolderLi);

			topFolder.after(siblings);

			this._cleanUpTree(parentSource);
			this.$sidebar.find('ul>ul, ul>.toggle').remove();

			// Delete the old folders
			for (var i = 0; i < folderDeleteList.length; i++)
			{
				Craft.postActionRequest('assets/deleteFolder', {folderId: folderDeleteList[i]});
			}

			this.setIndexAvailable();
			this.progressBar.hideProgressBar();
			this._folderDrag.returnHelpersToDraggees();
			this._selectSourceByFolderId(topFolderMovedId);

		}, this);

		if (fileMoveList.length > 0)
		{
			this._moveFile(fileMoveList, 0, $.proxy(function()
			{
				moveCallback(folderDeleteList, changedFolderIds, removeFromTree);
			}, this));
		}
		else
		{
			moveCallback(folderDeleteList, changedFolderIds, removeFromTree);
		}
	},

	/**
	 * Get parent source for a source.
	 *
	 * @param $source
	 * @returns {*}
	 * @private
	 */
	_getParentSource: function($source)
	{
		if (this._getSourceLevel($source) > 1)
		{
			return $source.parent().parent().siblings('a');
		}
	},

	/**
	 * Move a file using data from a parameter array.
	 *
	 * @param parameterArray
	 * @param parameterIndex
	 * @param callback
	 * @private
	 */
	_moveFile: function(parameterArray, parameterIndex, callback)
	{
		if (parameterIndex == 0)
		{
			this.responseArray = [];
		}

		Craft.postActionRequest('assets/moveFile', parameterArray[parameterIndex], $.proxy(function(data, textStatus)
		{
			this.progressBar.incrementProcessedItemCount(1);
			this.progressBar.updateProgressBar();

			if (textStatus == 'success')
			{
				this.responseArray.push(data);

				// If assets were just merged we should get the referece tags updated right away
				Craft.cp.runPendingTasks();
			}

			parameterIndex++;

			if (parameterIndex >= parameterArray.length)
			{
				callback(this.responseArray);
			}
			else
			{
				this._moveFile(parameterArray, parameterIndex, callback);
			}

		}, this));
	},

	_selectSourceByFolderId: function(targetFolderId)
	{
		var $targetSource = this._getSourceByFolderId(targetFolderId);

		// Make sure that all the parent sources are expanded and this source is visible.
		var $parentSources = $targetSource.parent().parents('li');

		for (var i = 0; i < $parentSources.length; i++)
		{
			var $parentSource = $($parentSources[i]);

			if (!$parentSource.hasClass('expanded'))
			{
				$parentSource.children('.toggle').click();
			}
		}

		this.selectSource($targetSource);
		this.updateElements();
	},

	/**
	 * Initialize the uploader.
	 *
	 * @private
	 */
	afterInit: function()
	{
		if (!this.$uploadButton)
		{
			this.$uploadButton = $('<div class="btn submit" data-icon="upload" style="position: relative; overflow: hidden;" role="button">' + Craft.t('Upload files') + '</div>');
			this.addButton(this.$uploadButton);

			this.$uploadInput = $('<input type="file" multiple="multiple" name="assets-upload" />').hide().insertBefore(this.$uploadButton);
		}

		this.promptHandler = new Craft.PromptHandler();
		this.progressBar = new Craft.ProgressBar(this.$main, true);

		var options = {
			url: Craft.getActionUrl('assets/uploadFile'),
			fileInput: this.$uploadInput,
			dropZone: this.$main
		};

		options.events = {
			fileuploadstart:       $.proxy(this, '_onUploadStart'),
			fileuploadprogressall: $.proxy(this, '_onUploadProgress'),
			fileuploaddone:        $.proxy(this, '_onUploadComplete')
		};

		if (typeof this.settings.criteria.kind != "undefined")
		{
			options.allowedKinds = this.settings.criteria.kind;
		}

		this._currentUploaderSettings = options;

		this.uploader = new Craft.Uploader (this.$uploadButton, options);

		this.$uploadButton.on('click', $.proxy(function()
		{
			if (this.$uploadButton.hasClass('disabled'))
			{
				return;
			}
			if (!this.isIndexBusy)
			{
				this.$uploadButton.parent().find('input[name=assets-upload]').click();
			}
		}, this));

		this.base();
	},

	onSelectSource: function()
	{
		this.uploader.setParams({folderId: this._getFolderIdFromSourceKey(this.sourceKey)});
		if (!this.$source.attr('data-upload'))
		{
			this.$uploadButton.addClass('disabled');
		}
		else
		{
			this.$uploadButton.removeClass('disabled');
		}
		this.base();
	},

	_getFolderIdFromSourceKey: function(sourceKey)
	{
		var parts = sourceKey.split(':');

		if (parts.length > 1 && parts[0] == 'folder')
		{
			return parts[1];
		}

		return null;
	},

	startSearching: function()
	{
		// Does this source have subfolders?
		if (this.$source.siblings('ul').length)
		{
			if (this.$includeSubfoldersContainer === null)
			{
				var id = 'includeSubfolders-'+Math.floor(Math.random()*1000000000);

				this.$includeSubfoldersContainer = $('<div style="margin-bottom: -23px; opacity: 0;"/>').insertAfter(this.$search);
				var $subContainer = $('<div style="padding-top: 5px;"/>').appendTo(this.$includeSubfoldersContainer);
				this.$includeSubfoldersCheckbox = $('<input type="checkbox" id="'+id+'" class="checkbox"/>').appendTo($subContainer);
				$('<label class="light smalltext" for="'+id+'"/>').text(' '+Craft.t('Search in subfolders')).appendTo($subContainer);

				this.addListener(this.$includeSubfoldersCheckbox, 'change', function()
				{
					this.setSelecetedSourceState('includeSubfolders', this.$includeSubfoldersCheckbox.prop('checked'));
					this.updateElements();
				});
			}
			else
			{
				this.$includeSubfoldersContainer.velocity('stop');
			}

			var checked = this.getSelectedSourceState('includeSubfolders', false);
			this.$includeSubfoldersCheckbox.prop('checked', checked);

			this.$includeSubfoldersContainer.velocity({
				marginBottom: 0,
				opacity: 1
			}, 'fast');

			this.showingIncludeSubfoldersCheckbox = true;
		}

		this.base();
	},

	stopSearching: function()
	{
		if (this.showingIncludeSubfoldersCheckbox)
		{
			this.$includeSubfoldersContainer.velocity('stop');

			this.$includeSubfoldersContainer.velocity({
				marginBottom: -23,
				opacity: 0
			}, 'fast');

			this.showingIncludeSubfoldersCheckbox = false;
		}

		this.base();
	},

	getViewParams: function()
	{
		var data = this.base();

		if (this.showingIncludeSubfoldersCheckbox && this.$includeSubfoldersCheckbox.prop('checked'))
		{
			data.criteria.includeSubfolders = true;
		}

		return data;
	},

	/**
	 * React on upload submit.
	 *
	 * @param id
	 * @private
	 */
	_onUploadStart: function(event)
	{
		this.setIndexBusy();

		// Initial values
		this._positionProgressBar();
		this.progressBar.resetProgressBar();
		this.progressBar.showProgressBar();
	},

	/**
	 * Update uploaded byte count.
	 */
	_onUploadProgress: function(event, data)
	{
		var progress = parseInt(data.loaded / data.total * 100, 10);
		this.progressBar.setProgressPercentage(progress);
	},

	/**
	 * On Upload Complete.
	 */
	_onUploadComplete: function(event, data)
	{
		var response = data.result;
		var fileName = data.files[0].name;

		var doReload = true;

		if (response.success || response.prompt)
		{
			// Add the uploaded file to the selected ones, if appropriate
			this._uploadedFileIds.push(response.fileId);

			// If there is a prompt, add it to the queue
			if (response.prompt)
			{
				this.promptHandler.addPrompt(response);
			}
		}
		else
		{
			if (response.error)
			{
				alert(Craft.t('Upload failed for {filename}. The error message was: “{error}”', { filename: fileName, error: response.error }));
			}
			else
			{
				alert(Craft.t('Upload failed for {filename}.', { filename: fileName }));
			}

			doReload = false;
		}

		// For the last file, display prompts, if any. If not - just update the element view.
		if (this.uploader.isLastUpload())
		{
			this.setIndexAvailable();
			this.progressBar.hideProgressBar();

			if (this.promptHandler.getPromptCount())
			{
				this.promptHandler.showBatchPrompts($.proxy(this, '_uploadFollowup'));
			}
			else
			{
				if (doReload)
				{
					this.updateElements();
				}
			}
		}
	},

	/**
	 * Follow up to an upload that triggered at least one conflict resolution prompt.
	 *
	 * @param returnData
	 * @private
	 */
	_uploadFollowup: function(returnData)
	{
		this.setIndexBusy();
		this.progressBar.resetProgressBar();

		this.promptHandler.resetPrompts();

		var finalCallback = $.proxy(function()
		{
			this.setIndexAvailable();
			this.progressBar.hideProgressBar();
			this.updateElements();
		}, this);

		this.progressBar.setItemCount(returnData.length);

		var doFollowup = $.proxy(function(parameterArray, parameterIndex, callback)
		{
			var postData = {
				newFileId:    parameterArray[parameterIndex].fileId,
				fileName:     parameterArray[parameterIndex].fileName,
				userResponse: parameterArray[parameterIndex].choice
			};

			Craft.postActionRequest('assets/uploadFile', postData, $.proxy(function(data, textStatus)
			{
				if (textStatus == 'success' && data.fileId)
				{
					this._uploadedFileIds.push(data.fileId);
				}
				parameterIndex++;
				this.progressBar.incrementProcessedItemCount(1);
				this.progressBar.updateProgressBar();

				if (parameterIndex == parameterArray.length)
				{
					callback();
				}
				else
				{
					doFollowup(parameterArray, parameterIndex, callback);
				}
			}, this));

		}, this);

		this.progressBar.showProgressBar();
		doFollowup(returnData, 0, finalCallback);
	},

	/**
	 * Perform actions after updating elements
	 * @private
	 */
	onUpdateElements: function()
	{
		this._onUpdateElements(false, this.view.getAllElements());
		this.view.on('appendElements', $.proxy(function(ev) {
			this._onUpdateElements(true, ev.newElements);
		}, this));

		this.base();
	},

	_onUpdateElements: function(append, $newElements)
	{
		if (this.settings.context == 'index')
		{
			if (!append)
			{
				this._fileDrag.removeAllItems();
			}

			this._fileDrag.addItems($newElements);
		}

		// See if we have freshly uploaded files to add to selection
		if (this._uploadedFileIds.length)
		{
			if (this.view.settings.selectable)
			{
				for (var i = 0; i < this._uploadedFileIds.length; i++)
				{
					this.view.selectElementById(this._uploadedFileIds[i]);
				}
			}

			// Reset the list.
			this._uploadedFileIds = [];
		}
	},

	/**
	 * On Drag Start
	 */
	_onDragStart: function()
	{
		this._tempExpandedFolders = [];
	},

	/**
	 * Get File Drag Helper
	 */
	_getFileDragHelper: function($element)
	{
		var currentView = this.getSelectedSourceState('mode');

		switch (currentView)
		{
			case 'table':
			{
				var $outerContainer = $('<div class="elements datatablesorthelper"/>').appendTo(Garnish.$bod),
					$innerContainer = $('<div class="tableview"/>').appendTo($outerContainer),
					$table = $('<table class="data"/>').appendTo($innerContainer),
					$tbody = $('<tbody/>').appendTo($table);

				$element.appendTo($tbody);

				// Copy the column widths
				this._$firstRowCells = this.view.$table.children('tbody').children('tr:first').children();
				var $helperCells = $element.children();

				for (var i = 0; i < $helperCells.length; i++)
				{
					// Hard-set the cell widths
					var $helperCell = $($helperCells[i]);

					// Skip the checkbox cell
					if ($helperCell.hasClass('checkbox-cell'))
					{
						$helperCell.remove();
						$outerContainer.css('margin-'+Craft.left, 19); // 26 - 7
						continue;
					}

					var $firstRowCell = $(this._$firstRowCells[i]),
						width = $firstRowCell.width();

					$firstRowCell.width(width);
					$helperCell.width(width);
				}

				return $outerContainer;
			}
			case 'thumbs':
			{
				var $outerContainer = $('<div class="elements thumbviewhelper"/>').appendTo(Garnish.$bod),
					$innerContainer = $('<ul class="thumbsview"/>').appendTo($outerContainer);

				$element.appendTo($innerContainer);

				return $outerContainer;
			}
		}

		return $();
	},

	/**
	 * On Drop Target Change
	 */
	_onDropTargetChange: function($dropTarget)
	{
		clearTimeout(this._expandDropTargetFolderTimeout);

		if ($dropTarget)
		{
			var folderId = this._getFolderIdFromSourceKey($dropTarget.data('key'));

			if (folderId)
			{
				this.dropTargetFolder = this._getSourceByFolderId(folderId);

				if (this._hasSubfolders(this.dropTargetFolder) && ! this._isExpanded(this.dropTargetFolder))
				{
					this._expandDropTargetFolderTimeout = setTimeout($.proxy(this, '_expandFolder'), 500);
				}
			}
			else
			{
				this.dropTargetFolder = null;
			}
		}

		if ($dropTarget && $dropTarget[0] != this.$source[0])
		{
			// Temporarily remove the .sel class on the active source
			this.$source.removeClass('sel');
		}
		else
		{
			this.$source.addClass('sel');
		}
	},

	/**
	 * Collapse Extra Expanded Folders
	 */
	_collapseExtraExpandedFolders: function(dropTargetFolderId)
	{
		clearTimeout(this._expandDropTargetFolderTimeout);

		var excluded;

		// If a source ID is passed in, exclude its parents
		if (dropTargetFolderId)
		{
			excluded = this._getSourceByFolderId(dropTargetFolderId).parents('li').children('a');
		}

		for (var i = this._tempExpandedFolders.length-1; i >= 0; i--)
		{
			var $source = this._tempExpandedFolders[i];

			// Check the parent list, if a source id is passed in
			if (! dropTargetFolderId || excluded.filter('[data-key="' + $source.data('key') + '"]').length == 0)
			{
				this._collapseFolder($source);
				this._tempExpandedFolders.splice(i, 1);
			}
		}
	},

	_getSourceByFolderId: function(folderId)
	{
		return this.$sources.filter('[data-key="folder:' + folderId + '"]');
	},

	_hasSubfolders: function($source)
	{
		return $source.siblings('ul').find('li').length;
	},

	_isExpanded: function($source)
	{
		return $source.parent('li').hasClass('expanded');
	},

	_expandFolder: function()
	{
		// Collapse any temp-expanded drop targets that aren't parents of this one
		this._collapseExtraExpandedFolders(this._getFolderIdFromSourceKey(this.dropTargetFolder.data('key')));

		this.dropTargetFolder.siblings('.toggle').click();

		// Keep a record of that
		this._tempExpandedFolders.push(this.dropTargetFolder);
	},

	_collapseFolder: function($source)
	{
		if ($source.parent().hasClass('expanded'))
		{
			$source.siblings('.toggle').click();
		}
	},

	_createFolderContextMenu: function($source)
	{
		if (!this._getFolderIdFromSourceKey($source.data('key'))) {
			return;
		}

		var menuOptions = [{ label: Craft.t('New subfolder'), onClick: $.proxy(this, '_createSubfolder', $source) }];

		// For all folders that are not top folders
		if (this.settings.context == 'index' && this._getSourceLevel($source) > 1)
		{
			menuOptions.push({ label: Craft.t('Rename folder'), onClick: $.proxy(this, '_renameFolder', $source) });
			menuOptions.push({ label: Craft.t('Delete folder'), onClick: $.proxy(this, '_deleteFolder', $source) });
		}

		new Garnish.ContextMenu($source, menuOptions, {menuClass: 'menu'});
	},

	_createSubfolder: function($parentFolder)
	{
		var subfolderName = prompt(Craft.t('Enter the name of the folder'));

		if (subfolderName)
		{
			var params = {
				parentId:  this._getFolderIdFromSourceKey($parentFolder.data('key')),
				folderName: subfolderName
			};

			this.setIndexBusy();

			Craft.postActionRequest('assets/createFolder', params, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success' && data.success)
				{
					this._prepareParentForChildren($parentFolder);

					var $subFolder = $(
						'<li>' +
							'<a data-key="folder:'+data.folderId+'"' +
								(Garnish.hasAttr($parentFolder, 'data-has-thumbs') ? ' data-has-thumbs' : '') +
								' data-upload="'+$parentFolder.attr('data-upload')+'"' +
							'>' +
								data.folderName +
							'</a>' +
						'</li>'
					);

					var $a = $subFolder.children('a:first');
					this._appendSubfolder($parentFolder, $subFolder);
					this.initSource($a);
				}

				if (textStatus == 'success' && data.error)
				{
					alert(data.error);
				}
			}, this));
		}
	},

	_deleteFolder: function($targetFolder)
	{
		if (confirm(Craft.t('Really delete folder “{folder}”?', {folder: $.trim($targetFolder.text())})))
		{
			var params = {
				folderId: this._getFolderIdFromSourceKey($targetFolder.data('key'))
			};

			this.setIndexBusy();

			Craft.postActionRequest('assets/deleteFolder', params, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success' && data.success)
				{
					var $parentFolder = this._getParentSource($targetFolder);

					// Remove folder and any trace from its parent, if needed
					this.deinitSource($targetFolder);

					$targetFolder.parent().remove();
					this._cleanUpTree($parentFolder);
				}

				if (textStatus == 'success' && data.error)
				{
					alert(data.error);
				}
			}, this));
		}
	},

	/**
	 * Rename
	 */
	_renameFolder: function($targetFolder)
	{
		var oldName = $.trim($targetFolder.text()),
			newName = prompt(Craft.t('Rename folder'), oldName);

		if (newName && newName != oldName)
		{
			var params = {
				folderId: this._getFolderIdFromSourceKey($targetFolder.data('key')),
				newName: newName
			};

			this.setIndexBusy();

			Craft.postActionRequest('assets/renameFolder', params, $.proxy(function(data, textStatus)
			{
				this.setIndexAvailable();

				if (textStatus == 'success' && data.success)
				{
					$targetFolder.text(data.newName);
				}

				if (textStatus == 'success' && data.error)
				{
					alert(data.error);
				}

			}, this), 'json');
		}
	},

	/**
	 * Prepare a source folder for children folder.
	 *
	 * @param $parentFolder
	 * @private
	 */
	_prepareParentForChildren: function($parentFolder)
	{
		if (!this._hasSubfolders($parentFolder))
		{
			$parentFolder.parent().addClass('expanded').append('<div class="toggle"></div><ul></ul>');
			this.initSourceToggle($parentFolder);
		}
	},

	/**
	 * Appends a subfolder to the parent folder at the correct spot.
	 *
	 * @param $parentFolder
	 * @param $subFolder
	 * @private
	 */
	_appendSubfolder: function($parentFolder, $subFolder)
	{
		var $subfolderList = $parentFolder.siblings('ul'),
			$existingChildren = $subfolderList.children('li'),
			subfolderLabel = $.trim($subFolder.children('a:first').text()),
			folderInserted = false;

		for (var i = 0; i < $existingChildren.length; i++)
		{
			var $existingChild = $($existingChildren[i]);

			if ($.trim($existingChild.children('a:first').text()) > subfolderLabel)
			{
				$existingChild.before($subFolder);
				folderInserted = true;
				break;
			}
		}

		if (!folderInserted)
		{
			$parentFolder.siblings('ul').append($subFolder);
		}
	},

	_cleanUpTree: function($parentFolder)
	{
		if ($parentFolder !== null && $parentFolder.siblings('ul').children('li').length == 0)
		{
			this.deinitSourceToggle($parentFolder);
			$parentFolder.siblings('ul').remove();
			$parentFolder.siblings('.toggle').remove();
			$parentFolder.parent().removeClass('expanded');
		}
	},

	_positionProgressBar: function()
	{
		var $container = $(),
			scrollTop = 0,
			offset = 0;

		if (this.settings.context == 'index')
		{
			$container = this.progressBar.$progressBar.closest('#content');
			scrollTop = Garnish.$win.scrollTop();
		}
		else
		{
			$container = this.progressBar.$progressBar.closest('.main');
			scrollTop = this.$main.scrollTop();
		}

		var containerTop = $container.offset().top;
		var diff = scrollTop - containerTop;
		var windowHeight = Garnish.$win.height();

		if ($container.height() > windowHeight)
		{
			offset = (windowHeight / 2) - 6 + diff;
		}
		else
		{
			offset = ($container.height() / 2) - 6;
		}

		if(this.settings.context != 'index')
		{
			offset = scrollTop + (($container.height() / 2) - 6);
		}

		this.progressBar.$progressBar.css({
			top: offset
		});
	}

});

// Register it!
Craft.registerElementIndexClass('Asset', Craft.AssetIndex);

/**
 * Asset Select input
 */
Craft.AssetSelectInput = Craft.BaseElementSelectInput.extend(
{
	requestId: 0,
	hud: null,
	uploader: null,
	progressBar: null,

	originalFilename: '',
	originalExtension: '',

	init: function (settings) {
		settings.editorSettings = {
			onShowHud: $.proxy(this.resetOriginalFilename, this),
			onCreateForm: $.proxy(this._renameHelper, this),
			validators: [$.proxy(this.validateElementForm, this)]
		};

		this.base(settings);
		this._attachUploader();
	},

	/**
	 * Attach the uploader with drag event handler
	 */
	_attachUploader: function () {
		this.progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').appendTo(this.$container));

		var options = {
			url: Craft.getActionUrl('assets/expressUpload'),
			dropZone: this.$container,
			formData: {
				fieldId: this.settings.fieldId,
				elementId: this.settings.sourceElementId
			}
		};

		// If CSRF protection isn't enabled, these won't be defined.
		if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined') {
			// Add the CSRF token
			options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		if (typeof this.settings.criteria.kind != "undefined") {
			options.allowedKinds = this.settings.criteria.kind;
		}

		options.canAddMoreFiles = $.proxy(this, 'canAddMoreFiles');

		options.events = {};
		options.events.fileuploadstart = $.proxy(this, '_onUploadStart');
		options.events.fileuploadprogressall = $.proxy(this, '_onUploadProgress');
		options.events.fileuploaddone = $.proxy(this, '_onUploadComplete');

		this.uploader = new Craft.Uploader(this.$container, options);
	},

	/**
	 * Add the freshly uploaded file to the input field.
	 */
	selectUploadedFile: function (element) {
		// Check if we're able to add new elements
		if (!this.canAddMoreElements()) {
			return;
		}

		var $newElement = element.$element;

		// Make a couple tweaks
		$newElement.addClass('removable');
		$newElement.prepend('<input type="hidden" name="' + this.settings.name + '[]" value="' + element.id + '">' +
			'<a class="delete icon" title="' + Craft.t('Remove') + '"></a>');

		$newElement.appendTo(this.$elementsContainer);

		var margin = -($newElement.outerWidth() + 10);

		this.$addElementBtn.css('margin-' + Craft.left, margin + 'px');

		var animateCss = {};
		animateCss['margin-' + Craft.left] = 0;
		this.$addElementBtn.velocity(animateCss, 'fast');

		this.addElements($newElement);

		delete this.modal;
	},

	/**
	 * On upload start.
	 */
	_onUploadStart: function (event) {
		this.progressBar.$progressBar.css({
			top: Math.round(this.$container.outerHeight() / 2) - 6
		});

		this.$container.addClass('uploading');
		this.progressBar.resetProgressBar();
		this.progressBar.showProgressBar();
	},

	/**
	 * On upload progress.
	 */
	_onUploadProgress: function (event, data) {
		var progress = parseInt(data.loaded / data.total * 100, 10);
		this.progressBar.setProgressPercentage(progress);
	},

	/**
	 * On a file being uploaded.
	 */
	_onUploadComplete: function (event, data) {
		if (data.result.error) {
			alert(data.result.error);
		}
		else {
			var html = $(data.result.html);
			Craft.appendHeadHtml(data.result.headHtml);
			this.selectUploadedFile(Craft.getElementInfo(html));
		}

		// Last file
		if (this.uploader.isLastUpload()) {
			this.progressBar.hideProgressBar();
			this.$container.removeClass('uploading');
		}
	},

	/**
	 * We have to take into account files about to be added as well
	 */
	canAddMoreFiles: function (slotsTaken) {
		return (!this.settings.limit || this.$elements.length + slotsTaken < this.settings.limit);
	},

	/**
	 * Parse the passed filename into the base filename and extension.
	 *
	 * @param filename
	 * @returns {{extension: string, baseFileName: string}}
	 */
	_parseFilename: function (filename) {
		var parts = filename.split('.'),
			extension = '';

		if (parts.length > 1) {
			extension = parts.pop();
		}
		var baseFileName = parts.join('.');
		return {extension: extension, baseFileName: baseFileName};
	},

	/**
	 * A helper function or the filename field.
	 * @private
	 */
	_renameHelper: function ($form) {
		$('.renameHelper', $form).on('focus', $.proxy(function (e) {
			input = e.currentTarget;
			var filename = this._parseFilename(input.value);

			if (this.originalFilename == "" && this.originalExtension == "") {
				this.originalFilename = filename.baseFileName;
				this.originalExtension = filename.extension;
			}

			var startPos = 0,
				endPos = filename.baseFileName.length;

			if (typeof input.selectionStart != "undefined") {
				input.selectionStart = startPos;
				input.selectionEnd = endPos;
			} else if (document.selection && document.selection.createRange) {
				// IE branch
				input.select();
				var range = document.selection.createRange();
				range.collapse(true);
				range.moveEnd("character", endPos);
				range.moveStart("character", startPos);
				range.select();
			}

		}, this));
	},

	resetOriginalFilename: function () {
		this.originalFilename = "";
		this.originalExtension = "";
	},

	validateElementForm: function () {
		var $filenameField = $('.renameHelper', this.elementEditor.hud.$hud.data('elementEditor').$form);
		var filename = this._parseFilename($filenameField.val());

		if (filename.extension != this.originalExtension) {
			// Blank extension
			if (filename.extension == "") {
				// If filename changed as well, assume removal of extension a mistake
				if (this.originalFilename != filename.baseFileName) {
					$filenameField.val(filename.baseFileName + '.' + this.originalExtension);
					return true;
				} else {
					// If filename hasn't changed, make sure they want to remove extension
					return confirm(Craft.t("Are you sure you want to remove the extension “.{ext}”?", {ext: this.originalExtension}));
				}
			} else {
				// If the extension has changed, make sure it s intentional
				return confirm(Craft.t("Are you sure you want to change the extension from “.{oldExt}” to “.{newExt}”?",
					{
						oldExt: this.originalExtension,
						newExt: filename.extension
					}));
			}
		}
		return true;
	}
});

/**
 * Asset selector modal class
 */
Craft.AssetSelectorModal = Craft.BaseElementSelectorModal.extend(
{
	$selectTransformBtn: null,
	_selectedTransform: null,

	init: function(elementType, settings)
	{
		settings = $.extend({}, Craft.AssetSelectorModal.defaults, settings);

		this.base(elementType, settings);

		if (settings.transforms.length)
		{
			this.createSelectTransformButton(settings.transforms);
		}
	},

	createSelectTransformButton: function(transforms)
	{
		if (!transforms || !transforms.length)
		{
			return;
		}

		var $btnGroup = $('<div class="btngroup"/>').appendTo(this.$primaryButtons);
		this.$selectBtn.appendTo($btnGroup);

		this.$selectTransformBtn = $('<div class="btn menubtn disabled">'+Craft.t('Select transform')+'</div>').appendTo($btnGroup);

		var $menu = $('<div class="menu" data-align="right"></div>').insertAfter(this.$selectTransformBtn),
			$menuList = $('<ul></ul>').appendTo($menu);

		for (var i = 0; i < transforms.length; i++)
		{
			$('<li><a data-transform="'+transforms[i].handle+'">'+transforms[i].name+'</a></li>').appendTo($menuList);
		}

		var MenuButton = new Garnish.MenuBtn(this.$selectTransformBtn, {
			onOptionSelect: $.proxy(this, 'onSelectTransform')
		});
		MenuButton.disable();

		this.$selectTransformBtn.data('menuButton', MenuButton);
	},

	onSelectionChange: function(ev)
	{
		var $selectedElements = this.elementIndex.getSelectedElements();

		var allowTransforms = false;

		if ($selectedElements.length && this.settings.transforms.length)
		{
			allowTransforms = true;

			for (var i = 0; i < $selectedElements.length; i++)
			{
				if (!$('.element.hasthumb:first', $selectedElements[i]).length)
				{
					allowTransforms = false;
					break;
				}
			}
		}

		var MenuBtn = null;

		if (this.$selectTransformBtn)
		{
			MenuBtn = this.$selectTransformBtn.data('menuButton');
		}

		if (allowTransforms)
		{
			if (MenuBtn)
			{
				MenuBtn.enable();
			}
			this.$selectTransformBtn.removeClass('disabled');
		}
		else if (this.$selectTransformBtn)
		{
			if (MenuBtn)
			{
				MenuBtn.disable();
			}
			this.$selectTransformBtn.addClass('disabled');
		}

		this.base();
	},

	onSelectTransform: function(option)
	{
		var transform = $(option).data('transform');
		this.selectImagesWithTransform(transform);
	},

	selectImagesWithTransform: function(transform)
	{
		// First we must get any missing transform URLs
		if (typeof Craft.AssetSelectorModal.transformUrls[transform] == 'undefined')
		{
			Craft.AssetSelectorModal.transformUrls[transform] = {};
		}

		var $selectedElements = this.elementIndex.getSelectedElements(),
			imageIdsWithMissingUrls = [];

		for (var i = 0; i < $selectedElements.length; i++)
		{
			var $item = $($selectedElements[i]),
				elementId = Craft.getElementInfo($item).id;

			if (typeof Craft.AssetSelectorModal.transformUrls[transform][elementId] == 'undefined')
			{
				imageIdsWithMissingUrls.push(elementId);
			}
		}

		if (imageIdsWithMissingUrls.length)
		{
			this.showFooterSpinner();

			this.fetchMissingTransformUrls(imageIdsWithMissingUrls, transform, $.proxy(function()
			{
				this.hideFooterSpinner();
				this.selectImagesWithTransform(transform);
			}, this));
		}
		else
		{
			this._selectedTransform = transform;
			this.selectElements();
			this._selectedTransform = null;
		}
	},

	fetchMissingTransformUrls: function(imageIdsWithMissingUrls, transform, callback)
	{
		var elementId = imageIdsWithMissingUrls.pop();

		var data = {
			fileId: elementId,
			handle: transform,
			returnUrl: true
		};

		Craft.postActionRequest('assets/generateTransform', data, $.proxy(function(response, textStatus)
		{
			Craft.AssetSelectorModal.transformUrls[transform][elementId] = false;

			if (textStatus == 'success')
			{
				if (response.url)
				{
					Craft.AssetSelectorModal.transformUrls[transform][elementId] = response.url;
				}
			}

			// More to load?
			if (imageIdsWithMissingUrls.length)
			{
				this.fetchMissingTransformUrls(imageIdsWithMissingUrls, transform, callback);
			}
			else
			{
				callback();
			}
		}, this));
	},

	getElementInfo: function($selectedElements)
	{
		var info = this.base($selectedElements);

		if (this._selectedTransform)
		{
			for (var i = 0; i < info.length; i++)
			{
				var elementId = info[i].id;

				if (
					typeof Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId] != 'undefined' &&
					Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId] !== false
				)
				{
					info[i].url = Craft.AssetSelectorModal.transformUrls[this._selectedTransform][elementId];
				}
			}
		}

		return info;
	},

	onSelect: function(elementInfo)
	{
		this.settings.onSelect(elementInfo, this._selectedTransform);
	}
},
{
	defaults: {
		canSelectImageTransforms: false,
		transforms: []
	},

	transformUrls: {}
});

// Register it!
Craft.registerElementSelectorModalClass('Asset', Craft.AssetSelectorModal);

/**
 * AuthManager class
 */
Craft.AuthManager = Garnish.Base.extend(
{
	checkAuthTimeoutTimer: null,
	showLoginModalTimer: null,
	decrementLogoutWarningInterval: null,

	showingLogoutWarningModal: false,
	showingLoginModal: false,

	logoutWarningModal: null,
	loginModal: null,

	$logoutWarningPara: null,
	$passwordInput: null,
	$passwordSpinner: null,
	$loginBtn: null,
	$loginErrorPara: null,

	submitLoginIfLoggedOut: false,

	/**
	 * Init
	 */
	init: function()
	{
		this.updateAuthTimeout(Craft.authTimeout);
	},

	/**
	 * Sets a timer for the next time to check the auth timeout.
	 */
	setCheckAuthTimeoutTimer: function(seconds)
	{
		if (this.checkAuthTimeoutTimer)
		{
			clearTimeout(this.checkAuthTimeoutTimer);
		}

		this.checkAuthTimeoutTimer = setTimeout($.proxy(this, 'checkAuthTimeout'), seconds*1000);
	},

	/**
	 * Pings the server to see how many seconds are left on the current user session, and handles the response.
	 */
	checkAuthTimeout: function(extendSession)
	{
		$.ajax({
			url: Craft.getActionUrl('users/getAuthTimeout', (extendSession ? null : 'dontExtendSession=1')),
			type: 'GET',
			complete: $.proxy(function(jqXHR, textStatus)
			{
				if (textStatus == 'success')
				{
					this.updateAuthTimeout(jqXHR.responseJSON.timeout);

					this.submitLoginIfLoggedOut = false;

					if (typeof jqXHR.responseJSON.csrfTokenValue !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined')
					{
						Craft.csrfTokenValue = jqXHR.responseJSON.csrfTokenValue;
					}
				}
				else
				{
					this.updateAuthTimeout(-1);
				}
			}, this)
		});
	},

	/**
	 * Updates our record of the auth timeout, and handles it.
	 */
	updateAuthTimeout: function(authTimeout)
	{
		this.authTimeout = parseInt(authTimeout);

		// Are we within the warning window?
		if (this.authTimeout != -1 && this.authTimeout < Craft.AuthManager.minSafeAuthTimeout)
		{
			// Is there still time to renew the session?
			if (this.authTimeout)
			{
				if (!this.showingLogoutWarningModal)
				{
					// Show the warning modal
					this.showLogoutWarningModal();
				}

				// Will the session expire before the next checkup?
				if (this.authTimeout < Craft.AuthManager.checkInterval)
				{
					if (this.showLoginModalTimer)
					{
						clearTimeout(this.showLoginModalTimer);
					}

					this.showLoginModalTimer = setTimeout($.proxy(this, 'showLoginModal'), this.authTimeout*1000);
				}
			}
			else
			{
				if (this.showingLoginModal)
				{
					if (this.submitLoginIfLoggedOut)
					{
						this.submitLogin();
					}
				}
				else
				{
					// Show the login modal
					this.showLoginModal();
				}
			}

			this.setCheckAuthTimeoutTimer(Craft.AuthManager.checkInterval);
		}
		else
		{
			// Everything's good!
			this.hideLogoutWarningModal();
			this.hideLoginModal();

			// Will be be within the minSafeAuthTimeout before the next update?
			if (this.authTimeout != -1 && this.authTimeout < (Craft.AuthManager.minSafeAuthTimeout + Craft.AuthManager.checkInterval))
			{
				this.setCheckAuthTimeoutTimer(this.authTimeout - Craft.AuthManager.minSafeAuthTimeout + 1);
			}
			else
			{
				this.setCheckAuthTimeoutTimer(Craft.AuthManager.checkInterval);
			}
		}
	},

	/**
	 * Shows the logout warning modal.
	 */
	showLogoutWarningModal: function()
	{
		var quickShow;

		if (this.showingLoginModal)
		{
			this.hideLoginModal(true);
			quickShow = true;
		}
		else
		{
			quickShow = false;
		}

		this.showingLogoutWarningModal = true;

		if (!this.logoutWarningModal)
		{
			var $form = $('<form id="logoutwarningmodal" class="modal alert fitted"/>'),
				$body = $('<div class="body"/>').appendTo($form),
				$buttons = $('<div class="buttons right"/>').appendTo($body),
				$logoutBtn = $('<div class="btn">'+Craft.t('Log out now')+'</div>').appendTo($buttons),
				$renewSessionBtn = $('<input type="submit" class="btn submit" value="'+Craft.t('Keep me logged in')+'" />').appendTo($buttons);

			this.$logoutWarningPara = $('<p/>').prependTo($body);

			this.logoutWarningModal = new Garnish.Modal($form, {
				autoShow: false,
				closeOtherModals: false,
				hideOnEsc: false,
				hideOnShadeClick: false,
				shadeClass: 'modal-shade dark',
				onFadeIn: function()
				{
					if (!Garnish.isMobileBrowser(true))
					{
						// Auto-focus the renew button
						setTimeout(function() {
							$renewSessionBtn.focus();
						}, 100);
					}
				}
			});

			this.addListener($logoutBtn, 'activate', 'logout');
			this.addListener($form, 'submit', 'renewSession');
		}

		if (quickShow)
		{
			this.logoutWarningModal.quickShow();
		}
		else
		{
			this.logoutWarningModal.show();
		}

		this.updateLogoutWarningMessage();

		this.decrementLogoutWarningInterval = setInterval($.proxy(this, 'decrementLogoutWarning'), 1000);
	},

	/**
	 * Updates the logout warning message indicating that the session is about to expire.
	 */
	updateLogoutWarningMessage: function()
	{
		this.$logoutWarningPara.text(Craft.t('Your session will expire in {time}.', {
			time: Craft.secondsToHumanTimeDuration(this.authTimeout)
		}));

		this.logoutWarningModal.updateSizeAndPosition();
	},

	decrementLogoutWarning: function()
	{
		if (this.authTimeout > 0)
		{
			this.authTimeout--;
			this.updateLogoutWarningMessage();
		}

		if (this.authTimeout == 0)
		{
			clearInterval(this.decrementLogoutWarningInterval);
		}
	},

	/**
	 * Hides the logout warning modal.
	 */
	hideLogoutWarningModal: function(quick)
	{
		this.showingLogoutWarningModal = false;

		if (this.logoutWarningModal)
		{
			if (quick)
			{
				this.logoutWarningModal.quickHide();
			}
			else
			{
				this.logoutWarningModal.hide();
			}

			if (this.decrementLogoutWarningInterval)
			{
				clearInterval(this.decrementLogoutWarningInterval);
			}
		}
	},

	/**
	 * Shows the login modal.
	 */
	showLoginModal: function()
	{
		var quickShow;

		if (this.showingLogoutWarningModal)
		{
			this.hideLogoutWarningModal(true);
			quickShow = true;
		}
		else
		{
			quickShow = false;
		}

		this.showingLoginModal = true;

		if (!this.loginModal)
		{
			var $form = $('<form id="loginmodal" class="modal alert fitted"/>'),
				$body = $('<div class="body"><h2>'+Craft.t('Your session has ended.')+'</h2><p>'+Craft.t('Enter your password to log back in.')+'</p></div>').appendTo($form),
				$inputContainer = $('<div class="inputcontainer">').appendTo($body),
				$inputsTable = $('<table class="inputs fullwidth"/>').appendTo($inputContainer),
				$inputsRow = $('<tr/>').appendTo($inputsTable),
				$passwordCell = $('<td/>').appendTo($inputsRow),
				$buttonCell = $('<td class="thin"/>').appendTo($inputsRow),
				$passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($passwordCell);

			this.$passwordInput = $('<input type="password" class="text password fullwidth" placeholder="'+Craft.t('Password')+'"/>').appendTo($passwordWrapper);
			this.$passwordSpinner = $('<div class="spinner hidden"/>').appendTo($inputContainer);
			this.$loginBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Login')+'" />').appendTo($buttonCell);
			this.$loginErrorPara = $('<p class="error"/>').appendTo($body);

			this.loginModal = new Garnish.Modal($form, {
				autoShow: false,
				closeOtherModals: false,
				hideOnEsc: false,
				hideOnShadeClick: false,
				shadeClass: 'modal-shade dark',
				onFadeIn: $.proxy(function()
				{
					if (!Garnish.isMobileBrowser(true))
					{
						// Auto-focus the password input
						setTimeout($.proxy(function() {
							this.$passwordInput.focus();
						}, this), 100);
					}
				}, this),
				onFadeOut: $.proxy(function()
				{
					this.$passwordInput.val('');
				}, this)
			});

			new Craft.PasswordInput(this.$passwordInput, {
				onToggleInput: $.proxy(function($newPasswordInput) {
					this.$passwordInput = $newPasswordInput;
				}, this)
			});

			this.addListener(this.$passwordInput, 'textchange', 'validatePassword');
			this.addListener($form, 'submit', 'login');
		}

		if (quickShow)
		{
			this.loginModal.quickShow();
		}
		else
		{
			this.loginModal.show();
		}
	},

	/**
	 * Hides the login modal.
	 */
	hideLoginModal: function(quick)
	{
		this.showingLoginModal = false;

		if (this.loginModal)
		{
			if (quick)
			{
				this.loginModal.quickHide();
			}
			else
			{
				this.loginModal.hide();
			}
		}
	},

	logout: function()
	{
		var url = Craft.getActionUrl('users/logout');

		$.get(url, $.proxy(function()
		{
			Craft.redirectTo('');
		}, this));
	},

	renewSession: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		this.hideLogoutWarningModal();
		this.checkAuthTimeout(true);
	},

	validatePassword: function()
	{
		if (this.$passwordInput.val().length >= 6)
		{
			this.$loginBtn.removeClass('disabled');
			return true;
		}
		else
		{
			this.$loginBtn.addClass('disabled');
			return false;
		}
	},

	login: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		if (this.validatePassword())
		{
			this.$passwordSpinner.removeClass('hidden');
			this.clearLoginError();

			if (typeof Craft.csrfTokenValue != 'undefined')
			{
				// Check the auth status one last time before sending this off,
				// in case the user has already logged back in from another window/tab
				this.submitLoginIfLoggedOut = true;
				this.checkAuthTimeout();
			}
			else
			{
				this.submitLogin();
			}
		}
	},

	submitLogin: function()
	{
		var data = {
			loginName: Craft.username,
			password: this.$passwordInput.val()
		};

		Craft.postActionRequest('users/login', data, $.proxy(function(response, textStatus)
		{
			this.$passwordSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.hideLoginModal();
					this.checkAuthTimeout();
				}
				else
				{
					this.showLoginError(response.error);
					Garnish.shake(this.loginModal.$container);

					if (!Garnish.isMobileBrowser(true))
					{
						this.$passwordInput.focus();
					}
				}
			}
			else
			{
				this.showLoginError();
			}

		}, this));
	},

	showLoginError: function(error)
	{
		if (error === null || typeof error == 'undefined')
		{
			error = Craft.t('An unknown error occurred.');
		}

		this.$loginErrorPara.text(error);
		this.loginModal.updateSizeAndPosition();
	},

	clearLoginError: function()
	{
		this.showLoginError('');
	}
},
{
	checkInterval: 60,
	minSafeAuthTimeout: 120
});

/**
 * Category index class
 */
Craft.CategoryIndex = Craft.BaseElementIndex.extend(
{
	editableGroups: null,
	$newCategoryBtnGroup: null,
	$newCategoryBtn: null,

	afterInit: function()
	{
		// Find which of the visible groups the user has permission to create new categories in
		this.editableGroups = [];

		for (var i = 0; i < Craft.editableCategoryGroups.length; i++)
		{
			var group = Craft.editableCategoryGroups[i];

			if (this.getSourceByKey('group:'+group.id))
			{
				this.editableGroups.push(group);
			}
		}

		this.base();
	},

	getDefaultSourceKey: function()
	{
		// Did they request a specific category group in the URL?
		if (this.settings.context == 'index' && typeof defaultGroupHandle != typeof undefined)
		{
			for (var i = 0; i < this.$sources.length; i++)
			{
				var $source = $(this.$sources[i]);

				if ($source.data('handle') == defaultGroupHandle)
				{
					return $source.data('key');
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		// Get the handle of the selected source
		var selectedSourceHandle = this.$source.data('handle');

		// Update the New Category button
		// ---------------------------------------------------------------------

		if (this.editableGroups.length)
		{
			// Remove the old button, if there is one
			if (this.$newCategoryBtnGroup)
			{
				this.$newCategoryBtnGroup.remove();
			}

			// Determine if they are viewing a group that they have permission to create categories in
			var selectedGroup;

			if (selectedSourceHandle)
			{
				for (var i = 0; i < this.editableGroups.length; i++)
				{
					if (this.editableGroups[i].handle == selectedSourceHandle)
					{
						selectedGroup = this.editableGroups[i];
						break;
					}
				}
			}

			this.$newCategoryBtnGroup = $('<div class="btngroup submit"/>');
			var $menuBtn;

			// If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
			// Otherwise only show a menu button
			if (selectedGroup)
			{
				var href = this._getGroupTriggerHref(selectedGroup),
					label = (this.settings.context == 'index' ? Craft.t('New category') : Craft.t('New {group} category', {group: selectedGroup.name}));
				this.$newCategoryBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newCategoryBtnGroup);

				if (this.settings.context != 'index')
				{
					this.addListener(this.$newCategoryBtn, 'click', function(ev)
					{
						this._openCreateCategoryModal(ev.currentTarget.getAttribute('data-id'));
					});
				}

				if (this.editableGroups.length > 1)
				{
					$menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newCategoryBtnGroup);
				}
			}
			else
			{
				this.$newCategoryBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('New category')+'</div>').appendTo(this.$newCategoryBtnGroup);
			}

			if ($menuBtn)
			{
				var menuHtml = '<div class="menu"><ul>';

				for (var i = 0; i < this.editableGroups.length; i++)
				{
					var group = this.editableGroups[i];

					if (this.settings.context == 'index' || group != selectedGroup)
					{
						var href = this._getGroupTriggerHref(group),
							label = (this.settings.context == 'index' ? group.name : Craft.t('New {group} category', {group: group.name}));
						menuHtml += '<li><a '+href+'">'+label+'</a></li>';
					}
				}

				menuHtml += '</ul></div>';

				var $menu = $(menuHtml).appendTo(this.$newCategoryBtnGroup),
					menuBtn = new Garnish.MenuBtn($menuBtn);

				if (this.settings.context != 'index')
				{
					menuBtn.on('optionSelect', $.proxy(function(ev)
					{
						this._openCreateCategoryModal(ev.option.getAttribute('data-id'));
					}, this));
				}
			}

			this.addButton(this.$newCategoryBtnGroup);
		}

		// Update the URL if we're on the Categories index
		// ---------------------------------------------------------------------

		if (this.settings.context == 'index' && typeof history != typeof undefined)
		{
			var uri = 'categories';

			if (selectedSourceHandle)
			{
				uri += '/'+selectedSourceHandle;
			}

			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	_getGroupTriggerHref: function(group)
	{
		if (this.settings.context == 'index')
		{
			return 'href="'+Craft.getUrl('categories/'+group.handle+'/new')+'"';
		}
		else
		{
			return 'data-id="'+group.id+'"';
		}
	},

	_openCreateCategoryModal: function(groupId)
	{
		if (this.$newCategoryBtn.hasClass('loading'))
		{
			return;
		}

		// Find the group
		var group;

		for (var i = 0; i < this.editableGroups.length; i++)
		{
			if (this.editableGroups[i].id == groupId)
			{
				group = this.editableGroups[i];
				break;
			}
		}

		if (!group)
		{
			return;
		}

		this.$newCategoryBtn.addClass('inactive');
		var newCategoryBtnText = this.$newCategoryBtn.text();
		this.$newCategoryBtn.text(Craft.t('New {group} category', {group: group.name}));

		new Craft.ElementEditor({
			hudTrigger: this.$newCategoryBtnGroup,
			elementType: 'Category',
			locale: this.locale,
			attributes: {
				groupId: groupId
			},
			onBeginLoading: $.proxy(function()
			{
				this.$newCategoryBtn.addClass('loading');
			}, this),
			onEndLoading: $.proxy(function()
			{
				this.$newCategoryBtn.removeClass('loading');
			}, this),
			onHideHud: $.proxy(function()
			{
				this.$newCategoryBtn.removeClass('inactive').text(newCategoryBtnText);
			}, this),
			onSaveElement: $.proxy(function(response)
			{
				// Make sure the right group is selected
				var groupSourceKey = 'group:'+groupId;

				if (this.sourceKey != groupSourceKey)
				{
					this.selectSourceByKey(groupSourceKey);
				}

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this)
		});
	}
});

// Register it!
Craft.registerElementIndexClass('Category', Craft.CategoryIndex);

/**
 * Category Select input
 */
Craft.CategorySelectInput = Craft.BaseElementSelectInput.extend(
{
	setSettings: function()
	{
		this.base.apply(this, arguments);
		this.settings.sortable = false;
	},

	getModalSettings: function()
	{
		var settings = this.base();
		settings.hideOnSelect = false;
		return settings;
	},

	getElements: function()
	{
		return this.$elementsContainer.find('.element');
	},

	onModalSelect: function(elements)
	{
		// Disable the modal
		this.modal.disable();
		this.modal.disableCancelBtn();
		this.modal.disableSelectBtn();
		this.modal.showFooterSpinner();

		// Get the new category HTML
		var selectedCategoryIds = this.getSelectedElementIds();

		for (var i = 0; i < elements.length; i++)
		{
			selectedCategoryIds.push(elements[i].id);
		}

		var data = {
			categoryIds:    selectedCategoryIds,
			locale:         elements[0].locale,
			id:             this.settings.id,
			name:           this.settings.name,
			limit:          this.settings.limit,
			selectionLabel: this.settings.selectionLabel
		};

		Craft.postActionRequest('elements/getCategoriesInputHtml', data, $.proxy(function(response, textStatus)
		{
			this.modal.enable();
			this.modal.enableCancelBtn();
			this.modal.enableSelectBtn();
			this.modal.hideFooterSpinner();

			if (textStatus == 'success')
			{
				var $newInput = $(response.html),
					$newElementsContainer = $newInput.children('.elements');

				this.$elementsContainer.replaceWith($newElementsContainer);
				this.$elementsContainer = $newElementsContainer;
				this.resetElements();

				for (var i = 0; i < elements.length; i++)
				{
					var element = elements[i],
						$element = this.getElementById(element.id);

					if ($element)
					{
						this.animateElementIntoPlace(element.$element, $element);
					}
				}

				this.updateDisabledElementsInModal();
				this.modal.hide();
				this.onSelectElements();
			}
		}, this));
	},

	removeElement: function($element)
	{
		// Find any descendants this category might have
		var $allCategories = $element.add($element.parent().siblings('ul').find('.element'));

		// Remove our record of them all at once
		this.removeElements($allCategories);

		// Animate them away one at a time
		for (var i = 0; i < $allCategories.length; i++)
		{
			this._animateCategoryAway($allCategories, i);
		}
	},

	_animateCategoryAway: function($allCategories, i)
	{
		var callback;

		// Is this the last one?
		if (i == $allCategories.length - 1)
		{
			callback = $.proxy(function()
			{
				var $li = $allCategories.first().parent().parent(),
					$ul = $li.parent();

				if ($ul[0] == this.$elementsContainer[0] || $li.siblings().length)
				{
					$li.remove();
				}
				else
				{
					$ul.remove();
				}
			}, this);
		}
		else
		{
			callback = null;
		}

		var func = $.proxy(function() {
			this.animateElementAway($allCategories.eq(i), callback);
		}, this);

		if (i == 0)
		{
			func();
		}
		else
		{
			setTimeout(func, 100 * i);
		}
	}
});

/**
 * Craft Charts
 */

Craft.charts = {};

/**
 * Class Craft.charts.DataTable
 */
Craft.charts.DataTable = Garnish.Base.extend(
{
    columns: null,
    rows: null,

    init: function(data)
    {
        columns = data.columns;
        rows = data.rows;

        rows.forEach($.proxy(function(d)
        {
            $.each(d, function(cellIndex, cell)
            {
                var column = columns[cellIndex];

                switch(column.type)
                {
                    case 'date':
                        d[cellIndex] = d3.time.format("%Y-%m-%d").parse(d[cellIndex]);
                    break;

                    case 'datetime':
                        d[cellIndex] = d3.time.format("%Y-%m-%d %H:00:00").parse(d[cellIndex]);
                    break;

                    case 'percent':
                    d[cellIndex] = d[cellIndex] / 100;
                    break;

                    case 'number':
                        d[cellIndex] = +d[cellIndex];
                        break;

                    default:
                        // do nothing
                }
            });

        }, this));

        this.columns = columns;
        this.rows = rows;
    }
});

/**
 * Class Craft.charts.Tip
 */
Craft.charts.Tip = Garnish.Base.extend(
{
    $tip: null,

    init: function($container, settings)
    {
        this.setSettings(settings, Craft.charts.Tip.defaults);

        this.$container = $container;

        this.$tip = $('<div class="tooltip"></div>').appendTo(this.$container);

        this.hide();
    },

    tipContentFormat: function(d)
    {
        var locale = this.settings.locale;


        if(this.settings.tipContentFormat)
        {
            return this.settings.tipContentFormat(locale, d);
        }
        else
        {
            var $content = $('<div />');
            var $xValue = $('<div class="x-value" />').appendTo($content);
            var $yValue = $('<div class="y-value" />').appendTo($content);

            $xValue.html(this.settings.xTickFormat(d[0]));
            $yValue.html(this.settings.yTickFormat(d[1]));

            return $content.get(0);
        }
    },

    show: function(d)
    {
        this.$tip.html(this.tipContentFormat(d));
        this.$tip.css("display", 'block');

        var position = this.settings.getPosition(this.$tip, d);

        this.$tip.css("left", position.left + "px");
        this.$tip.css("top", position.top + "px");
    },

    hide: function()
    {
        this.$tip.css("display", 'none');
    },
},
{
    defaults: {
        locale: null,
        tipContentFormat: null, // $.noop ?
        getPosition: null, // $.noop ?
    }
});

/**
 * Class Craft.charts.BaseChart
 */
Craft.charts.BaseChart = Garnish.Base.extend(
{
    $container: null,
    $chart: null,

    chartBaseClass: 'cp-chart',
    dataTable: null,

    // dataTables: [],
    // isStacked: true,

    locale: null,
    orientation: null,

    svg: null,
    width: null,
    height: null,
    x: null,
    y: null,

    init: function(container)
    {
        this.$container = container;

        d3.select(window).on('resize', $.proxy(function() {
            this.resize();
        }, this));
    },

    initLocale: function()
    {
        var localeDefinition = window.d3_locale;

        if(this.settings.localeDefinition)
        {
            localeDefinition = $.extend(true, {}, localeDefinition, this.settings.localeDefinition);
        }

        this.locale = d3.locale(localeDefinition);
    },

    initChartElement: function()
    {
        // reset chart element's HTML

        if(this.$chart)
        {
            this.$chart.remove();
        }

        // chart class

        var className = this.chartBaseClass;

        if(this.settings.chartClass)
        {
            className += ' '+this.settings.chartClass;
        }

        this.$chart = $('<div class="'+className+'" />').appendTo(this.$container);
    },

    draw: function(dataTable, settings, settingsDefaults)
    {
        // settings

        this.setSettings(settings, Craft.charts.BaseChart.defaults);

        if(settingsDefaults)
        {
            this.setSettings(settings, settingsDefaults);
        }


        // chart

        this.initLocale();
        this.initChartElement();

        this.orientation = this.settings.orientation;

        this.dataTable = dataTable;
    },

    xTickFormat: function(locale)
    {
        switch(this.settings.dataScale)
        {
            case 'year':
                return locale.timeFormat('%Y');

            case 'month':
                return locale.timeFormat(this.settings.formats.shortDateFormats.month);

            case 'hour':
                return locale.timeFormat(this.settings.formats.shortDateFormats.day+" %H:00:00");

            default:
                return locale.timeFormat(this.settings.formats.shortDateFormats.day);
        }
    },

    yTickFormat: function(locale)
    {
        switch(this.dataTable.columns[1].type)
        {
            case 'currency':
                return locale.numberFormat(this.settings.formats.currencyFormat);

            case 'percent':
                return locale.numberFormat(this.settings.formats.percentFormat);

            case 'time':
                return Craft.charts.utils.getDuration;

            default:
                return locale.numberFormat("n");
        }
    },

    resize: function()
    {
        this.draw(this.dataTable, this.settings);
    },

    onAfterDrawTicks: function()
    {
        // White border for ticks' text
        $('.tick', this.$chart).each(function(tickKey, tick)
        {
            var $tickText = $('text', tick);

            var $clone = $tickText.clone();
            $clone.appendTo(tick);

            $tickText.attr('stroke', '#ffffff');
            $tickText.attr('stroke-width', 3);
        });
    }
},
{
    defaults: {
        margin: { top: 25, right: 25, bottom: 25, left: 25 },
        chartClass: null,
        colors: ["#0594D1", "#DE3800", "#FF9A00", "#009802", "#9B009B"],
        ticksStyles: {
            'fill': '#555',
            'font-size': '11px'
        }
    }
});


/**
 * Class Craft.charts.Area
 */
Craft.charts.Area = Craft.charts.BaseChart.extend(
{
    tip: null,

    paddedX: null,
    paddedY: null,

    draw: function(dataTable, settings)
    {
        this.base(dataTable, settings, Craft.charts.Area.defaults);

        if(this.tip)
        {
            this.tip = null;
        }

        this.width = this.$chart.width() - this.settings.margin.left - this.settings.margin.right;
        this.height = this.$chart.height() - this.settings.margin.top - this.settings.margin.bottom;

        // X & Y Scales & Domains
        this.x = d3.time.scale().range([0, this.width]);
        this.y = d3.scale.linear().range([this.height, 0]);
        this.x.domain(this.xDomain());
        this.y.domain(this.yDomain());

        // Append SVG to chart element

        var svg = {
            width: this.width + (this.settings.margin.left + this.settings.margin.right),
            height: this.height + (this.settings.margin.top + this.settings.margin.bottom),
            translateX: (this.orientation != 'rtl' ? (this.settings.margin.left) : (this.settings.margin.right)),
            translateY: this.settings.margin.top
        };

        this.svg = d3.select(this.$chart.get(0)).append("svg")
                .attr("width", svg.width)
                .attr("height", svg.height)
            .append("g")
                .attr("transform", "translate(" + svg.translateX + "," + svg.translateY + ")");

        // Draw elements
        this.drawGridlines();
        this.drawYTicks();


        // Draw padded elements
        var chartMargin = this.getChartMargin();
        this.paddedX = d3.time.scale().range([chartMargin.left, (this.width - chartMargin.right)]);
        this.paddedY = d3.scale.linear().range([this.height, 0]);
        this.paddedX.domain(this.xDomain());
        this.paddedY.domain(this.yDomain());

        this.drawXTicks();
        this.onAfterDrawTicks();
        this.drawAxes();
        this.drawChart();
        this.drawPlots();
        this.drawTipTriggers();
    },

    getChartMargin: function()
    {
        var left = 0;
        var right = 0;


        // calculate left based on widest Y tick's width

        var yTickMaxWidth = 0;

        $('.y .tick text:last', this.$chart).each(function(tickKey, tick)
        {
            var tickWidth = $(tick).get(0).getBoundingClientRect().width;

            if(tickWidth > yTickMaxWidth)
            {
                yTickMaxWidth = tickWidth;
            }
        });

        left = yTickMaxWidth + 14;

        return {
            left: (this.orientation != 'rtl' ? left : right),
            right: (this.orientation != 'rtl' ? right : left)
        };
    },

    drawChart: function()
    {
        var x = this.paddedX;
        var y = this.paddedY;

        // Line

        var line = d3.svg.line()
            .x(function(d) { return x(d[0]); })
            .y(function(d) { return y(d[1]); });

        this.svg
            .append("g")
                .attr("class", "chart-line")
            .append("path")
                .datum(this.dataTable.rows)
                .style({
                    'fill': 'none',
                    'stroke': this.settings.colors[0],
                    'stroke-width': '3px',
                })
                .attr("d", line);

        // Area
        var area = d3.svg.area()
            .x(function(d) { return x(d[0]); })
            .y0(this.height)
            .y1(function(d) { return y(d[1]); });

        // Area
        this.svg
            .append("g")
                .attr("class", "chart-area")
            .append("path")
                .datum(this.dataTable.rows)
                .style({
                    'fill': this.settings.colors[0],
                    'fill-opacity': '0.3'
                })
                .attr("d", area);
    },

    drawAxes: function()
    {
        var x = d3.time.scale().range([0, this.width]);
        var y = this.y;

        var xAxis = d3.svg.axis().scale(x).orient("bottom").ticks(0).outerTickSize(0);

        var xTranslateX = - 0;
        var xTranslateY = this.height;

        this.svg.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate("+ xTranslateX +"," + xTranslateY + ")")
            .call(xAxis);

        var chartMargin = this.getChartMargin();

        if(this.settings.axis.y.show)
        {
            if(this.orientation == 'rtl')
            {
                var yTranslateX = this.width - chartMargin.right;
                var yTranslateY = 0;

                var yAxis = d3.svg.axis().scale(y).orient("left").ticks(0);

                this.svg.append("g")
                    .attr("class", "y axis")
                    .attr("transform", "translate(" + yTranslateX + ", "+ yTranslateY +")")
                    .call(yAxis);
            }
            else
            {
                var yTranslateX = chartMargin.left;
                var yTranslateY = 0;

                var yAxis = d3.svg.axis().scale(y).orient("right").ticks(0);

                this.svg.append("g")
                    .attr("class", "y axis")
                    .attr("transform", "translate(" + yTranslateX + ", "+ yTranslateY +")")
                    .call(yAxis);
            }
        }
    },

    drawYTicks: function()
    {
        var y = this.y;

        if(this.orientation == 'rtl')
        {
            var yAxis = d3.svg.axis().scale(y).orient("left")
                .tickFormat(this.yTickFormat(this.locale))
                .tickValues(this.yTickValues())
                .ticks(this.yTicks());

            var translateX = this.width + 10;
            var translateY = 0;

            this.svg.append("g")
                .attr("class", "y ticks-axis")
                .attr("transform", "translate(" + translateX + ",0)")
                .style(this.settings.ticksStyles)
                .call(yAxis);

            this.svg.selectAll('.y.ticks-axis text').style({
                'text-anchor': 'start',
            });
        }
        else
        {
            var yAxis = d3.svg.axis().scale(y).orient("right")
                .tickFormat(this.yTickFormat(this.locale))
                .tickValues(this.yTickValues())
                .ticks(this.yTicks());

            var translateX = - (10);
            var translateY = 0;

            this.svg.append("g")
                .attr("class", "y ticks-axis")
                .attr("transform", "translate("+ translateX + ", "+ translateY +")")
                .style(this.settings.ticksStyles)
                .call(yAxis);
        }
    },

    drawXTicks: function()
    {
        var x = this.paddedX;

        var xAxis = d3.svg.axis().scale(x).orient("bottom")
            .tickFormat(this.xTickFormat(this.locale))
            .ticks(this.xTicks());

        this.svg.append("g")
            .attr("class", "x ticks-axis")
            .attr("transform", "translate(0," + this.height + ")")
            .style(this.settings.ticksStyles)
            .call(xAxis);
    },

    drawGridlines: function()
    {
        var x = this.x;
        var y = this.y;

        if(this.settings.xAxisGridlines)
        {
            var xLineAxis = d3.svg.axis().scale(x).orient("bottom");

            // draw x lines
            this.svg.append("g")
                .attr("class", "x grid-line")
                .attr("transform", "translate(0," + this.height + ")")
                .call(xLineAxis
                    .tickSize(-this.height, 0, 0)
                    .tickFormat("")
                );
        }

        if(this.settings.yAxisGridlines)
        {
            var yLineAxis = d3.svg.axis().scale(y).orient("left");

            var translateX = 0;
            var translateY = 0;

            var innerTickSize = - (this.width);
            var outerTickSize = 0;

            this.svg.append("g")
                .attr("class", "y grid-line")
                .attr("transform", "translate(-"+ translateX +" , "+ translateY +")")
                .call(yLineAxis
                    .tickSize(innerTickSize, outerTickSize)
                    .tickFormat("")
                    .tickValues(this.yTickValues())
                    .ticks(this.yTicks())
                );
        }
    },

    drawPlots: function()
    {
        var x = this.paddedX;
        var y = this.paddedY;

        if(this.settings.enablePlots)
        {
            this.svg.append('g')
                .attr("class", "plots")
            .selectAll("circle")
                .data(this.dataTable.rows)
                .enter()
                .append("circle")
                    .style({
                        'fill': this.settings.colors[0],
                    })
                    .attr("class", $.proxy(function(d, index) { return 'plot plot-'+index; }, this))
                    .attr("r", 4)
                    .attr("cx", $.proxy(function(d) { return x(d[0]); }, this))
                    .attr("cy", $.proxy(function(d) { return y(d[1]); }, this));
        }
    },

    expandPlot: function(index)
    {
        this.svg.select('.plot-'+index).attr("r", 5);
    },

    unexpandPlot: function(index)
    {
        this.svg.select('.plot-'+index).attr("r", 4);
    },

    getTipTriggerWidth: function () {

        return Math.max(0, this.xAxisTickInterval());
    },

    xAxisTickInterval: function()
    {
        var chartMargin = this.getChartMargin();

        var outerTickSize = 6;
        var length = this.svg.select('.x path.domain').node().getTotalLength() - chartMargin.left - chartMargin.right - outerTickSize * 2;
        var interval = length / (this.dataTable.rows.length - 1);

        return interval;
    },

    drawTipTriggers: function()
    {
        var x = this.paddedX;

        if(this.settings.enableTips)
        {
            var tipSettings = {
                chart: this,
                locale: this.locale,
                xTickFormat: this.xTickFormat(this.locale),
                yTickFormat: this.yTickFormat(this.locale),
                tipContentFormat: $.proxy(this, 'tipContentFormat'),
                getPosition: $.proxy(this, 'getTipPosition')
            };

            if(!this.tip)
            {
                this.tip = new Craft.charts.Tip(this.$chart, tipSettings);
            }
            else
            {
                this.tip.setSettings(tipSettings);
            }

            this.svg.append('g')
                .attr("class", "tip-triggers")
            .selectAll("rect")
                .data(this.dataTable.rows)
            .enter().append("rect")
                .attr("class", "tip-trigger")
                .style({
                    'fill': 'transparent',
                    'fill-opacity': '1',
                })
                .attr("width", this.getTipTriggerWidth())
                .attr("height", this.height)
                .attr("x", $.proxy(function(d) { return x(d[0]) - this.getTipTriggerWidth() / 2; }, this))
                .on("mouseover", $.proxy(function(d, index)
                {
                    this.expandPlot(index);
                    this.tip.show(d);
                }, this))
                .on("mouseout", $.proxy(function(d, index)
                {
                    this.unexpandPlot(index);
                    this.tip.hide();
                }, this));
        }

        // Apply shadow filter
        Craft.charts.utils.applyShadowFilter('drop-shadow', this.svg);
    },

    getTipPosition: function($tip, d)
    {
        var x = this.paddedX;
        var y = this.paddedY;

        var chartMargin = this.getChartMargin();

        var offset = 24;
        var top = (y(d[1]) - $tip.height() / 2);
        var left;

        if(this.orientation != 'rtl')
        {
            left = (x(d[0]) + this.settings.margin.left + offset);

            var calcLeft = (this.$chart.offset().left + left + $tip.width());
            var maxLeft = this.$chart.offset().left + this.$chart.width() - offset;

            if(calcLeft > maxLeft)
            {
                left = x(d[0]) - ($tip.width() + offset);
            }
        }
        else
        {
            left = (x(d[0]) - ($tip.width() + this.settings.margin.left + offset));
        }

        if(left < 0)
        {
            left = (x(d[0]) + this.settings.margin.left + offset);
        }

        return {
            top: top,
            left: left,
        };
    },

    xDomain: function()
    {
        var min = d3.min(this.dataTable.rows, function(d) { return d[0]; });
        var max = d3.max(this.dataTable.rows, function(d) { return d[0]; });

        if(this.orientation == 'rtl')
        {
            return [max, min];
        }
        else
        {
            return [min, max];
        }
    },

    xTicks: function()
    {
        return 3;
    },

    yAxisMaxValue: function()
    {
        return d3.max(this.dataTable.rows, function(d) { return d[1]; });
    },

    yDomain: function()
    {
        var yDomainMax = $.proxy(function()
        {
            return this.yAxisMaxValue();

        }, this);

        return [0, yDomainMax()];
    },

    yTicks: function()
    {
        return 2;
    },

    yTickValues: function()
    {
        return [this.yAxisMaxValue() / 2, this.yAxisMaxValue()];
    },
},
{
    defaults: {
        chartClass: 'area',
        enablePlots: true,
        enableTips: true,
        xAxisGridlines: false,
        yAxisGridlines: true,
        axis: {
            y: {
                show: false
            }
        }
    }
});

/**
 * Class Craft.charts.Utils
 */
Craft.charts.utils = {

    getDuration: function(value)
    {
        var sec_num = parseInt(value, 10);
        var hours   = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        if (hours < 10)
        {
            hours = "0"+hours;
        }

        if (minutes < 10)
        {
            minutes = "0"+minutes;
        }

        if (seconds < 10)
        {
            seconds = "0"+seconds;
        }

        var time = hours+':'+minutes+':'+seconds;

        return time;
    },

    /**
     * arrayToDataTable
     */
    arrayToDataTable: function(twoDArray)
    {

        var data = {
            columns: [],
            rows: []
        };

        $.each(twoDArray, function(k, v) {
            if(k == 0)
            {
                // first row is column definition

                data.columns = [];

                $.each(v, function(k2, v2) {

                    // guess column type from first row
                    var columnType = typeof(twoDArray[(k + 1)][k2]);

                    var column = {
                        name: v2,
                        type: columnType,
                    };

                    data.columns.push(column);
                });
            }
            else
            {
                var row = [];

                $.each(v, function(k2, v2) {
                    var cell = v2;

                    row.push(cell);
                });

                data.rows.push(row);
            }
        });

        var dataTable = new Craft.charts.DataTable(data);

        return dataTable;
    },

    applyShadowFilter: function(id, svg)
    {
        // filters go in defs element
        var defs = svg.append("defs");

        // create filter with id #{id}
        // height=130% so that the shadow is not clipped
        var filter = defs.append("filter")
            .attr("id", id)
            .attr("width", "200%")
            .attr("height", "200%")
            .attr("x", "-50%")
            .attr("y", "-50%");

        // SourceAlpha refers to opacity of graphic that this filter will be applied to
        // convolve that with a Gaussian with standard deviation 3 and store result
        // in blur
        filter.append("feGaussianBlur")
            .attr("in", "SourceAlpha")
            .attr("stdDeviation", 1)
            .attr("result", "blur");

        // translate output of Gaussian blur to the right and downwards with 2px
        // store result in offsetBlur
        filter.append("feOffset")
            .attr("in", "blur")
            .attr("dx", 0)
            .attr("dy", 0)
            .attr("result", "offsetBlur");

        // overlay original SourceGraphic over translated blurred opacity by using
        // feMerge filter. Order of specifying inputs is important!
        var feMerge = filter.append("feMerge");

        feMerge.append("feMergeNode")
            .attr("in", "offsetBlur");
        feMerge.append("feMergeNode")
            .attr("in", "SourceGraphic");
    }
};

/**
 * Customize Sources modal
 */
Craft.CustomizeSourcesModal = Garnish.Modal.extend(
{
	elementIndex: null,
	$elementIndexSourcesContainer: null,

	$sidebar: null,
	$sourcesContainer: null,
	$sourceSettingsContainer: null,
	$newHeadingBtn: null,
	$footer: null,
	$footerBtnContainer: null,
	$saveBtn: null,
	$cancelBtn: null,
	$saveSpinner: null,
	$loadingSpinner: null,

	sourceSort: null,
	sources: null,
	selectedSource: null,
	updateSourcesOnSave: false,

	availableTableAttributes: null,

	init: function(elementIndex, settings)
	{
		this.base();

		this.setSettings(settings, {
			resizable: true
		});

		this.elementIndex = elementIndex;
		this.$elementIndexSourcesContainer = this.elementIndex.$sidebar.children('nav').children('ul');

		var $container = $('<form class="modal customize-sources-modal"/>').appendTo(Garnish.$bod);

		this.$sidebar = $('<div class="cs-sidebar block-types"/>').appendTo($container);
		this.$sourcesContainer = $('<div class="sources">').appendTo(this.$sidebar);
		this.$sourceSettingsContainer = $('<div class="source-settings">').appendTo($container);

		this.$footer = $('<div class="footer"/>').appendTo($container);
		this.$footerBtnContainer = $('<div class="buttons right"/>').appendTo(this.$footer);
		this.$cancelBtn = $('<div class="btn" role="button"/>').text(Craft.t('Cancel')).appendTo(this.$footerBtnContainer);
		this.$saveBtn = $('<div class="btn submit disabled" role="button"/>').text(Craft.t('Save')).appendTo(this.$footerBtnContainer);
		this.$saveSpinner = $('<div class="spinner hidden"/>').appendTo(this.$footerBtnContainer);
		this.$newHeadingBtn = $('<div class="btn submit add icon"/>').text(Craft.t('New heading')).appendTo($('<div class="buttons left secondary-buttons"/>').appendTo(this.$footer));

		this.$loadingSpinner = $('<div class="spinner"/>').appendTo($container);

		this.setContainer($container);
		this.show();

		var data = {
			elementType: this.elementIndex.elementType
		};

		Craft.postActionRequest('elementIndexSettings/getCustomizeSourcesModalData', data, $.proxy(function(response, textStatus)
		{
			this.$loadingSpinner.remove();

			if (textStatus == 'success')
			{
				this.$saveBtn.removeClass('disabled');
				this.buildModal(response);
			}

		}, this));

		this.addListener(this.$newHeadingBtn, 'click', 'handleNewHeadingBtnClick');
		this.addListener(this.$cancelBtn, 'click', 'hide');
		this.addListener(this.$saveBtn, 'click', 'save');
		this.addListener(this.$container, 'submit', 'save');
	},

	buildModal: function(response)
	{
		// Store the available table attribute options
		this.availableTableAttributes = response.availableTableAttributes;

		// Create the source item sorter
		this.sourceSort = new Garnish.DragSort({
			handle: '.move',
			axis: 'y',
			onSortChange: $.proxy(function() {
				this.updateSourcesOnSave = true;
			}, this)
		});

		// Create the sources
		this.sources = [];

		for (var i = 0; i < response.sources.length; i++)
		{
			var source = this.addSource(response.sources[i]);
			this.sources.push(source);
		}

		if (!this.selectedSource && typeof this.sources[0] != typeof undefined)
		{
			this.sources[0].select();
		}
	},

	addSource: function(sourceData)
	{
		var $item = $('<div class="customize-sources-item"/>').appendTo(this.$sourcesContainer),
			$itemLabel = $('<div class="label"/>').appendTo($item),
			$itemInput = $('<input type="hidden"/>').appendTo($item),
			$moveHandle = $('<a class="move icon" title="'+Craft.t('Reorder')+'" role="button"></a>').appendTo($item),
			source;

		// Is this a heading?
		if (typeof sourceData.heading !== typeof undefined)
		{
			$item.addClass('heading');
			$itemInput.attr('name', 'sourceOrder[][heading]');
			source = new Craft.CustomizeSourcesModal.Heading(this, $item, $itemLabel, $itemInput, sourceData);
			source.updateItemLabel(sourceData.heading);
		}
		else
		{
			$itemInput.attr('name', 'sourceOrder[][key]').val(sourceData.key);
			source = new Craft.CustomizeSourcesModal.Source(this, $item, $itemLabel, $itemInput, sourceData);
			source.updateItemLabel(sourceData.label);

			// Select this by default?
			if (sourceData.key == this.elementIndex.sourceKey)
			{
				source.select();
			}
		}

		this.sourceSort.addItems($item);

		return source;
	},

	handleNewHeadingBtnClick: function()
	{
		var source = this.addSource({
			heading: ''
		});

		Garnish.scrollContainerToElement(this.$sidebar, source.$item);

		source.select();
		this.updateSourcesOnSave = true;
	},

	save: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		if (this.$saveBtn.hasClass('disabled') || !this.$saveSpinner.hasClass('hidden'))
		{
			return;
		}

		this.$saveSpinner.removeClass('hidden');
		var data = this.$container.serialize()+'&elementType='+this.elementIndex.elementType;

		Craft.postActionRequest('elementIndexSettings/saveCustomizeSourcesModalSettings', data, $.proxy(function(response, textStatus)
		{
			this.$saveSpinner.addClass('hidden');

			if (textStatus == 'success' && response.success)
			{
				// Have any changes been made to the source list?
				if (this.updateSourcesOnSave)
				{
					if (this.$elementIndexSourcesContainer.length)
					{
						var $lastSource,
							$pendingHeading;

						for (var i = 0; i < this.sourceSort.$items.length; i++)
						{
							var $item = this.sourceSort.$items.eq(i),
								source = $item.data('source'),
								$indexSource = source.getIndexSource();

							if (!$indexSource)
							{
								continue;
							}

							if (source.isHeading())
							{
								$pendingHeading = $indexSource;
							}
							else
							{
								if ($pendingHeading)
								{
									this.appendSource($pendingHeading, $lastSource);
									$lastSource = $pendingHeading;
									$pendingHeading = null;
								}

								this.appendSource($indexSource, $lastSource);
								$lastSource = $indexSource;
							}
						}

						// Remove any additional sources (most likely just old headings)
						if ($lastSource)
						{
							var $extraSources = $lastSource.nextAll();
							this.elementIndex.sourceSelect.removeItems($extraSources);
							$extraSources.remove();
						}
					}
				}

				// If a source is selected, have the element index select that one by default on the next request
				if (this.selectedSource && this.selectedSource.sourceData.key)
				{
					this.elementIndex.selectSourceByKey(this.selectedSource.sourceData.key);
					this.elementIndex.updateElements();
				}

				Craft.cp.displayNotice(Craft.t('Source settings saved'));
				this.hide();
			}
			else
			{
				var error = (textStatus == 'success' && response.error ? response.error : Craft.t('An unknown error occurred.'));
				Craft.cp.displayError(error);
			}
		}, this));
	},

	appendSource: function($source, $lastSource)
	{
		if (!$lastSource)
		{
			$source.prependTo(this.$elementIndexSourcesContainer);
		}
		else
		{
			$source.insertAfter($lastSource);
		}
	},

	destroy: function()
	{
		for (var i = 0; i < this.sources.length; i++)
		{
			this.sources[i].destroy();
		}

		delete this.sources;
		this.base();
	}
});

Craft.CustomizeSourcesModal.BaseSource = Garnish.Base.extend(
{
	modal: null,

	$item: null,
	$itemLabel: null,
	$itemInput: null,
	$settingsContainer: null,

	sourceData: null,

	init: function(modal, $item, $itemLabel, $itemInput, sourceData)
	{
		this.modal = modal;
		this.$item = $item;
		this.$itemLabel = $itemLabel;
		this.$itemInput = $itemInput;
		this.sourceData = sourceData;

		this.$item.data('source', this);

		this.addListener(this.$item, 'click', 'select');
	},

	isHeading: function()
	{
		return false;
	},

	isSelected: function()
	{
		return (this.modal.selectedSource == this);
	},

	select: function()
	{
		if (this.isSelected())
		{
			return;
		}

		if (this.modal.selectedSource)
		{
			this.modal.selectedSource.deselect();
		}

		this.$item.addClass('sel');
		this.modal.selectedSource = this;

		if (!this.$settingsContainer)
		{
			this.$settingsContainer = $('<div/>')
				.append(this.createSettings())
				.appendTo(this.modal.$sourceSettingsContainer);
		}
		else
		{
			this.$settingsContainer.removeClass('hidden');
		}

		this.modal.$sourceSettingsContainer.scrollTop(0);
	},

	createSettings: function()
	{
	},

	getIndexSource: function()
	{
	},

	deselect: function()
	{
		this.$item.removeClass('sel');
		this.modal.selectedSource = null;
		this.$settingsContainer.addClass('hidden');
	},

	updateItemLabel: function(val)
	{
		this.$itemLabel.text(val);
	},

	destroy: function()
	{
		this.$item.data('source', null);
		this.base();
	}
});

Craft.CustomizeSourcesModal.Source = Craft.CustomizeSourcesModal.BaseSource.extend(
{
	createSettings: function()
	{
		if (this.sourceData.tableAttributes.length)
		{
			// Create the title column option
			var firstAttribute = this.sourceData.tableAttributes[0],
				firstKey = firstAttribute[0],
				firstLabel = firstAttribute[1],
				$titleColumnCheckbox = this.createTableColumnOption(firstKey, firstLabel, true, true);

			// Create the rest of the options
			var $columnCheckboxes = $('<div/>'),
				selectedAttributes = [firstKey];

			$('<input type="hidden" name="sources['+this.sourceData.key+'][tableAttributes][]" value=""/>').appendTo($columnCheckboxes);

			// Add the selected columns, in the selected order
			for (var i = 1; i < this.sourceData.tableAttributes.length; i++)
			{
				var attribute = this.sourceData.tableAttributes[i],
					key = attribute[0],
					label = attribute[1];

				$columnCheckboxes.append(this.createTableColumnOption(key, label, false, true));
				selectedAttributes.push(key);
			}

			// Add the rest
			for (var i = 0; i < this.modal.availableTableAttributes.length; i++)
			{
				var attribute = this.modal.availableTableAttributes[i],
					key = attribute[0],
					label = attribute[1];

				if (!Craft.inArray(key, selectedAttributes))
				{
					$columnCheckboxes.append(this.createTableColumnOption(key, label, false, false));
				}
			}

			new Garnish.DragSort($columnCheckboxes.children(), {
				handle: '.move',
				axis: 'y'
			});

			return Craft.ui.createField($([$titleColumnCheckbox[0], $columnCheckboxes[0]]), {
				label: Craft.t('Table Columns'),
				instructions: Craft.t('Choose which table columns should be visible for this source, and in which order.')
			});
		}
	},

	createTableColumnOption: function(key, label, first, checked)
	{
		$option = $('<div class="customize-sources-table-column"/>')
		.append('<div class="icon move"/>')
		.append(
			Craft.ui.createCheckbox({
				label: label,
				name: 'sources['+this.sourceData.key+'][tableAttributes][]',
				value: key,
				checked: checked,
				disabled: first
			})
		);

		if (first)
		{
			$option.children('.move').addClass('disabled');
		}

		return $option;
	},

	getIndexSource: function()
	{
		var $source = this.modal.elementIndex.getSourceByKey(this.sourceData.key);

		if ($source)
		{
			return $source.closest('li');
		}
	}
});

Craft.CustomizeSourcesModal.Heading = Craft.CustomizeSourcesModal.BaseSource.extend(
{
	$labelField: null,
	$labelInput: null,
	$deleteBtn: null,

	isHeading: function()
	{
		return true;
	},

	select: function()
	{
		this.base();
		this.$labelInput.focus();
	},

	createSettings: function()
	{
		this.$labelField = Craft.ui.createTextField({
			label: Craft.t('Heading'),
			instructions: Craft.t('This can be left blank if you just want an unlabeled separator.'),
			value: this.sourceData.heading
		});

		this.$labelInput = this.$labelField.find('.text');

		this.$deleteBtn = $('<a class="error delete"/>').text(Craft.t('Delete heading'));

		this.addListener(this.$labelInput, 'textchange', 'handleLabelInputChange');
		this.addListener(this.$deleteBtn, 'click', 'deleteHeading');

		return $([
			this.$labelField[0],
			$('<hr/>')[0],
			this.$deleteBtn[0]
		]);
	},

	handleLabelInputChange: function()
	{
		this.updateItemLabel(this.$labelInput.val());
		this.modal.updateSourcesOnSave = true;
	},

	updateItemLabel: function(val)
	{
		this.$itemLabel.html((val ? Craft.escapeHtml(val) : '<em class="light">'+Craft.t('(blank)')+'</em>')+'&nbsp;');
		this.$itemInput.val(val);
	},

	deleteHeading: function()
	{
		this.modal.sourceSort.removeItems(this.$item);
		this.modal.sources.splice($.inArray(this, this.modal.sources), 1);
		this.modal.updateSourcesOnSave = true;

		if (this.isSelected())
		{
			this.deselect();

			if (this.modal.sources.length)
			{
				this.modal.sources[0].select();
			}
		}

		this.$item.remove();
		this.$settingsContainer.remove();
		this.destroy();
	},

	getIndexSource: function()
	{
		var label = (this.$labelInput ? this.$labelInput.val() : this.sourceData.heading);
		return $('<li class="heading"/>').append($('<span/>').text(label));
	}
});

/**
 * DataTableSorter
 */
Craft.DataTableSorter = Garnish.DragSort.extend(
{
	$table: null,

	init: function(table, settings)
	{
		this.$table = $(table);
		var $rows = this.$table.children('tbody').children(':not(.filler)');

		settings = $.extend({}, Craft.DataTableSorter.defaults, settings);

		settings.container = this.$table.children('tbody');
		settings.helper = $.proxy(this, 'getHelper');
		settings.caboose = '<tr/>';
		settings.axis = Garnish.Y_AXIS;
		settings.magnetStrength = 4;
		settings.helperLagBase = 1.5;

		this.base($rows, settings);
	},

	getHelper: function($helperRow)
	{
		var $helper = $('<div class="'+this.settings.helperClass+'"/>').appendTo(Garnish.$bod),
			$table = $('<table/>').appendTo($helper),
			$tbody = $('<tbody/>').appendTo($table);

		$helperRow.appendTo($tbody);

		// Copy the table width and classes
		$table.width(this.$table.width());
		$table.prop('className', this.$table.prop('className'));

		// Copy the column widths
		var $firstRow = this.$table.find('tr:first'),
			$cells = $firstRow.children(),
			$helperCells = $helperRow.children();

		for (var i = 0; i < $helperCells.length; i++)
		{
			$($helperCells[i]).width($($cells[i]).width());
		}

		return $helper;
	}

},
{
	defaults: {
		handle: '.move',
		helperClass: 'datatablesorthelper'
	}
});


/**
 * Chart Date Range Picker
 */
Craft.DateRangePicker = Garnish.Base.extend(
{
    hud: null,
    value: null,
    presets: null,

    startDate: null,

    $startDateInput: null,
    $endDateInput: null,

    init: function(trigger, settings)
    {
        this.$trigger = trigger;

        this.setSettings(settings, Craft.DateRangePicker.defaults);

        if(this.settings.customRangeStartDate)
        {
            this.customRangeStartDate = new Date(this.settings.customRangeStartDate);
        }

        if(this.settings.customRangeEndDate)
        {
            this.customRangeEndDate = new Date(this.settings.customRangeEndDate);
        }

        this.value = this.settings.value;
        this.presets = this.settings.presets;


        if(this.value == 'customrange')
        {
            this.startDate = this.customRangeStartDate;
            this.endDate = this.customRangeEndDate;
        }
        else
        {
            this.startDate = this.presets[this.value].startDate;
            this.endDate = this.presets[this.value].endDate;
        }


        var dateRangeValue = this.presets[this.value].label;
        this.$trigger.data('value', dateRangeValue);

        this.addListener(this.$trigger, 'click', 'showHud');
    },

    getStartDate: function()
    {
        return this.startDate;
    },

    getEndDate: function()
    {
        return this.endDate;
    },

    showHud: function()
    {
        this.$trigger.addClass('active');

        if (!this.hud)
        {
            this.createHud();

            // default value

            if(this.value)
            {
                var $item = this.$items.filter('[data-value='+this.value+']');
                var value = $item.data('value');
                var label = $item.data('label');
                var startDate = ($item.data('start-date') != 'undefined' ? $item.data('start-date') : null);
                var endDate = ($item.data('end-date') != 'undefined' ? $item.data('end-date') : null);

                $item.addClass('sel');

                this.$trigger.data('value', label);
            }
        }
        else
        {
            this.hud.show();
        }
    },

    createHud: function()
    {
        this.$hudBody = $('<div></div>');

        this.createPresets();

        $('<hr />').appendTo(this.$hudBody);

        this.createCustomRangeFields();


        // initialize items

        this.$items = $('a.item', this.$hudBody);

        this.addListener(this.$items, 'click', 'selectItem');

        // instiantiate hud
        this.hud = new Garnish.HUD(this.$trigger, this.$hudBody, {
            hudClass: 'hud daterange-hud',
            onSubmit: $.proxy(this, 'save'),
            onShow: $.proxy(function()
            {
                this.$trigger.addClass('active');
            }, this),
            onHide: $.proxy(function()
            {
                this.$trigger.removeClass('active');
            }, this)
        });
    },

    createPresets: function()
    {
        var $presets = $('<div class="daterange-items" />').appendTo(this.$hudBody);
        var $presetsUl = $('<ul />').appendTo($presets);

        $.each(this.presets, function(key, item)
        {
            if(key != 'customrange')
            {
                $('<li><a class="item" data-value="'+key+'" data-label="'+item.label+'" data-start-date="'+item.startDate+'" data-end-date="'+item.endDate+'">'+item.label+'</a></li>').appendTo($presetsUl);
            }
        });
    },

    createCustomRangeFields: function()
    {
        var $customRange = $('<div class="daterange-items" />').appendTo(this.$hudBody),
            $customRangeUl = $('<ul />').appendTo($customRange),
            $customRangeLi = $('<li />').appendTo($customRangeUl),
            $customRangeLink = $('<a class="item" data-value="customrange" data-label="'+this.presets.customrange.label+'">'+this.presets.customrange.label+'</a>').appendTo($customRangeLi);

        var $dateRangeFields =  $('<div class="daterange-fields"></div>').appendTo($customRange),
            $startDateWrapper = $('<div class="datewrapper"></div>').appendTo($dateRangeFields),
            $endDateWrapper = $('<div class="datewrapper"></div>').appendTo($dateRangeFields);

        // custom range startDate

        if(!this.customRangeStartDate)
        {
            var date = new Date();
            date = date.getTime() - (60 * 60 * 24 * 7 * 1000);
            this.customRangeStartDate = new Date(date);
        }

        this.$startDateInput = $('<input type="text" value="'+Craft.formatDate(this.customRangeStartDate)+'" class="text" size="20" autocomplete="off" value="" />').appendTo($startDateWrapper);
        this.$startDateInput.datepicker($.extend({
            onClose: $.proxy(function(dateText, inst)
            {
                this.$items.removeClass('sel');
                $('[data-value=customrange]').addClass('sel');

                var selectedDate = new Date(inst.currentYear, inst.currentMonth, inst.currentDay);

                this.customRangeStartDate = selectedDate;

                if(selectedDate.getTime() > this.customRangeEndDate.getTime())
                {
                    // if selectedDate > endDate, set endDate at selectedDate plus 7 days
                    var newEndDate = selectedDate.getTime() + (60 * 60 * 24 * 7 * 1000);
                    newEndDate = new Date(newEndDate);
                    this.customRangeEndDate = newEndDate;
                    this.$endDateInput.val(Craft.formatDate(this.customRangeEndDate));
                }

                this.showCustomRangeApplyButton();

            }, this)
        }, Craft.datepickerOptions));

        // custom range endDate

        if(!this.customRangeEndDate)
        {
            this.customRangeEndDate = new Date();
        }

        this.$endDateInput = $('<input type="text" value="'+Craft.formatDate(this.customRangeEndDate)+'" class="text" size="20" autocomplete="off" value="" />').appendTo($endDateWrapper);
        this.$endDateInput.datepicker($.extend({
            onClose: $.proxy(function(dateText, inst)
            {
                this.$items.removeClass('sel');
                $('[data-value=customrange]').addClass('sel');

                var selectedDate = new Date(inst.currentYear, inst.currentMonth, inst.currentDay);

                this.customRangeEndDate = selectedDate;

                if(selectedDate.getTime() < this.customRangeStartDate.getTime())
                {
                    // if selectedDate < startDate, set startDate at selectedDate minus 7 days
                    var newStartDate = selectedDate.getTime() - (60 * 60 * 24 * 7 * 1000);
                    newStartDate = new Date(newStartDate);
                    this.customRangeStartDate = newStartDate;
                    this.$startDateInput.val(Craft.formatDate(this.customRangeStartDate));
                }

                this.showCustomRangeApplyButton();

            }, this)
        }, Craft.datepickerOptions));
    },

    selectItem: function(ev)
    {
        var $item = $(ev.currentTarget);
        this._selectItem($item);
    },

    _selectItem: function($item)
    {
        this.$items.removeClass('sel');

        var label = $item.data('label');
        var value = $item.data('value');

        if(value != 'customrange')
        {
            this.startDate = $item.data('start-date');
            this.endDate = $item.data('end-date');
        }
        else
        {
            this.startDate = this.customRangeStartDate;
            this.endDate = this.customRangeEndDate;
        }

        this.startDate = (this.startDate != 'undefined' ? this.startDate : null);
        this.endDate = (this.endDate != 'undefined' ? this.endDate : null);

        $item.addClass('sel');

        this.$trigger.data('value', label);

        this.hud.hide();

        this.hideCustomRangeApplyButton();

        this.onAfterSelect(value, this.startDate, this.endDate, this.customRangeStartDate, this.customRangeEndDate);
    },

    hideCustomRangeApplyButton: function()
    {
        if(this.$applyBtn)
        {
            this.$applyBtn.parent().addClass('hidden');
        }
    },

    showCustomRangeApplyButton: function()
    {
        if(!this.$applyBtn)
        {
            var $buttons = $('<div class="buttons" />').appendTo(this.$hudBody);
            this.$applyBtn = $('<input type="button" class="btn" value="'+Craft.t('Apply')+'" />').appendTo($buttons);

            this.addListener(this.$applyBtn, 'click', 'applyCustomRange');
        }
        else
        {
            this.$applyBtn.parent().removeClass('hidden');
        }
    },

    applyCustomRange: function()
    {
        var $item = this.$items.filter('[data-value=customrange]');

        this._selectItem($item);
    },

    onAfterSelect: function(value, startDate, endDate, customRangeStartDate, customRangeEndDate)
    {
        this.settings.onAfterSelect(value, startDate, endDate, customRangeStartDate, customRangeEndDate);
    }
},
{
    defaults: {
        value: null,
        presets: {
            d7 : {
                label: Craft.t('Last 7 days'),
                startDate: '-7 days'
            },
            d30: {
                label: Craft.t('Last 30 days'),
                startDate: '-30 days'
            },
            lastweek: {
                label: Craft.t('Last Week'),
                startDate: '-14 days',
                endDate: '-7 days',
            },
            lastmonth: {
                label: Craft.t('Last Month'),
                startDate: '-60 days',
                endDate: '-30 days',
            },

            customrange: {
                label: Craft.t('Custom Range'),
            }
        },
        customRangeStartDate: null,
        customRangeEndDate: null,
        onAfterSelect: $.noop
    }
});

/**
 * Delete User Modal
 */
Craft.DeleteUserModal = Garnish.Modal.extend(
{
	id: null,
	userId: null,

	$deleteActionRadios: null,
	$deleteSpinner: null,

	userSelect: null,
	_deleting: false,

	init: function(userId, settings)
	{
		this.id = Math.floor(Math.random()*1000000000);
		this.userId = userId;
		settings = $.extend(Craft.DeleteUserModal.defaults, settings);

		var $form = $(
				'<form class="modal fitted deleteusermodal" method="post" accept-charset="UTF-8">' +
					Craft.getCsrfInput() +
					'<input type="hidden" name="action" value="users/deleteUser"/>' +
					(!Garnish.isArray(this.userId) ? '<input type="hidden" name="userId" value="'+this.userId+'"/>' : '') +
					'<input type="hidden" name="redirect" value="'+(Craft.edition == Craft.Pro ? 'users' : 'dashboard')+'"/>' +
				'</form>'
			).appendTo(Garnish.$bod),
			$body = $(
				'<div class="body">' +
					'<p>'+Craft.t('What do you want to do with their content?')+'</p>' +
					'<div class="options">' +
						'<label><input type="radio" name="contentAction" value="transfer"/> '+Craft.t('Transfer it to:')+'</label>' +
						'<div id="transferselect'+this.id+'" class="elementselect">' +
							'<div class="elements"></div>' +
							'<div class="btn add icon dashed">'+Craft.t('Choose a user')+'</div>' +
						'</div>' +
					'</div>' +
					'<div>' +
						'<label><input type="radio" name="contentAction" value="delete"/> '+Craft.t('Delete it')+'</label>' +
					'</div>' +
				'</div>'
			).appendTo($form),
			$buttons = $('<div class="buttons right"/>').appendTo($body),
			$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttons);

		this.$deleteActionRadios = $body.find('input[type=radio]');
		this.$deleteSubmitBtn = $('<input type="submit" class="btn submit disabled" value="'+(Garnish.isArray(this.userId) ? Craft.t('Delete users') : Craft.t('Delete user'))+'" />').appendTo($buttons);
		this.$deleteSpinner = $('<div class="spinner hidden"/>').appendTo($buttons);

		var idParam;

		if (Garnish.isArray(this.userId))
		{
			idParam = ['and'];

			for (var i = 0; i < this.userId.length; i++)
			{
				idParam.push('not '+this.userId[i]);
			}
		}
		else
		{
			idParam = 'not '+this.userId;
		}

		this.userSelect = new Craft.BaseElementSelectInput({
			id: 'transferselect'+this.id,
			name: 'transferContentTo',
			elementType: 'User',
			criteria: {
				id: idParam
			},
			limit: 1,
			modalSettings: {
				closeOtherModals: false
			},
			onSelectElements: $.proxy(function()
			{
				this.updateSizeAndPosition();

				if (!this.$deleteActionRadios.first().prop('checked'))
				{
					this.$deleteActionRadios.first().click();
				}
				else
				{
					this.validateDeleteInputs();
				}
			}, this),
			onRemoveElements: $.proxy(this, 'validateDeleteInputs'),
			selectable: false,
			editable: false
		});

		this.addListener($cancelBtn, 'click', 'hide');

		this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
		this.addListener($form, 'submit', 'handleSubmit');

		this.base($form, settings);
	},

	validateDeleteInputs: function()
	{
		var validates = false;

		if (this.$deleteActionRadios.eq(0).prop('checked'))
		{
			validates = !!this.userSelect.totalSelected;
		}
		else if (this.$deleteActionRadios.eq(1).prop('checked'))
		{
			validates = true;
		}

		if (validates)
		{
			this.$deleteSubmitBtn.removeClass('disabled');
		}
		else
		{
			this.$deleteSubmitBtn.addClass('disabled');
		}

		return validates;
	},

	handleSubmit: function(ev)
	{
		if (this._deleting || !this.validateDeleteInputs())
		{
			ev.preventDefault();
			return;
		}

		this.$deleteSubmitBtn.addClass('active');
		this.$deleteSpinner.removeClass('hidden');
		this.disable();
		this.userSelect.disable();
		this._deleting = true;

		// Let the onSubmit callback prevent the form from getting submitted
		if (this.settings.onSubmit() === false)
		{
			ev.preventDefault();
		}
	},

	onFadeIn: function()
	{
		// Auto-focus the first radio
		if (!Garnish.isMobileBrowser(true))
		{
			this.$deleteActionRadios.first().focus();
		}

		this.base();
	}
},
{
	defaults: {
		onSubmit: $.noop
	}
});

/**
 * Editable table class
 */
Craft.EditableTable = Garnish.Base.extend(
{
	initialized: false,

	id: null,
	baseName: null,
	columns: null,
	sorter: null,
	biggestId: -1,

	$table: null,
	$tbody: null,
	$addRowBtn: null,

	init: function(id, baseName, columns, settings)
	{
		this.id = id;
		this.baseName = baseName;
		this.columns = columns;
		this.setSettings(settings, Craft.EditableTable.defaults);

		this.$table = $('#'+id);
		this.$tbody = this.$table.children('tbody');

		this.sorter = new Craft.DataTableSorter(this.$table, {
			helperClass: 'editabletablesorthelper',
			copyDraggeeInputValuesToHelper: true
		});

		if (this.isVisible())
		{
			this.initialize();
		}
		else
		{
            // Give everything a chance to initialize
			setTimeout($.proxy(this, 'initializeIfVisible'), 500);
		}
	},

	isVisible: function()
	{
		return (this.$table.height() > 0);
	},

	initialize: function()
	{
		if (this.initialized)
		{
			return;
		}

		this.initialized = true;
		this.removeListener(Garnish.$win, 'resize');

		var $rows = this.$tbody.children();

		for (var i = 0; i < $rows.length; i++)
		{
			new Craft.EditableTable.Row(this, $rows[i]);
		}

		this.$addRowBtn = this.$table.next('.add');
		this.addListener(this.$addRowBtn, 'activate', 'addRow');
	},

	initializeIfVisible: function()
	{
        this.removeListener(Garnish.$win, 'resize');

        if (this.isVisible())
        {
            this.initialize();
        }
        else
		{
            this.addListener(Garnish.$win, 'resize', 'initializeIfVisible');
        }
	},

	addRow: function()
	{
		var rowId = this.settings.rowIdPrefix+(this.biggestId+1),
			rowHtml = this.getRowHtml(rowId, this.columns, this.baseName, {}),
			$tr = $(rowHtml).appendTo(this.$tbody);

		new Craft.EditableTable.Row(this, $tr);
		this.sorter.addItems($tr);

		// Focus the first input in the row
		$tr.find('input,textarea,select').first().focus();

		// onAddRow callback
		this.settings.onAddRow($tr);
	},

	getRowHtml: function(rowId, columns, baseName, values)
	{
		return Craft.EditableTable.getRowHtml(rowId, columns, baseName, values);
	}
},
{
	textualColTypes: ['singleline', 'multiline', 'number'],
	defaults: {
		rowIdPrefix: '',
		onAddRow: $.noop,
		onDeleteRow: $.noop
	},

	getRowHtml: function(rowId, columns, baseName, values)
	{
		var rowHtml = '<tr data-id="'+rowId+'">';

		for (var colId in columns)
		{
			var col = columns[colId],
				name = baseName+'['+rowId+']['+colId+']',
				value = (typeof values[colId] != 'undefined' ? values[colId] : ''),
				textual = Craft.inArray(col.type, Craft.EditableTable.textualColTypes);

			rowHtml += '<td class="'+(textual ? 'textual' : '')+' '+(typeof col['class'] != 'undefined' ? col['class'] : '')+'"' +
			              (typeof col.width != 'undefined' ? ' width="'+col.width+'"' : '') +
			              '>';

			switch (col.type)
			{
				case 'select':
				{
					rowHtml += '<div class="select small"><select name="'+name+'">';

					var hasOptgroups = false;

					for (var key in col.options)
					{
						var option = col.options[key];

						if (typeof option.optgroup != 'undefined')
						{
							if (hasOptgroups)
							{
								rowHtml += '</optgroup>';
							}
							else
							{
								hasOptgroups = true;
							}

							rowHtml += '<optgroup label="'+option.optgroup+'">';
						}
						else
						{
							var optionLabel = (typeof option.label != 'undefined' ? option.label : option),
								optionValue = (typeof option.value != 'undefined' ? option.value : key),
								optionDisabled = (typeof option.disabled != 'undefined' ? option.disabled : false);

							rowHtml += '<option value="'+optionValue+'"'+(optionValue == value ? ' selected' : '')+(optionDisabled ? ' disabled' : '')+'>'+optionLabel+'</option>';
						}
					}

					if (hasOptgroups)
					{
						rowHtml += '</optgroup>';
					}

					rowHtml += '</select></div>';

					break;
				}

				case 'checkbox':
				{
					rowHtml += '<input type="hidden" name="'+name+'">' +
					           '<input type="checkbox" name="'+name+'" value="1"'+(value ? ' checked' : '')+'>';

					break;
				}

				default:
				{
					rowHtml += '<textarea name="'+name+'" rows="1">'+value+'</textarea>';
				}
			}

			rowHtml += '</td>';
		}

		rowHtml += '<td class="thin action"><a class="move icon" title="'+Craft.t('Reorder')+'"></a></td>' +
				'<td class="thin action"><a class="delete icon" title="'+Craft.t('Delete')+'"></a></td>' +
			'</tr>';

		return rowHtml;
	}
});

/**
 * Editable table row class
 */
Craft.EditableTable.Row = Garnish.Base.extend(
{
	table: null,
	id: null,
	niceTexts: null,

	$tr: null,
	$tds: null,
	$textareas: null,
	$deleteBtn: null,

	init: function(table, tr)
	{
		this.table = table;
		this.$tr = $(tr);
		this.$tds = this.$tr.children();

		// Get the row ID, sans prefix
		var id = parseInt(this.$tr.attr('data-id').substr(this.table.settings.rowIdPrefix.length));

		if (id > this.table.biggestId)
		{
			this.table.biggestId = id;
		}

		this.$textareas = $();
		this.niceTexts = [];
		var textareasByColId = {};

		var i = 0;

		for (var colId in this.table.columns)
		{
			var col = this.table.columns[colId];

			if (Craft.inArray(col.type, Craft.EditableTable.textualColTypes))
			{
				$textarea = $('textarea', this.$tds[i]);
				this.$textareas = this.$textareas.add($textarea);

				this.addListener($textarea, 'focus', 'onTextareaFocus');
				this.addListener($textarea, 'mousedown', 'ignoreNextTextareaFocus');

				this.niceTexts.push(new Garnish.NiceText($textarea, {
					onHeightChange: $.proxy(this, 'onTextareaHeightChange')
				}));

				if (col.type == 'singleline' || col.type == 'number')
				{
					this.addListener($textarea, 'keypress', { type: col.type }, 'validateKeypress');
					this.addListener($textarea, 'textchange', { type: col.type }, 'validateValue');
				}

				textareasByColId[colId] = $textarea;
			}

			i++;
		}

		// Now that all of the text cells have been nice-ified, let's normalize the heights
		this.onTextareaHeightChange();

		// Now look for any autopopulate columns
		for (var colId in this.table.columns)
		{
			var col = this.table.columns[colId];

			if (col.autopopulate && typeof textareasByColId[col.autopopulate] != 'undefined' && !textareasByColId[colId].val())
			{
				new Craft.HandleGenerator(textareasByColId[colId], textareasByColId[col.autopopulate]);
			}
		}

		var $deleteBtn = this.$tr.children().last().find('.delete');
		this.addListener($deleteBtn, 'click', 'deleteRow');
	},

	onTextareaFocus: function(ev)
	{
		this.onTextareaHeightChange();

		var $textarea = $(ev.currentTarget);

		if ($textarea.data('ignoreNextFocus'))
		{
			$textarea.data('ignoreNextFocus', false);
			return;
		}

		setTimeout(function()
		{
			var val = $textarea.val();

			// Does the browser support setSelectionRange()?
			if (typeof $textarea[0].setSelectionRange != 'undefined')
			{
				// Select the whole value
				var length = val.length * 2;
				$textarea[0].setSelectionRange(0, length);
			}
			else
			{
				// Refresh the value to get the cursor positioned at the end
				$textarea.val(val);
			}
		}, 0);
	},

	ignoreNextTextareaFocus: function(ev)
	{
		$.data(ev.currentTarget, 'ignoreNextFocus', true);
	},

	validateKeypress: function(ev)
	{
		var keyCode = ev.keyCode ? ev.keyCode : ev.charCode;

		if (!Garnish.isCtrlKeyPressed(ev) && (
			(keyCode == Garnish.RETURN_KEY) ||
			(ev.data.type == 'number' && !Craft.inArray(keyCode, Craft.EditableTable.Row.numericKeyCodes))
		))
		{
			ev.preventDefault();
		}
	},

	validateValue: function(ev)
	{
		var safeValue;

		if (ev.data.type == 'number')
		{
			// Only grab the number at the beginning of the value (if any)
			var match = ev.currentTarget.value.match(/^\s*(-?[\d\.]*)/);

			if (match !== null)
			{
				safeValue = match[1];
			}
			else
			{
				safeValue = '';
			}
		}
		else
		{
			// Just strip any newlines
			safeValue = ev.currentTarget.value.replace(/[\r\n]/g, '');
		}

		if (safeValue !== ev.currentTarget.value)
		{
			ev.currentTarget.value = safeValue;
		}
	},

	onTextareaHeightChange: function()
	{
		// Keep all the textareas' heights in sync
		var tallestTextareaHeight = -1;

		for (var i = 0; i < this.niceTexts.length; i++)
		{
			if (this.niceTexts[i].height > tallestTextareaHeight)
			{
				tallestTextareaHeight = this.niceTexts[i].height;
			}
		}

		this.$textareas.css('min-height', tallestTextareaHeight);

		// If the <td> is still taller, go with that insted
		var tdHeight = this.$textareas.first().parent().height();

		if (tdHeight > tallestTextareaHeight)
		{
			this.$textareas.css('min-height', tdHeight);
		}
	},

	deleteRow: function()
	{
		this.table.sorter.removeItems(this.$tr);
		this.$tr.remove();

		// onDeleteRow callback
		this.table.settings.onDeleteRow(this.$tr);
	}
},
{
	numericKeyCodes: [9 /* (tab) */ , 8 /* (delete) */ , 37,38,39,40 /* (arrows) */ , 45,91 /* (minus) */ , 46,190 /* period */ , 48,49,50,51,52,53,54,55,56,57 /* (0-9) */ ]
});

/**
 * Element Action Trigger
 */
Craft.ElementActionTrigger = Garnish.Base.extend(
{
	maxLevels: null,
	newChildUrl: null,
	$trigger: null,
	$selectedItems: null,
	triggerEnabled: true,

	init: function(settings)
	{
		this.setSettings(settings, Craft.ElementActionTrigger.defaults);

		this.$trigger = $('#'+settings.handle+'-actiontrigger');

		// Do we have a custom handler?
		if (this.settings.activate)
		{
			// Prevent the element index's click handler
			this.$trigger.data('custom-handler', true);

			// Is this a custom trigger?
			if (this.$trigger.prop('nodeName') == 'FORM')
			{
				this.addListener(this.$trigger, 'submit', 'handleTriggerActivation');
			}
			else
			{
				this.addListener(this.$trigger, 'click', 'handleTriggerActivation');
			}
		}

		this.updateTrigger();
		Craft.elementIndex.on('selectionChange', $.proxy(this, 'updateTrigger'));
	},

	updateTrigger: function()
	{
		// Ignore if the last element was just unselected
		if (Craft.elementIndex.getSelectedElements().length == 0)
		{
			return;
		}

		if (this.validateSelection())
		{
			this.enableTrigger();
		}
		else
		{
			this.disableTrigger();
		}
	},

	/**
	 * Determines if this action can be performed on the currently selected elements.
	 *
	 * @return bool
	 */
	validateSelection: function()
	{
		var valid = true;
		this.$selectedItems = Craft.elementIndex.getSelectedElements();

		if (!this.settings.batch && this.$selectedItems.length > 1)
		{
			valid = false;
		}
		else if (typeof this.settings.validateSelection == 'function')
		{
			valid = this.settings.validateSelection(this.$selectedItems);
		}

		return valid;
	},

	enableTrigger: function()
	{
		if (this.triggerEnabled)
		{
			return;
		}

		this.$trigger.removeClass('disabled');
		this.triggerEnabled = true;
	},

	disableTrigger: function()
	{
		if (!this.triggerEnabled)
		{
			return;
		}

		this.$trigger.addClass('disabled');
		this.triggerEnabled = false;
	},

	handleTriggerActivation: function(ev)
	{
		ev.preventDefault();
		ev.stopPropagation();

		if (this.triggerEnabled)
		{
			this.settings.activate(this.$selectedItems);
		}
	}
},
{
	defaults: {
		handle: null,
		batch: true,
		validateSelection: null,
		activate: null
	}
});

/**
 * Element editor
 */
Craft.ElementEditor = Garnish.Base.extend(
{
	$element: null,
	elementId: null,
	locale: null,

	$form: null,
	$fieldsContainer: null,
	$cancelBtn: null,
	$saveBtn: null,
	$spinner: null,

	$localeSelect: null,
	$localeSpinner: null,

	hud: null,

	init: function($element, settings)
	{
		// Param mapping
		if (typeof settings == typeof undefined && $.isPlainObject($element))
		{
			// (settings)
			settings = $element;
			$element = null;
		}

		this.$element = $element;
		this.setSettings(settings, Craft.ElementEditor.defaults);

		this.loadHud();
	},

	setElementAttribute: function(name, value)
	{
		if (!this.settings.attributes)
		{
			this.settings.attributes = {};
		}

		if (value === null)
		{
			delete this.settings.attributes[name];
		}
		else
		{
			this.settings.attributes[name] = value;
		}
	},

	getBaseData: function()
	{
		var data = $.extend({}, this.settings.params);

		if (this.settings.locale)
		{
			data.locale = this.settings.locale;
		}
		else if (this.$element && this.$element.data('locale'))
		{
			data.locale = this.$element.data('locale');
		}

		if (this.settings.elementId)
		{
			data.elementId = this.settings.elementId;
		}
		else if (this.$element && this.$element.data('id'))
		{
			data.elementId = this.$element.data('id');
		}

		if (this.settings.elementType)
		{
			data.elementType = this.settings.elementType;
		}

		if (this.settings.attributes)
		{
			data.attributes = this.settings.attributes;
		}

		return data;
	},

	loadHud: function()
	{
		this.onBeginLoading();
		var data = this.getBaseData();
		data.includeLocales = this.settings.showLocaleSwitcher;
		Craft.postActionRequest('elements/getEditorHtml', data, $.proxy(this, 'showHud'));
	},

	showHud: function(response, textStatus)
	{
		this.onEndLoading();

		if (textStatus == 'success')
		{
			var $hudContents = $();

			if (response.locales)
			{
				var $header = $('<div class="hud-header"/>'),
					$localeSelectContainer = $('<div class="select"/>').appendTo($header);

				this.$localeSelect = $('<select/>').appendTo($localeSelectContainer);
				this.$localeSpinner = $('<div class="spinner hidden"/>').appendTo($header);

				for (var i = 0; i < response.locales.length; i++)
				{
					var locale = response.locales[i];
					$('<option value="'+locale.id+'"'+(locale.id == response.locale ? ' selected="selected"' : '')+'>'+locale.name+'</option>').appendTo(this.$localeSelect);
				}

				this.addListener(this.$localeSelect, 'change', 'switchLocale');

				$hudContents = $hudContents.add($header);
			}

			this.$form = $('<div/>');
			this.$fieldsContainer = $('<div class="fields"/>').appendTo(this.$form);

			this.updateForm(response);

			this.onCreateForm(this.$form);

			var $footer = $('<div class="hud-footer"/>').appendTo(this.$form),
				$buttonsContainer = $('<div class="buttons right"/>').appendTo($footer);
			this.$cancelBtn = $('<div class="btn">'+Craft.t('Cancel')+'</div>').appendTo($buttonsContainer);
			this.$saveBtn = $('<input class="btn submit" type="submit" value="'+Craft.t('Save')+'"/>').appendTo($buttonsContainer);
			this.$spinner = $('<div class="spinner hidden"/>').appendTo($buttonsContainer);

			$hudContents = $hudContents.add(this.$form);

			if (!this.hud)
			{
				var hudTrigger = (this.settings.hudTrigger || this.$element);

				this.hud = new Garnish.HUD(hudTrigger, $hudContents, {
					bodyClass: 'body elementeditor',
					closeOtherHUDs: false,
					onShow: $.proxy(this, 'onShowHud'),
					onHide: $.proxy(this, 'onHideHud'),
					onSubmit: $.proxy(this, 'saveElement')
				});

				this.hud.$hud.data('elementEditor', this);

				this.hud.on('hide', $.proxy(function() {
					delete this.hud;
				}, this));
			}
			else
			{
				this.hud.updateBody($hudContents);
				this.hud.updateSizeAndPosition();
			}

			// Focus on the first text input
			$hudContents.find('.text:first').focus();

			this.addListener(this.$cancelBtn, 'click', function() {
				this.hud.hide();
			});
		}
	},

	switchLocale: function()
	{
		var newLocale = this.$localeSelect.val();

		if (newLocale == this.locale)
		{
			return;
		}

		this.$localeSpinner.removeClass('hidden');


		var data = this.getBaseData();
		data.locale = newLocale;

		Craft.postActionRequest('elements/getEditorHtml', data, $.proxy(function(response, textStatus)
		{
			this.$localeSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				this.updateForm(response);
			}
			else
			{
				this.$localeSelect.val(this.locale);
			}
		}, this));
	},

	updateForm: function(response)
	{
		this.locale = response.locale;

		this.$fieldsContainer.html(response.html);

		// Swap any instruction text with info icons
		var $instructions = this.$fieldsContainer.find('> .meta > .field > .heading > .instructions');

		for (var i = 0; i < $instructions.length; i++)
		{

			$instructions.eq(i)
				.replaceWith($('<span/>', {
					'class': 'info',
					'html': $instructions.eq(i).children().html()
				}))
				.infoicon();
		}

		Garnish.requestAnimationFrame($.proxy(function()
		{
			Craft.appendHeadHtml(response.headHtml);
			Craft.appendFootHtml(response.footHtml);
			Craft.initUiElements(this.$fieldsContainer);
		}, this));
	},

	saveElement: function()
	{
		var validators = this.settings.validators;

		if ($.isArray(validators))
		{
			for (var i = 0; i < validators.length; i++)
			{
				if ($.isFunction(validators[i]) && !validators[i].call())
				{
					return false;
				}
			}
		}

		this.$spinner.removeClass('hidden');

		var data = $.param(this.getBaseData())+'&'+this.hud.$body.serialize();
		Craft.postActionRequest('elements/saveElement', data, $.proxy(function(response, textStatus)
		{
			this.$spinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (textStatus == 'success' && response.success)
				{
					if (this.$element && this.locale == this.$element.data('locale'))
					{
						// Update the label
						var $title = this.$element.find('.title'),
							$a = $title.find('a');

						if ($a.length && response.cpEditUrl)
						{
							$a.attr('href', response.cpEditUrl);
							$a.text(response.newTitle);
						}
						else
						{
							$title.text(response.newTitle);
						}
					}

					// Update Live Preview
					if (typeof Craft.livePreview != 'undefined')
					{
						Craft.livePreview.updateIframe(true);
					}

					this.closeHud();
					this.onSaveElement(response);
				}
				else
				{
					this.updateForm(response);
					Garnish.shake(this.hud.$hud);
				}
			}
		}, this));
	},

	closeHud: function()
	{
		this.hud.hide();
		delete this.hud;
	},

	// Events
	// -------------------------------------------------------------------------

	onShowHud: function()
	{
		this.settings.onShowHud();
		this.trigger('showHud');
	},

	onHideHud: function()
	{
		this.settings.onHideHud();
		this.trigger('hideHud');
	},

	onBeginLoading: function()
	{
		if (this.$element)
		{
			this.$element.addClass('loading');
		}

		this.settings.onBeginLoading();
		this.trigger('beginLoading');
	},

	onEndLoading: function()
	{
		if (this.$element)
		{
			this.$element.removeClass('loading');
		}

		this.settings.onEndLoading();
		this.trigger('endLoading');
	},

	onSaveElement: function(response)
	{
		this.settings.onSaveElement(response);
		this.trigger('saveElement', {
			response: response
		});
	},

	onCreateForm: function ($form)
	{
		this.settings.onCreateForm($form);
	}
},
{
	defaults: {
		hudTrigger: null,
		showLocaleSwitcher: true,
		elementId: null,
		elementType: null,
		locale: null,
		attributes: null,
		params: null,

		onShowHud: $.noop,
		onHideHud: $.noop,
		onBeginLoading: $.noop,
		onEndLoading: $.noop,
		onCreateForm: $.noop,
		onSaveElement: $.noop,

		validators: []
	}
});

/**
 * Elevated Session Form
 */
Craft.ElevatedSessionForm = Garnish.Base.extend(
{
	$form: null,
	inputs: null,

	init: function(form, inputs)
	{
		this.$form = $(form);

		// Only check specific inputs?
		if (typeof inputs !== typeof undefined)
		{
			this.inputs = [];
			var inputs = $.makeArray(inputs);

			for (var i = 0; i < inputs.length; i++)
			{
				var $inputs = $(inputs[i]);

				for (var j = 0; j < $inputs.length; j++)
				{
					var $input = $inputs.eq(j);

					this.inputs.push({
						input: $input,
						val: Garnish.getInputPostVal($input)
					});
				}
			}
		}

		this.addListener(this.$form, 'submit', 'handleFormSubmit');
	},

	handleFormSubmit: function(ev)
	{
		// Ignore if we're in the middle of getting the elevated session timeout
		if (Craft.elevatedSessionManager.fetchingTimeout)
		{
			ev.preventDefault();
			return;
		}

		// Are we only interested in certain inputs?
		if (this.inputs)
		{
			var inputsChanged = false;

			for (var i = 0; i < this.inputs.length; i++)
			{
				// Has this input's value changed?
				if (Garnish.getInputPostVal(this.inputs[i].input) != this.inputs[i].val)
				{
					inputsChanged = true;
					break;
				}
			}

			if (!inputsChanged)
			{
				// No need to interrupt the submit
				return;
			}
		}

		// Prevent the form from submitting until the user has an elevated session
		ev.preventDefault();
		Craft.elevatedSessionManager.requireElevatedSession($.proxy(this, 'submitForm'));
	},

	submitForm: function()
	{
		// Don't let handleFormSubmit() interrupt this time
		this.disable();
		this.$form.submit();
		this.enable();
	}
});

/**
 * Elevated Session Manager
 */
Craft.ElevatedSessionManager = Garnish.Base.extend(
{
	fetchingTimeout: false,

	passwordModal: null,
	$passwordInput: null,
	$passwordSpinner: null,
	$submitBtn: null,
	$errorPara: null,

	callback: null,

	/**
	 * Requires that the user has an elevated session.
	 *
	 * @param function callback The callback function that should be called once the user has an elevated session
	 */
	requireElevatedSession: function(callback)
	{
		this.callback = callback;

		// Check the time remaining on the user's elevated session (if any)
		this.fetchingTimeout = true;

		Craft.postActionRequest('users/getElevatedSessionTimeout', $.proxy(function(response, textStatus)
		{
			this.fetchingTimeout = false;

			if (textStatus == 'success')
			{
				// Is there still enough time left or has it been disabled?
				if (response.timeout === false || response.timeout >= Craft.ElevatedSessionManager.minSafeElevatedSessionTimeout)
				{
					this.callback();
				}
				else
				{
					// Show the password modal
					this.showPasswordModal();
				}
			}
		}, this));
	},

	showPasswordModal: function()
	{
		if (!this.passwordModal)
		{
			var $passwordModal = $('<form id="elevatedsessionmodal" class="modal secure fitted"/>'),
				$body = $('<div class="body"><p>'+Craft.t('Enter your password to continue.')+'</p></div>').appendTo($passwordModal),
				$inputContainer = $('<div class="inputcontainer">').appendTo($body),
				$inputsTable = $('<table class="inputs fullwidth"/>').appendTo($inputContainer),
				$inputsRow = $('<tr/>').appendTo($inputsTable),
				$passwordCell = $('<td/>').appendTo($inputsRow),
				$buttonCell = $('<td class="thin"/>').appendTo($inputsRow),
				$passwordWrapper = $('<div class="passwordwrapper"/>').appendTo($passwordCell);

			this.$passwordInput = $('<input type="password" class="text password fullwidth" placeholder="'+Craft.t('Password')+'"/>').appendTo($passwordWrapper);
			this.$passwordSpinner = $('<div class="spinner hidden"/>').appendTo($inputContainer);
			this.$submitBtn = $('<input type="submit" class="btn submit disabled" value="'+Craft.t('Submit')+'" />').appendTo($buttonCell);
			this.$errorPara = $('<p class="error"/>').appendTo($body);

			this.passwordModal = new Garnish.Modal($passwordModal, {
				closeOtherModals: false,
				onFadeIn: $.proxy(function()
				{
					setTimeout($.proxy(this, 'focusPasswordInput'), 100);
				}, this),
				onFadeOut: $.proxy(function()
				{
					this.$passwordInput.val('');
				}, this),
			});

			new Craft.PasswordInput(this.$passwordInput, {
				onToggleInput: $.proxy(function($newPasswordInput) {
					this.$passwordInput = $newPasswordInput;
				}, this)
			});

			this.addListener(this.$passwordInput, 'textchange', 'validatePassword');
			this.addListener($passwordModal, 'submit', 'submitPassword');
		}
		else
		{
			this.passwordModal.show();
		}
	},

	focusPasswordInput: function()
	{
		if (!Garnish.isMobileBrowser(true))
		{
			this.$passwordInput.focus();
		}
	},

	validatePassword: function()
	{
		if (this.$passwordInput.val().length >= 6)
		{
			this.$submitBtn.removeClass('disabled');
			return true;
		}
		else
		{
			this.$submitBtn.addClass('disabled');
			return false;
		}
	},

	submitPassword: function(ev)
	{
		if (ev)
		{
			ev.preventDefault();
		}

		if (!this.validatePassword())
		{
			return;
		}

		this.$passwordSpinner.removeClass('hidden');
		this.clearLoginError();

		var data = {
			password: this.$passwordInput.val()
		};

		Craft.postActionRequest('users/startElevatedSession', data, $.proxy(function(response, textStatus)
		{
			this.$passwordSpinner.addClass('hidden');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.passwordModal.hide();
					this.callback();
				}
				else
				{
					this.showPasswordError(Craft.t('Incorrect password.'));
					Garnish.shake(this.passwordModal.$container);
					this.focusPasswordInput();
				}
			}
			else
			{
				this.showPasswordError();
			}

		}, this));
	},

	showPasswordError: function(error)
	{
		if (error === null || typeof error == 'undefined')
		{
			error = Craft.t('An unknown error occurred.');
		}

		this.$errorPara.text(error);
		this.passwordModal.updateSizeAndPosition();
	},

	clearLoginError: function()
	{
		this.showPasswordError('');
	},
},
{
	minSafeElevatedSessionTimeout: 5,
});

// Instantiate it
Craft.elevatedSessionManager = new Craft.ElevatedSessionManager();

/**
 * Entry index class
 */
Craft.EntryIndex = Craft.BaseElementIndex.extend(
{
	publishableSections: null,
	$newEntryBtnGroup: null,
	$newEntryBtn: null,

	afterInit: function()
	{
		// Find which of the visible sections the user has permission to create new entries in
		this.publishableSections = [];

		for (var i = 0; i < Craft.publishableSections.length; i++)
		{
			var section = Craft.publishableSections[i];

			if (this.getSourceByKey('section:'+section.id))
			{
				this.publishableSections.push(section);
			}
		}

		this.base();
	},

	getDefaultSourceKey: function()
	{
		// Did they request a specific section in the URL?
		if (this.settings.context == 'index' && typeof defaultSectionHandle != typeof undefined)
		{
			if (defaultSectionHandle == 'singles')
			{
				return 'singles';
			}
			else
			{
				for (var i = 0; i < this.$sources.length; i++)
				{
					var $source = $(this.$sources[i]);

					if ($source.data('handle') == defaultSectionHandle)
					{
						return $source.data('key');
					}
				}
			}
		}

		return this.base();
	},

	onSelectSource: function()
	{
		var selectedSourceHandle;

		// Get the handle of the selected source
		if (this.$source.data('key') == 'singles')
		{
			selectedSourceHandle = 'singles';
		}
		else
		{
			selectedSourceHandle = this.$source.data('handle');
		}

		// Update the New Entry button
		// ---------------------------------------------------------------------

		if (this.publishableSections.length)
		{
			// Remove the old button, if there is one
			if (this.$newEntryBtnGroup)
			{
				this.$newEntryBtnGroup.remove();
			}

			// Determine if they are viewing a section that they have permission to create entries in
			var selectedSection;

			if (selectedSourceHandle)
			{
				for (var i = 0; i < this.publishableSections.length; i++)
				{
					if (this.publishableSections[i].handle == selectedSourceHandle)
					{
						selectedSection = this.publishableSections[i];
						break;
					}
				}
			}

			this.$newEntryBtnGroup = $('<div class="btngroup submit"/>');
			var $menuBtn;

			// If they are, show a primary "New entry" button, and a dropdown of the other sections (if any).
			// Otherwise only show a menu button
			if (selectedSection)
			{
				var href = this._getSectionTriggerHref(selectedSection),
					label = (this.settings.context == 'index' ? Craft.t('New entry') : Craft.t('New {section} entry', {section: selectedSection.name}));
				this.$newEntryBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newEntryBtnGroup);

				if (this.settings.context != 'index')
				{
					this.addListener(this.$newEntryBtn, 'click', function(ev)
					{
						this._openCreateEntryModal(ev.currentTarget.getAttribute('data-id'));
					});
				}

				if (this.publishableSections.length > 1)
				{
					$menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newEntryBtnGroup);
				}
			}
			else
			{
				this.$newEntryBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('New entry')+'</div>').appendTo(this.$newEntryBtnGroup);
			}

			if ($menuBtn)
			{
				var menuHtml = '<div class="menu"><ul>';

				for (var i = 0; i < this.publishableSections.length; i++)
				{
					var section = this.publishableSections[i];

					if (this.settings.context == 'index' || section != selectedSection)
					{
						var href = this._getSectionTriggerHref(section),
							label = (this.settings.context == 'index' ? section.name : Craft.t('New {section} entry', {section: section.name}));
						menuHtml += '<li><a '+href+'">'+label+'</a></li>';
					}
				}

				menuHtml += '</ul></div>';

				var $menu = $(menuHtml).appendTo(this.$newEntryBtnGroup),
					menuBtn = new Garnish.MenuBtn($menuBtn);

				if (this.settings.context != 'index')
				{
					menuBtn.on('optionSelect', $.proxy(function(ev)
					{
						this._openCreateEntryModal(ev.option.getAttribute('data-id'));
					}, this));
				}
			}

			this.addButton(this.$newEntryBtnGroup);
		}

		// Update the URL if we're on the Entries index
		// ---------------------------------------------------------------------

		if (this.settings.context == 'index' && typeof history != typeof undefined)
		{
			var uri = 'entries';

			if (selectedSourceHandle)
			{
				uri += '/'+selectedSourceHandle;
			}

			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	_getSectionTriggerHref: function(section)
	{
		if (this.settings.context == 'index')
		{
			return 'href="'+Craft.getUrl('entries/'+section.handle+'/new')+'"';
		}
		else
		{
			return 'data-id="'+section.id+'"';
		}
	},

	_openCreateEntryModal: function(sectionId)
	{
		if (this.$newEntryBtn.hasClass('loading'))
		{
			return;
		}

		// Find the section
		var section;

		for (var i = 0; i < this.publishableSections.length; i++)
		{
			if (this.publishableSections[i].id == sectionId)
			{
				section = this.publishableSections[i];
				break;
			}
		}

		if (!section)
		{
			return;
		}

		this.$newEntryBtn.addClass('inactive');
		var newEntryBtnText = this.$newEntryBtn.text();
		this.$newEntryBtn.text(Craft.t('New {section} entry', {section: section.name}));

		new Craft.ElementEditor({
			hudTrigger: this.$newEntryBtnGroup,
			elementType: 'Entry',
			locale: this.locale,
			attributes: {
				sectionId: sectionId
			},
			onBeginLoading: $.proxy(function()
			{
				this.$newEntryBtn.addClass('loading');
			}, this),
			onEndLoading: $.proxy(function()
			{
				this.$newEntryBtn.removeClass('loading');
			}, this),
			onHideHud: $.proxy(function()
			{
				this.$newEntryBtn.removeClass('inactive').text(newEntryBtnText);
			}, this),
			onSaveElement: $.proxy(function(response)
			{
				// Make sure the right section is selected
				var sectionSourceKey = 'section:'+sectionId;

				if (this.sourceKey != sectionSourceKey)
				{
					this.selectSourceByKey(sectionSourceKey);
				}

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this)
		});
	}
});

// Register it!
Craft.registerElementIndexClass('Entry', Craft.EntryIndex);

/**
 * Handle Generator
 */
Craft.EntryUrlFormatGenerator = Craft.BaseInputGenerator.extend(
{
	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		sourceVal = sourceVal.replace("/<(.*?)>/g", '');

		// Make it lowercase
		sourceVal = sourceVal.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		sourceVal = Craft.asciiString(sourceVal);

		// Handle must start with a letter and end with a letter/number
		sourceVal = sourceVal.replace(/^[^a-z]+/, '');
		sourceVal = sourceVal.replace(/[^a-z0-9]+$/, '');

		// Get the "words"
		var words = Craft.filterArray(sourceVal.split(/[^a-z0-9]+/));

		var urlFormat = words.join('-');

		if (urlFormat && this.settings.suffix)
		{
			urlFormat += this.settings.suffix;
		}

		return urlFormat;
	}
});

Craft.FieldLayoutDesigner = Garnish.Base.extend(
{
	$container: null,
	$tabContainer: null,
	$unusedFieldContainer: null,
	$newTabBtn: null,
	$allFields: null,

	tabGrid: null,
	unusedFieldGrid: null,

	tabDrag: null,
	fieldDrag: null,

	init: function(container, settings)
	{
		this.$container = $(container);
		this.setSettings(settings, Craft.FieldLayoutDesigner.defaults);

		this.$tabContainer = this.$container.children('.fld-tabs');
		this.$unusedFieldContainer = this.$container.children('.unusedfields');
		this.$newTabBtn = this.$container.find('> .newtabbtn-container > .btn');
		this.$allFields = this.$unusedFieldContainer.find('.fld-field');

		// Set up the layout grids
		this.tabGrid = new Craft.Grid(this.$tabContainer, Craft.FieldLayoutDesigner.gridSettings);
		this.unusedFieldGrid = new Craft.Grid(this.$unusedFieldContainer, Craft.FieldLayoutDesigner.gridSettings);

		var $tabs = this.$tabContainer.children();
		for (var i = 0; i < $tabs.length; i++)
		{
			this.initTab($($tabs[i]));
		}

		this.fieldDrag = new Craft.FieldLayoutDesigner.FieldDrag(this);

		if (this.settings.customizableTabs)
		{
			this.tabDrag = new Craft.FieldLayoutDesigner.TabDrag(this);

			this.addListener(this.$newTabBtn, 'activate', 'addTab');
		}
	},

	initTab: function($tab)
	{
		if (this.settings.customizableTabs)
		{
			var $editBtn = $tab.find('.tabs .settings'),
				$menu = $('<div class="menu" data-align="center"/>').insertAfter($editBtn),
				$ul = $('<ul/>').appendTo($menu);

			$('<li><a data-action="rename">'+Craft.t('Rename')+'</a></li>').appendTo($ul);
			$('<li><a data-action="delete">'+Craft.t('Delete')+'</a></li>').appendTo($ul);

			new Garnish.MenuBtn($editBtn, {
				onOptionSelect: $.proxy(this, 'onTabOptionSelect')
			});
		}

		// Don't forget the fields!
		var $fields = $tab.children('.fld-tabcontent').children();

		for (var i = 0; i < $fields.length; i++)
		{
			this.initField($($fields[i]));
		}
	},

	initField: function($field)
	{
		var $editBtn = $field.find('.settings'),
			$menu = $('<div class="menu" data-align="center"/>').insertAfter($editBtn),
			$ul = $('<ul/>').appendTo($menu);

		if ($field.hasClass('fld-required'))
		{
			$('<li><a data-action="toggle-required">'+Craft.t('Make not required')+'</a></li>').appendTo($ul);
		}
		else
		{
			$('<li><a data-action="toggle-required">'+Craft.t('Make required')+'</a></li>').appendTo($ul);
		}

		$('<li><a data-action="remove">'+Craft.t('Remove')+'</a></li>').appendTo($ul);

		new Garnish.MenuBtn($editBtn, {
			onOptionSelect: $.proxy(this, 'onFieldOptionSelect')
		});
	},

	onTabOptionSelect: function(option)
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		var $option = $(option),
			$tab = $option.data('menu').$anchor.parent().parent().parent(),
			action = $option.data('action');

		switch (action)
		{
			case 'rename':
			{
				this.renameTab($tab);
				break;
			}
			case 'delete':
			{
				this.deleteTab($tab);
				break;
			}
		}
	},

	onFieldOptionSelect: function(option)
	{
		var $option = $(option),
			$field = $option.data('menu').$anchor.parent(),
			action = $option.data('action');

		switch (action)
		{
			case 'toggle-required':
			{
				this.toggleRequiredField($field, $option);
				break;
			}
			case 'remove':
			{
				this.removeField($field);
				break;
			}
		}
	},

	renameTab: function($tab)
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		var $labelSpan = $tab.find('.tabs .tab span'),
			oldName = $labelSpan.text(),
			newName = prompt(Craft.t('Give your tab a name.'), oldName);

		if (newName && newName != oldName)
		{
			$labelSpan.text(newName);
			$tab.find('.id-input').attr('name', this.getFieldInputName(newName));
		}
	},

	deleteTab: function($tab)
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		// Find all the fields in this tab
		var $fields = $tab.find('.fld-field');

		for (var i = 0; i < $fields.length; i++)
		{
			var fieldId = $($fields[i]).attr('data-id');
			this.removeFieldById(fieldId);
		}

		this.tabGrid.removeItems($tab);
		this.tabDrag.removeItems($tab);

		$tab.remove();
	},

	toggleRequiredField: function($field, $option)
	{
		if ($field.hasClass('fld-required'))
		{
			$field.removeClass('fld-required');
			$field.find('.required-input').remove();

			setTimeout(function() {
				$option.text(Craft.t('Make required'));
			}, 500);
		}
		else
		{
			$field.addClass('fld-required');
			$('<input class="required-input" type="hidden" name="'+this.settings.requiredFieldInputName+'" value="'+$field.data('id')+'">').appendTo($field);

			setTimeout(function() {
				$option.text(Craft.t('Make not required'));
			}, 500);
		}
	},

	removeField: function($field)
	{
		var fieldId = $field.attr('data-id');

		$field.remove();

		this.removeFieldById(fieldId);
		this.tabGrid.refreshCols(true);
	},

	removeFieldById: function(fieldId)
	{
		var $field = this.$allFields.filter('[data-id='+fieldId+']:first'),
			$group = $field.closest('.fld-tab');

		$field.removeClass('hidden');

		if ($group.hasClass('hidden'))
		{
			$group.removeClass('hidden');
			this.unusedFieldGrid.addItems($group);

			if (this.settings.customizableTabs)
			{
				this.tabDrag.addItems($group);
			}
		}
		else
		{
			this.unusedFieldGrid.refreshCols(true);
		}
	},

	addTab: function()
	{
		if (!this.settings.customizableTabs)
		{
			return;
		}

		var $tab = $('<div class="fld-tab">' +
						'<div class="tabs">' +
							'<div class="tab sel draggable">' +
								'<span>Tab '+(this.tabGrid.$items.length+1)+'</span>' +
								'<a class="settings icon" title="'+Craft.t('Rename')+'"></a>' +
							'</div>' +
						'</div>' +
						'<div class="fld-tabcontent"></div>' +
					'</div>').appendTo(this.$tabContainer);

		this.tabGrid.addItems($tab);
		this.tabDrag.addItems($tab);

		this.initTab($tab);
	},

	getFieldInputName: function(tabName)
	{
		return this.settings.fieldInputName.replace(/__TAB_NAME__/g, Craft.encodeUriComponent(tabName));
	}
},
{
	gridSettings: {
		itemSelector: '.fld-tab:not(.hidden)',
		minColWidth: 240,
		percentageWidths: false,
		fillMode: 'grid',
		snapToGrid: 30
	},
	defaults: {
		customizableTabs: true,
		fieldInputName: 'fieldLayout[__TAB_NAME__][]',
		requiredFieldInputName: 'requiredFields[]'
	}
});


Craft.FieldLayoutDesigner.BaseDrag = Garnish.Drag.extend(
{
	designer: null,
	$insertion: null,
	showingInsertion: false,
	$caboose: null,
	draggingUnusedItem: false,
	addToTabGrid: false,

	/**
	 * Constructor
	 */
	init: function(designer, settings)
	{
		this.designer = designer;

		// Find all the items from both containers
		var $items = this.designer.$tabContainer.find(this.itemSelector)
			.add(this.designer.$unusedFieldContainer.find(this.itemSelector));

		this.base($items, settings);
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.base();

		// Are we dragging an unused item?
		this.draggingUnusedItem = this.$draggee.hasClass('unused');

		// Create the insertion
		this.$insertion = this.getInsertion();

		// Add the caboose
		this.addCaboose();
		this.$items = $().add(this.$items.add(this.$caboose));

		if (this.addToTabGrid)
		{
			this.designer.tabGrid.addItems(this.$caboose);
		}

		// Swap the draggee with the insertion if dragging a selected item
		if (this.draggingUnusedItem)
		{
			this.showingInsertion = false;
		}
		else
		{
			// Actually replace the draggee with the insertion
			this.$insertion.insertBefore(this.$draggee);
			this.$draggee.detach();
			this.$items = $().add(this.$items.not(this.$draggee).add(this.$insertion));
			this.showingInsertion = true;

			if (this.addToTabGrid)
			{
				this.designer.tabGrid.removeItems(this.$draggee);
				this.designer.tabGrid.addItems(this.$insertion);
			}
		}

		this.setMidpoints();
	},

	/**
	 * Append the caboose
	 */
	addCaboose: $.noop,

	/**
	 * Returns the item's container
	 */
	getItemContainer: $.noop,

	/**
	 * Tests if an item is within the tab container.
	 */
	isItemInTabContainer: function($item)
	{
		return (this.getItemContainer($item)[0] == this.designer.$tabContainer[0]);
	},

	/**
	 * Sets the item midpoints up front so we don't have to keep checking on every mouse move
	 */
	setMidpoints: function()
	{
		for (var i = 0; i < this.$items.length; i++)
		{
			var $item = $(this.$items[i]);

			// Skip the unused tabs
			if (!this.isItemInTabContainer($item))
			{
				continue;
			}

			var offset = $item.offset();

			$item.data('midpoint', {
				left: offset.left + $item.outerWidth() / 2,
				top:  offset.top + $item.outerHeight() / 2
			});
		}
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		// Are we hovering over the tab container?
		if (this.draggingUnusedItem && !Garnish.hitTest(this.mouseX, this.mouseY, this.designer.$tabContainer))
		{
			if (this.showingInsertion)
			{
				this.$insertion.remove();
				this.$items = $().add(this.$items.not(this.$insertion));
				this.showingInsertion = false;

				if (this.addToTabGrid)
				{
					this.designer.tabGrid.removeItems(this.$insertion);
				}
				else
				{
					this.designer.tabGrid.refreshCols(true);
				}

				this.setMidpoints();
			}
		}
		else
		{
			// Is there a new closest item?
			this.onDrag._closestItem = this.getClosestItem();

			if (this.onDrag._closestItem != this.$insertion[0])
			{
				if (this.showingInsertion &&
					($.inArray(this.$insertion[0], this.$items) < $.inArray(this.onDrag._closestItem, this.$items)) &&
					($.inArray(this.onDrag._closestItem, this.$caboose) == -1)
				)
				{
					this.$insertion.insertAfter(this.onDrag._closestItem);
				}
				else
				{
					this.$insertion.insertBefore(this.onDrag._closestItem);
				}

				this.$items = $().add(this.$items.add(this.$insertion));
				this.showingInsertion = true;

				if (this.addToTabGrid)
				{
					this.designer.tabGrid.addItems(this.$insertion);
				}
				else
				{
					this.designer.tabGrid.refreshCols(true);
				}

				this.setMidpoints();
			}
		}

		this.base();
	},

	/**
	 * Returns the closest item to the cursor.
	 */
	getClosestItem: function()
	{
		this.getClosestItem._closestItem = null;
		this.getClosestItem._closestItemMouseDiff = null;

		for (this.getClosestItem._i = 0; this.getClosestItem._i < this.$items.length; this.getClosestItem._i++)
		{
			this.getClosestItem._$item = $(this.$items[this.getClosestItem._i]);

			// Skip the unused tabs
			if (!this.isItemInTabContainer(this.getClosestItem._$item))
			{
				continue;
			}

			this.getClosestItem._midpoint = this.getClosestItem._$item.data('midpoint');
			this.getClosestItem._mouseDiff = Garnish.getDist(this.getClosestItem._midpoint.left, this.getClosestItem._midpoint.top, this.mouseX, this.mouseY);

			if (this.getClosestItem._closestItem === null || this.getClosestItem._mouseDiff < this.getClosestItem._closestItemMouseDiff)
			{
				this.getClosestItem._closestItem = this.getClosestItem._$item[0];
				this.getClosestItem._closestItemMouseDiff = this.getClosestItem._mouseDiff;
			}
		}

		return this.getClosestItem._closestItem;
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.showingInsertion)
		{
			this.$insertion.replaceWith(this.$draggee);
			this.$items = $().add(this.$items.not(this.$insertion).add(this.$draggee));

			if (this.addToTabGrid)
			{
				this.designer.tabGrid.removeItems(this.$insertion);
				this.designer.tabGrid.addItems(this.$draggee);
			}
		}

		// Drop the caboose
		this.$items = this.$items.not(this.$caboose);
		this.$caboose.remove();

		if (this.addToTabGrid)
		{
			this.designer.tabGrid.removeItems(this.$caboose);
		}

		// "show" the drag items, but make them invisible
		this.$draggee.css({
			display:    this.draggeeDisplay,
			visibility: 'hidden'
		});

		this.designer.tabGrid.refreshCols(true);
		this.designer.unusedFieldGrid.refreshCols(true);

		// return the helpers to the draggees
		this.returnHelpersToDraggees();

		this.base();
	}
});


Craft.FieldLayoutDesigner.TabDrag = Craft.FieldLayoutDesigner.BaseDrag.extend(
{
	itemSelector: '> div.fld-tab',
	addToTabGrid: true,

	/**
	 * Constructor
	 */
	init: function(designer)
	{
		var settings = {
			handle: '.tab'
		};

		this.base(designer, settings);
	},

	/**
	 * Append the caboose
	 */
	addCaboose: function()
	{
		this.$caboose = $('<div class="fld-tab fld-tab-caboose"/>').appendTo(this.designer.$tabContainer);
	},

	/**
	 * Returns the insertion
	 */
	getInsertion: function()
	{
		var $tab = this.$draggee.find('.tab');

		return $('<div class="fld-tab fld-insertion" style="height: '+this.$draggee.height()+'px;">' +
					'<div class="tabs"><div class="tab sel draggable" style="width: '+$tab.width()+'px; height: '+$tab.height()+'px;"></div></div>' +
					'<div class="fld-tabcontent" style="height: '+this.$draggee.find('.fld-tabcontent').height()+'px;"></div>' +
				'</div>');
	},

	/**
	 * Returns the item's container
	 */
	getItemContainer: function($item)
	{
		return $item.parent();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.draggingUnusedItem && this.showingInsertion)
		{
			// Create a new tab based on that field group
			var $tab = this.$draggee.clone().removeClass('unused'),
				tabName = $tab.find('.tab span').text();

			$tab.find('.fld-field').removeClass('unused');

			// Add the edit button
			$tab.find('.tabs .tab').append('<a class="settings icon" title="'+Craft.t('Edit')+'"></a>');

			// Remove any hidden fields
			var $fields = $tab.find('.fld-field'),
				$hiddenFields = $fields.filter('.hidden').remove();

			$fields = $fields.not($hiddenFields);
			$fields.prepend('<a class="settings icon" title="'+Craft.t('Edit')+'"></a>');

			for (var i = 0; i < $fields.length; i++)
			{
				var $field = $($fields[i]),
					inputName = this.designer.getFieldInputName(tabName);

				$field.append('<input class="id-input" type="hidden" name="'+inputName+'" value="'+$field.data('id')+'">');
			}

			this.designer.fieldDrag.addItems($fields);

			this.designer.initTab($tab);

			// Set the unused field group and its fields to hidden
			this.$draggee.css({ visibility: 'inherit', display: 'field' }).addClass('hidden');
			this.$draggee.find('.fld-field').addClass('hidden');

			// Set this.$draggee to the clone, as if we were dragging that all along
			this.$draggee = $tab;

			// Remember it for later
			this.addItems($tab);

			// Update the grids
			this.designer.tabGrid.addItems($tab);
			this.designer.unusedFieldGrid.removeItems(this.$draggee);
		}

		this.base();
	}
});


Craft.FieldLayoutDesigner.FieldDrag = Craft.FieldLayoutDesigner.BaseDrag.extend(
{
	itemSelector: '> div.fld-tab .fld-field',

	/**
	 * Append the caboose
	 */
	addCaboose: function()
	{
		this.$caboose = $();

		var $fieldContainers = this.designer.$tabContainer.children().children('.fld-tabcontent');

		for (var i = 0; i < $fieldContainers.length; i++)
		{
			var $caboose = $('<div class="fld-tab fld-tab-caboose"/>').appendTo($fieldContainers[i]);
			this.$caboose = this.$caboose.add($caboose);
		}
	},

	/**
	 * Returns the insertion
	 */
	getInsertion: function()
	{
		return $('<div class="fld-field fld-insertion" style="height: '+this.$draggee.height()+'px;"/>');
	},

	/**
	 * Returns the item's container
	 */
	getItemContainer: function($item)
	{
		return $item.parent().parent().parent();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.draggingUnusedItem && this.showingInsertion)
		{
			// Create a new field based on that one
			var $field = this.$draggee.clone().removeClass('unused');
			$field.prepend('<a class="settings icon" title="'+Craft.t('Edit')+'"></a>');
			this.designer.initField($field);

			// Hide the unused field
			this.$draggee.css({ visibility: 'inherit', display: 'field' }).addClass('hidden');

			// Hide the group too?
			if (this.$draggee.siblings(':not(.hidden)').length == 0)
			{
				var $group = this.$draggee.parent().parent();
				$group.addClass('hidden');
				this.designer.unusedFieldGrid.removeItems($group);
			}

			// Set this.$draggee to the clone, as if we were dragging that all along
			this.$draggee = $field;

			// Remember it for later
			this.addItems($field);
		}

		if (this.showingInsertion)
		{
			// Find the field's new tab name
			var tabName = this.$insertion.parent().parent().find('.tab span').text(),
				inputName = this.designer.getFieldInputName(tabName);

			if (this.draggingUnusedItem)
			{
				this.$draggee.append('<input class="id-input" type="hidden" name="'+inputName+'" value="'+this.$draggee.data('id')+'">');
			}
			else
			{
				this.$draggee.find('.id-input').attr('name', inputName);
			}
		}

		this.base();
	}
});

/**
 * FieldToggle
 */
Craft.FieldToggle = Garnish.Base.extend(
{
	$toggle: null,
	targetPrefix: null,
	targetSelector: null,
	reverseTargetSelector: null,

	_$target: null,
	_$reverseTarget: null,
	type: null,

	init: function(toggle)
	{
		this.$toggle = $(toggle);

		// Is this already a field toggle?
		if (this.$toggle.data('fieldtoggle'))
		{
			Garnish.log('Double-instantiating a field toggle on an element');
			this.$toggle.data('fieldtoggle').destroy();
		}

		this.$toggle.data('fieldtoggle', this);

		this.type = this.getType();

		if (this.type == 'select')
		{
			this.targetPrefix = (this.$toggle.attr('data-target-prefix') || '');
		}
		else
		{
			this.targetSelector = this.normalizeTargetSelector(this.$toggle.data('target'));
			this.reverseTargetSelector = this.normalizeTargetSelector(this.$toggle.data('reverse-target'));
		}

		this.findTargets();

		if (this.type == 'link')
		{
			this.addListener(this.$toggle, 'click', 'onToggleChange');
		}
		else
		{
			this.addListener(this.$toggle, 'change', 'onToggleChange');
		}
	},

	normalizeTargetSelector: function(selector)
	{
		if (selector && !selector.match(/^[#\.]/))
		{
			selector = '#'+selector;
		}

		return selector;
	},

	getType: function()
	{
		if (this.$toggle.prop('nodeName') == 'INPUT' && this.$toggle.attr('type').toLowerCase() == 'checkbox')
		{
			return 'checkbox';
		}
		else if (this.$toggle.prop('nodeName') == 'SELECT')
		{
			return 'select';
		}
		else if (this.$toggle.prop('nodeName') == 'A')
		{
			return 'link';
		}
		else if (this.$toggle.prop('nodeName') == 'DIV' && this.$toggle.hasClass('lightswitch'))
		{
			return 'lightswitch';
		}
	},

	findTargets: function()
	{
		if (this.type == 'select')
		{
			this._$target = $(this.normalizeTargetSelector(this.targetPrefix+this.getToggleVal()));
		}
		else
		{
			if (this.targetSelector)
			{
				this._$target = $(this.targetSelector);
			}

			if (this.reverseTargetSelector)
			{
				this._$reverseTarget = $(this.reverseTargetSelector);
			}
		}
	},

	getToggleVal: function()
	{
		if (this.type == 'lightswitch')
		{
			return this.$toggle.children('input').val();
		}
		else
		{
			return Garnish.getInputPostVal(this.$toggle);
		}
	},

	onToggleChange: function()
	{
		if (this.type == 'select')
		{
			this.hideTarget(this._$target);
			this.findTargets();
			this.showTarget(this._$target);
		}
		else
		{
			if (this.type == 'link')
			{
				this.onToggleChange._show = this.$toggle.hasClass('collapsed') || !this.$toggle.hasClass('expanded');
			}
			else
			{
				this.onToggleChange._show = !!this.getToggleVal();
			}

			if (this.onToggleChange._show)
			{
				this.showTarget(this._$target);
				this.hideTarget(this._$reverseTarget);
			}
			else
			{
				this.hideTarget(this._$target);
				this.showTarget(this._$reverseTarget);
			}

			delete this.onToggleChange._show;
		}
	},

	showTarget: function($target)
	{
		if ($target && $target.length)
		{
			this.showTarget._currentHeight = $target.height();

			$target.removeClass('hidden');

			if (this.type != 'select')
			{
				if (this.type == 'link')
				{
					this.$toggle.removeClass('collapsed');
					this.$toggle.addClass('expanded');
				}

				$target.height('auto');
				this.showTarget._targetHeight = $target.height();
				$target.css({
					height: this.showTarget._currentHeight,
					overflow: 'hidden'
				});

				$target.velocity('stop');

				$target.velocity({ height: this.showTarget._targetHeight }, 'fast', function()
				{
					$target.css({
						height: '',
						overflow: ''
					});
				});

				delete this.showTarget._targetHeight;
			}

			delete this.showTarget._currentHeight;

			// Trigger a resize event in case there are any grids in the target that need to initialize
			Garnish.$win.trigger('resize');
		}
	},

	hideTarget: function($target)
	{
		if ($target && $target.length)
		{
			if (this.type == 'select')
			{
				$target.addClass('hidden');
			}
			else
			{
				if (this.type == 'link')
				{
					this.$toggle.removeClass('expanded');
					this.$toggle.addClass('collapsed');
				}

				$target.css('overflow', 'hidden');
				$target.velocity('stop');
				$target.velocity({ height: 0 }, 'fast', function()
				{
					$target.addClass('hidden');
				});
			}
		}
	}
});

Craft.Grid = Garnish.Base.extend(
{
	$container: null,

	$items: null,
	items: null,
	totalCols: null,
	colPctWidth: null,
	sizeUnit: null,

	possibleItemColspans: null,
	possibleItemPositionsByColspan: null,

	itemPositions: null,
	itemColspansByPosition: null,

	layouts: null,
	layout: null,
	itemHeights: null,
	leftPadding: null,

	_refreshingCols: false,
	_refreshColsAfterRefresh: false,
	_forceRefreshColsAfterRefresh: false,

	init: function(container, settings)
	{
		this.$container = $(container);

		// Is this already a grid?
		if (this.$container.data('grid'))
		{
			Garnish.log('Double-instantiating a grid on an element');
			this.$container.data('grid').destroy();
		}

		this.$container.data('grid', this);

		this.setSettings(settings, Craft.Grid.defaults);

		if (this.settings.mode == 'pct')
		{
			this.sizeUnit = '%';
		}
		else
		{
			this.sizeUnit = 'px';
		}

		// Set the refreshCols() proxy that container resizes will trigger
		this.handleContainerHeightProxy = $.proxy(function() {
			this.refreshCols(false, true);
		}, this);

		this.$items = this.$container.children(this.settings.itemSelector);
		this.setItems();
		this.refreshCols(true, false);

		Garnish.$doc.ready($.proxy(function() {
			this.refreshCols(false, false);
		}, this));
	},

	addItems: function(items)
	{
		this.$items = $().add(this.$items.add(items));
		this.setItems();
		this.refreshCols(true, true);
		$(items).velocity('finish');
	},

	removeItems: function(items)
	{
		this.$items = $().add(this.$items.not(items));
		this.setItems();
		this.refreshCols(true, true);
	},

	resetItemOrder: function()
	{
		this.$items = $().add(this.$items);
		this.setItems();
		this.refreshCols(true, true);
	},

	setItems: function()
	{
		this.setItems._ = {};

		this.items = [];

		for (this.setItems._.i = 0; this.setItems._.i < this.$items.length; this.setItems._.i++)
		{
			this.items.push($(this.$items[this.setItems._.i]));
		}

		delete this.setItems._;
	},

	refreshCols: function(force, animate)
	{
		if (this._refreshingCols) {
			this._refreshColsAfterRefresh = true;
			if (force) {
				this._forceRefreshColsAfterRefresh = true;
			}
			return;
		}

		this._refreshingCols = true;

		if (!this.items.length)
		{
			this.completeRefreshCols();
			return;
		}

		this.refreshCols._ = {};

		// Check to see if the grid is actually visible
		this.refreshCols._.oldHeight = this.$container[0].style.height;
		this.$container[0].style.height = 1;
		this.refreshCols._.scrollHeight = this.$container[0].scrollHeight;
		this.$container[0].style.height = this.refreshCols._.oldHeight;

		if (this.refreshCols._.scrollHeight == 0)
		{
			this.completeRefreshCols();
			return;
		}

		if (this.settings.cols)
		{
			this.refreshCols._.totalCols = this.settings.cols;
		}
		else
		{
			this.refreshCols._.totalCols = Math.floor(this.$container.width() / this.settings.minColWidth);

			if (this.settings.maxCols && this.refreshCols._.totalCols > this.settings.maxCols)
			{
				this.refreshCols._.totalCols = this.settings.maxCols;
			}
		}

		if (this.refreshCols._.totalCols == 0)
		{
			this.refreshCols._.totalCols = 1;
		}

		// Same number of columns as before?
		if (force !== true && this.totalCols === this.refreshCols._.totalCols)
		{
			this.completeRefreshCols();
			return;
		}

		this.totalCols = this.refreshCols._.totalCols;

		// Temporarily stop listening to container resizes
		this.removeListener(this.$container, 'resize');

		if (this.settings.fillMode == 'grid')
		{
			this.refreshCols._.itemIndex = 0;

			while (this.refreshCols._.itemIndex < this.items.length)
			{
				// Append the next X items and figure out which one is the tallest
				this.refreshCols._.tallestItemHeight = -1;
				this.refreshCols._.colIndex = 0;

				for (this.refreshCols._.i = this.refreshCols._.itemIndex; (this.refreshCols._.i < this.refreshCols._.itemIndex + this.totalCols && this.refreshCols._.i < this.items.length); this.refreshCols._.i++)
				{
					this.refreshCols._.itemHeight = this.items[this.refreshCols._.i].height('auto').height();

					if (this.refreshCols._.itemHeight > this.refreshCols._.tallestItemHeight)
					{
						this.refreshCols._.tallestItemHeight = this.refreshCols._.itemHeight;
					}

					this.refreshCols._.colIndex++;
				}

				if (this.settings.snapToGrid)
				{
					this.refreshCols._.remainder = this.refreshCols._.tallestItemHeight % this.settings.snapToGrid;

					if (this.refreshCols._.remainder)
					{
						this.refreshCols._.tallestItemHeight += this.settings.snapToGrid - this.refreshCols._.remainder;
					}
				}

				// Now set their heights to the tallest one
				for (this.refreshCols._.i = this.refreshCols._.itemIndex; (this.refreshCols._.i < this.refreshCols._.itemIndex + this.totalCols && this.refreshCols._.i < this.items.length); this.refreshCols._.i++)
				{
					this.items[this.refreshCols._.i].height(this.refreshCols._.tallestItemHeight);
				}

				// set the this.refreshCols._.itemIndex pointer to the next one up
				this.refreshCols._.itemIndex += this.totalCols;
			}
		}
		else
		{
			this.removeListener(this.$items, 'resize');

			// If there's only one column, sneak out early
			if (this.totalCols == 1)
			{
				this.$container.height('auto');
				this.$items
					.show()
					.css({
						position: 'relative',
						width: 'auto',
						top: 0
					})
					.css(Craft.left, 0);
			}
			else
			{
				this.$items.css('position', 'absolute');

				if (this.settings.mode == 'pct')
				{
					this.colPctWidth = (100 / this.totalCols);
				}

				// The setup

				this.layouts = [];

				this.itemPositions = [];
				this.itemColspansByPosition = [];

				// Figure out all of the possible colspans for each item,
				// as well as all the possible positions for each item at each of its colspans

				this.possibleItemColspans = [];
				this.possibleItemPositionsByColspan = [];
				this.itemHeightsByColspan = [];

				for (this.refreshCols._.item = 0; this.refreshCols._.item < this.items.length; this.refreshCols._.item++)
				{
					this.possibleItemColspans[this.refreshCols._.item] = [];
					this.possibleItemPositionsByColspan[this.refreshCols._.item] = {};
					this.itemHeightsByColspan[this.refreshCols._.item] = {};

					this.refreshCols._.$item = this.items[this.refreshCols._.item].show();
					this.refreshCols._.positionRight = (this.refreshCols._.$item.data('position') == 'right');
					this.refreshCols._.positionLeft = (this.refreshCols._.$item.data('position') == 'left');
					this.refreshCols._.minColspan = (this.refreshCols._.$item.data('colspan') ? this.refreshCols._.$item.data('colspan') : (this.refreshCols._.$item.data('min-colspan') ? this.refreshCols._.$item.data('min-colspan') : 1));
					this.refreshCols._.maxColspan = (this.refreshCols._.$item.data('colspan') ? this.refreshCols._.$item.data('colspan') : (this.refreshCols._.$item.data('max-colspan') ? this.refreshCols._.$item.data('max-colspan') : this.totalCols));

					if (this.refreshCols._.minColspan > this.totalCols) this.refreshCols._.minColspan = this.totalCols;
					if (this.refreshCols._.maxColspan > this.totalCols) this.refreshCols._.maxColspan = this.totalCols;

					for (this.refreshCols._.colspan = this.refreshCols._.minColspan; this.refreshCols._.colspan <= this.refreshCols._.maxColspan; this.refreshCols._.colspan++)
					{
						// Get the height for this colspan
						this.refreshCols._.$item.css('width', this.getItemWidth(this.refreshCols._.colspan) + this.sizeUnit);
						this.itemHeightsByColspan[this.refreshCols._.item][this.refreshCols._.colspan] = this.refreshCols._.$item.outerHeight();

						this.possibleItemColspans[this.refreshCols._.item].push(this.refreshCols._.colspan);
						this.possibleItemPositionsByColspan[this.refreshCols._.item][this.refreshCols._.colspan] = [];

						if (this.refreshCols._.positionLeft)
						{
							this.refreshCols._.minPosition = 0;
							this.refreshCols._.maxPosition = 0;
						}
						else if (this.refreshCols._.positionRight)
						{
							this.refreshCols._.minPosition = this.totalCols - this.refreshCols._.colspan;
							this.refreshCols._.maxPosition = this.refreshCols._.minPosition;
						}
						else
						{
							this.refreshCols._.minPosition = 0;
							this.refreshCols._.maxPosition = this.totalCols - this.refreshCols._.colspan;
						}

						for (this.refreshCols._.position = this.refreshCols._.minPosition; this.refreshCols._.position <= this.refreshCols._.maxPosition; this.refreshCols._.position++)
						{
							this.possibleItemPositionsByColspan[this.refreshCols._.item][this.refreshCols._.colspan].push(this.refreshCols._.position);
						}
					}
				}

				// Find all the possible layouts

				this.refreshCols._.colHeights = [];

				for (this.refreshCols._.i = 0; this.refreshCols._.i < this.totalCols; this.refreshCols._.i++)
				{
					this.refreshCols._.colHeights.push(0);
				}

				this.createLayouts(0, [], [], this.refreshCols._.colHeights, 0);

				// Now find the layout that looks the best.

				// First find the layouts with the highest number of used columns
				this.refreshCols._.layoutTotalCols = [];

				for (this.refreshCols._.i = 0; this.refreshCols._.i < this.layouts.length; this.refreshCols._.i++)
				{
					this.refreshCols._.layoutTotalCols[this.refreshCols._.i] = 0;

					for (this.refreshCols._.j = 0; this.refreshCols._.j < this.totalCols; this.refreshCols._.j++)
					{
						if (this.layouts[this.refreshCols._.i].colHeights[this.refreshCols._.j])
						{
							this.refreshCols._.layoutTotalCols[this.refreshCols._.i]++;
						}
					}
				}

				this.refreshCols._.highestTotalCols = Math.max.apply(null, this.refreshCols._.layoutTotalCols);

				// Filter out the ones that aren't using as many columns as they could be
				for (this.refreshCols._.i = this.layouts.length - 1; this.refreshCols._.i >= 0; this.refreshCols._.i--)
				{
					if (this.refreshCols._.layoutTotalCols[this.refreshCols._.i] != this.refreshCols._.highestTotalCols)
					{
						this.layouts.splice(this.refreshCols._.i, 1);
					}
				}

				// Find the layout(s) with the least overall height
				this.refreshCols._.layoutHeights = [];

				for (this.refreshCols._.i = 0; this.refreshCols._.i < this.layouts.length; this.refreshCols._.i++)
				{
					this.refreshCols._.layoutHeights.push(Math.max.apply(null, this.layouts[this.refreshCols._.i].colHeights));
				}

				this.refreshCols._.shortestHeight = Math.min.apply(null, this.refreshCols._.layoutHeights);
				this.refreshCols._.shortestLayouts = [];
				this.refreshCols._.emptySpaces = [];

				for (this.refreshCols._.i = 0; this.refreshCols._.i < this.refreshCols._.layoutHeights.length; this.refreshCols._.i++)
				{
					if (this.refreshCols._.layoutHeights[this.refreshCols._.i] == this.refreshCols._.shortestHeight)
					{
						this.refreshCols._.shortestLayouts.push(this.layouts[this.refreshCols._.i]);

						// Now get its total empty space, including any trailing empty space
						this.refreshCols._.emptySpace = this.layouts[this.refreshCols._.i].emptySpace;

						for (this.refreshCols._.j = 0; this.refreshCols._.j < this.totalCols; this.refreshCols._.j++)
						{
							this.refreshCols._.emptySpace += (this.refreshCols._.shortestHeight - this.layouts[this.refreshCols._.i].colHeights[this.refreshCols._.j]);
						}

						this.refreshCols._.emptySpaces.push(this.refreshCols._.emptySpace);
					}
				}

				// And the layout with the least empty space is...
				this.layout = this.refreshCols._.shortestLayouts[$.inArray(Math.min.apply(null, this.refreshCols._.emptySpaces), this.refreshCols._.emptySpaces)];

				// Figure out the left padding based on the number of empty columns
				this.refreshCols._.totalEmptyCols = 0;

				for (this.refreshCols._.i = this.layout.colHeights.length-1; this.refreshCols._.i >= 0; this.refreshCols._.i--)
				{
					if (this.layout.colHeights[this.refreshCols._.i] == 0)
					{
						this.refreshCols._.totalEmptyCols++;
					}
					else
					{
						break;
					}
				}

				this.leftPadding = this.getItemWidth(this.refreshCols._.totalEmptyCols) / 2;

				if (this.settings.mode == 'fixed')
				{
					this.leftPadding += (this.$container.width() - (this.settings.minColWidth * this.totalCols)) / 2;
				}

				// Set the item widths and left positions
				for (this.refreshCols._.i = 0; this.refreshCols._.i < this.items.length; this.refreshCols._.i++)
				{
					this.refreshCols._.css = {
						width: this.getItemWidth(this.layout.colspans[this.refreshCols._.i]) + this.sizeUnit
					};
					this.refreshCols._.css[Craft.left] = this.leftPadding + this.getItemWidth(this.layout.positions[this.refreshCols._.i]) + this.sizeUnit;

					if (animate)
					{
						this.items[this.refreshCols._.i].velocity(this.refreshCols._.css, {
							queue: false
						});
					}
					else
					{
						this.items[this.refreshCols._.i].velocity('finish').css(this.refreshCols._.css);
					}
				}

				// If every item is at position 0, then let them lay out au naturel
				if (this.isSimpleLayout())
				{

					this.$container.height('auto');
					this.$items.css('position', 'relative');
				}
				else
				{
					this.$items.css('position', 'absolute');

					// Now position the items
					this.positionItems(animate);

					// Update the positions as the items' heigthts change
					this.addListener(this.$items, 'resize', 'onItemResize');
				}
			}
		}

		this.completeRefreshCols();

		// Resume container resize listening
		this.addListener(this.$container, 'resize', this.handleContainerHeightProxy);

		this.onRefreshCols();
	},

	completeRefreshCols: function()
	{
		// Delete the internal variable object
		if (typeof this.refreshCols._ != typeof undefined)
		{
			delete this.refreshCols._;
		}

		this._refreshingCols = false;

		if (this._refreshColsAfterRefresh) {
			force = this._forceRefreshColsAfterRefresh;
			this._refreshColsAfterRefresh = false;
			this._forceRefreshColsAfterRefresh = false;

			Garnish.requestAnimationFrame($.proxy(function() {
				this.refreshCols(force);
			}, this));
		}
	},

	getItemWidth: function(colspan)
	{
		if (this.settings.mode == 'pct')
		{
			return (this.colPctWidth * colspan);
		}
		else
		{
			return (this.settings.minColWidth * colspan);
		}
	},

	createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace)
	{
		(new Craft.Grid.LayoutGenerator(this)).createLayouts(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace);
	},

	isSimpleLayout: function()
	{
		this.isSimpleLayout._ = {};

		for (this.isSimpleLayout._.i = 0; this.isSimpleLayout._.i < this.layout.positions.length; this.isSimpleLayout._.i++)
		{
			if (this.layout.positions[this.isSimpleLayout._.i] != 0)
			{
				delete this.isSimpleLayout._;
				return false;
			}
		}

		delete this.isSimpleLayout._;
		return true;
	},

	positionItems: function(animate)
	{
		this.positionItems._ = {};

		this.positionItems._.colHeights = [];

		for (this.positionItems._.i = 0; this.positionItems._.i < this.totalCols; this.positionItems._.i++)
		{
			this.positionItems._.colHeights.push(0);
		}

		for (this.positionItems._.i = 0; this.positionItems._.i < this.items.length; this.positionItems._.i++)
		{
			this.positionItems._.endingCol = this.layout.positions[this.positionItems._.i] + this.layout.colspans[this.positionItems._.i] - 1;
			this.positionItems._.affectedColHeights = [];

			for (this.positionItems._.col = this.layout.positions[this.positionItems._.i]; this.positionItems._.col <= this.positionItems._.endingCol; this.positionItems._.col++)
			{
				this.positionItems._.affectedColHeights.push(this.positionItems._.colHeights[this.positionItems._.col]);
			}

			this.positionItems._.top = Math.max.apply(null, this.positionItems._.affectedColHeights);

			if (animate)
			{
				this.items[this.positionItems._.i].velocity({ top: this.positionItems._.top }, {
					queue: false
				});
			}
			else
			{
				this.items[this.positionItems._.i].velocity('finish').css('top', this.positionItems._.top);
			}

			// Now add the new heights to those columns
			for (this.positionItems._.col = this.layout.positions[this.positionItems._.i]; this.positionItems._.col <= this.positionItems._.endingCol; this.positionItems._.col++)
			{
				this.positionItems._.colHeights[this.positionItems._.col] = this.positionItems._.top + this.itemHeightsByColspan[this.positionItems._.i][this.layout.colspans[this.positionItems._.i]];
			}
		}

		// Set the container height
		this.$container.height(Math.max.apply(null, this.positionItems._.colHeights));

		delete this.positionItems._;
	},

	onItemResize: function(ev)
	{
		this.onItemResize._ = {};

		// Prevent this from bubbling up to the container, which has its own resize listener
		ev.stopPropagation();

		this.onItemResize._.item = $.inArray(ev.currentTarget, this.$items);

		if (this.onItemResize._.item != -1)
		{
			// Update the height and reposition the items
			this.onItemResize._.newHeight = this.items[this.onItemResize._.item].outerHeight();

			if (this.onItemResize._.newHeight != this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]])
			{
				this.itemHeightsByColspan[this.onItemResize._.item][this.layout.colspans[this.onItemResize._.item]] = this.onItemResize._.newHeight;
				this.positionItems(false);
			}
		}

		delete this.onItemResize._;
	},

	onRefreshCols: function()
	{
		this.trigger('refreshCols');
		this.settings.onRefreshCols();
	}
},
{
	defaults: {
		itemSelector: '.item',
		cols: null,
		maxCols: null,
		minColWidth: 320,
		mode: 'pct',
		fillMode: 'top',
		colClass: 'col',
		snapToGrid: null,

		onRefreshCols: $.noop
	}
});


Craft.Grid.LayoutGenerator = Garnish.Base.extend(
{
	grid: null,
	_: null,

	init: function(grid)
	{
		this.grid = grid;
	},

	createLayouts: function(item, prevPositions, prevColspans, prevColHeights, prevEmptySpace)
	{
		this._ = {};

		// Loop through all possible colspans
		for (this._.c = 0; this._.c < this.grid.possibleItemColspans[item].length; this._.c++)
		{
			this._.colspan = this.grid.possibleItemColspans[item][this._.c];

			// Loop through all the possible positions for this colspan,
			// and find the one that is closest to the top

			this._.tallestColHeightsByPosition = [];

			for (this._.p = 0; this._.p < this.grid.possibleItemPositionsByColspan[item][this._.colspan].length; this._.p++)
			{
				this._.position = this.grid.possibleItemPositionsByColspan[item][this._.colspan][this._.p];

				this._.colHeightsForPosition = [];
				this._.endingCol = this._.position + this._.colspan - 1;

				for (this._.col = this._.position; this._.col <= this._.endingCol; this._.col++)
				{
					this._.colHeightsForPosition.push(prevColHeights[this._.col]);
				}

				this._.tallestColHeightsByPosition[this._.p] = Math.max.apply(null, this._.colHeightsForPosition);
			}

			// And the shortest position for this colspan is...
			this._.p = $.inArray(Math.min.apply(null, this._.tallestColHeightsByPosition), this._.tallestColHeightsByPosition);
			this._.position = this.grid.possibleItemPositionsByColspan[item][this._.colspan][this._.p];

			// Now log the colspan/position placement
			this._.positions = prevPositions.slice(0);
			this._.colspans = prevColspans.slice(0);
			this._.colHeights = prevColHeights.slice(0);
			this._.emptySpace = prevEmptySpace;

			this._.positions.push(this._.position);
			this._.colspans.push(this._.colspan);

			// Add the new heights to those columns
			this._.tallestColHeight = this._.tallestColHeightsByPosition[this._.p];
			this._.endingCol = this._.position + this._.colspan - 1;

			for (this._.col = this._.position; this._.col <= this._.endingCol; this._.col++)
			{
				this._.emptySpace += this._.tallestColHeight - this._.colHeights[this._.col];
				this._.colHeights[this._.col] = this._.tallestColHeight + this.grid.itemHeightsByColspan[item][this._.colspan];
			}

			// If this is the last item, create the layout
			if (item == this.grid.items.length-1)
			{
				this.grid.layouts.push({
					positions:  this._.positions,
					colspans:   this._.colspans,
					colHeights: this._.colHeights,
					emptySpace: this._.emptySpace
				});
			}
			else
			{
				// Dive deeper
				this.grid.createLayouts(item+1, this._.positions, this._.colspans, this._.colHeights, this._.emptySpace);
			}
		}

		delete this._;
	}

});

/**
 * Handle Generator
 */
Craft.HandleGenerator = Craft.BaseInputGenerator.extend(
{
	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		var handle = sourceVal.replace("/<(.*?)>/g", '');

		// Remove inner-word punctuation
		handle = handle.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');

		// Make it lowercase
		handle = handle.toLowerCase();

		// Convert extended ASCII characters to basic ASCII
		handle = Craft.asciiString(handle);

		// Handle must start with a letter
		handle = handle.replace(/^[^a-z]+/, '');

		// Get the "words"
		var words = Craft.filterArray(handle.split(/[^a-z0-9]+/)),
			handle = '';

		// Make it camelCase
		for (var i = 0; i < words.length; i++)
		{
			if (i == 0)
			{
				handle += words[i];
			}
			else
			{
				handle += words[i].charAt(0).toUpperCase()+words[i].substr(1);
			}
		}

		return handle;
	}
});

/**
 * postParameters     - an object of POST data to pass along with each Ajax request
 * modalClass         - class to add to the modal window to allow customization
 * uploadButton       - jQuery object of the element that should open the file chooser
 * uploadAction       - upload to this location (in form of "controller/action")
 * deleteButton       - jQuery object of the element that starts the image deletion process
 * deleteMessage      - confirmation message presented to the user for image deletion
 * deleteAction       - delete image at this location (in form of "controller/action")
 * cropAction         - crop image at this (in form of "controller/action")
 * areaToolOptions    - object with some options for the area tool selector
 *   aspectRatio      - decimal aspect ratio of width/height
 *   initialRectangle - object with options for the initial rectangle
 *     mode           - if set to auto, then the part selected will be the maximum size in the middle of image
 *     x1             - top left x coordinate of th rectangle, if the mode is not set to auto
 *     x2             - bottom right x coordinate of th rectangle, if the mode is not set to auto
 *     y1             - top left y coordinate of th rectangle, if the mode is not set to auto
 *     y2             - bottom right y coordinate of th rectangle, if the mode is not set to auto
 *
 * onImageDelete     - callback to call when image is deleted. First parameter will contain response data.
 * onImageSave       - callback to call when an cropped image is saved. First parameter will contain response data.
 */


/**
 * Image Upload tool.
 */
Craft.ImageUpload = Garnish.Base.extend(
{
	_imageHandler: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.ImageUpload.defaults);
		this._imageHandler = new Craft.ImageHandler(settings);
	},

	destroy: function()
	{
		this._imageHandler.destroy();
		delete this._imageHandler;

		this.base();
	}
},
{
	$modalContainerDiv: null,

	defaults: {
		postParameters: {},

		modalClass: "",
		uploadButton: {},
		uploadAction: "",

		deleteButton: {},
		deleteMessage: "",
		deleteAction: "",

		cropAction:"",

		constraint: 500,

		areaToolOptions:
		{
			aspectRatio: "1",
			initialRectangle: {
				mode: "auto",
				x1: 0,
				x2: 0,
				y1: 0,
				y2: 0
			}
		},

		onImageDelete: function(response)
		{
			location.reload();
		},
		onImageSave: function(response)
		{
			location.reload();
		}
	}
});


Craft.ImageHandler = Garnish.Base.extend(
{
	modal: null,
	progressBar: null,
	$container: null,

	init: function(settings)
	{
		this.setSettings(settings);

		var element = settings.uploadButton;
		var $uploadInput = $('<input type="file" name="image-upload"/>').hide().insertBefore(element);

		this.progressBar = new Craft.ProgressBar($('<div class="progress-shade"></div>').insertBefore(element));
		this.progressBar.$progressBar.css({
			top: Math.round(element.outerHeight() / 2) - 6
		});

		this.$container = element.parent();

		var options = {
			url: Craft.getActionUrl(this.settings.uploadAction),
			fileInput: $uploadInput,

			element:    this.settings.uploadButton[0],
			action:     Craft.actionUrl + '/' + this.settings.uploadAction,
			formData:   typeof this.settings.postParameters === 'object' ? this.settings.postParameters : {},
			events:     {
				fileuploadstart: $.proxy(function()
				{
					this.$container.addClass('uploading');
					this.progressBar.resetProgressBar();
					this.progressBar.showProgressBar();
				}, this),
				fileuploadprogressall: $.proxy(function(data)
				{
					var progress = parseInt(data.loaded / data.total * 100, 10);
					this.progressBar.setProgressPercentage(progress);
				}, this),
				fileuploaddone: $.proxy(function(event, data)
				{
					this.progressBar.hideProgressBar();
					this.$container.removeClass('uploading');

					var response = data.result;

					if (response.error)
					{
						alert(response.error);
						return;
					}

					if (Craft.ImageUpload.$modalContainerDiv == null)
					{
						Craft.ImageUpload.$modalContainerDiv = $('<div class="modal fitted"></div>').addClass(settings.modalClass).appendTo(Garnish.$bod);
					}

					if (response.fileName)
					{
						this.source = response.fileName;
					}

					if (response.html)
					{
						Craft.ImageUpload.$modalContainerDiv.empty().append(response.html);

						if (!this.modal)
						{
							this.modal = new Craft.ImageModal(Craft.ImageUpload.$modalContainerDiv, {
								postParameters: settings.postParameters,
								cropAction:     settings.cropAction
							});

							this.modal.imageHandler = this;
						}
						else
						{
							this.modal.show();
						}

						this.modal.bindButtons();
						this.modal.addListener(this.modal.$saveBtn, 'click', 'saveImage');
						this.modal.addListener(this.modal.$cancelBtn, 'click', 'cancel');

						this.modal.removeListener(Garnish.Modal.$shade, 'click');

						setTimeout($.proxy(function()
						{
							Craft.ImageUpload.$modalContainerDiv.find('img').load($.proxy(function()
							{
								var areaTool = new Craft.ImageAreaTool(settings.areaToolOptions, this.modal);
								areaTool.showArea();
								this.modal.cropAreaTool = areaTool;
							}, this));
						}, this), 1);
					}
				}, this)
			},
			acceptFileTypes: /(jpg|jpeg|gif|png)/
		};

		// If CSRF protection isn't enabled, these won't be defined.
		if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined')
		{
			// Add the CSRF token
			options.formData[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		this.uploader = new Craft.Uploader(element, options);


		this.addListener($(settings.deleteButton), 'click', function(ev)
		{
			if (confirm(settings.deleteMessage))
			{
				$(ev.currentTarget).parent().append('<div class="blocking-modal"></div>');
				Craft.postActionRequest(settings.deleteAction, settings.postParameters, $.proxy(function(response, textStatus)
				{
					if (textStatus == 'success')
					{
						this.onImageDelete(response);
					}

				}, this));

			}
		});

		this.addListener($(settings.uploadButton), 'click', function(ev)
		{
			$(ev.currentTarget).siblings('input[type=file]').click();
		});

	},

	onImageSave: function(data)
	{
		this.settings.onImageSave.apply(this, [data]);
	},

	onImageDelete: function(data)
	{
		this.settings.onImageDelete.apply(this, [data]);
	},

	destroy: function()
	{
		this.progressBar.destroy();
		delete this.progressBar;

		if (this.modal)
		{
			this.modal.destroy();
			delete this.modal;
		}

		if (this.uploader)
		{
			this.uploader.destroy();
			delete this.uploader;
		}

		this.base();
	}
});


Craft.ImageModal = Garnish.Modal.extend(
{
	$container: null,
	$saveBtn: null,
	$cancelBtn: null,

	areaSelect: null,
	source: null,
	_postParameters: null,
	_cropAction: "",
	imageHandler: null,
	cropAreaTool: null,


	init: function($container, settings)
	{
		this.cropAreaTool = null;
		this.base($container, settings);
		this._postParameters = settings.postParameters;
		this._cropAction = settings.cropAction;
	},

	bindButtons: function()
	{
		this.$saveBtn = this.$container.find('.submit:first');
		this.$cancelBtn = this.$container.find('.cancel:first');
	},

	cancel: function()
	{
		this.hide();
		this.$container.remove();
		this.destroy();
	},

	saveImage: function()
	{
		var selection = this.areaSelect.tellSelect();

		var params = {
			x1: selection.x,
			y1: selection.y,
			x2: selection.x2,
			y2: selection.y2,
			source: this.source
		};

		params = $.extend(this._postParameters, params);

		Craft.postActionRequest(this._cropAction, params, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				if (response.error)
				{
					Craft.cp.displayError(response.error);
				}
				else
				{
					this.imageHandler.onImageSave.apply(this.imageHandler, [response]);
				}
			}

			this.hide();
			this.$container.remove();
			this.destroy();
		}, this));

		this.areaSelect.setOptions({disable: true});
		this.removeListener(this.$saveBtn, 'click');
		this.removeListener(this.$cancelBtn, 'click');

		this.$container.find('.crop-image').fadeTo(50, 0.5);
	}

});


Craft.ImageAreaTool = Garnish.Base.extend(
{
	api:             null,
	$container:      null,
	containingModal: null,

	init: function(settings, containingModal)
	{
		this.$container = Craft.ImageUpload.$modalContainerDiv;
		this.setSettings(settings);
		this.containingModal = containingModal;
	},

	showArea: function()
	{
		var $target = this.$container.find('img');

		var cropperOptions = {
			aspectRatio: this.settings.aspectRatio,
			maxSize: [$target.width(), $target.height()],
			bgColor: 'none'
		};


		var initCropper = $.proxy(function (api)
		{
			this.api = api;

			var x1 = this.settings.initialRectangle.x1;
			var x2 = this.settings.initialRectangle.x2;
			var y1 = this.settings.initialRectangle.y1;
			var y2 = this.settings.initialRectangle.y2;

			if (this.settings.initialRectangle.mode == "auto")
			{
				var rectangleWidth = 0;
				var rectangleHeight = 0;

				if (this.settings.aspectRatio == "")
				{
					rectangleWidth = $target.width();
					rectangleHeight = $target.height();
				}
				else if (this.settings.aspectRatio > 1)
				{
					rectangleWidth = $target.width();
					rectangleHeight = rectangleWidth / this.settings.aspectRatio;
				}
				else if (this.settings.aspectRatio < 1)
				{
					rectangleHeight = $target.height();
					rectangleWidth = rectangleHeight * this.settings.aspectRatio;
				}
				else
				{
					rectangleHeight = rectangleWidth = Math.min($target.width(), $target.height());
				}

				x1 = Math.round(($target.width() - rectangleWidth) / 2);
				y1 = Math.round(($target.height() - rectangleHeight) / 2);
				x2 = x1 + rectangleWidth;
				y2 = y1 + rectangleHeight;

			}
			this.api.setSelect([x1, y1, x2, y2]);

			this.containingModal.areaSelect = this.api;
			this.containingModal.source = $target.attr('src').split('/').pop();
			this.containingModal.updateSizeAndPosition();

		}, this);

		$target.Jcrop(cropperOptions, function ()
		{
			initCropper(this);
		});
	}
});

/**
 * Info icon class
 */
Craft.InfoIcon = Garnish.Base.extend(
{
	$icon: null,
	hud: null,

	init: function(icon)
	{
		this.$icon = $(icon);

		this.addListener(this.$icon, 'click', 'showHud');
	},

	showHud: function()
	{
		if (!this.hud)
		{
			this.hud = new Garnish.HUD(this.$icon, this.$icon.html(), {
				hudClass: 'hud info-hud',
				closeOtherHUDs: false
			});
		}
		else
		{
			this.hud.show();
		}
	}
});

/**
 * Light Switch
 */
Craft.LightSwitch = Garnish.Base.extend(
{
	settings: null,
	$outerContainer: null,
	$innerContainer: null,
	$input: null,
	small: false,
	on: null,
	dragger: null,

	dragStartMargin: null,

	init: function(outerContainer, settings)
	{
		this.$outerContainer = $(outerContainer);

		// Is this already a lightswitch?
		if (this.$outerContainer.data('lightswitch'))
		{
			Garnish.log('Double-instantiating a lightswitch on an element');
			this.$outerContainer.data('lightswitch').destroy();
		}

		this.$outerContainer.data('lightswitch', this);

		this.small = this.$outerContainer.hasClass('small');

		this.setSettings(settings, Craft.LightSwitch.defaults);

		this.$innerContainer = this.$outerContainer.find('.lightswitch-container:first');
		this.$input = this.$outerContainer.find('input:first');

		// If the input is disabled, go no further
		if (this.$input.prop('disabled'))
		{
			return;
		}

		this.on = this.$outerContainer.hasClass('on');

		this.$outerContainer.attr({
			'role': 'checkbox',
			'aria-checked': (this.on ? 'true' : 'false'),
		});

		this.addListener(this.$outerContainer, 'mousedown', '_onMouseDown');
		this.addListener(this.$outerContainer, 'keydown', '_onKeyDown');

		this.dragger = new Garnish.BaseDrag(this.$outerContainer, {
			axis:                 Garnish.X_AXIS,
			ignoreHandleSelector: null,
			onDragStart:          $.proxy(this, '_onDragStart'),
			onDrag:               $.proxy(this, '_onDrag'),
			onDragStop:           $.proxy(this, '_onDragStop')
		});
	},

	turnOn: function()
	{
		this.$outerContainer.addClass('dragging');

		var animateCss = {};
		animateCss['margin-'+Craft.left] = 0;
		this.$innerContainer.velocity('stop').velocity(animateCss, Craft.LightSwitch.animationDuration, $.proxy(this, '_onSettle'));

		this.$input.val('1');
		this.$outerContainer.addClass('on');
		this.$outerContainer.attr('aria-checked', 'true');
		this.on = true;
		this.onChange();
	},

	turnOff: function()
	{
		this.$outerContainer.addClass('dragging');

		var animateCss = {};
		animateCss['margin-'+Craft.left] = this._getOffMargin();
		this.$innerContainer.velocity('stop').velocity(animateCss, Craft.LightSwitch.animationDuration, $.proxy(this, '_onSettle'));

		this.$input.val('');
		this.$outerContainer.removeClass('on');
		this.$outerContainer.attr('aria-checked', 'false');
		this.on = false;
		this.onChange();
	},

	toggle: function(event)
	{
		if (!this.on)
		{
			this.turnOn();
		}
		else
		{
			this.turnOff();
		}
	},

	onChange: function()
	{
		this.trigger('change');
		this.settings.onChange();
		this.$outerContainer.trigger('change');
	},

	_onMouseDown: function()
	{
		this.addListener(Garnish.$doc, 'mouseup', '_onMouseUp');
	},

	_onMouseUp: function()
	{
		this.removeListener(Garnish.$doc, 'mouseup');

		// Was this a click?
		if (!this.dragger.dragging)
		{
			this.toggle();
		}
	},

	_onKeyDown: function(event)
	{
		switch (event.keyCode)
		{
			case Garnish.SPACE_KEY:
			{
				this.toggle();
				event.preventDefault();
				break;
			}
			case Garnish.RIGHT_KEY:
			{
				if (Craft.orientation == 'ltr')
				{
					this.turnOn();
				}
				else
				{
					this.turnOff();
				}

				event.preventDefault();
				break;
			}
			case Garnish.LEFT_KEY:
			{
				if (Craft.orientation == 'ltr')
				{
					this.turnOff();
				}
				else
				{
					this.turnOn();
				}

				event.preventDefault();
				break;
			}
		}
	},

	_getMargin: function()
	{
		return parseInt(this.$innerContainer.css('margin-'+Craft.left));
	},

	_onDragStart: function()
	{
		this.$outerContainer.addClass('dragging');
		this.dragStartMargin = this._getMargin();
	},

	_onDrag: function()
	{
		var margin;

		if (Craft.orientation == 'ltr')
		{
			margin = this.dragStartMargin + this.dragger.mouseDistX;
		}
		else
		{
			margin = this.dragStartMargin - this.dragger.mouseDistX;
		}

		if (margin < this._getOffMargin())
		{
			margin = this._getOffMargin();
		}
		else if (margin > 0)
		{
			margin = 0;
		}

		this.$innerContainer.css('margin-'+Craft.left, margin);
	},

	_onDragStop: function()
	{
		var margin = this._getMargin();

		if (margin > (this._getOffMargin() / 2))
		{
			this.turnOn();
		}
		else
		{
			this.turnOff();
		}
	},

	_onSettle: function()
	{
		this.$outerContainer.removeClass('dragging');
	},

	destroy: function()
	{
		this.base();
		this.dragger.destroy();
	},

	_getOffMargin: function()
	{
		return (this.small ? -9 : -11);
	}

}, {
	animationDuration: 100,
	defaults: {
		onChange: $.noop
	}
});

/**
 * Live Preview
 */
Craft.LivePreview = Garnish.Base.extend(
{
	$extraFields: null,
	$trigger: null,
	$spinner: null,
	$shade: null,
	$editorContainer: null,
	$editor: null,
	$dragHandle: null,
	$iframeContainer: null,
	$iframe: null,
	$fieldPlaceholder: null,

	previewUrl: null,
	basePostData: null,
	inPreviewMode: false,
	fields: null,
	lastPostData: null,
	updateIframeInterval: null,
	loading: false,
	checkAgain: false,

	dragger: null,
	dragStartEditorWidth: null,

	_handleSuccessProxy: null,
	_handleErrorProxy: null,

	_scrollX: null,
	_scrollY: null,

	_editorWidth: null,
	_editorWidthInPx: null,

	init: function(settings)
	{
		this.setSettings(settings, Craft.LivePreview.defaults);

		// Should preview requests use a specific URL?
		// This won't affect how the request gets routed (the action param will override it),
		// but it will allow the templates to change behavior based on the request URI.
		if (this.settings.previewUrl)
		{
			this.previewUrl = this.settings.previewUrl;
		}
		else
		{
			this.previewUrl = Craft.baseSiteUrl.replace(/\/+$/, '') + '/';
		}

		// Load the preview over SSL if the current request is
		if (document.location.protocol == 'https:')
		{
			this.previewUrl = this.previewUrl.replace(/^http:/, 'https:');
		}

		// Set the base post data
		this.basePostData = $.extend({
			action: this.settings.previewAction,
			livePreview: true
		}, this.settings.previewParams);

		if (Craft.csrfTokenName)
		{
			this.basePostData[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		this._handleSuccessProxy = $.proxy(this, 'handleSuccess');
		this._handleErrorProxy = $.proxy(this, 'handleError');

		// Find the DOM elements
		this.$extraFields = $(this.settings.extraFields);
		this.$trigger = $(this.settings.trigger);
		this.$spinner = this.settings.spinner ? $(this.settings.spinner) : this.$trigger.find('.spinner');
		this.$fieldPlaceholder = $('<div/>');

		// Set the initial editor width
		this.editorWidth = Craft.getLocalStorage('LivePreview.editorWidth', Craft.LivePreview.defaultEditorWidth);

		// Event Listeners
		this.addListener(this.$trigger, 'activate', 'toggle');

		Craft.cp.on('beforeSaveShortcut', $.proxy(function()
		{
			if (this.inPreviewMode)
			{
				this.moveFieldsBack();
			}
		}, this));
	},

	get editorWidth()
	{
		return this._editorWidth;
	},

	get editorWidthInPx()
	{
		return this._editorWidthInPx;
	},

	set editorWidth(width)
	{
		var inPx;

		// Is this getting set in pixels?
		if (width >= 1)
		{
			inPx = width;
			width /= Garnish.$win.width();
		}
		else
		{
			inPx = Math.round(width * Garnish.$win.width());
		}

		// Make sure it's no less than the minimum
		if (inPx < Craft.LivePreview.minEditorWidthInPx)
		{
			inPx = Craft.LivePreview.minEditorWidthInPx;
			width = inPx / Garnish.$win.width();
		}

		this._editorWidth = width;
		this._editorWidthInPx = inPx;
	},

	toggle: function()
	{
		if (this.inPreviewMode)
		{
			this.exit();
		}
		else
		{
			this.enter();
		}
	},

	enter: function()
	{
		if (this.inPreviewMode)
		{
			return;
		}

		this.trigger('beforeEnter');

		$(document.activeElement).blur();

		if (!this.$editor)
		{
			this.$shade = $('<div class="modal-shade dark"/>').appendTo(Garnish.$bod).css('z-index', 2);
			this.$editorContainer = $('<div class="lp-editor-container"/>').appendTo(Garnish.$bod);
			this.$editor = $('<div class="lp-editor"/>').appendTo(this.$editorContainer);
			this.$iframeContainer = $('<div class="lp-iframe-container"/>').appendTo(Garnish.$bod);
			this.$iframe = $('<iframe class="lp-iframe" frameborder="0"/>').appendTo(this.$iframeContainer);
			this.$dragHandle = $('<div class="lp-draghandle"/>').appendTo(this.$editorContainer);

			var $header = $('<header class="header"></header>').appendTo(this.$editor),
				$closeBtn = $('<div class="btn">'+Craft.t('Close Live Preview')+'</div>').appendTo($header),
				$saveBtn = $('<div class="btn submit">'+Craft.t('Save')+'</div>').appendTo($header);

			this.dragger = new Garnish.BaseDrag(this.$dragHandle, {
				axis:          Garnish.X_AXIS,
				onDragStart:   $.proxy(this, '_onDragStart'),
				onDrag:        $.proxy(this, '_onDrag'),
				onDragStop:    $.proxy(this, '_onDragStop')
			});

			this.addListener($closeBtn, 'click', 'exit');
			this.addListener($saveBtn, 'click', 'save');
		}

		// Set the sizes
		this.handleWindowResize();
		this.addListener(Garnish.$win, 'resize', 'handleWindowResize');

		this.$editorContainer.css(Craft.left, -(this.editorWidthInPx+Craft.LivePreview.dragHandleWidth)+'px');
		this.$iframeContainer.css(Craft.right, -this.getIframeWidth());

		// Move all the fields into the editor rather than copying them
		// so any JS that's referencing the elements won't break.
		this.fields = [];
		var $fields = $(this.settings.fields);

		for (var i= 0; i < $fields.length; i++)
		{
			var $field = $($fields[i]),
				$clone = this._getClone($field);

			// It's important that the actual field is added to the DOM *after* the clone,
			// so any radio buttons in the field get deselected from the clone rather than the actual field.
			this.$fieldPlaceholder.insertAfter($field);
			$field.detach();
			this.$fieldPlaceholder.replaceWith($clone);
			$field.appendTo(this.$editor);

			this.fields.push({
				$field: $field,
				$clone: $clone
			});
		}

		if (this.updateIframe())
		{
			this.$spinner.removeClass('hidden');
			this.addListener(this.$iframe, 'load', function()
			{
				this.slideIn();
				this.removeListener(this.$iframe, 'load');
			});
		}
		else
		{
			this.slideIn();
		}

		this.inPreviewMode = true;
		this.trigger('enter');
	},

	save: function()
	{
		Craft.cp.submitPrimaryForm();
	},

	handleWindowResize: function()
	{
		// Reset the width so the min width is enforced
		this.editorWidth = this.editorWidth;

		// Update the editor/iframe sizes
		this.updateWidths();
	},

	slideIn: function()
	{
		$('html').addClass('noscroll');
		this.$spinner.addClass('hidden');

		this.$shade.velocity('fadeIn');

		this.$editorContainer.show().velocity('stop').animateLeft(0, 'slow', $.proxy(function()
		{
			this.trigger('slideIn');
			Garnish.$win.trigger('resize');
		}, this));

		this.$iframeContainer.show().velocity('stop').animateRight(0, 'slow', $.proxy(function()
		{
			this.updateIframeInterval = setInterval($.proxy(this, 'updateIframe'), 1000);

			this.addListener(Garnish.$bod, 'keyup', function(ev)
			{
				if (ev.keyCode == Garnish.ESC_KEY)
				{
					this.exit();
				}
			});
		}, this));
	},

	exit: function()
	{
		if (!this.inPreviewMode)
		{
			return;
		}

		this.trigger('beforeExit');

		$('html').removeClass('noscroll');

		this.removeListener(Garnish.$win, 'resize');
		this.removeListener(Garnish.$bod, 'keyup');

		if (this.updateIframeInterval)
		{
			clearInterval(this.updateIframeInterval);
		}

		this.moveFieldsBack();

		var windowWidth = Garnish.$win.width();

		this.$shade.delay(200).velocity('fadeOut');

		this.$editorContainer.velocity('stop').animateLeft(-(this.editorWidthInPx+Craft.LivePreview.dragHandleWidth), 'slow', $.proxy(function()
		{
			for (var i = 0; i < this.fields.length; i++)
			{
				this.fields[i].$newClone.remove();
			}
			this.$editorContainer.hide();
			this.trigger('slideOut');
		}, this));

		this.$iframeContainer.velocity('stop').animateRight(-this.getIframeWidth(), 'slow', $.proxy(function()
		{
			this.$iframeContainer.hide();
		}, this));

		this.inPreviewMode = false;
		this.trigger('exit');
	},

	moveFieldsBack: function()
	{
		for (var i = 0; i < this.fields.length; i++)
		{
			var field = this.fields[i];
			field.$newClone = this._getClone(field.$field);

			// It's important that the actual field is added to the DOM *after* the clone,
			// so any radio buttons in the field get deselected from the clone rather than the actual field.
			this.$fieldPlaceholder.insertAfter(field.$field);
			field.$field.detach();
			this.$fieldPlaceholder.replaceWith(field.$newClone);
			field.$clone.replaceWith(field.$field);
		}

		Garnish.$win.trigger('resize');
	},

	getIframeWidth: function()
	{
		return Garnish.$win.width()-(this.editorWidthInPx+Craft.LivePreview.dragHandleWidth);
	},

	updateWidths: function()
	{
		this.$editorContainer.css('width', this.editorWidthInPx+'px');
		this.$iframeContainer.width(this.getIframeWidth());
	},

	updateIframe: function(force)
	{
		if (force)
		{
			this.lastPostData = null;
		}

		if (!this.inPreviewMode)
		{
			return false;
		}

		if (this.loading)
		{
			this.checkAgain = true;
			return false;
		}

		// Has the post data changed?
		var postData = $.extend(Garnish.getPostData(this.$editor), Garnish.getPostData(this.$extraFields));

		if (!this.lastPostData || !Craft.compare(postData, this.lastPostData, false))
		{
			this.lastPostData = postData;
			this.loading = true;

			var data = $.extend({}, postData, this.basePostData),
				$doc = $(this.$iframe[0].contentWindow.document);

			this._scrollX = $doc.scrollLeft();
			this._scrollY = $doc.scrollTop();

			$.ajax({
				url: this.previewUrl,
				method: 'POST',
				data: $.extend({}, postData, this.basePostData),
				xhrFields: {
				   withCredentials: true
				},
				crossDomain: true,
				success: this._handleSuccessProxy,
				error: this._handleErrorProxy
			});

			return true;
		}
		else
		{
			return false;
		}
	},

	handleSuccess: function(data, textStatus, jqXHR)
	{
		var html = data +
			'<script type="text/javascript">window.scrollTo('+this._scrollX+', '+this._scrollY+');</script>';

		// Set the iframe to use the same bg as the iframe body,
		// to reduce the blink when reloading the DOM
		this.$iframe.css('background', $(this.$iframe[0].contentWindow.document.body).css('background'));

		this.$iframe[0].contentWindow.document.open();
		this.$iframe[0].contentWindow.document.write(html);
		this.$iframe[0].contentWindow.document.close();

		this.onResponse();
	},

	handleError: function(jqXHR, textStatus, errorThrown)
	{
		this.onResponse();
	},

	onResponse: function()
	{
		this.loading = false;

		if (this.checkAgain)
		{
			this.checkAgain = false;
			this.updateIframe();
		}
	},

	_getClone: function($field)
	{
		var $clone = $field.clone();

		// clone() won't account for input values that have changed since the original HTML set them
		Garnish.copyInputValues($field, $clone);

		// Remove any id= attributes
		$clone.attr('id', '');
		$clone.find('[id]').attr('id', '');

		return $clone;
	},

	_onDragStart: function()
	{
		this.dragStartEditorWidth = this.editorWidthInPx;
		this.$iframeContainer.addClass('dragging');
	},

	_onDrag: function()
	{
		if (Craft.orientation == 'ltr')
		{
			this.editorWidth = this.dragStartEditorWidth + this.dragger.mouseDistX;
		}
		else
		{
			this.editorWidth = this.dragStartEditorWidth - this.dragger.mouseDistX;
		}

		this.updateWidths();
	},

	_onDragStop: function()
	{
		this.$iframeContainer.removeClass('dragging');
		Craft.setLocalStorage('LivePreview.editorWidth', this.editorWidth);
	}
},
{
	defaultEditorWidth: 0.33,
	minEditorWidthInPx: 320,
	dragHandleWidth: 4,

	defaults: {
		trigger: '.livepreviewbtn',
		spinner: null,
		fields: null,
		extraFields: null,
		previewUrl: null,
		previewAction: null,
		previewParams: {}
	}
});

Craft.LivePreview.init = function(settings)
{
	Craft.livePreview = new Craft.LivePreview(settings);
};

/**
 * Pane class
 */
Craft.Pane = Garnish.Base.extend(
{
	$pane: null,
	$content: null,
	$sidebar: null,
	$tabsContainer: null,

	tabs: null,
	selectedTab: null,
	hasSidebar: null,

	init: function(pane)
	{
		this.$pane = $(pane);

		// Is this already a pane?
		if (this.$pane.data('pane'))
		{
			Garnish.log('Double-instantiating a pane on an element');
			this.$pane.data('pane').destroy();
		}

		this.$pane.data('pane', this);

		this.$content = this.$pane.find('.content:not(.hidden):first');

		// Initialize the tabs
		this.$tabsContainer = this.$pane.children('.tabs');
		var $tabs = this.$tabsContainer.find('a');

		if ($tabs.length)
		{
			this.tabs = {};

			// Find the tabs that link to a div on the page
			for (var i = 0; i < $tabs.length; i++)
			{
				var $tab = $($tabs[i]),
					href = $tab.attr('href');

				if (href && href.charAt(0) == '#')
				{
					this.tabs[href] = {
						$tab: $tab,
						$target: $(href)
					};

					this.addListener($tab, 'activate', 'selectTab');
				}

				if (!this.selectedTab && $tab.hasClass('sel'))
				{
					this.selectedTab = href;
				}
			}

			if (document.location.hash && typeof this.tabs[document.location.hash] != 'undefined')
			{
				this.tabs[document.location.hash].$tab.trigger('activate');
			}
			else if (!this.selectedTab)
			{
				$($tabs[0]).trigger('activate');
			}
		}

		if (this.$pane.hasClass('meta'))
		{
			var $inputs = Garnish.findInputs(this.$pane);
			this.addListener($inputs, 'focus', 'focusMetaField');
			this.addListener($inputs, 'blur', 'blurMetaField');
		}

		this.initContent();
	},

	focusMetaField: function(ev)
	{
		$(ev.currentTarget).closest('.field')
			.removeClass('has-errors')
			.addClass('has-focus');
	},

	blurMetaField: function(ev)
	{
		$(ev.currentTarget).closest('.field')
			.removeClass('has-focus');
	},

	/**
	 * Selects a tab.
	 */
	selectTab: function(ev)
	{
		if (!this.selectedTab || ev.currentTarget != this.tabs[this.selectedTab].$tab[0])
		{
			// Hide the selected tab
			this.deselectTab();

			var $tab = $(ev.currentTarget).addClass('sel');
			this.selectedTab = $tab.attr('href');

			var $target = this.tabs[this.selectedTab].$target;
			$target.removeClass('hidden');

			if ($target.hasClass('content'))
			{
				this.$content = $target;
			}

			Garnish.$win.trigger('resize');

			// Fixes Redactor fixed toolbars on previously hidden panes
			Garnish.$doc.trigger('scroll');
		}
	},

	/**
	 * Deselects the current tab.
	 */
	deselectTab: function()
	{
		if (this.selectedTab)
		{
			this.tabs[this.selectedTab].$tab.removeClass('sel');
			this.tabs[this.selectedTab].$target.addClass('hidden');
		}
	},

	initContent: function()
	{
		this.hasSidebar = this.$content.hasClass('has-sidebar');

		if (this.hasSidebar)
		{
			this.$sidebar = this.$content.children('.sidebar');

			this.addListener(this.$content, 'resize', function()
			{
				this.updateSidebarStyles();
			});

			this.addListener(this.$sidebar, 'resize', 'setMinContentSizeForSidebar');
			this.setMinContentSizeForSidebar();

			this.addListener(Garnish.$win, 'resize', 'updateSidebarStyles');
			this.addListener(Garnish.$win, 'scroll', 'updateSidebarStyles');

			this.updateSidebarStyles();
		}
	},

	setMinContentSizeForSidebar: function()
	{
		if (true || this.$pane.hasClass('showing-sidebar'))
		{
			this.setMinContentSizeForSidebar._minHeight = this.$sidebar.prop('scrollHeight') - this.$tabsContainer.height() - 48;
		}
		else
		{
			this.setMinContentSizeForSidebar._minHeight = 0;
		}

		this.$content.css('min-height', this.setMinContentSizeForSidebar._minHeight);
	},

	updateSidebarStyles: function()
	{
		this.updateSidebarStyles._styles = {};

		this.updateSidebarStyles._scrollTop = Garnish.$win.scrollTop();
		this.updateSidebarStyles._paneOffset = this.$pane.offset().top + this.$tabsContainer.height();
		this.updateSidebarStyles._paneHeight = this.$pane.outerHeight() - this.$tabsContainer.height();
		this.updateSidebarStyles._windowHeight = Garnish.$win.height();

		// Have we scrolled passed the top of the pane?
		if (Garnish.$win.width() > 992 && this.updateSidebarStyles._scrollTop > this.updateSidebarStyles._paneOffset)
		{
			// Set the top position to the difference
			this.updateSidebarStyles._styles.position = 'fixed';
			this.updateSidebarStyles._styles.top = '24px';
		}
		else
		{
			this.updateSidebarStyles._styles.position = 'absolute';
			this.updateSidebarStyles._styles.top = 'auto';
		}

		// Now figure out how tall the sidebar can be
		this.updateSidebarStyles._styles.maxHeight = Math.min(
			this.updateSidebarStyles._paneHeight - (this.updateSidebarStyles._scrollTop - this.updateSidebarStyles._paneOffset),
			this.updateSidebarStyles._windowHeight
		);

		if(this.updateSidebarStyles._paneHeight > this.updateSidebarStyles._windowHeight)
		{
			this.updateSidebarStyles._styles.height = this.updateSidebarStyles._styles.maxHeight;
		}
		else
		{
			this.updateSidebarStyles._styles.height = this.updateSidebarStyles._paneHeight;
		}

		this.$sidebar.css(this.updateSidebarStyles._styles);
	},

	destroy: function()
	{
		this.base();
		this.$pane.data('pane', null);
	}
});

/**
 * Password Input
 */
Craft.PasswordInput = Garnish.Base.extend(
{
	$passwordInput: null,
	$textInput: null,
	$currentInput: null,

	$showPasswordToggle: null,
	showingPassword: null,

	init: function(passwordInput, settings)
	{
		this.$passwordInput = $(passwordInput);
		this.settings = $.extend({}, Craft.PasswordInput.defaults, settings);

		// Is this already a password input?
		if (this.$passwordInput.data('passwordInput'))
		{
			Garnish.log('Double-instantiating a password input on an element');
			this.$passwordInput.data('passwordInput').destroy();
		}

		this.$passwordInput.data('passwordInput', this);

		this.$showPasswordToggle = $('<a/>').hide();
		this.$showPasswordToggle.addClass('password-toggle');
		this.$showPasswordToggle.insertAfter(this.$passwordInput);
		this.addListener(this.$showPasswordToggle, 'mousedown', 'onToggleMouseDown');
		this.hidePassword();
	},

	setCurrentInput: function($input)
	{
		if (this.$currentInput)
		{
			// Swap the inputs, while preventing the focus animation
			$input.addClass('focus');
			this.$currentInput.replaceWith($input);
			$input.focus();
			$input.removeClass('focus');

			// Restore the input value
			$input.val(this.$currentInput.val());
		}

		this.$currentInput = $input;

		this.addListener(this.$currentInput, 'keypress,keyup,change,blur', 'onInputChange');
	},

	updateToggleLabel: function(label)
	{
		this.$showPasswordToggle.text(label);
	},

	showPassword: function()
	{
		if (this.showingPassword)
		{
			return;
		}

		if (!this.$textInput)
		{
			this.$textInput = this.$passwordInput.clone(true);
			this.$textInput.attr('type', 'text');
		}

		this.setCurrentInput(this.$textInput);
		this.updateToggleLabel(Craft.t('Hide'));
		this.showingPassword = true;
	},

	hidePassword: function()
	{
		// showingPassword could be null, which is acceptable
		if (this.showingPassword === false)
		{
			return;
		}

		this.setCurrentInput(this.$passwordInput);
		this.updateToggleLabel(Craft.t('Show'));
		this.showingPassword = false;
	},

	togglePassword: function()
	{
		if (this.showingPassword)
		{
			this.hidePassword();
		}
		else
		{
			this.showPassword();
		}

		this.settings.onToggleInput(this.$currentInput);
	},

	onInputChange: function()
	{
		if (this.$currentInput.val())
		{
			this.$showPasswordToggle.show();
		}
		else
		{
			this.$showPasswordToggle.hide();
		}
	},

	onToggleMouseDown: function(ev)
	{
		// Prevent focus change
		ev.preventDefault();

		var selectionStart,
			selectionEnd;

		if (this.$currentInput[0].setSelectionRange)
		{
			selectionStart = this.$currentInput[0].selectionStart;
			selectionEnd   = this.$currentInput[0].selectionEnd;
		}

		this.togglePassword();

		if (this.$currentInput[0].setSelectionRange)
		{
			this.$currentInput[0].setSelectionRange(selectionStart, selectionEnd);
		}
	}
},
{
	defaults: {
		onToggleInput: $.noop
	}
});

/**
 * File Manager.
 */
Craft.ProgressBar = Garnish.Base.extend(
{
    $progressBar: null,
    $innerProgressBar: null,

    _itemCount: 0,
    _processedItemCount: 0,

    init: function($element)
    {
		this.$progressBar = $('<div class="progressbar pending hidden"/>').appendTo($element);
		this.$innerProgressBar = $('<div class="progressbar-inner"/>').appendTo(this.$progressBar);

        this.resetProgressBar();
    },

    /**
     * Reset the progress bar
     */
    resetProgressBar: function()
    {
		// Since setting the progress percentage implies that there is progress to be shown
		// It removes the pending class - we must add it back.
		this.setProgressPercentage(100);
		this.$progressBar.addClass('pending');

		// Reset all the counters
		this.setItemCount(1);
        this.setProcessedItemCount(0);
    },

    /**
     * Fade to invisible, hide it using a class and reset opacity to visible
     */
    hideProgressBar: function()
    {
        this.$progressBar.fadeTo('fast', 0.01, $.proxy(function() {
            this.$progressBar.addClass('hidden').fadeTo(1, 1, $.noop);
        }, this));
    },

    showProgressBar: function()
    {
        this.$progressBar.removeClass('hidden');
    },

    setItemCount: function(count)
    {
        this._itemCount = count;
    },

    incrementItemCount: function(count)
    {
        this._itemCount += count;
    },

    setProcessedItemCount: function(count)
    {
        this._processedItemCount = count;
    },

    incrementProcessedItemCount: function(count)
    {
        this._processedItemCount += count;
    },

    updateProgressBar: function()
    {
        // Only fools would allow accidental division by zero.
        this._itemCount = Math.max(this._itemCount, 1);

        var width = Math.min(100, Math.round(100 * this._processedItemCount / this._itemCount));

        this.setProgressPercentage(width);
    },

    setProgressPercentage: function(percentage, animate)
    {
		if (percentage == 0)
		{
			this.$progressBar.addClass('pending');
		}
		else
		{
			this.$progressBar.removeClass('pending');

            if (animate)
            {
                this.$innerProgressBar.velocity('stop').velocity({ width: percentage+'%' }, 'fast');
            }
            else
            {
                this.$innerProgressBar.velocity('stop').width(percentage+'%');
            }
		}
    }
});

/**
 * File Manager.
 */
Craft.PromptHandler = Garnish.Base.extend({

    $modalContainerDiv: null,
    $prompt: null,
    $promptApplyToRemainingContainer: null,
    $promptApplyToRemainingCheckbox: null,
    $promptApplyToRemainingLabel: null,
    $promptButtons: null,


    _prompts: [],
    _promptBatchCallback: $.noop,
    _promptBatchReturnData: [],
    _promptBatchNum: 0,

    init: function()
    {

    },

    resetPrompts: function()
    {
        this._prompts = [];
        this._promptBatchCallback = $.noop;
        this._promptBatchReturnData = [];
        this._promptBatchNum = 0;
    },

    addPrompt: function(prompt)
    {
        this._prompts.push(prompt);
    },

    getPromptCount: function()
    {
        return this._prompts.length;
    },

    showBatchPrompts: function(callback)
    {
        this._promptBatchCallback = callback;
        this._promptBatchReturnData = [];
        this._promptBatchNum = 0;

        this._showNextPromptInBatch();
    },

    _showNextPromptInBatch: function()
    {
        var prompt = this._prompts[this._promptBatchNum].prompt,
            remainingInBatch = this._prompts.length - (this._promptBatchNum + 1);

        this._showPrompt(prompt.message, prompt.choices, $.proxy(this, '_handleBatchPromptSelection'), remainingInBatch);
    },

    /**
     * Handles a prompt choice selection.
     *
     * @param choice
     * @param applyToRemaining
     * @private
     */
    _handleBatchPromptSelection: function(choice, applyToRemaining)
    {
        var prompt = this._prompts[this._promptBatchNum],
            remainingInBatch = this._prompts.length - (this._promptBatchNum + 1);

        // Record this choice
        var choiceData = $.extend(prompt, {choice: choice});
        this._promptBatchReturnData.push(choiceData);

        // Are there any remaining items in the batch?
        if (remainingInBatch)
        {
            // Get ready to deal with the next prompt
            this._promptBatchNum++;

            // Apply the same choice to the remaining items?
            if (applyToRemaining)
            {
                this._handleBatchPromptSelection(choice, true);
            }
            else
            {
                // Show the next prompt
                this._showNextPromptInBatch();
            }
        }
        else
        {
            // All done! Call the callback
            if (typeof this._promptBatchCallback == 'function')
            {
                this._promptBatchCallback(this._promptBatchReturnData);
            }
        }
    },

    /**
     * Show the user prompt with a given message and choices, plus an optional "Apply to remaining" checkbox.
     *
     * @param string message
     * @param array choices
     * @param function callback
     * @param int itemsToGo
     */
    _showPrompt: function(message, choices, callback, itemsToGo)
    {
        this._promptCallback = callback;

        if (this.modal == null) {
            this.modal = new Garnish.Modal({closeOtherModals: false});
        }

        if (this.$modalContainerDiv == null) {
            this.$modalContainerDiv = $('<div class="modal fitted prompt-modal"></div>').addClass().appendTo(Garnish.$bod);
        }

        this.$prompt = $('<div class="body"></div>').appendTo(this.$modalContainerDiv.empty());

        this.$promptMessage = $('<p class="prompt-msg"/>').appendTo(this.$prompt);

        $('<p>').html(Craft.t('What do you want to do?')).appendTo(this.$prompt);

        this.$promptApplyToRemainingContainer = $('<label class="assets-applytoremaining"/>').appendTo(this.$prompt).hide();
        this.$promptApplyToRemainingCheckbox = $('<input type="checkbox"/>').appendTo(this.$promptApplyToRemainingContainer);
        this.$promptApplyToRemainingLabel = $('<span/>').appendTo(this.$promptApplyToRemainingContainer);
        this.$promptButtons = $('<div class="buttons"/>').appendTo(this.$prompt);


        this.modal.setContainer(this.$modalContainerDiv);

        this.$promptMessage.html(message);

        for (var i = 0; i < choices.length; i++)
        {
            var $btn = $('<div class="btn" data-choice="'+choices[i].value+'">' + choices[i].title + '</div>');

            this.addListener($btn, 'activate', function(ev)
            {
                var choice = ev.currentTarget.getAttribute('data-choice'),
                    applyToRemaining = this.$promptApplyToRemainingCheckbox.prop('checked');

                this._selectPromptChoice(choice, applyToRemaining);
            });

            this.$promptButtons.append($btn);
        }

        if (itemsToGo)
        {
            this.$promptApplyToRemainingContainer.show();
            this.$promptApplyToRemainingLabel.html(' ' + Craft.t('Apply this to the {number} remaining conflicts?', {number: itemsToGo}));
        }

        this.modal.show();
        this.modal.removeListener(Garnish.Modal.$shade, 'click');
        this.addListener(Garnish.Modal.$shade, 'click', '_cancelPrompt');

    },

    /**
     * Handles when a user selects one of the prompt choices.
     *
     * @param choice
     * @param applyToRemaining
     * @private
     */
    _selectPromptChoice: function(choice, applyToRemaining)
    {
        this.$prompt.fadeOut('fast', $.proxy(function() {
            this.modal.hide();
            this._promptCallback(choice, applyToRemaining);
        }, this));
    },

    /**
     * Cancels the prompt.
     */
    _cancelPrompt: function()
    {
        this._selectPromptChoice('cancel', true);
    }
});
/**
 * Slug Generator
 */
Craft.SlugGenerator = Craft.BaseInputGenerator.extend(
{
	generateTargetValue: function(sourceVal)
	{
		// Remove HTML tags
		sourceVal = sourceVal.replace(/<(.*?)>/g, '');

		// Remove inner-word punctuation
		sourceVal = sourceVal.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');

		// Make it lowercase
		sourceVal = sourceVal.toLowerCase();

		if (Craft.limitAutoSlugsToAscii)
		{
			// Convert extended ASCII characters to basic ASCII
			sourceVal = Craft.asciiString(sourceVal);
		}

		// Get the "words". Split on anything that is not alphanumeric.
		// Reference: http://www.regular-expressions.info/unicode.html
		var words = Craft.filterArray(XRegExp.matchChain(sourceVal, [XRegExp('[\\p{L}\\p{N}\\p{M}]+')]));

		if (words.length)
		{
			return words.join(Craft.slugWordSeparator);
		}
		else
		{
			return '';
		}
	}
});

/**
 * Structure class
 */
Craft.Structure = Garnish.Base.extend(
{
	id: null,

	$container: null,
	state: null,
	structureDrag: null,

	/**
	 * Init
	 */
	init: function(id, container, settings)
	{
		this.id = id;
		this.$container = $(container);
		this.setSettings(settings, Craft.Structure.defaults);

		// Is this already a structure?
		if (this.$container.data('structure'))
		{
			Garnish.log('Double-instantiating a structure on an element');
			this.$container.data('structure').destroy();
		}

		this.$container.data('structure', this);

		this.state = {};

		if (this.settings.storageKey)
		{
			$.extend(this.state, Craft.getLocalStorage(this.settings.storageKey, {}));
		}

		if (typeof this.state.collapsedElementIds == 'undefined')
		{
			this.state.collapsedElementIds = [];
		}

		var $parents = this.$container.find('ul').prev('.row');

		for (var i = 0; i < $parents.length; i++)
		{
			var $row = $($parents[i]),
				$li = $row.parent(),
				$toggle = $('<div class="toggle" title="'+Craft.t('Show/hide children')+'"/>').prependTo($row);

			if ($.inArray($row.children('.element').data('id'), this.state.collapsedElementIds) != -1)
			{
				$li.addClass('collapsed');
			}

			this.initToggle($toggle);
		}

		if (this.settings.sortable)
		{
			this.structureDrag = new Craft.StructureDrag(this, this.settings.maxLevels);
		}

		if (this.settings.newChildUrl)
		{
			this.initNewChildMenus(this.$container.find('.add'));
		}
	},

	initToggle: function($toggle)
	{
		$toggle.click($.proxy(function(ev)
		{
			var $li = $(ev.currentTarget).closest('li'),
				elementId = $li.children('.row').find('.element:first').data('id'),
				viewStateKey = $.inArray(elementId, this.state.collapsedElementIds);

			if ($li.hasClass('collapsed'))
			{
				$li.removeClass('collapsed');

				if (viewStateKey != -1)
				{
					this.state.collapsedElementIds.splice(viewStateKey, 1);
				}
			}
			else
			{
				$li.addClass('collapsed');

				if (viewStateKey == -1)
				{
					this.state.collapsedElementIds.push(elementId);
				}
			}

			if (this.settings.storageKey)
			{
				Craft.setLocalStorage(this.settings.storageKey, this.state);
			}

		}, this));
	},

	initNewChildMenus: function($addBtns)
	{
		this.addListener($addBtns, 'click', 'onNewChildMenuClick');
	},

	onNewChildMenuClick: function(ev)
	{
		var $btn = $(ev.currentTarget);

		if (!$btn.data('menubtn'))
		{
			var elementId = $btn.parent().children('.element').data('id'),
				newChildUrl = Craft.getUrl(this.settings.newChildUrl, 'parentId='+elementId),
				$menu = $('<div class="menu"><ul><li><a href="'+newChildUrl+'">'+Craft.t('New child')+'</a></li></ul></div>').insertAfter($btn);

			var menuBtn = new Garnish.MenuBtn($btn);
			menuBtn.showMenu();
		}
	},

	getIndent: function(level)
	{
		return Craft.Structure.baseIndent + (level-1) * Craft.Structure.nestedIndent;
	},

	addElement: function($element)
	{
		var $li = $('<li data-level="1"/>').appendTo(this.$container),
			$row = $('<div class="row" style="margin-'+Craft.left+': -'+Craft.Structure.baseIndent+'px; padding-'+Craft.left+': '+Craft.Structure.baseIndent+'px;">').appendTo($li);

		$row.append($element);

		if (this.settings.sortable)
		{
			$row.append('<a class="move icon" title="'+Craft.t('Move')+'"></a>');
			this.structureDrag.addItems($li);
		}

		if (this.settings.newChildUrl)
		{
			var $addBtn = $('<a class="add icon" title="'+Craft.t('New child')+'"></a>').appendTo($row);
			this.initNewChildMenus($addBtn);
		}

		$row.css('margin-bottom', -30);
		$row.velocity({ 'margin-bottom': 0 }, 'fast');
	},

	removeElement: function($element)
	{
		var $li = $element.parent().parent();

		if (this.settings.sortable)
		{
			this.structureDrag.removeItems($li);
		}

		if (!$li.siblings().length)
		{
			var $parentUl = $li.parent();
		}

		$li.css('visibility', 'hidden').velocity({ marginBottom: -$li.height() }, 'fast', $.proxy(function()
		{
			$li.remove();

			if (typeof $parentUl != 'undefined')
			{
				this._removeUl($parentUl);
			}
		}, this));
	},

	_removeUl: function($ul)
	{
		$ul.siblings('.row').children('.toggle').remove();
		$ul.remove();
	}
},
{
	baseIndent: 8,
	nestedIndent: 35,

	defaults: {
		storageKey:  null,
		sortable:    false,
		newChildUrl: null,
		maxLevels:   null
	}
});

/**
 * Structure drag class
 */
Craft.StructureDrag = Garnish.Drag.extend(
{
	structure: null,
	maxLevels: null,
	draggeeLevel: null,

	$helperLi: null,
	$targets: null,
	draggeeHeight: null,

	init: function(structure, maxLevels)
	{
		this.structure = structure;
		this.maxLevels = maxLevels;

		this.$insertion = $('<li class="draginsertion"/>');

		var $items = this.structure.$container.find('li');

		this.base($items, {
			handle: '.element:first, .move:first',
			helper: $.proxy(this, 'getHelper')
		});
	},

	getHelper: function($helper)
	{
		this.$helperLi = $helper;
		var $ul = $('<ul class="structure draghelper"/>').append($helper);
		$helper.css('padding-'+Craft.left, this.$draggee.css('padding-'+Craft.left));
		$helper.find('.move').removeAttr('title');
		return $ul;
	},

	onDragStart: function()
	{
		this.$targets = $();

		// Recursively find each of the targets, in the order they appear to be in
		this.findTargets(this.structure.$container);

		// How deep does the rabbit hole go?
		this.draggeeLevel = 0;
		var $level = this.$draggee;
		do {
			this.draggeeLevel++;
			$level = $level.find('> ul > li');
		} while($level.length);

		// Collapse the draggee
		this.draggeeHeight = this.$draggee.height();
		this.$draggee.velocity({
			height: 0
		}, 'fast', $.proxy(function() {
			this.$draggee.addClass('hidden');
		}, this));
		this.base();

		this.addListener(Garnish.$doc, 'keydown', function(ev)
		{
			if (ev.keyCode == Garnish.ESC_KEY)
			{
				this.cancelDrag();
			}
		});
	},

	findTargets: function($ul)
	{
		var $lis = $ul.children().not(this.$draggee);

		for (var i = 0; i < $lis.length; i++)
		{
			var $li = $($lis[i]);
			this.$targets = this.$targets.add($li.children('.row'));

			if (!$li.hasClass('collapsed'))
			{
				this.findTargets($li.children('ul'));
			}
		}
	},

	onDrag: function()
	{
		if (this._.$closestTarget)
		{
			this._.$closestTarget.removeClass('draghover');
			this.$insertion.remove();
		}

		// First let's find the closest target
		this._.$closestTarget = null;
		this._.closestTargetPos = null;
		this._.closestTargetYDiff = null;
		this._.closestTargetOffset = null;
		this._.closestTargetHeight = null;

		for (this._.i = 0; this._.i < this.$targets.length; this._.i++)
		{
			this._.$target = $(this.$targets[this._.i]);
			this._.targetOffset = this._.$target.offset();
			this._.targetHeight = this._.$target.outerHeight();
			this._.targetYMidpoint = this._.targetOffset.top + (this._.targetHeight / 2);
			this._.targetYDiff = Math.abs(this.mouseY - this._.targetYMidpoint);

			if (this._.i == 0 || (this.mouseY >= this._.targetOffset.top + 5 && this._.targetYDiff < this._.closestTargetYDiff))
			{
				this._.$closestTarget = this._.$target;
				this._.closestTargetPos = this._.i;
				this._.closestTargetYDiff = this._.targetYDiff;
				this._.closestTargetOffset = this._.targetOffset;
				this._.closestTargetHeight = this._.targetHeight;
			}
			else
			{
				// Getting colder
				break;
			}
		}

		if (!this._.$closestTarget)
		{
			return;
		}

		// Are we hovering above the first row?
		if (this._.closestTargetPos == 0 && this.mouseY < this._.closestTargetOffset.top + 5)
		{
			this.$insertion.prependTo(this.structure.$container);
		}
		else
		{
			this._.$closestTargetLi = this._.$closestTarget.parent();
			this._.closestTargetLevel = this._.$closestTargetLi.data('level');

			// Is there a next row?
			if (this._.closestTargetPos < this.$targets.length - 1)
			{
				this._.$nextTargetLi = $(this.$targets[this._.closestTargetPos+1]).parent();
				this._.nextTargetLevel = this._.$nextTargetLi.data('level');
			}
			else
			{
				this._.$nextTargetLi = null;
				this._.nextTargetLevel = null;
			}

			// Are we hovering between this row and the next one?
			this._.hoveringBetweenRows = (this.mouseY >= this._.closestTargetOffset.top + this._.closestTargetHeight - 5);

			/**
			 * Scenario 1: Both rows have the same level.
			 *
			 *     * Row 1
			 *     ----------------------
			 *     * Row 2
			 */

			if (this._.$nextTargetLi && this._.nextTargetLevel == this._.closestTargetLevel)
			{
				if (this._.hoveringBetweenRows)
				{
					if (!this.maxLevels || this.maxLevels >= (this._.closestTargetLevel + this.draggeeLevel - 1))
					{
						// Position the insertion after the closest target
						this.$insertion.insertAfter(this._.$closestTargetLi);
					}

				}
				else
				{
					if (!this.maxLevels || this.maxLevels >= (this._.closestTargetLevel + this.draggeeLevel))
					{
						this._.$closestTarget.addClass('draghover');
					}
				}
			}

			/**
			 * Scenario 2: Next row is a child of this one.
			 *
			 *     * Row 1
			 *     ----------------------
			 *         * Row 2
			 */

			else if (this._.$nextTargetLi && this._.nextTargetLevel > this._.closestTargetLevel)
			{
				if (!this.maxLevels || this.maxLevels >= (this._.nextTargetLevel + this.draggeeLevel - 1))
				{
					if (this._.hoveringBetweenRows)
					{
						// Position the insertion as the first child of the closest target
						this.$insertion.insertBefore(this._.$nextTargetLi);
					}
					else
					{
						this._.$closestTarget.addClass('draghover');
						this.$insertion.appendTo(this._.$closestTargetLi.children('ul'));
					}
				}
			}

			/**
			 * Scenario 3: Next row is a child of a parent node, or there is no next row.
			 *
			 *         * Row 1
			 *     ----------------------
			 *     * Row 2
			 */

			else
			{
				if (this._.hoveringBetweenRows)
				{
					// Determine which <li> to position the insertion after
					this._.draggeeX = this.mouseX - this.targetItemMouseDiffX;

					if (Craft.orientation == 'rtl')
					{
						this._.draggeeX += this.$helperLi.width();
					}

					this._.$parentLis = this._.$closestTarget.parentsUntil(this.structure.$container, 'li');
					this._.$closestParentLi = null;
					this._.closestParentLiXDiff = null;
					this._.closestParentLevel = null;

					for (this._.i = 0; this._.i < this._.$parentLis.length; this._.i++)
					{
						this._.$parentLi = $(this._.$parentLis[this._.i]);
						this._.parentLiX = this._.$parentLi.offset().left;

						if (Craft.orientation == 'rtl')
						{
							this._.parentLiX += this._.$parentLi.width();
						}

						this._.parentLiXDiff = Math.abs(this._.parentLiX - this._.draggeeX);
						this._.parentLevel = this._.$parentLi.data('level');

						if ((!this.maxLevels || this.maxLevels >= (this._.parentLevel + this.draggeeLevel - 1)) && (
							!this._.$closestParentLi || (
								this._.parentLiXDiff < this._.closestParentLiXDiff &&
								(!this._.$nextTargetLi || this._.parentLevel >= this._.nextTargetLevel)
							)
						))
						{
							this._.$closestParentLi = this._.$parentLi;
							this._.closestParentLiXDiff = this._.parentLiXDiff;
							this._.closestParentLevel = this._.parentLevel;
						}
					}

					if (this._.$closestParentLi)
					{
						this.$insertion.insertAfter(this._.$closestParentLi);
					}
				}
				else
				{
					if (!this.maxLevels || this.maxLevels >= (this._.closestTargetLevel + this.draggeeLevel))
					{
						this._.$closestTarget.addClass('draghover');
					}
				}
			}
		}
	},

	cancelDrag: function()
	{
		this.$insertion.remove();

		if (this._.$closestTarget)
		{
			this._.$closestTarget.removeClass('draghover');
		}

		this.onMouseUp();
	},

	onDragStop: function()
	{
		// Are we repositioning the draggee?
		if (this._.$closestTarget && (this.$insertion.parent().length || this._.$closestTarget.hasClass('draghover')))
		{
			var $draggeeParent,
				moved;

			// Are we about to leave the draggee's original parent childless?
			if (!this.$draggee.siblings().length)
			{
				$draggeeParent = this.$draggee.parent();
			}
			else
			{
				$draggeeParent = null;
			}

			if (this.$insertion.parent().length)
			{
				// Make sure the insertion isn't right next to the draggee
				var $closestSiblings = this.$insertion.next().add(this.$insertion.prev());

				if ($.inArray(this.$draggee[0], $closestSiblings) == -1)
				{
					this.$insertion.replaceWith(this.$draggee);
					var moved = true;
				}
				else
				{
					this.$insertion.remove();
					var moved = false;
				}
			}
			else
			{
				var $ul = this._.$closestTargetLi.children('ul');

				// Make sure this is a different parent than the draggee's
				if (!$draggeeParent || !$ul.length || $ul[0] != $draggeeParent[0])
				{
					if (!$ul.length)
					{
						var $toggle = $('<div class="toggle" title="'+Craft.t('Show/hide children')+'"/>').prependTo(this._.$closestTarget);
						this.structure.initToggle($toggle);

						$ul = $('<ul>').appendTo(this._.$closestTargetLi);
					}
					else if (this._.$closestTargetLi.hasClass('collapsed'))
					{
						this._.$closestTarget.children('.toggle').trigger('click');
					}

					this.$draggee.appendTo($ul);
					moved = true;
				}
				else
				{
					moved = false;
				}
			}

			// Remove the class either way
			this._.$closestTarget.removeClass('draghover');

			if (moved)
			{
				// Now deal with the now-childless parent
				if ($draggeeParent)
				{
					this.structure._removeUl($draggeeParent);
				}

				// Has the level changed?
				var newLevel = this.$draggee.parentsUntil(this.structure.$container, 'li').length + 1;

				if (newLevel != this.$draggee.data('level'))
				{
					// Correct the helper's padding if moving to/from level 1
					if (this.$draggee.data('level') == 1)
					{
						var animateCss = {};
						animateCss['padding-'+Craft.left] = 38;
						this.$helperLi.velocity(animateCss, 'fast');
					}
					else if (newLevel == 1)
					{
						var animateCss = {};
						animateCss['padding-'+Craft.left] = Craft.Structure.baseIndent;
						this.$helperLi.velocity(animateCss, 'fast');
					}

					this.setLevel(this.$draggee, newLevel);
				}

				// Make it real
				var $element = this.$draggee.children('.row').children('.element');

				var data = {
					structureId: this.structure.id,
					elementId:   $element.data('id'),
					locale:      $element.data('locale'),
					prevId:      this.$draggee.prev().children('.row').children('.element').data('id'),
					parentId:    this.$draggee.parent('ul').parent('li').children('.row').children('.element').data('id')
				};

				Craft.postActionRequest('structures/moveElement', data, function(response, textStatus)
				{
					if (textStatus == 'success')
					{
						Craft.cp.displayNotice(Craft.t('New order saved.'));
					}

				});
			}
		}

		// Animate things back into place
		this.$draggee.velocity('stop').removeClass('hidden').velocity({
			height: this.draggeeHeight
		}, 'fast', $.proxy(function() {
			this.$draggee.css('height', 'auto');
		}, this));

		this.returnHelpersToDraggees();

		this.base();
	},

	setLevel: function($li, level)
	{
		$li.data('level', level);

		var indent = this.structure.getIndent(level);

		var css = {};
		css['margin-'+Craft.left] = '-'+indent+'px';
		css['padding-'+Craft.left] = indent+'px';
		this.$draggee.children('.row').css(css);

		var $childLis = $li.children('ul').children();

		for (var i = 0; i < $childLis.length; i++)
		{
			this.setLevel($($childLis[i]), level+1);
		}
	}

});

Craft.StructureTableSorter = Garnish.DragSort.extend({

	// Properties
	// =========================================================================

	tableView: null,
	structureId: null,
	maxLevels: null,

	_helperMargin: null,

	_$firstRowCells: null,
	_$titleHelperCell: null,

	_titleHelperCellOuterWidth: null,

	_ancestors: null,
	_updateAncestorsFrame: null,
	_updateAncestorsProxy: null,

	_draggeeLevel: null,
	_draggeeLevelDelta: null,
	draggingLastElements: null,
	_loadingDraggeeLevelDelta: false,

	_targetLevel: null,
	_targetLevelBounds: null,

	_positionChanged: null,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 */
	init: function(tableView, $elements, settings)
	{
		this.tableView = tableView;
		this.structureId = this.tableView.$table.data('structure-id');
		this.maxLevels = parseInt(this.tableView.$table.attr('data-max-levels'));

		settings = $.extend({}, Craft.StructureTableSorter.defaults, settings, {
			handle:           '.move',
			collapseDraggees: true,
			singleHelper:     true,
			helperSpacingY:   2,
			magnetStrength:   4,
			helper:           $.proxy(this, 'getHelper'),
			helperLagBase:    1.5,
			axis:             Garnish.Y_AXIS
		});

		this.base($elements, settings);
	},

	/**
	 * Start Dragging
	 */
	startDragging: function()
	{
		this._helperMargin = Craft.StructureTableSorter.HELPER_MARGIN + (this.tableView.elementIndex.actions ? 24 : 0);
		this.base();
	},

	/**
	 * Returns the draggee rows (including any descendent rows).
	 */
	findDraggee: function()
	{
		this._draggeeLevel = this._targetLevel = this.$targetItem.data('level');
		this._draggeeLevelDelta = 0;

		var $draggee = $(this.$targetItem),
			$nextRow = this.$targetItem.next();

		while ($nextRow.length)
		{
			// See if this row is a descendant of the draggee
			var nextRowLevel = $nextRow.data('level');

			if (nextRowLevel <= this._draggeeLevel)
			{
				break;
			}

			// Is this the deepest descendant we've seen so far?
			var nextRowLevelDelta = nextRowLevel - this._draggeeLevel;

			if (nextRowLevelDelta > this._draggeeLevelDelta)
			{
				this._draggeeLevelDelta = nextRowLevelDelta;
			}

			// Add it and prep the next row
			$draggee = $draggee.add($nextRow);
			$nextRow = $nextRow.next();
		}

		// Are we dragging the last elements on the page?
		this.draggingLastElements = !$nextRow.length;

		// Do we have a maxLevels to enforce,
		// and does it look like this draggee has descendants we don't know about yet?
		if (
			this.maxLevels &&
			this.draggingLastElements &&
			this.tableView.getMorePending()
		)
		{
			// Only way to know the true descendant level delta is to ask PHP
			this._loadingDraggeeLevelDelta = true;

			var data = this._getAjaxBaseData(this.$targetItem);

			Craft.postActionRequest('structures/getElementLevelDelta', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					this._loadingDraggeeLevelDelta = false;

					if (this.dragging)
					{
						this._draggeeLevelDelta = response.delta;
						this.drag(false);
					}
				}
			}, this));
		}

		return $draggee;
	},

	/**
	 * Returns the drag helper.
	 */
	getHelper: function($helperRow)
	{
		var $outerContainer = $('<div class="elements datatablesorthelper"/>').appendTo(Garnish.$bod),
			$innerContainer = $('<div class="tableview"/>').appendTo($outerContainer),
			$table = $('<table class="data"/>').appendTo($innerContainer),
			$tbody = $('<tbody/>').appendTo($table);

		$helperRow.appendTo($tbody);

		// Copy the column widths
		this._$firstRowCells = this.tableView.$elementContainer.children('tr:first').children();
		var $helperCells = $helperRow.children();

		for (var i = 0; i < $helperCells.length; i++)
		{
			var $helperCell = $($helperCells[i]);

			// Skip the checkbox cell
			if ($helperCell.hasClass('checkbox-cell'))
			{
				$helperCell.remove();
				continue;
			}

			// Hard-set the cell widths
			var $firstRowCell = $(this._$firstRowCells[i]),
				width = $firstRowCell.width();

			$firstRowCell.width(width);
			$helperCell.width(width);

			// Is this the title cell?
			if (Garnish.hasAttr($firstRowCell, 'data-titlecell'))
			{
				this._$titleHelperCell = $helperCell;

				var padding = parseInt($firstRowCell.css('padding-'+Craft.left));
				this._titleHelperCellOuterWidth = width + padding - (this.tableView.elementIndex.actions ? 12 : 0);

				$helperCell.css('padding-'+Craft.left, Craft.StructureTableSorter.BASE_PADDING);
			}
		}

		return $outerContainer;
	},

	/**
	 * Returns whether the draggee can be inserted before a given item.
	 */
	canInsertBefore: function($item)
	{
		if (this._loadingDraggeeLevelDelta)
		{
			return false;
		}

		return (this._getLevelBounds($item.prev(), $item) !== false);
	},

	/**
	 * Returns whether the draggee can be inserted after a given item.
	 */
	canInsertAfter: function($item)
	{
		if (this._loadingDraggeeLevelDelta)
		{
			return false;
		}

		return (this._getLevelBounds($item, $item.next()) !== false);
	},

	// Events
	// -------------------------------------------------------------------------

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		// Get the initial set of ancestors, before the item gets moved
		this._ancestors = this._getAncestors(this.$targetItem, this.$targetItem.data('level'));

		// Set the initial target level bounds
		this._setTargetLevelBounds();

		// Check to see if we should load more elements now
		this.tableView.maybeLoadMore();

		this.base();
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		this.base();
		this._updateIndent();
	},

	/**
	 * On Insertion Point Change
	 */
	onInsertionPointChange: function()
	{
		this._setTargetLevelBounds();
		this._updateAncestorsBeforeRepaint();
		this.base();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		this._positionChanged = false;
		this.base();

		// Update the draggee's padding if the position just changed
		// ---------------------------------------------------------------------

		if (this._targetLevel != this._draggeeLevel)
		{
			var levelDiff = this._targetLevel - this._draggeeLevel;

			for (var i = 0; i < this.$draggee.length; i++)
			{
				var $draggee = $(this.$draggee[i]),
					oldLevel = $draggee.data('level'),
					newLevel = oldLevel + levelDiff,
					padding = Craft.StructureTableSorter.BASE_PADDING + (this.tableView.elementIndex.actions ? 7 : 0) + this._getLevelIndent(newLevel);

				$draggee.data('level', newLevel);
				$draggee.find('.element').data('level', newLevel);
				$draggee.children('[data-titlecell]:first').css('padding-'+Craft.left, padding);
			}

			this._positionChanged = true;
		}

		// Keep in mind this could have also been set by onSortChange()
		if (this._positionChanged)
		{
			// Tell the server about the new position
			// -----------------------------------------------------------------

			var data = this._getAjaxBaseData(this.$draggee);

			// Find the previous sibling/parent, if there is one
			var $prevRow = this.$draggee.first().prev();

			while ($prevRow.length)
			{
				var prevRowLevel = $prevRow.data('level');

				if (prevRowLevel == this._targetLevel)
				{
					data.prevId = $prevRow.data('id');
					break;
				}

				if (prevRowLevel < this._targetLevel)
				{
					data.parentId = $prevRow.data('id');

					// Is this row collapsed?
					var $toggle = $prevRow.find('> td > .toggle');

					if (!$toggle.hasClass('expanded'))
					{
						// Make it look expanded
						$toggle.addClass('expanded');

						// Add a temporary row
						var $spinnerRow = this.tableView._createSpinnerRowAfter($prevRow);

						// Remove the target item
						if (this.tableView.elementSelect)
						{
							this.tableView.elementSelect.removeItems(this.$targetItem);
						}

						this.removeItems(this.$targetItem);
						this.$targetItem.remove();
						this.tableView._totalVisible--;
					}

					break;
				}

				$prevRow = $prevRow.prev();
			}

			Craft.postActionRequest('structures/moveElement', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success')
				{
					Craft.cp.displayNotice(Craft.t('New position saved.'));
					this.onPositionChange();

					// Were we waiting on this to complete so we can expand the new parent?
					if ($spinnerRow && $spinnerRow.parent().length)
					{
						$spinnerRow.remove();
						this.tableView._expandElement($toggle, true);
					}

					// See if we should run any pending tasks
					Craft.cp.runPendingTasks();
				}
			}, this));
		}
	},

	onSortChange: function()
	{
		if (this.tableView.elementSelect)
		{
			this.tableView.elementSelect.resetItemOrder();
		}

		this._positionChanged = true;
		this.base();
	},

	onPositionChange: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('positionChange');
			this.settings.onPositionChange();
		}, this));
	},

	onReturnHelpersToDraggees: function()
	{
		this._$firstRowCells.css('width', '');

		// If we were dragging the last elements on the page and ended up loading any additional elements in,
		// there could be a gap between the last draggee item and whatever now comes after it.
		// So remove the post-draggee elements and possibly load up the next batch.
		if (this.draggingLastElements && this.tableView.getMorePending())
		{
			// Update the element index's record of how many items are actually visible
			this.tableView._totalVisible += (this.newDraggeeIndexes[0] - this.oldDraggeeIndexes[0]);

			var $postDraggeeItems = this.$draggee.last().nextAll();

			if ($postDraggeeItems.length)
			{
				this.removeItems($postDraggeeItems);
				$postDraggeeItems.remove();
				this.tableView.maybeLoadMore();
			}
		}

		this.base();
	},

	// Private methods
	// =========================================================================

	/**
	 * Returns the min and max levels that the draggee could occupy between
	 * two given rows, or false if it’s not going to work out.
	 */
	_getLevelBounds: function($prevRow, $nextRow)
	{
		// Can't go any lower than the next row, if there is one
		if ($nextRow && $nextRow.length)
		{
			this._getLevelBounds._minLevel = $nextRow.data('level');
		}
		else
		{
			this._getLevelBounds._minLevel = 1;
		}

		// Can't go any higher than the previous row + 1
		if ($prevRow && $prevRow.length)
		{
			this._getLevelBounds._maxLevel = $prevRow.data('level') + 1;
		}
		else
		{
			this._getLevelBounds._maxLevel = 1;
		}

		// Does this structure have a max level?
		if (this.maxLevels)
		{
			// Make sure it's going to fit at all here
			if (
				this._getLevelBounds._minLevel != 1 &&
				this._getLevelBounds._minLevel + this._draggeeLevelDelta > this.maxLevels
			)
			{
				return false;
			}

			// Limit the max level if we have to
			if (this._getLevelBounds._maxLevel + this._draggeeLevelDelta > this.maxLevels)
			{
				this._getLevelBounds._maxLevel = this.maxLevels - this._draggeeLevelDelta;

				if (this._getLevelBounds._maxLevel < this._getLevelBounds._minLevel)
				{
					this._getLevelBounds._maxLevel = this._getLevelBounds._minLevel;
				}
			}
		}

		return {
			min: this._getLevelBounds._minLevel,
			max: this._getLevelBounds._maxLevel
		};
	},

	/**
	 * Determines the min and max possible levels at the current draggee's position.
	 */
	_setTargetLevelBounds: function()
	{
		this._targetLevelBounds = this._getLevelBounds(
			this.$draggee.first().prev(),
			this.$draggee.last().next()
		);
	},

	/**
	 * Determines the target level based on the current mouse position.
	 */
	_updateIndent: function(forcePositionChange)
	{
		// Figure out the target level
		// ---------------------------------------------------------------------

		// How far has the cursor moved?
		this._updateIndent._mouseDist = this.realMouseX - this.mousedownX;

		// Flip that if this is RTL
		if (Craft.orientation == 'rtl')
		{
			this._updateIndent._mouseDist *= -1;
		}

		// What is that in indentation levels?
		this._updateIndent._indentationDist = Math.round(this._updateIndent._mouseDist / Craft.StructureTableSorter.LEVEL_INDENT);

		// Combine with the original level to get the new target level
		this._updateIndent._targetLevel = this._draggeeLevel + this._updateIndent._indentationDist;

		// Contain it within our min/max levels
		if (this._updateIndent._targetLevel < this._targetLevelBounds.min)
		{
			this._updateIndent._indentationDist += (this._targetLevelBounds.min - this._updateIndent._targetLevel);
			this._updateIndent._targetLevel = this._targetLevelBounds.min;
		}
		else if (this._updateIndent._targetLevel > this._targetLevelBounds.max)
		{
			this._updateIndent._indentationDist -= (this._updateIndent._targetLevel - this._targetLevelBounds.max);
			this._updateIndent._targetLevel = this._targetLevelBounds.max;
		}

		// Has the target level changed?
		if (this._targetLevel !== (this._targetLevel = this._updateIndent._targetLevel))
		{
			// Target level is changing, so update the ancestors
			this._updateAncestorsBeforeRepaint();
		}

		// Update the UI
		// ---------------------------------------------------------------------

		// How far away is the cursor from the exact target level distance?
		this._updateIndent._targetLevelMouseDiff = this._updateIndent._mouseDist - (this._updateIndent._indentationDist * Craft.StructureTableSorter.LEVEL_INDENT);

		// What's the magnet impact of that?
		this._updateIndent._magnetImpact = Math.round(this._updateIndent._targetLevelMouseDiff / 15);

		// Put it on a leash
		if (Math.abs(this._updateIndent._magnetImpact) > Craft.StructureTableSorter.MAX_GIVE)
		{
			this._updateIndent._magnetImpact = (this._updateIndent._magnetImpact > 0 ? 1 : -1) * Craft.StructureTableSorter.MAX_GIVE;
		}

		// Apply the new margin/width
		this._updateIndent._closestLevelMagnetIndent = this._getLevelIndent(this._targetLevel) + this._updateIndent._magnetImpact;
		this.helpers[0].css('margin-'+Craft.left, this._updateIndent._closestLevelMagnetIndent + this._helperMargin);
		this._$titleHelperCell.width(this._titleHelperCellOuterWidth - (this._updateIndent._closestLevelMagnetIndent + Craft.StructureTableSorter.BASE_PADDING));
	},

	/**
	 * Returns the indent size for a given level
	 */
	_getLevelIndent: function(level)
	{
		return (level - 1) * Craft.StructureTableSorter.LEVEL_INDENT;
	},

	/**
	 * Returns the base data that should be sent with StructureController Ajax requests.
	 */
	_getAjaxBaseData: function($row)
	{
		return {
			structureId: this.structureId,
			elementId:   $row.data('id'),
			locale:      $row.find('.element:first').data('locale')
		};
	},

	/**
	 * Returns a row's ancestor rows
	 */
	_getAncestors: function($row, targetLevel)
	{
		this._getAncestors._ancestors = [];

		if (targetLevel != 0)
		{
			this._getAncestors._level = targetLevel;
			this._getAncestors._$prevRow = $row.prev();

			while (this._getAncestors._$prevRow.length)
			{
				if (this._getAncestors._$prevRow.data('level') < this._getAncestors._level)
				{
					this._getAncestors._ancestors.unshift(this._getAncestors._$prevRow);
					this._getAncestors._level = this._getAncestors._$prevRow.data('level');

					// Did we just reach the top?
					if (this._getAncestors._level == 0)
					{
						break;
					}
				}

				this._getAncestors._$prevRow = this._getAncestors._$prevRow.prev();
			}
		}

		return this._getAncestors._ancestors;
	},

	/**
	 * Prepares to have the ancestors updated before the screen is repainted.
	 */
	_updateAncestorsBeforeRepaint: function()
	{
		if (this._updateAncestorsFrame)
		{
			Garnish.cancelAnimationFrame(this._updateAncestorsFrame);
		}

		if (!this._updateAncestorsProxy)
		{
			this._updateAncestorsProxy = $.proxy(this, '_updateAncestors');
		}

		this._updateAncestorsFrame = Garnish.requestAnimationFrame(this._updateAncestorsProxy);
	},

	_updateAncestors: function()
	{
		this._updateAncestorsFrame = null;

		// Update the old ancestors
		// -----------------------------------------------------------------

		for (this._updateAncestors._i = 0; this._updateAncestors._i < this._ancestors.length; this._updateAncestors._i++)
		{
			this._updateAncestors._$ancestor = this._ancestors[this._updateAncestors._i];

			// One less descendant now
			this._updateAncestors._$ancestor.data('descendants', this._updateAncestors._$ancestor.data('descendants') - 1);

			// Is it now childless?
			if (this._updateAncestors._$ancestor.data('descendants') == 0)
			{
				// Remove its toggle
				this._updateAncestors._$ancestor.find('> td > .toggle:first').remove();
			}
		}

		// Update the new ancestors
		// -----------------------------------------------------------------

		this._updateAncestors._newAncestors = this._getAncestors(this.$targetItem, this._targetLevel);

		for (this._updateAncestors._i = 0; this._updateAncestors._i < this._updateAncestors._newAncestors.length; this._updateAncestors._i++)
		{
			this._updateAncestors._$ancestor = this._updateAncestors._newAncestors[this._updateAncestors._i];

			// One more descendant now
			this._updateAncestors._$ancestor.data('descendants', this._updateAncestors._$ancestor.data('descendants') + 1);

			// Is this its first child?
			if (this._updateAncestors._$ancestor.data('descendants') == 1)
			{
				// Create its toggle
				$('<span class="toggle expanded" title="'+Craft.t('Show/hide children')+'"></span>')
					.insertAfter(this._updateAncestors._$ancestor.find('> td .move:first'));

			}
		}

		this._ancestors = this._updateAncestors._newAncestors;

		delete this._updateAncestors._i;
		delete this._updateAncestors._$ancestor;
		delete this._updateAncestors._newAncestors;
	}
},

// Static Properties
// =============================================================================

{
	BASE_PADDING: 36,
	HELPER_MARGIN: -7,
	LEVEL_INDENT: 44,
	MAX_GIVE: 22,

	defaults: {
		onPositionChange: $.noop
	}
});

/**
 * Table Element Index View
 */
Craft.TableElementIndexView = Craft.BaseElementIndexView.extend(
{
	$table: null,
	$selectedSortHeader: null,

	structureTableSort: null,

	_totalVisiblePostStructureTableDraggee: null,
	_morePendingPostStructureTableDraggee: false,

	getElementContainer: function()
	{
		// Save a reference to the table
		this.$table = this.$container.find('table:first');
		return this.$table.children('tbody:first');
	},

	afterInit: function()
	{
		// Make the table collapsible for mobile devices
		Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.add(this.$table);
		Craft.cp.updateResponsiveTables();

		// Set the sort header
		this.initTableHeaders();

		// Create the Structure Table Sorter
		if (
			this.elementIndex.settings.context == 'index' &&
			this.elementIndex.getSelectedSortAttribute() == 'structure' &&
			Garnish.hasAttr(this.$table, 'data-structure-id')
		)
		{
			this.structureTableSort = new Craft.StructureTableSorter(this, this.getAllElements(), {
				onSortChange: $.proxy(this, '_onStructureTableSortChange')
			});
		}
		else
		{
			this.structureTableSort = null;
		}

		// Handle expand/collapse toggles for Structures
		if (this.elementIndex.getSelectedSortAttribute() == 'structure')
		{
			this.addListener(this.$elementContainer, 'click', function(ev)
			{
				var $target = $(ev.target);

				if ($target.hasClass('toggle'))
				{
					if (this._collapseElement($target) === false)
					{
						this._expandElement($target);
					}
				}
			});
		}
	},

	initTableHeaders: function()
	{
		var selectedSortAttr = this.elementIndex.getSelectedSortAttribute(),
			$tableHeaders = this.$table.children('thead').children().children('[data-attribute]');

		for (var i = 0; i < $tableHeaders.length; i++)
		{
			var $header = $tableHeaders.eq(i),
				attr = $header.attr('data-attribute');

			// Is this the selected sort attribute?
			if (attr == selectedSortAttr)
			{
				this.$selectedSortHeader = $header;
				var selectedSortDir = this.elementIndex.getSelectedSortDirection();

				$header
					.addClass('ordered '+selectedSortDir)
					.click($.proxy(this, '_handleSelectedSortHeaderClick'));
			}
			else
			{
				// Is this attribute sortable?
				var $sortAttribute = this.elementIndex.getSortAttributeOption(attr);

				if ($sortAttribute.length)
				{
					$header
						.addClass('orderable')
						.click($.proxy(this, '_handleUnselectedSortHeaderClick'));
				}
			}
		}
	},

	isVerticalList: function()
	{
		return true;
	},

	getTotalVisible: function()
	{
		if (this._isStructureTableDraggingLastElements())
		{
			return this._totalVisiblePostStructureTableDraggee;
		}
		else
		{
			return this._totalVisible;
		}
	},

	setTotalVisible: function(totalVisible)
	{
		if (this._isStructureTableDraggingLastElements())
		{
			this._totalVisiblePostStructureTableDraggee = totalVisible;
		}
		else
		{
			this._totalVisible = totalVisible;
		}
	},

	getMorePending: function()
	{
		if (this._isStructureTableDraggingLastElements())
		{
			return this._morePendingPostStructureTableDraggee;
		}
		else
		{
			return this._morePending;
		}
	},

	setMorePending: function(morePending)
	{
		if (this._isStructureTableDraggingLastElements())
		{
			this._morePendingPostStructureTableDraggee = morePending;
		}
		else
		{
			this._morePending = this._morePendingPostStructureTableDraggee = morePending;
		}
	},

	getLoadMoreParams: function()
	{
		var params = this.base();

		// If we are dragging the last elements on the page,
		// tell the controller to only load elements positioned after the draggee.
		if (this._isStructureTableDraggingLastElements())
		{
			params.criteria.positionedAfter = this.structureTableSort.$targetItem.data('id');
		}

		return params;
	},

	appendElements: function($newElements)
	{
		this.base($newElements);

		if (this.structureTableSort)
		{
			this.structureTableSort.addItems($newElements);
		}

		Craft.cp.updateResponsiveTables();
	},

	createElementEditor: function($element)
	{
		new Craft.ElementEditor($element, {
			params: {
				includeTableAttributesForSource: this.elementIndex.sourceKey
			},
			onSaveElement: $.proxy(function(response) {
				if (response.tableAttributes) {
					this._updateTableAttributes($element, response.tableAttributes);
				}
			}, this)
		});
	},

	destroy: function()
	{
		if (this.$table)
		{
			// Remove the soon-to-be-wiped-out table from the list of collapsible tables
			Craft.cp.$collapsibleTables = Craft.cp.$collapsibleTables.not(this.$table);
		}

		this.base();
	},

	_collapseElement: function($toggle, force)
	{
		if (!force && !$toggle.hasClass('expanded'))
		{
			return false;
		}

		$toggle.removeClass('expanded');

		// Find and remove the descendant rows
		var $row = $toggle.parent().parent(),
			id = $row.data('id'),
			level = $row.data('level'),
			$nextRow = $row.next();

		while ($nextRow.length)
		{
			if (!Garnish.hasAttr($nextRow, 'data-spinnerrow'))
			{
				if ($nextRow.data('level') <= level)
				{
					break;
				}

				if (this.elementSelect)
				{
					this.elementSelect.removeItems($nextRow);
				}

				if (this.structureTableSort)
				{
					this.structureTableSort.removeItems($nextRow);
				}

				this._totalVisible--;
			}

			var $nextNextRow = $nextRow.next();
			$nextRow.remove();
			$nextRow = $nextNextRow;
		}

		// Remember that this row should be collapsed
		if (!this.elementIndex.instanceState.collapsedElementIds)
		{
			this.elementIndex.instanceState.collapsedElementIds = [];
		}

		this.elementIndex.instanceState.collapsedElementIds.push(id);
		this.elementIndex.setInstanceState('collapsedElementIds', this.elementIndex.instanceState.collapsedElementIds);

		// Bottom of the index might be viewable now
		this.maybeLoadMore();
	},

	_expandElement: function($toggle, force)
	{
		if (!force && $toggle.hasClass('expanded'))
		{
			return false;
		}

		$toggle.addClass('expanded');

		// Remove this element from our list of collapsed elements
		if (this.elementIndex.instanceState.collapsedElementIds)
		{
			var $row = $toggle.parent().parent(),
				id = $row.data('id'),
				index = $.inArray(id, this.elementIndex.instanceState.collapsedElementIds);

			if (index != -1)
			{
				this.elementIndex.instanceState.collapsedElementIds.splice(index, 1);
				this.elementIndex.setInstanceState('collapsedElementIds', this.elementIndex.instanceState.collapsedElementIds);

				// Add a temporary row
				var $spinnerRow = this._createSpinnerRowAfter($row);

				// Load the nested elements
				var params = $.extend(true, {}, this.settings.params);
				params.criteria.descendantOf = id;

				Craft.postActionRequest('elementIndex/getMoreElements', params, $.proxy(function(response, textStatus)
				{
					// Do we even care about this anymore?
					if (!$spinnerRow.parent().length)
					{
						return;
					}

					if (textStatus == 'success')
					{
						var $newElements = $(response.html);

						// Are there more descendants we didn't get in this batch?
						var totalVisible = (this._totalVisible + $newElements.length),
							morePending = (this.settings.batchSize && $newElements.length == this.settings.batchSize);

						if (morePending)
						{
							// Remove all the elements after it
							var $nextRows = $spinnerRow.nextAll();

							if (this.elementSelect)
							{
								this.elementSelect.removeItems($nextRows);
							}

							if (this.structureTableSort)
							{
								this.structureTableSort.removeItems($nextRows);
							}

							$nextRows.remove();
							totalVisible -= $nextRows.length;
						}
						else
						{
							// Maintain the current 'more' status
							morePending = this._morePending;
						}

						$spinnerRow.replaceWith($newElements);

						if (this.elementIndex.actions || this.settings.selectable)
						{
							this.elementSelect.addItems($newElements.filter(':not(.disabled)'));
							this.elementIndex.updateActionTriggers();
						}

						if (this.structureTableSort)
						{
							this.structureTableSort.addItems($newElements);
						}

						Craft.appendHeadHtml(response.headHtml);
						Craft.appendFootHtml(response.footHtml);
						Craft.cp.updateResponsiveTables();

						this.setTotalVisible(totalVisible);
						this.setMorePending(morePending);

						// Is there room to load more right now?
						this.maybeLoadMore();
					}

				}, this));
			}
		}
	},

	_createSpinnerRowAfter: function($row)
	{
		return $(
			'<tr data-spinnerrow>' +
				'<td class="centeralign" colspan="'+$row.children().length+'">' +
					'<div class="spinner"/>' +
				'</td>' +
			'</tr>'
		).insertAfter($row);
	},

	_isStructureTableDraggingLastElements: function()
	{
		return (
			this.structureTableSort &&
			this.structureTableSort.dragging &&
			this.structureTableSort.draggingLastElements
		);
	},

	_handleSelectedSortHeaderClick: function(ev)
	{
		var $header = $(ev.currentTarget);

		if ($header.hasClass('loading'))
		{
			return;
		}

		// Reverse the sort direction
		var selectedSortDir = this.elementIndex.getSelectedSortDirection(),
			newSortDir = (selectedSortDir == 'asc' ? 'desc' : 'asc');

		this.elementIndex.setSortDirection(newSortDir);
		this._handleSortHeaderClick(ev, $header);
	},

	_handleUnselectedSortHeaderClick: function(ev)
	{
		var $header = $(ev.currentTarget);

		if ($header.hasClass('loading'))
		{
			return;
		}

		var attr = $header.attr('data-attribute');

		this.elementIndex.setSortAttribute(attr);
		this._handleSortHeaderClick(ev, $header);
	},

	_handleSortHeaderClick: function(ev, $header)
	{
		if (this.$selectedSortHeader)
		{
			this.$selectedSortHeader.removeClass('ordered asc desc');
		}

		$header.removeClass('orderable').addClass('ordered loading');
		this.elementIndex.storeSortAttributeAndDirection();
		this.elementIndex.updateElements();

		// No need for two spinners
		this.elementIndex.setIndexAvailable();
	},

	_updateTableAttributes: function($element, tableAttributes)
	{
		var $tr = $element.closest('tr');

		for (var attr in tableAttributes)
		{
			$tr.children('td[data-attr="'+attr+'"]:first').html(tableAttributes[attr]);
		}
	}
});

/**
 * Tag select input
 */
Craft.TagSelectInput = Craft.BaseElementSelectInput.extend(
{
	searchTimeout: null,
	searchMenu: null,

	$container: null,
	$elementsContainer: null,
	$elements: null,
	$addTagInput: null,
	$spinner: null,

	_ignoreBlur: false,

	init: function(settings)
	{
		// Normalize the settings
		// ---------------------------------------------------------------------

		// Are they still passing in a bunch of arguments?
		if (!$.isPlainObject(settings))
		{
			// Loop through all of the old arguments and apply them to the settings
			var normalizedSettings = {},
				args = ['id', 'name', 'tagGroupId', 'sourceElementId'];

			for (var i = 0; i < args.length; i++)
			{
				if (typeof arguments[i] != typeof undefined)
				{
					normalizedSettings[args[i]] = arguments[i];
				}
				else
				{
					break;
				}
			}

			settings = normalizedSettings;
		}

		this.base($.extend({}, Craft.TagSelectInput.defaults, settings));

		this.$addTagInput = this.$container.children('.add').children('.text');
		this.$spinner = this.$addTagInput.next();

		this.addListener(this.$addTagInput, 'textchange', $.proxy(function()
		{
			if (this.searchTimeout)
			{
				clearTimeout(this.searchTimeout);
			}

			this.searchTimeout = setTimeout($.proxy(this, 'searchForTags'), 500);
		}, this));

		this.addListener(this.$addTagInput, 'keypress', function(ev)
		{
			if (ev.keyCode == Garnish.RETURN_KEY)
			{
				ev.preventDefault();

				if (this.searchMenu)
				{
					this.selectTag(this.searchMenu.$options[0]);
				}
			}
		});

		this.addListener(this.$addTagInput, 'focus', function()
		{
			if (this.searchMenu)
			{
				this.searchMenu.show();
			}
		});

		this.addListener(this.$addTagInput, 'blur', function()
		{
			if (this._ignoreBlur)
			{
				this._ignoreBlur = false;
				return;
			}

			setTimeout($.proxy(function()
			{
				if (this.searchMenu)
				{
					this.searchMenu.hide();
				}
			}, this), 1);
		});
	},

	// No "add" button
	getAddElementsBtn: $.noop,

	getElementSortAxis: function()
	{
		return null;
	},

	searchForTags: function()
	{
		if (this.searchMenu)
		{
			this.killSearchMenu();
		}

		var val = this.$addTagInput.val();

		if (val)
		{
			this.$spinner.removeClass('hidden');

			var excludeIds = [];

			for (var i = 0; i < this.$elements.length; i++)
			{
				var id = $(this.$elements[i]).data('id');

				if (id)
				{
					excludeIds.push(id);
				}
			}

			if (this.settings.sourceElementId)
			{
				excludeIds.push(this.settings.sourceElementId);
			}

			var data = {
				search:     this.$addTagInput.val(),
				tagGroupId: this.settings.tagGroupId,
				excludeIds: excludeIds
			};

			Craft.postActionRequest('tags/searchForTags', data, $.proxy(function(response, textStatus)
			{
				this.$spinner.addClass('hidden');

				if (textStatus == 'success')
				{
					var $menu = $('<div class="menu tagmenu"/>').appendTo(Garnish.$bod),
						$ul = $('<ul/>').appendTo($menu);

					for (var i = 0; i < response.tags.length; i++)
					{
						var $li = $('<li/>').appendTo($ul);
						$('<a data-icon="tag"/>').appendTo($li).text(response.tags[i].title).data('id', response.tags[i].id);
					}

					if (!response.exactMatch)
					{
						var $li = $('<li/>').appendTo($ul);
						$('<a data-icon="+"/>').appendTo($li).text(data.search);
					}

					$ul.find('> li:first-child > a').addClass('hover');

					this.searchMenu = new Garnish.Menu($menu, {
						attachToElement: this.$addTagInput,
						onOptionSelect: $.proxy(this, 'selectTag')
					});

					this.addListener($menu, 'mousedown', $.proxy(function()
					{
						this._ignoreBlur = true;
					}, this));

					this.searchMenu.show();
				}

			}, this));
		}
		else
		{
			this.$spinner.addClass('hidden');
		}
	},

	selectTag: function(option)
	{
		var $option = $(option),
			id = $option.data('id'),
			title = $option.text();

		var $element = $('<div class="element small removable" data-id="'+id+'" data-editable/>').appendTo(this.$elementsContainer),
			$input = $('<input type="hidden" name="'+this.settings.name+'[]" value="'+id+'"/>').appendTo($element);

		$('<a class="delete icon" title="'+Craft.t('Remove')+'"></a>').appendTo($element);
		$('<span class="label">'+title+'</span>').appendTo($element);

		var margin = -($element.outerWidth()+10);
		this.$addTagInput.css('margin-'+Craft.left, margin+'px');

		var animateCss = {};
		animateCss['margin-'+Craft.left] = 0;
		this.$addTagInput.velocity(animateCss, 'fast');

		this.$elements = this.$elements.add($element);

		this.addElements($element);

		this.killSearchMenu();
		this.$addTagInput.val('');
		this.$addTagInput.focus();

		if (!id)
		{
			// We need to create the tag first
			$element.addClass('loading disabled');

			var data = {
				groupId: this.settings.tagGroupId,
				title: title
			};

			Craft.postActionRequest('tags/createTag', data, $.proxy(function(response, textStatus)
			{
				if (textStatus == 'success' && response.success)
				{
					$element.attr('data-id', response.id);
					$input.val(response.id);

					$element.removeClass('loading disabled');
				}
				else
				{
					this.removeElement($element);

					if (textStatus == 'success')
					{
						// Some sort of validation error that still resulted in  a 200 response. Shouldn't be possible though.
						Craft.cp.displayError(Craft.t('An unknown error occurred.'));
					}
				}
			}, this));
		}
	},

	killSearchMenu: function()
	{
		this.searchMenu.hide();
		this.searchMenu.destroy();
		this.searchMenu = null;
	}
},
{
	defaults: {
		tagGroupId: null
	}
});

/**
 * Thumb Element Index View
 */
Craft.ThumbsElementIndexView = Craft.BaseElementIndexView.extend(
{
	getElementContainer: function()
	{
		return this.$container.children('ul');
	}
});

Craft.ui =
{
	createTextInput: function(config)
	{
		var $input = $('<input/>', {
			'class': 'text',
			type: (config.type || 'text'),
			id: config.id,
			size: config.size,
			name: config.name,
			value: config.value,
			maxlength: config.maxlength,
			'data-show-chars-left': config.showCharsLeft,
			autofocus: this.getAutofocusValue(config.autofocus),
			autocomplete: (typeof config.autocomplete === typeof undefined || !config.autocomplete ? 'off' : null),
			disabled: this.getDisabledValue(config.disabled),
			readonly: config.readonly,
			title: config.title,
			placeholder: config.placeholder
		});

		if (config.class) $input.addClass(config.class);
		if (config.placeholder) $input.addClass('nicetext');
		if (config.type == 'password') $input.addClass('password');
		if (config.disabled) $input.addClass('disabled');
		if (!config.size) $input.addClass('fullwidth');

		if (config.showCharsLeft && config.maxlength)
		{
			$input.css('padding-'+(Craft.orientation == 'ltr' ? 'right' : 'left'), (7.2*config.maxlength.toString().length+14)+'px');
		}

		if (config.placeholder || config.showCharsLeft)
		{
			new Garnish.NiceText($input);
		}

		if (config.type == 'password')
		{
			return $('<div class="passwordwrapper"/>').append($input);
		}
		else
		{
			return $input;
		}
	},

	createTextField: function(config)
	{
		return this.createField(this.createTextInput(config), config);
	},

	createCheckbox: function(config)
	{
		var id = (config.id || 'checkbox'+Math.floor(Math.random() * 1000000000));

		var $input = $('<input/>', {
			type: 'checkbox',
			value: (typeof config.value !== typeof undefined ? config.value : '1'),
			id: id,
			'class': 'checkbox',
			name: config.name,
			checked: (config.checked ? 'checked' : null),
			autofocus: this.getAutofocusValue(config.autofocus),
			disabled: this.getDisabledValue(config.disabled),
			'data-target': config.toggle,
			'data-reverse-target': config.reverseToggle
		});

		if (config.class) $input.addClass(config.class);

		if (config.toggle || config.reverseToggle)
		{
			$input.addClass('fieldtoggle');
			new Craft.FieldToggle($input);
		}

		var $label = $('<label/>', {
			'for': id,
			text: config.label
		});

		// Should we include a hidden input first?
		if (config.name && (config.name.length < 3 || config.name.substr(-2) != '[]'))
		{
			return $([
				$('<input/>', {
					type: 'hidden',
					name: config.name,
					value: ''
				})[0],
				$input[0],
				$label[0]
			]);
		}
		else
		{
			return $([
				$input[0],
				$label[0]
			]);
		}
	},

	createCheckboxField: function(config)
	{
		var $field = $('<div class="field checkboxfield"/>', {
			id: (cofig.id ? config.id+'-field' : null)
		});

		if (config.first) $field.addClass('first');
		if (config.instructions) $field.addClass('has-instructions');

		this.createCheckbox(config).appendTo($field);

		if (config.instructions)
		{
			$('<div class="instructions"/>').text(config.instructions).appendTo($field);
		}

		return $field;
	},

	createCheckboxSelect: function(config)
	{
		var allValue = (config.allValue || '*'),
			allChecked = (!config.values || config.values == config.allValue);

		var $container = $('<div class="checkbox-select"/>');
		if (config.class) $container.addClass(config.class);

		// Create the "All" checkbox
		$('<div/>').appendTo($container).append(
			this.createCheckbox({
				id:        config.id,
				'class':   'all',
				label:     '<b>'+(config.allLabel || Craft.t('All'))+'</b>',
				name:      config.name,
				value:     allValue,
				checked:   allChecked,
				autofocus: config.autofocus
			})
		);

		// Create the actual options
		for (var i = 0; i < config.options.length; i++)
		{
			var option = config.options[i];

			if (option.value == allValue)
			{
				continue;
			}

			$('<div/>').appendTo($container).append(
				this.createCheckbox({
					label:    option.label,
					name:     (config.name ? config.name+'[]' : null),
					value:    option.value,
					checked:  (allChecked || Craft.inArray(option.value, config.values)),
					disabled: allChecked
				})
			);
		}

		new Garnish.CheckboxSelect($container);

		return $container;
	},

	createCheckboxSelectField: function(config)
	{
		return this.createField(this.createCheckboxSelect(config), config);
	},

	createField: function(input, config)
	{
		var label = (config.label && config.label != '__blank__' ? config.label : null),
			locale = (Craft.isLocalized && config.locale ? config.locale : null);

		var $field = $('<div/>', {
			'class': 'field',
			'id': config.fieldId || (config.id ? config.id+'-field' : null)
		});

		if (config.first) $field.addClass('first');

		if (label || config.instructions)
		{
			var $heading = $('<div class="heading"/>').appendTo($field);

			if (label)
			{
				var $label = $('<label/>', {
					'id': config.labelId || (config.id ? config.id+'-label' : null),
					'class': (config.required ? 'required' : null),
					'for': config.id,
					text: label
				}).appendTo($heading);

				if (locale)
				{
					$('<span class="locale"/>').text(locale).appendTo($label);
				}
			}

			if (config.instructions)
			{
				$('<div class="instructions"/>').text(config.instructions).appendTo($heading);
			}
		}

		$('<div class="input"/>').append(input).appendTo($field);

		if (config.warning)
		{
			$('<p class="warning"/>').text(config.warning).appendTo($field);
		}

		if (config.errors)
		{
			this.addErrorsToField($field, config.errors);
		}

		return $field;
	},

	createErrorList: function(errors)
	{
		var $list = $('<ul class="errors"/>');

		if (errors)
		{
			this.addErrorsToList($list, errors);
		}

		return $list;
	},

	addErrorsToList: function($list, errors)
	{
		for (var i = 0; i < errors.length; i++)
		{
			$('<li/>').text(errors[i]).appendTo($list);
		}
	},

	addErrorsToField: function($field, errors)
	{
		if (!errors)
		{
			return;
		}

		$field.addClass('has-errors');
		$field.children('.input').addClass('errors');

		var $errors = $field.children('ul.errors');

		if (!$errors.length)
		{
			$errors = this.createErrorList().appendTo($field);
		}

		this.addErrorsToList($errors, errors);
	},

	clearErrorsFromField: function($field)
	{
		$field.removeClass('has-errors');
		$field.children('.input').removeClass('errors');
		$field.children('ul.errors').remove();
	},

	getAutofocusValue: function(autofocus)
	{
		return (autofocus && !Garnish.isMobileBrowser(true) ? 'autofocus' : null);
	},

	getDisabledValue: function(disabled)
	{
		return (disabled ? 'disabled' : null);
	}
};

/**
 * Craft Upgrade Modal
 */
Craft.UpgradeModal = Garnish.Modal.extend(
{
	$container: null,
	$body: null,
	$compareScreen: null,
	$checkoutScreen: null,
	$successScreen: null,

	$checkoutForm: null,
	$checkoutLogo: null,
	$checkoutSubmitBtn: null,
	$checkoutSpinner: null,
	$checkoutFormError: null,
	$checkoutSecure: null,
	clearCheckoutFormTimeout: null,
	$customerNameInput: null,
	$customerEmailInput: null,
	$ccField: null,
	$ccNumInput: null,
	$ccExpInput: null,
	$ccCvcInput: null,
	$businessFieldsToggle: null,
	$businessNameInput: null,
	$businessAddress1Input: null,
	$businessAddress2Input: null,
	$businessCityInput: null,
	$businessStateInput: null,
	$businessCountryInput: null,
	$businessZipInput: null,
	$businessTaxIdInput: null,
	$purchaseNotesInput: null,
	$couponInput: null,
	$couponSpinner: null,
	submittingPurchase: false,

	stripePublicKey: null,
	editions: null,
	countries: null,
	states: null,
	edition: null,
	initializedCheckoutForm: false,

	applyingCouponCode: false,
	applyNewCouponCodeAfterDoneLoading: false,
	couponPrice: null,
	formattedCouponPrice: null,

	init: function(settings)
	{
		this.$container = $('<div id="upgrademodal" class="modal loading"/>').appendTo(Garnish.$bod),

		this.base(this.$container, $.extend({
			resizable: true
		}, settings));

		Craft.postActionRequest('app/getUpgradeModal', $.proxy(function(response, textStatus)
		{
			this.$container.removeClass('loading');

			if (textStatus == 'success')
			{
				if (response.success)
				{
					this.stripePublicKey = response.stripePublicKey;
					this.editions = response.editions;
					this.countries = response.countries;
					this.states = response.states;

					this.$container.append(response.modalHtml);
					this.$container.append('<script type="text/javascript" src="'+Craft.getResourceUrl('lib/jquery.payment'+(Craft.useCompressedJs ? '.min' : '')+'.js')+'"></script>');

					this.$compareScreen     = this.$container.children('#upgrademodal-compare');
					this.$checkoutScreen    = this.$container.children('#upgrademodal-checkout');
					this.$successScreen     = this.$container.children('#upgrademodal-success');

					this.$checkoutLogo           = this.$checkoutScreen.find('.logo:first');
					this.$checkoutForm           = this.$checkoutScreen.find('form:first');
					this.$checkoutSubmitBtn      = this.$checkoutForm.find('#pay-button');
					this.$checkoutSpinner        = this.$checkoutForm.find('#pay-spinner');
					this.$customerNameInput      = this.$checkoutForm.find('#customer-name');
					this.$customerEmailInput     = this.$checkoutForm.find('#customer-email');
					this.$ccField                = this.$checkoutForm.find('#cc-inputs');
					this.$ccNumInput             = this.$ccField.find('#cc-num');
					this.$ccExpInput             = this.$ccField.find('#cc-exp');
					this.$ccCvcInput             = this.$ccField.find('#cc-cvc');
					this.$businessFieldsToggle   = this.$checkoutForm.find('.fieldtoggle');
					this.$businessNameInput      = this.$checkoutForm.find('#business-name');
					this.$businessAddress1Input  = this.$checkoutForm.find('#business-address1');
					this.$businessAddress2Input  = this.$checkoutForm.find('#business-address2');
					this.$businessCityInput      = this.$checkoutForm.find('#business-city');
					this.$businessStateInput     = this.$checkoutForm.find('#business-state');
					this.$businessCountryInput   = this.$checkoutForm.find('#business-country');
					this.$businessZipInput       = this.$checkoutForm.find('#business-zip');
					this.$businessTaxIdInput     = this.$checkoutForm.find('#business-taxid');
					this.$purchaseNotesInput     = this.$checkoutForm.find('#purchase-notes');
					this.$checkoutSecure         = this.$checkoutScreen.find('.secure:first');
					this.$couponInput            = this.$checkoutForm.find('#coupon-input');
					this.$couponSpinner          = this.$checkoutForm.find('#coupon-spinner');

					var $buyBtns = this.$compareScreen.find('.buybtn');
					this.addListener($buyBtns, 'click', 'onBuyBtnClick');

					var $testBtns = this.$compareScreen.find('.btn.test');
					this.addListener($testBtns, 'click', 'onTestBtnClick');

					var $cancelCheckoutBtn = this.$checkoutScreen.find('#upgrademodal-cancelcheckout');
					this.addListener($cancelCheckoutBtn, 'click', 'cancelCheckout');
				}
				else
				{
					var error;

					if (response.error)
					{
						error = response.error;
					}
					else
					{
						error = Craft.t('An unknown error occurred.');
					}

					this.$container.append('<div class="body">'+error+'</div>');
				}

				// Include Stripe.js
				$('<script type="text/javascript" src="https://js.stripe.com/v1/"></script>').appendTo(Garnish.$bod);
			}
		}, this));
	},

	initializeCheckoutForm: function()
	{
		this.$ccNumInput.payment('formatCardNumber');
		this.$ccExpInput.payment('formatCardExpiry');
		this.$ccCvcInput.payment('formatCardCVC');

		this.$businessFieldsToggle.fieldtoggle();

		this.$businessCountryInput.selectize({ valueField: 'iso', labelField: 'name', searchField: ['name', 'iso'], dropdownParent: 'body', inputClass: 'selectize-input text' });
		this.$businessCountryInput[0].selectize.addOption(this.countries);
		this.$businessCountryInput[0].selectize.refreshOptions(false);

		this.$businessStateInput.selectize({ valueField: 'abbr', labelField: 'name', searchField: ['name', 'abbr'], dropdownParent: 'body', inputClass: 'selectize-input text', create: true });
		this.$businessStateInput[0].selectize.addOption(this.states);
		this.$businessStateInput[0].selectize.refreshOptions(false);

		this.addListener(this.$couponInput, 'textchange', {delay: 500}, 'applyCoupon');
		this.addListener(this.$checkoutForm, 'submit', 'submitPurchase');
	},

	applyCoupon: function()
	{
		if (this.applyingCouponCode)
		{
			this.applyNewCouponCodeAfterDoneLoading = true;
			return;
		}

		var couponCode = this.$couponInput.val();

		if (couponCode)
		{
			var data = {
				edition: this.edition,
				couponCode: couponCode
			};

			this.applyingCouponCode = true;
			this.$couponSpinner.removeClass('hidden');

			Craft.postActionRequest('app/getCouponPrice', data, $.proxy(function(response, textStatus)
			{
				this.applyingCouponCode = false;

				// Are we just waiting to apply a new code?
				if (this.applyNewCouponCodeAfterDoneLoading)
				{
					this.applyNewCouponCodeAfterDoneLoading = false;
					this.applyCoupon();
				}
				else
				{
					this.$couponSpinner.addClass('hidden');

					if (textStatus == 'success' && response.success)
					{
						this.couponPrice = response.couponPrice;
						this.formattedCouponPrice = response.formattedCouponPrice;
						this.updateCheckoutUi();
					}
				}
			}, this));
		}
		else
		{
			// Clear out the coupon price
			this.couponPrice = null;
			this.updateCheckoutUi();
		}
	},

	onHide: function()
	{
		if (this.initializedCheckoutForm)
		{
			this.$businessCountryInput[0].selectize.blur();
			this.$businessStateInput[0].selectize.blur();
		}

		this.clearCheckoutFormInABit();
		this.base();
	},

	onBuyBtnClick: function(ev)
	{
		var $btn = $(ev.currentTarget);
		this.edition = $btn.data('edition');
		this.couponPrice = null;
		this.formattedCouponPrice = null;

		switch (this.edition)
		{
			case 1:
			{
				this.$checkoutLogo.attr('class', 'logo craftclient').text('Client');
				break;
			}
			case 2:
			{
				this.$checkoutLogo.attr('class', 'logo craftpro').text('Pro');
				break;
			}
		}

		this.updateCheckoutUi();

		if (this.clearCheckoutFormTimeout)
		{
			clearTimeout(this.clearCheckoutFormTimeout);
		}

		// Slide it in

		var width = this.getWidth();

		this.$compareScreen.velocity('stop').animateLeft(-width, 'fast', $.proxy(function()
		{
			this.$compareScreen.addClass('hidden');

			if (!this.initializedCheckoutForm)
			{
				this.initializeCheckoutForm();
				this.initializedCheckoutForm = true;
			}
		}, this));

		this.$checkoutScreen.velocity('stop').css(Craft.left, width).removeClass('hidden').animateLeft(0, 'fast');
	},

	updateCheckoutUi: function()
	{
		// Only show the CC fields if there is a price
		if (this.getPrice() == 0)
		{
			this.$ccField.hide();
		}
		else
		{
			this.$ccField.show();
		}

		// Update the Pay button
		this.$checkoutSubmitBtn.val(Craft.t('Pay {price}', {
			price: this.getFormattedPrice()
		}));
	},

	getPrice: function()
	{
		if (this.couponPrice !== null)
		{
			return this.couponPrice;
		}

		if (this.editions[this.edition].salePrice)
		{
			return this.editions[this.edition].salePrice;
		}

		return this.editions[this.edition].price;
	},

	getFormattedPrice: function()
	{
		if (this.couponPrice !== null)
		{
			return this.formattedCouponPrice;
		}

		if (this.editions[this.edition].salePrice)
		{
			return this.editions[this.edition].formattedSalePrice;
		}

		return this.editions[this.edition].formattedPrice;
	},

	onTestBtnClick: function(ev)
	{
		var data = {
			edition: $(ev.currentTarget).data('edition')
		};

		Craft.postActionRequest('app/testUpgrade', data, $.proxy(function(response, textStatus)
		{
			if (textStatus == 'success')
			{
				var width = this.getWidth();

				this.$compareScreen.velocity('stop').animateLeft(-width, 'fast', $.proxy(function()
				{
					this.$compareScreen.addClass('hidden');
				}, this));

				this.onUpgrade();
			}
		}, this));
	},

	cancelCheckout: function()
	{
		var width = this.getWidth();

		this.$compareScreen.velocity('stop').removeClass('hidden').animateLeft(0, 'fast');
		this.$checkoutScreen.velocity('stop').animateLeft(width, 'fast', $.proxy(function()
		{
			this.$checkoutScreen.addClass('hidden');
		}, this));

		this.clearCheckoutFormInABit();
	},

	getExpiryValues: function()
	{
		return this.$ccExpInput.payment('cardExpiryVal');
	},

	submitPurchase: function(ev)
	{
		ev.preventDefault();

		if (this.submittingPurchase)
		{
			return;
		}

		this.cleanupCheckoutForm();

		// Get the price
		var price = this.getPrice();

		// Get the CC data
		var expVal = this.getExpiryValues();
		var ccData = {
			name:      this.$customerNameInput.val(),
			number:    this.$ccNumInput.val(),
			exp_month: expVal.month,
			exp_year:  expVal.year,
			cvc:       this.$ccCvcInput.val()
		};

		// Validate it
		var validates = true;

		if (!ccData.name)
		{
			validates = false;
			this.$customerNameInput.addClass('error');
		}

		if (price != 0)
		{
			if (!Stripe.validateCardNumber(ccData.number))
			{
				validates = false;
				this.$ccNumInput.addClass('error');
			}

			if (!Stripe.validateExpiry(ccData.exp_month, ccData.exp_year))
			{
				validates = false;
				this.$ccExpInput.addClass('error');
			}

			if (!Stripe.validateCVC(ccData.cvc))
			{
				validates = false;
				this.$ccCvcInput.addClass('error');
			}
		}

		if (validates)
		{
			this.submittingPurchase = true;

			// Get a CC token from Stripe.js
			this.$checkoutSubmitBtn.addClass('active');
			this.$checkoutSpinner.removeClass('hidden');

			if (price != 0)
			{
				Stripe.setPublishableKey(this.stripePublicKey);
				Stripe.createToken(ccData, $.proxy(function(status, response)
				{
					if (!response.error)
					{
						this.sendPurchaseRequest(price, response.id);
					}
					else
					{
						this.onPurchaseResponse();
						this.showError(response.error.message);
						Garnish.shake(this.$checkoutForm, 'left');
					}
				}, this));
			}
			else
			{
				this.sendPurchaseRequest(0, null);
			}
		}
		else
		{
			Garnish.shake(this.$checkoutForm, 'left');
		}
	},

	sendPurchaseRequest: function(expectedPrice, ccTokenId)
	{
		// Pass the token along to Elliott to charge the card
		var expVal = expectedPrice != 0 ? this.getExpiryValues() : {month: null, year: null};

		var data = {
			ccTokenId:            ccTokenId,
			expMonth:             expVal.month,
			expYear:              expVal.year,
			edition:              this.edition,
			expectedPrice:        expectedPrice,
			name:                 this.$customerNameInput.val(),
			email:                this.$customerEmailInput.val(),
			businessName:         this.$businessNameInput.val(),
			businessAddress1:     this.$businessAddress1Input.val(),
			businessAddress2:     this.$businessAddress2Input.val(),
			businessCity:         this.$businessCityInput.val(),
			businessState:        this.$businessStateInput.val(),
			businessCountry:      this.$businessCountryInput.val(),
			businessZip:          this.$businessZipInput.val(),
			businessTaxId:        this.$businessTaxIdInput.val(),
			purchaseNotes:        this.$purchaseNotesInput.val(),
			couponCode:           this.$couponInput.val()
		};

		Craft.postActionRequest('app/purchaseUpgrade', data, $.proxy(this, 'onPurchaseUpgrade'));
	},

	onPurchaseResponse: function()
	{
		this.submittingPurchase = false;
		this.$checkoutSubmitBtn.removeClass('active');
		this.$checkoutSpinner.addClass('hidden');
	},

	onPurchaseUpgrade: function(response, textStatus)
	{
		this.onPurchaseResponse();

		if (textStatus == 'success')
		{
			if (response.success)
			{
				var width = this.getWidth();

				this.$checkoutScreen.velocity('stop').animateLeft(-width, 'fast', $.proxy(function()
				{
					this.$checkoutScreen.addClass('hidden');
				}, this));

				this.onUpgrade();
			}
			else
			{
				if (response.errors)
				{
					var errorText = '';

					for (var i in response.errors)
					{
						if (errorText)
						{
							errorText += '<br>';
						}

						errorText += response.errors[i];
					}

					this.showError(errorText);
				}
				else
				{
					var errorText = Craft.t('An unknown error occurred.');
				}

				Garnish.shake(this.$checkoutForm, 'left');
			}
		}
	},

	showError: function(error)
	{
		this.$checkoutFormError = $('<p class="error centeralign">'+error+'</p>').insertBefore(this.$checkoutSecure);
	},

	onUpgrade: function()
	{
		this.$successScreen.css(Craft.left, this.getWidth()).removeClass('hidden').animateLeft(0, 'fast');

		var $refreshBtn = this.$successScreen.find('.btn:first');
		this.addListener($refreshBtn, 'click', function()
		{
			location.reload();
		});

		this.trigger('upgrade');
	},

	cleanupCheckoutForm: function()
	{
		this.$checkoutForm.find('.error').removeClass('error');

		if (this.$checkoutFormError)
		{
			this.$checkoutFormError.remove();
			this.$checkoutFormError = null;
		}
	},

	clearCheckoutForm: function()
	{
		this.$customerNameInput.val('');
		this.$customerEmailInput.val('');
		this.$ccNumInput.val('');
		this.$ccExpInput.val('');
		this.$ccCvcInput.val('');
		this.$businessNameInput.val('');
		this.$businessAddress1Input.val('');
		this.$businessAddress2Input.val('');
		this.$businessCityInput.val('');
		this.$businessStateInput.val('');
		this.$businessCountryInput.val('');
		this.$businessZipInput.val('');
		this.$businessTaxIdInput.val('');
		this.$purchaseNotesInput.val('');
		this.$couponInput.val('');
	},

	clearCheckoutFormInABit: function()
	{
		// Clear the CC info after a period of inactivity
		this.clearCheckoutFormTimeout = setTimeout(
			$.proxy(this, 'clearCheckoutForm'),
			Craft.UpgradeModal.clearCheckoutFormTimeoutDuration
		);
	}
},
{
	clearCheckoutFormTimeoutDuration: 30000 // 1000 x 60 x 5
});

/**
 * File Manager.
 */
Craft.Uploader = Garnish.Base.extend(
{
	uploader: null,
	allowedKinds: null,
	$element: null,
	settings: null,
	_rejectedFiles: {},
	_extensionList: null,
	_totalFileCounter: 0,
	_validFileCounter: 0,

	init: function($element, settings)
	{
		this._rejectedFiles = {"size": [], "type": [], "limit": []};
		this.$element = $element;
		this.allowedKinds = null;
		this._extensionList = null;
		this._totalFileCounter = 0;
		this._validFileCounter = 0;

		settings = $.extend({}, Craft.Uploader.defaults, settings);

		var events = settings.events;
		delete settings.events;

		if (settings.allowedKinds && settings.allowedKinds.length)
		{
			if (typeof settings.allowedKinds == "string")
			{
				settings.allowedKinds = [settings.allowedKinds];
			}

			this.allowedKinds = settings.allowedKinds;
			delete settings.allowedKinds;
		}

		settings.autoUpload = false;

		this.uploader = this.$element.fileupload(settings);
		for (var event in events)
		{
			this.uploader.on(event, events[event]);
		}

		this.settings = settings;

		this.uploader.on('fileuploadadd', $.proxy(this, 'onFileAdd'));
	},

	/**
	 * Set uploader parameters.
	 */
	setParams: function(paramObject)
	{
		// If CSRF protection isn't enabled, these won't be defined.
		if (typeof Craft.csrfTokenName !== 'undefined' && typeof Craft.csrfTokenValue !== 'undefined')
		{
			// Add the CSRF token
			paramObject[Craft.csrfTokenName] = Craft.csrfTokenValue;
		}

		this.uploader.fileupload('option', {formData: paramObject});
	},

	/**
	 * Get the number of uploads in progress.
	 */
	getInProgress: function()
	{
		return this.uploader.fileupload('active');
	},

	/**
	 * Return true, if this is the last upload.
	 */
	isLastUpload: function()
	{
		// Processing the last file or not processing at all.
		return this.getInProgress() < 2;
	},

	/**
	 * Called on file add.
	 */
	onFileAdd: function(e, data)
	{
		e.stopPropagation();

		var validateExtension = false;

		if (this.allowedKinds)
		{
			if (!this._extensionList)
			{
				this._createExtensionList();
			}

			validateExtension = true;
		}

		// Make sure that file API is there before relying on it
		data.process().done($.proxy(function()
		{
			var file = data.files[0];
			var pass = true;
			if (validateExtension)
			{

				var matches = file.name.match(/\.([a-z0-4_]+)$/i);
				var fileExtension = matches[1];
				if ($.inArray(fileExtension.toLowerCase(), this._extensionList) == -1)
				{
					pass = false;
					this._rejectedFiles.type.push('“' + file.name + '”');
				}
			}

			if (file.size > this.settings.maxFileSize)
			{
				this._rejectedFiles.size.push('“' + file.name + '”');
				pass = false;
			}

			// If the validation has passed for this file up to now, check if we're not hitting any limits
			if (pass && typeof this.settings.canAddMoreFiles == "function" && !this.settings.canAddMoreFiles(this._validFileCounter))
			{
				this._rejectedFiles.limit.push('“' + file.name + '”');
				pass = false;
			}

			if (pass)
			{
				this._validFileCounter++;
				data.submit();
			}

			if (++this._totalFileCounter == data.originalFiles.length)
			{
				this._totalFileCounter = 0;
				this._validFileCounter = 0;
				this.processErrorMessages();
			}

		}, this));

		return true;
	},

	/**
	 * Process error messages.
	 */
	processErrorMessages: function()
	{
		var str;

		if (this._rejectedFiles.type.length)
		{
			if (this._rejectedFiles.type.length == 1)
			{
				str = "The file {files} could not be uploaded. The allowed file kinds are: {kinds}.";
			}
			else
			{
				str = "The files {files} could not be uploaded. The allowed file kinds are: {kinds}.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.type.join(", "), kinds: this.allowedKinds.join(", ")});
			this._rejectedFiles.type = [];
			alert(str);
		}

		if (this._rejectedFiles.size.length)
		{
			if (this._rejectedFiles.size.length == 1)
			{
				str = "The file {files} could not be uploaded, because it exceeds the maximum upload size of {size}.";
			}
			else
			{
				str = "The files {files} could not be uploaded, because they exceeded the maximum upload size of {size}.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.size.join(", "), size: this.humanFileSize(Craft.maxUploadSize)});
			this._rejectedFiles.size = [];
			alert(str);
		}

		if (this._rejectedFiles.limit.length)
		{
			if (this._rejectedFiles.limit.length == 1)
			{
				str = "The file {files} could not be uploaded, because the field limit has been reached.";
			}
			else
			{
				str = "The files {files} could not be uploaded, because the field limit has been reached.";
			}

			str = Craft.t(str, {files: this._rejectedFiles.limit.join(", ")});
			this._rejectedFiles.limit = [];
			alert(str);
		}
	},

	humanFileSize: function (bytes, si)
	{
		var threshold = 1024;

		if(bytes < threshold)
		{
			return bytes + ' B';
		}

		var units = ['kB','MB','GB','TB','PB','EB','ZB','YB'];

		var u = -1;

		do
		{
			bytes = bytes /threshold;
			++u;
		}
		while(bytes >= threshold);

		return bytes.toFixed(1)+' '+units[u];
	},

	_createExtensionList: function()
	{
		this._extensionList = [];

		for (var i = 0; i < this.allowedKinds.length; i++)
		{
			var allowedKind = this.allowedKinds[i];

			if (typeof Craft.fileKinds[allowedKind] !== typeof undefined)
			{
				for (var j = 0; j < Craft.fileKinds[allowedKind].extensions.length; j++)
				{
					var ext = Craft.fileKinds[allowedKind].extensions[j];
					this._extensionList.push(ext);
				}
			}
		}
	},

	destroy: function ()
	{
		this.$element.fileupload('destroy');
		this.base();
	}
},

// Static Properties
// =============================================================================

{
	defaults: {
		dropZone: null,
		pasteZone: null,
		fileInput: null,
		sequentialUploads: true,
		maxFileSize: Craft.maxUploadSize,
		allowedKinds: null,
		events: {},
		canAddMoreFiles: null
	}
});

})(jQuery);
