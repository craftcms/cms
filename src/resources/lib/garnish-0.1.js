/**
 * Garnish UI toolkit
 *
 * @copyright 2013 Pixel & Tonic, Inc.. All rights reserved.
 * @author    Brandon Kelly <brandon@pixelandtonic.com>
 * @version   0.1
 * @license   THIS IS NO F.O.S.S!
 */
(function($){

/*!
	Base.js, version 1.1a
	Copyright 2006-2010, Dean Edwards
	License: http://www.opensource.org/licenses/mit-license.php
*/

var Base = function() {
	// dummy
};

Base.extend = function(_instance, _static) { // subclass
	var extend = Base.prototype.extend;

	// build the prototype
	Base._prototyping = true;
	var proto = new this;
	extend.call(proto, _instance);
	proto.base = function() {
		// call this method from any other method to invoke that method's ancestor
	};
	delete Base._prototyping;

	// create the wrapper for the constructor function
	//var constructor = proto.constructor.valueOf(); //-dean
	var constructor = proto.constructor;
	var klass = proto.constructor = function() {
		if (!Base._prototyping) {
			if (this._constructing || this.constructor == klass) { // instantiation
				this._constructing = true;
				constructor.apply(this, arguments);
				delete this._constructing;
			} else if (arguments[0] != null) { // casting
				return (arguments[0].extend || extend).call(arguments[0], proto);
			}
		}
	};

	// build the class interface
	klass.ancestor = this;
	klass.extend = this.extend;
	klass.forEach = this.forEach;
	klass.implement = this.implement;
	klass.prototype = proto;
	klass.toString = this.toString;
	klass.valueOf = function(type) {
		//return (type == "object") ? klass : constructor; //-dean
		return (type == "object") ? klass : constructor.valueOf();
	};
	extend.call(klass, _static);
	// class initialisation
	if (typeof klass.init == "function") klass.init();
	return klass;
};

Base.prototype = {
	extend: function(source, value) {
		if (arguments.length > 1) { // extending with a name/value pair
			var ancestor = this[source];
			if (ancestor && (typeof value == "function") && // overriding a method?
				// the valueOf() comparison is to avoid circular references
				(!ancestor.valueOf || ancestor.valueOf() != value.valueOf()) &&
				/\bbase\b/.test(value)) {
				// get the underlying method
				var method = value.valueOf();
				// override
				value = function() {
					var previous = this.base || Base.prototype.base;
					this.base = ancestor;
					var returnValue = method.apply(this, arguments);
					this.base = previous;
					return returnValue;
				};
				// point to the underlying method
				value.valueOf = function(type) {
					return (type == "object") ? value : method;
				};
				value.toString = Base.toString;
			}
			this[source] = value;
		} else if (source) { // extending with an object literal
			var extend = Base.prototype.extend;
			// if this object has a customised extend method then use it
			if (!Base._prototyping && typeof this != "function") {
				extend = this.extend || extend;
			}
			var proto = {toSource: null};
			// do the "toString" and other methods manually
			var hidden = ["constructor", "toString", "valueOf"];
			// if we are prototyping then include the constructor
			var i = Base._prototyping ? 0 : 1;
			while (key = hidden[i++]) {
				if (source[key] != proto[key]) {
					extend.call(this, key, source[key]);
				}
			}
			// copy each of the source object's properties to this object
			for (var key in source) {
				if (!proto[key]) {
					var desc = Object.getOwnPropertyDescriptor(source, key);
					if (typeof desc.value != typeof undefined) {
						// set the value normally in case it's a function that needs to be overwritten
						extend.call(this, key, desc.value);
					} else {
						// set it while maintaining the original descriptor settings
						Object.defineProperty(this, key, desc);
					}
				}
			}
		}
		return this;
	}
};

// initialise
Base = Base.extend({
	constructor: function() {
		this.extend(arguments[0]);
	}
}, {
	ancestor: Object,
	version: "1.1",

	forEach: function(object, block, context) {
		for (var key in object) {
			if (this.prototype[key] === undefined) {
				block.call(context, object[key], key, object);
			}
		}
	},

	implement: function() {
		for (var i = 0; i < arguments.length; i++) {
			if (typeof arguments[i] == "function") {
				// if it's a function, call it
				arguments[i](this.prototype);
			} else {
				// add the interface using the extend method
				this.prototype.extend(arguments[i]);
			}
		}
		return this;
	},

	toString: function() {
		return String(this.valueOf());
	}
});


/*!
 * Garnish
 */

// Bail if Garnish is already defined
if (typeof Garnish != 'undefined')
{
	throw 'Garnish is already defined!';
}


Garnish = {

	// jQuery objects for common elements
	$win: $(window),
	$doc: $(document),
	$bod: $(document.body)

};

Garnish.rtl = Garnish.$bod.hasClass('rtl');
Garnish.ltr = !Garnish.rtl;

Garnish = $.extend(Garnish, {

	// Key code constants
	DELETE_KEY:  8,
	SHIFT_KEY:  16,
	CTRL_KEY:   17,
	ALT_KEY:    18,
	RETURN_KEY: 13,
	ESC_KEY:    27,
	SPACE_KEY:  32,
	LEFT_KEY:   37,
	UP_KEY:     38,
	RIGHT_KEY:  39,
	DOWN_KEY:   40,
	A_KEY:      65,
	S_KEY:      83,
	CMD_KEY:    91,

	// Mouse button constants
	PRIMARY_CLICK:   1,
	SECONDARY_CLICK: 3,

	// Axis constants
	X_AXIS: 'x',
	Y_AXIS: 'y',

	FX_DURATION: 100,

	// Node types
	TEXT_NODE: 3,

	/**
	 * Logs a message to the browser's console, if the browser has one.
	 *
	 * @param string msg
	 */
	log: function(msg)
	{
		if (typeof console != 'undefined' && typeof console.log == 'function')
		{
			console.log(msg);
		}
	},

	_isMobileBrowser: null,
	_isMobileOrTabletBrowser: null,

	/**
	 * Returns whether this is a mobile browser.
	 * Detection script courtesy of http://detectmobilebrowsers.com
	 *
	 * Last updated: 2013-02-04
	 *
	 * @param bool detectTablets
	 * @return bool
	 */
	isMobileBrowser: function(detectTablets)
	{
		var key = detectTablets ? '_isMobileOrTabletBrowser' : '_isMobileBrowser';

		if (Garnish[key] === null)
		{
			var a = navigator.userAgent || navigator.vendor || window.opera;
			Garnish[key] = ((new RegExp('(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino'+(detectTablets ? '|android|ipad|playbook|silk' : ''), 'i')).test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)));
		}

		return Garnish[key];
	},

	/**
	 * Returns whether a variable is an array.
	 *
	 * @param mixed val
	 * @return bool
	 */
	isArray: function(val)
	{
		return (val instanceof Array);
	},

	/**
	 * Returns whether a variable is a jQuery collection.
	 *
	 * @param mixed val
	 * @return bool
	 */
	isJquery: function(val)
	{
		return (val instanceof jQuery);
	},

	/**
	 * Returns whether a variable is a string.
	 *
	 * @param mixed val
	 * @return bool
	 */
	isString: function(val)
	{
		return (typeof val == 'string');
	},

	/**
	 * Returns whether an element has an attribute.
	 *
	 * @see http://stackoverflow.com/questions/1318076/jquery-hasattr-checking-to-see-if-there-is-an-attribute-on-an-element/1318091#1318091
	 */
	hasAttr: function(elem, attr)
	{
		var val = $(elem).attr(attr);
		return (typeof val != 'undefined' && val !== false);
	},

	/**
	 * Returns whether something is a text node.
	 *
	 * @param object elem
	 * @return bool
	 */
	isTextNode: function(elem)
	{
		return (elem.nodeType == Garnish.TEXT_NODE);
	},

	/**
	 * Returns the distance between two coordinates.
	 *
	 * @param int x1 The first coordinate's X position.
	 * @param int y1 The first coordinate's Y position.
	 * @param int x2 The second coordinate's X position.
	 * @param int y2 The second coordinate's Y position.
	 * @return float
	 */
	getDist: function(x1, y1, x2, y2)
	{
		return Math.sqrt(Math.pow(x1-x2, 2) + Math.pow(y1-y2, 2));
	},

	/**
	 * Returns whether an element is touching an x/y coordinate.
	 *
	 * @param int    x    The coordinate's X position.
	 * @param int    y    The coordinate's Y position.
	 * @param object elem Either an actual element or a jQuery collection.
	 * @return bool
	 */
	hitTest: function(x, y, elem)
	{
		Garnish.hitTest._$elem = $(elem);
		Garnish.hitTest._offset = Garnish.hitTest._$elem.offset();
		Garnish.hitTest._x1 = Garnish.hitTest._offset.left;
		Garnish.hitTest._y1 = Garnish.hitTest._offset.top;
		Garnish.hitTest._x2 = Garnish.hitTest._x1 + Garnish.hitTest._$elem.outerWidth();
		Garnish.hitTest._y2 = Garnish.hitTest._y1 + Garnish.hitTest._$elem.outerHeight();

		return (x >= Garnish.hitTest._x1 && x < Garnish.hitTest._x2 && y >= Garnish.hitTest._y1 && y < Garnish.hitTest._y2);
	},

	/**
	 * Returns whether the cursor is touching an element.
	 *
	 * @param object ev   The mouse event object containing pageX and pageY properties.
	 * @param object elem Either an actual element or a jQuery collection.
	 * @return bool
	 */
	isCursorOver: function(ev, elem)
	{
		return Garnish.hitTest(ev.pageX, ev.pageY, elem);
	},

	/**
	 * Copies text styles from one element to another.
	 *
	 * @param object source The source element. Can be either an actual element or a jQuery collection.
	 * @param object target The target element. Can be either an actual element or a jQuery collection.
	 */
	copyTextStyles: function(source, target)
	{
		var $source = $(source),
			$target = $(target);

		$target.css({
			fontFamily:    $source.css('fontFamily'),
			fontSize:      $source.css('fontSize'),
			fontWeight:    $source.css('fontWeight'),
			letterSpacing: $source.css('letterSpacing'),
			lineHeight:    $source.css('lineHeight'),
			textAlign:     $source.css('textAlign'),
			textIndent:    $source.css('textIndent'),
			whiteSpace:    $source.css('whiteSpace'),
			wordSpacing:   $source.css('wordSpacing'),
			wordWrap:      $source.css('wordWrap')
		});
	},

	/**
	 * Returns the body's real scrollTop, discarding any window banding in Safari.
	 *
	 * @return int
	 */
	getBodyScrollTop: function()
	{
		Garnish.getBodyScrollTop._scrollTop = document.body.scrollTop;

		if (Garnish.getBodyScrollTop._scrollTop < 0)
		{
			Garnish.getBodyScrollTop._scrollTop = 0;
		}
		else
		{
			Garnish.getBodyScrollTop._maxScrollTop = Garnish.$bod.outerHeight() - Garnish.$win.height();

			if (Garnish.getBodyScrollTop._scrollTop > Garnish.getBodyScrollTop._maxScrollTop)
			{
				Garnish.getBodyScrollTop._scrollTop = Garnish.getBodyScrollTop._maxScrollTop;
			}
		}

		return Garnish.getBodyScrollTop._scrollTop;
	},

	requestAnimationFrame: (
		function()
		{
			var raf = (
				window.requestAnimationFrame ||
				window.mozRequestAnimationFrame ||
				window.webkitRequestAnimationFrame ||
				function(fn){ return window.setTimeout(fn, 20); }
			);

			return function(fn){ return raf(fn); };
		}
	)(),

	cancelAnimationFrame: (
		function()
		{
			var cancel = (
				window.cancelAnimationFrame ||
				window.mozCancelAnimationFrame ||
				window.webkitCancelAnimationFrame ||
				window.clearTimeout
			);

			return function(id){ return cancel(id); };
		}
	)(),

	/**
	 * Scrolls a container element to an element within it.
	 *
	 * @param object container Either an actual element or a jQuery collection.
	 * @param object elem      Either an actual element or a jQuery collection.
	 */
	scrollContainerToElement: function(container, elem)
	{
		var $container = $(container),
			$elem = $(elem);

		var scrollTop = $container.scrollTop(),
				elemOffset = $elem.offset().top;

		if ($container[0] == window)
		{
			var elemScrollOffset = elemOffset - scrollTop;
		}
		else
		{
			var elemScrollOffset = elemOffset - $container.offset().top;
		}

		// Is the element above the fold?
		if (elemScrollOffset < 0)
		{
			$container.scrollTop(scrollTop + elemScrollOffset);
		}
		else
		{
			var elemHeight = $elem.outerHeight(),
				containerHeight = ($container[0] == window ? window.innerHeight : $container[0].clientHeight);

			// Is it below the fold?
			if (elemScrollOffset + elemHeight > containerHeight)
			{
				$container.scrollTop(scrollTop + (elemScrollOffset - (containerHeight - elemHeight)));
			}
		}
	},

	SHAKE_STEPS: 10,
	SHAKE_STEP_DURATION: 25,

	/**
	 * Shakes an element.
	 *
	 * @param mixed  elem Either an actual element or a jQuery collection.
	 * @param string prop The property that should be adjusted (default is 'margin-left').
	 */
	shake: function(elem, prop)
	{
		var $elem = $(elem);

		if (!prop)
		{
			prop = 'margin-left';
		}

		var startingPoint = parseInt($elem.css(prop));
		if (isNaN(startingPoint))
		{
			startingPoint = 0;
		}

		for (var i = 0; i <= Garnish.SHAKE_STEPS; i++)
		{
			(function(i)
			{
				setTimeout(function()
				{
					Garnish.shake._properties = {};
					Garnish.shake._properties[prop] = startingPoint + (i % 2 ? -1 : 1) * (10-i);
					$elem.velocity(Garnish.shake._properties, Garnish.SHAKE_STEP_DURATION);
				}, (Garnish.SHAKE_STEP_DURATION * i));
			})(i);
		}
	},

	/**
	 * Returns the first element in an array or jQuery collection.
	 *
	 * @param mixed elem
	 * @return mixed
	 */
	getElement: function(elem)
	{
		return $.makeArray(elem)[0];
	},

	/**
	 * Returns the beginning of an input's name= attribute value with any [bracktes] stripped out.
	 *
	 * @param object elem
	 * @return string|null
	 */
	getInputBasename: function(elem)
	{
		var name = $(elem).attr('name');

		if (name)
		{
			return name.replace(/\[.*/, '');
		}
		else
		{
			return null;
		}
	},

	/**
	 * Returns an input's value as it would be POSTed.
	 * So unchecked checkboxes and radio buttons return null,
	 * and multi-selects whose name don't end in "[]" only return the last selection
	 *
	 * @param jQuery $input
	 * @return mixed
	 */
	getInputPostVal: function($input)
	{
		var type = $input.attr('type'),
			val  = $input.val();

		// Is this an unchecked checkbox or radio button?
		if ((type == 'checkbox' || type == 'radio'))
		{
			if ($input.prop('checked'))
			{
				return val;
			}
			else
			{
				return null;
			}
		}

		// Flatten any array values whose input name doesn't end in "[]"
		//  - e.g. a multi-select
		else if (Garnish.isArray(val) && $input.attr('name').substr(-2) != '[]')
		{
			if (val.length)
			{
				return val[val.length-1];
			}
			else
			{
				return null;
			}
		}

		// Just return the value
		else
		{
			return val;
		}
	},

	/**
	 * Returns the inputs within a container
	 *
	 * @param mixed container The container element. Can be either an actual element or a jQuery collection.
	 * @return jQuery
	 */
	findInputs: function(container)
	{
		return $(container).find('input,text,textarea,select,button');
	},

	/**
	 * Returns the post data within a container.
	 *
	 * @param mixed container
	 * @return array
	 */
	getPostData: function(container)
	{
		var postData = {},
			arrayInputCounters = {},
			$inputs = Garnish.findInputs(container);

		for (var i = 0; i < $inputs.length; i++)
		{
			var $input = $($inputs[i]);

			if ($input.prop('disabled'))
			{
				continue;
			}

			var inputName = $input.attr('name');
			if (!inputName)
			{
				continue;
			}

			var inputVal = Garnish.getInputPostVal($input);
			if (inputVal === null)
			{
				continue;
			}

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

			if (!Garnish.isArray(inputVal))
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
	}
});


/**
 * Garnish base class
 */
Garnish.Base = Base.extend({

	settings: null,

	_eventHandlers: null,
	_namespace: null,
	_$listeners: null,

	constructor: function()
	{
		this._eventHandlers = [];
		this._namespace = '.Garnish'+Math.floor(Math.random()*1000000000);
		this._$listeners = $();
		this.init.apply(this, arguments);
	},

	init: $.noop,

	setSettings: function(settings, defaults)
	{
		var baseSettings = (typeof this.settings == 'undefined' ? {} : this.settings);
		this.settings = $.extend({}, baseSettings, defaults, settings);
	},

	on: function(events, data, handler)
	{
		if (typeof data == 'function')
		{
			handler = data;
			data = {};
		}

		var events = this._normalizeEvents(events);

		for (var i = 0; i < events.length; i++)
		{
			var ev = events[i];

			this._eventHandlers.push({
				type: ev[0],
				namepsace: ev[1],
				data: data,
				handler: handler
			});
		}
	},

	off: function(events)
	{
		var events = this._normalizeEvents(events);

		for (var i = 0; i < events; i++)
		{
			var ev = events[i];

			for (var j = this._eventHandlers.length - 1; j >= 0; i--)
			{
				var handler = this._eventHandlers[j];

				if (handler.type == ev[0] && (!ev[1] || handler.namespace == ev[1]))
				{
					this._eventHandlers.splice(j, 1);
				}
			}
		}
	},

	trigger: function(type, data)
	{
		var ev = {
			type: type,
			target: this
		};

		if (typeof params == 'undefined')
		{
			params = [];
		}

		for (var i = 0; i < this._eventHandlers.length; i++)
		{
			var handler = this._eventHandlers[i];

			if (handler.type == type)
			{
				var _ev = $.extend({ data: handler.data }, data, ev);
				handler.handler(_ev)
			}
		}
	},

	_normalizeEvents: function(events)
	{
		if (typeof events == 'string')
		{
			events = events.split(' ');
		}

		for (var i = 0; i < events.length; i++)
		{
			if (typeof events[i] == 'string')
			{
				events[i] = events[i].split('.');
			}
		}

		return events;
	},

	_formatEvents: function(events)
	{
		if (typeof events == 'string')
		{
			events = events.split(',');

			for (var i = 0; i < events.length; i++)
			{
				events[i] = $.trim(events[i]);
			}
		}

		for (var i = 0; i < events.length; i++)
		{
			events[i] += this._namespace;
		}

		return events.join(' ');
	},

	addListener: function(elem, events, data, func)
	{
		var $elem = $(elem);

		// Ignore if there aren't any elements
		if (!$elem.length)
		{
			return;
		}

		events = this._formatEvents(events);

		// Param mapping
		if (typeof data != 'object')
		{
			// (elem, events, func)
			func = data;
			data = {};
		}

		if (typeof func == 'function')
		{
			func = $.proxy(func, this);
		}
		else
		{
			func = $.proxy(this, func);
		}

		$elem.on(events, data, func);

		// Remember that we're listening to this element
		this._$listeners = this._$listeners.add(elem);

		// Prep for activate event?
		if (events.search(/\bactivate\b/) != -1 && !$elem.data('garnish-activatable'))
		{
			var activateNamespace = this._namespace+'-activate';

			// Prevent buttons from getting focus on click
			$elem.on('mousedown'+activateNamespace, function(ev)
			{
				ev.preventDefault();
			});

			$elem.on('click'+activateNamespace, function(ev)
			{
				ev.preventDefault();

				var elemIndex = $.inArray(ev.currentTarget, $elem),
					$evElem = $(elem[elemIndex]);

				if (!$evElem.hasClass('disabled'))
				{
					$evElem.trigger('activate');
				}
			});

			$elem.on('keydown'+activateNamespace, function(ev)
			{
				var elemIndex = $.inArray(ev.currentTarget, $elem);
				if (elemIndex != -1 && ev.keyCode == Garnish.SPACE_KEY)
				{
					ev.preventDefault();
					var $evElem = $($elem[elemIndex]);

					if (!$evElem.hasClass('disabled'))
					{
						$evElem.addClass('active');

						Garnish.$doc.on('keyup'+activateNamespace, function(ev)
						{
							$elem.removeClass('active');
							if (ev.keyCode == Garnish.SPACE_KEY)
							{
								ev.preventDefault();
								$evElem.trigger('activate');
							}
							Garnish.$doc.off('keyup'+activateNamespace);
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

			$elem.data('garnish-activatable', true);
		}

		// Prep for chanegtext event?
		if (events.search(/\btextchange\b/) != -1)
		{
			// Store the initial values
			for (var i = 0; i < $elem.length; i++)
			{
				var _$elem = $($elem[i]);
				_$elem.data('garnish-textchangeValue', _$elem.val());

				if (!_$elem.data('garnish-textchangeable'))
				{
					var textchangeNamespace = this._namespace+'-textchange',
						events = 'keypress'+textchangeNamespace +
							' keyup'+textchangeNamespace +
							' change'+textchangeNamespace +
							' blur'+textchangeNamespace;

					_$elem.on(events, function(ev)
					{
						var _$elem = $(ev.currentTarget),
							val = _$elem.val();

						if (val != _$elem.data('garnish-textchangeValue'))
						{
							_$elem.data('garnish-textchangeValue', val);
							_$elem.trigger('textchange');
						}
					});

					_$elem.data('garnish-textchangeable', true);
				}
			}
		}

		// Prep for resize event?
		if (events.search(/\bresize\b/) != -1)
		{
			// Resize detection technique adapted from http://www.backalleycoder.com/2013/03/18/cross-browser-event-based-element-resize-detection/ -- thanks!
			for (var i = 0; i < $elem.length; i++)
			{
				(function(elem)
				{
					// window is the only element that natively supports a resize event
					if (elem == window)
					{
						return;
					}

					// IE < 11 had a proprietary 'resize' event and 'attachEvent' method.
					// Conveniently both dropped in 11.
					if (document.attachEvent)
					{
						return;
					}

					// Is this the first resize listener added to this element?
					if (!elem.__resizeTrigger__)
					{
						// The element must be relative, absolute, or fixed
						if (getComputedStyle(elem).position == 'static')
						{
							elem.style.position = 'relative';
						}

						var obj = elem.__resizeTrigger__ = document.createElement('object');
						obj.className = 'resize-trigger';
						obj.setAttribute('style', 'display: block; position: absolute; top: 0; left: 0; height: 100%; width: 100%; overflow: hidden; pointer-events: none; z-index: -1;');
						obj.__resizeElement__ = $(elem);
						obj.__resizeElement__.data('initialWidth', obj.__resizeElement__.prop('offsetWidth'));
						obj.__resizeElement__.data('initialHeight', obj.__resizeElement__.prop('offsetHeight'));
						obj.onload = objectLoad;
						obj.type = 'text/html';
						obj.__resizeElement__.prepend(obj);
						obj.data = 'about:blank';

						// Listen for window resizes too
						Garnish.$win.on('resize', function()
						{
							// Has the object been loaded yet?
							if (obj.contentDocument)
							{
								$(obj.contentDocument.defaultView).trigger('resize');
							}
						});

						// Avoid a top margin on the next element
						$(obj).next().addClass('first');
					}
				})($elem[i]);
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

// Resize event helper functions
// =============================================================================

function resizeListener(ev)
{
	var win = ev.currentTarget;

	// Ignore if there's no resize trigger yet
	if (typeof win.__resizeTrigger__ == typeof undefined)
	{
		return;
	}

	if (win.__resizeRAF__)
	{
		Garnish.cancelAnimationFrame(win.__resizeRAF__);
	}

	win.__resizeRAF__ = Garnish.requestAnimationFrame(function()
	{
		// Ignore if the size hasn't changed
		if (
			typeof win.__lastOffsetWidth__ != typeof undefined &&
			win.__resizeTrigger__.prop('offsetWidth') == win.__lastOffsetWidth__ &&
			win.__resizeTrigger__.prop('offsetHeight') == win.__lastOffsetHeight__
		)
		{
			return;
		}

		win.__lastOffsetWidth__ = win.__resizeTrigger__.prop('offsetWidth');
		win.__lastOffsetHeight__ = win.__resizeTrigger__.prop('offsetHeight');

		win.__resizeTrigger__.trigger('resize');

	});
}

function objectLoad(e)
{
	this.contentDocument.defaultView.__resizeTrigger__ = this.__resizeElement__;
	this.contentDocument.defaultView.__lastOffsetWidth__ = this.__resizeElement__.data('initialWidth');
	this.contentDocument.defaultView.__lastOffsetHeight__ = this.__resizeElement__.data('initialHeight');
	this.__resizeElement__.removeData('initialWidth');
	this.__resizeElement__.removeData('initialHeight');
	$(this.contentDocument.defaultView).on('resize', resizeListener).trigger('resize');
}


/**
 * Base drag class
 *
 * Does all the grunt work for manipulating elements via click-and-drag,
 * while leaving the actual element manipulation up to a subclass.
 */
Garnish.BaseDrag = Garnish.Base.extend({

	// Properties
	// =========================================================================

	$items: null,

	dragging: false,

	mousedownX: null,
	mousedownY: null,
	realMouseX: null,
	realMouseY: null,
	mouseX: null,
	mouseY: null,
	mouseDistX: null,
	mouseDistY: null,
	mouseOffsetX: null,
	mouseOffsetY: null,

	$targetItem: null,

	scrollProperty: null,
	scrollAxis: null,
	scrollDist: null,
	scrollProxy: null,
	scrollFrame: null,

	_: null,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param mixed  items    Elements that should be draggable right away. (Can be skipped.)
	 * @param object settings Any settings that should override the defaults.
	 */
	init: function(items, settings)
	{
		// Param mapping
		if (!settings && $.isPlainObject(items))
		{
			// (settings)
			settings = items;
			items = null;
		}

		this.settings = $.extend({}, Garnish.BaseDrag.defaults, settings);

		this.$items = $();
		this._ = {};

		if (items)
		{
			this.addItems(items);
		}
	},

	/**
	 * Returns whether dragging is allowed right now.
	 */
	allowDragging: function()
	{
		return true;
	},

	/**
	 * Start Dragging
	 */
	startDragging: function()
	{
		this.dragging = true;
		this.onDragStart();
	},

	/**
	 * Drag
	 */
	drag: function(didMouseMove)
	{
		if (didMouseMove)
		{
			// Is the mouse up against one of the window edges?
			this.drag._scrollProperty = null;

			if (this.settings.axis != Garnish.X_AXIS)
			{
				// Scrolling up?
				this.drag._winScrollTop = Garnish.$win.scrollTop();
				this.drag._minMouseScrollY = this.drag._winScrollTop + Garnish.BaseDrag.windowScrollTargetSize;

				if (this.mouseY < this.drag._minMouseScrollY)
				{
					this.drag._scrollProperty = 'scrollTop';
					this.drag._scrollAxis = 'Y';
					this.drag._scrollDist = Math.round((this.mouseY - this.drag._minMouseScrollY) / 2);
				}
				else
				{
					// Scrolling down?
					this.drag._maxMouseScrollY = this.drag._winScrollTop + Garnish.$win.height() - Garnish.BaseDrag.windowScrollTargetSize;

					if (this.mouseY > this.drag._maxMouseScrollY)
					{
						this.drag._scrollProperty = 'scrollTop';
						this.drag._scrollAxis = 'Y';
						this.drag._scrollDist = Math.round((this.mouseY - this.drag._maxMouseScrollY) / 2);
					}
				}
			}

			if (!this.drag._scrollProperty && this.settings.axis != Garnish.Y_AXIS)
			{
				// Scrolling left?
				this.drag._winScrollLeft = Garnish.$win.scrollLeft();
				this.drag._minMouseScrollX = this.drag._winScrollLeft + Garnish.BaseDrag.windowScrollTargetSize;

				if (this.mouseX < this.drag._minMouseScrollX)
				{
					this.drag._scrollProperty = 'scrollLeft';
					this.drag._scrollAxis = 'X';
					this.drag._scrollDist = Math.round((this.mouseX - this.drag._minMouseScrollX) / 2);
				}
				else
				{
					// Scrolling right?
					this.drag._maxMouseScrollX = this.drag._winScrollLeft + Garnish.$win.width() - Garnish.BaseDrag.windowScrollTargetSize;

					if (this.mouseX > this.drag._maxMouseScrollX)
					{
						this.drag._scrollProperty = 'scrollLeft';
						this.drag._scrollAxis = 'X';
						this.drag._scrollDist = Math.round((this.mouseX - this.drag._maxMouseScrollX) / 2);
					}
				}
			}

			if (this.drag._scrollProperty)
			{
				// Are we starting to scroll now?
				if (!this.scrollProperty)
				{
					if (!this.scrollProxy)
					{
						this.scrollProxy = $.proxy(this, '_scrollWindow');
					}

					if (this.scrollFrame)
					{
						Garnish.cancelAnimationFrame(this.scrollFrame);
						this.scrollFrame = null;
					}

					this.scrollFrame = Garnish.requestAnimationFrame(this.scrollProxy);
				}

				this.scrollProperty = this.drag._scrollProperty;
				this.scrollAxis = this.drag._scrollAxis;
				this.scrollDist = this.drag._scrollDist;
			}
			else
			{
				this._cancelWindowScroll();
			}
		}

		this.onDrag();
	},

	/**
	 * Stop Dragging
	 */
	stopDragging: function()
	{
		this.dragging = false;
		this.onDragStop();

		// Clear the scroll animation
		this._cancelWindowScroll();
	},

	/**
	 * Add Items
	 *
	 * @param mixed items Elements that should be draggable.
	 */
	addItems: function(items)
	{
		items = $.makeArray(items);

		for (var i = 0; i < items.length; i++)
		{
			var item = items[i];

			// Make sure this element doesn't belong to another dragger
			if ($.data(item, 'drag'))
			{
				Garnish.log('Element was added to more than one dragger');
				$.data(item, 'drag').removeItems(item);
			}

			// Add the item
			$.data(item, 'drag', this);

			// Get the handle
			if (this.settings.handle)
			{
				if (typeof this.settings.handle == 'object')
				{
					var $handle = $(this.settings.handle);
				}
				else if (typeof this.settings.handle == 'string')
				{
					var $handle = $(item).find(this.settings.handle);
				}
				else if (typeof this.settings.handle == 'function')
				{
					var $handle = $(this.settings.handle(item));
				}
			}
			else
			{
				var $handle = $(item);
			}

			if ($handle.length)
			{
				$.data(item, 'drag-handle', $handle[0]);
				$handle.data('drag-item', item);
				this.addListener($handle, 'mousedown', '_onMouseDown');
			}
		}

		this.$items = $().add(this.$items.add(items));
	},

	/**
	 * Remove Items
	 *
	 * @param mixed items Elements that should no longer be draggable.
	 */
	removeItems: function(items)
	{
		items = $.makeArray(items);

		for (var i = 0; i < items.length; i++)
		{
			var item = items[i];

			// Make sure we actually know about this item
			var index = $.inArray(item, this.$items);
			if (index != -1)
			{
				this._deinitItem(item);
				this.$items.splice(index, 1);
			}
		}
	},

	/**
	 * Remove All Items
	 */
	removeAllItems: function()
	{
		for (var i = 0; i < this.$items.length; i++)
		{
			this._deinitItem(this.$items[i]);
		}

		this.$items = $();
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.removeAllItems();
		this.base();
	},

	// Events
	// -------------------------------------------------------------------------

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('dragStart');
			this.settings.onDragStart();
		}, this));
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('drag');
			this.settings.onDrag();
		}, this));
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('dragStop');
			this.settings.onDragStop();
		}, this));
	},

	// Private methods
	// =========================================================================

	/**
	 * On Mouse Down
	 */
	_onMouseDown: function(ev)
	{
		// Ignore right clicks
		if (ev.which != Garnish.PRIMARY_CLICK)
		{
			return;
		}

		// Ignore if we already have a target
		if (this.$targetItem)
		{
			return;
		}

		// Make sure the target isn't a button (unless the button is the handle)
		if (ev.currentTarget != ev.target && this.settings.ignoreHandleSelector)
		{
			var $target = $(ev.target);

			if (
				$target.is(this.settings.ignoreHandleSelector) ||
				$target.closest(this.settings.ignoreHandleSelector).length
			)
			{
				return;
			}
		}

		ev.preventDefault();

		// Make sure that dragging is allowed right now
		if (!this.allowDragging())
		{
			return;
		}

		// Capture the target
		this.$targetItem = $($.data(ev.currentTarget, 'drag-item'));

		// Capture the current mouse position
		this.mousedownX = this.mouseX = ev.pageX;
		this.mousedownY = this.mouseY = ev.pageY;

		// Capture the difference between the mouse position and the target item's offset
		var offset = this.$targetItem.offset();
		this.mouseOffsetX = ev.pageX - offset.left;
		this.mouseOffsetY = ev.pageY - offset.top;

		// Listen for mousemove, mouseup
		this.addListener(Garnish.$doc, 'mousemove', '_onMouseMove');
		this.addListener(Garnish.$doc, 'mouseup', '_onMouseUp');
	},

	/**
	 * On Mouse Move
	 */
	_onMouseMove: function(ev)
	{
		ev.preventDefault();

		this.realMouseX = ev.pageX;
		this.realMouseY = ev.pageY;

		if (this.settings.axis != Garnish.Y_AXIS)
		{
			this.mouseX = ev.pageX;
		}

		if (this.settings.axis != Garnish.X_AXIS)
		{
			this.mouseY = ev.pageY;
		}

		this.mouseDistX = this.mouseX - this.mousedownX;
		this.mouseDistY = this.mouseY - this.mousedownY;

		if (!this.dragging)
		{
			// Has the mouse moved far enough to initiate dragging yet?
			this._onMouseMove._mouseDist = Garnish.getDist(this.mousedownX, this.mousedownY, this.realMouseX, this.realMouseY);

			if (this._onMouseMove._mouseDist >= Garnish.BaseDrag.minMouseDist)
			{
				this.startDragging();
			}
		}

		if (this.dragging)
		{
			this.drag(true);
		}
	},

	/**
	 * On Moues Up
	 */
	_onMouseUp: function(ev)
	{
		// Unbind the document events
		this.removeAllListeners(Garnish.$doc);

		if (this.dragging)
		{
			this.stopDragging();
		}

		this.$targetItem = null;
	},

	/**
	 * Scroll Window
	 */
	_scrollWindow: function()
	{
		this._.scrollPos = Garnish.$win[this.scrollProperty]();
		Garnish.$win[this.scrollProperty](this._.scrollPos + this.scrollDist);

		this['mouse'+this.scrollAxis] -= this._.scrollPos - Garnish.$win[this.scrollProperty]();
		this['realMouse'+this.scrollAxis] = this['mouse'+this.scrollAxis];

		this.drag();

		this.scrollFrame = Garnish.requestAnimationFrame(this.scrollProxy);
	},

	/**
	 * Cancel Window Scroll
	 */
	_cancelWindowScroll: function()
	{
		if (this.scrollFrame)
		{
			Garnish.cancelAnimationFrame(this.scrollFrame);
			this.scrollFrame = null;
		}

		this.scrollProperty = null;
		this.scrollAxis = null;
		this.scrollDist = null;
	},

	/**
	 * Deinitialize an item.
	 */
	_deinitItem: function(item)
	{
		var handle = $.data(item, 'drag-handle');

		if (handle)
		{
			$.removeData(handle, 'drag-item');
			this.removeAllListeners(handle);
		}

		$.removeData(item, 'drag');
		$.removeData(item, 'drag-handle');
	}
},

// Static Properties
// =============================================================================

{
	minMouseDist: 1,
	windowScrollTargetSize: 25,

	defaults: {
		handle: null,
		axis: null,
		ignoreHandleSelector: 'input, textarea, button, select, .btn',

		onDragStart: $.noop,
		onDrag:      $.noop,
		onDragStop:  $.noop
	}
});



/**
 * Checkbox select class
 */
Garnish.CheckboxSelect = Garnish.Base.extend({

	$container: null,
	$all: null,
	$options: null,

	init: function(container)
	{
		this.$container = $(container);

		// Is this already a checkbox select?
		if (this.$container.data('checkboxSelect'))
		{
			Garnish.log('Double-instantiating a checkbox select on an element');
			this.$container.data('checkbox-select').destroy();
		}

		this.$container.data('checkboxSelect', this);

		var $checkboxes = this.$container.find('input');
		this.$all = $checkboxes.filter('.all:first');
		this.$options = $checkboxes.not(this.$all);

		this.addListener(this.$all, 'change', 'onAllChange');
	},

	onAllChange: function()
	{
		var isAllChecked = this.$all.prop('checked');

		this.$options.prop({
			checked:  isAllChecked,
			disabled: isAllChecked
		});
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$container.removeData('checkboxSelect');
		this.base();
	}
});


/**
 * Context Menu
 */
Garnish.ContextMenu = Garnish.Base.extend({

	$target: null,
	options: null,
	$menu: null,
	showingMenu: false,

	/**
	 * Constructor
	 */
	init: function(target, options, settings)
	{
		this.$target = $(target);

		// Is this already a context menu target?
		if (this.$target.data('contextmenu'))
		{
			Garnish.log('Double-instantiating a context menu on an element');
			this.$target.data('contextmenu').destroy();
		}

		this.$target.data('contextmenu', this);

		this.options = options;
		this.setSettings(settings, Garnish.ContextMenu.defaults);

		Garnish.ContextMenu.counter++;

		this.enable();
	},

	/**
	 * Build Menu
	 */
	buildMenu: function()
	{
		this.$menu = $('<div class="'+this.settings.menuClass+'" style="display: none" />');

		var $ul = $('<ul/>').appendTo(this.$menu);

		for (var i in this.options)
		{
			var option = this.options[i];

			if (option == '-')
			{
				// Create a new <ul>
				$('<hr/>').appendTo(this.$menu);
				$ul = $('<ul/>').appendTo(this.$menu);
			}
			else
			{
				var $li = $('<li></li>').appendTo($ul),
					$a = $('<a>'+option.label+'</a>').appendTo($li);

				if (typeof option.onClick == 'function')
				{
					// maintain the current $a and options.onClick variables
					(function($a, onClick)
					{
						setTimeout($.proxy(function(){
							$a.mousedown($.proxy(function(ev)
							{
								this.hideMenu();
								// call the onClick callback, with the scope set to the item,
								// and pass it the event with currentTarget set to the item as well
								onClick.call(this.currentTarget, $.extend(ev, { currentTarget: this.currentTarget }));
							}, this));
						}, this), 1);
					}).call(this, $a, option.onClick);
				}
			}
		}
	},

	/**
	 * Show Menu
	 */
	showMenu: function(ev)
	{
		// Ignore left mouse clicks
		if (ev.type == 'mousedown' && ev.which != Garnish.SECONDARY_CLICK)
		{
			return;
		}

		if (ev.type == 'contextmenu')
		{
			// Prevent the real context menu from showing
			ev.preventDefault();
		}

		// Ignore if already showing
		if (this.showing && ev.currentTarget == this.currentTarget)
		{
			return;
		}

		this.currentTarget = ev.currentTarget;

		if (! this.$menu)
		{
			this.buildMenu();
		}

		this.$menu.appendTo(document.body);
		this.$menu.show();
		this.$menu.css({ left: ev.pageX+1, top: ev.pageY-4 });

		this.showing = true;

		setTimeout($.proxy(function()
		{
			this.addListener(Garnish.$doc, 'mousedown', 'hideMenu');
		}, this), 0);
	},

	/**
	 * Hide Menu
	 */
	hideMenu: function()
	{
		this.removeListener(Garnish.$doc, 'mousedown');
		this.$menu.hide();
		this.showing = false;
	},

	/**
	 * Enable
	 */
	enable: function()
	{
		this.addListener(this.$target, 'contextmenu,mousedown', 'showMenu');
	},

	/**
	 * Disable
	 */
	disable: function()
	{
		this.removeListener(this.$target, 'contextmenu,mousedown');
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$target.removeData('contextmenu');
		this.base();
	}
},
{
	defaults: {
		menuClass: 'menu'
	},
	counter: 0
});


/**
 * Drag class
 *
 * Builds on the BaseDrag class by "picking up" the selceted element(s),
 * without worrying about what to do when an element is being dragged.
 */
Garnish.Drag = Garnish.BaseDrag.extend({

	// Properties
	// =========================================================================

	targetItemWidth: null,
	targetItemHeight: null,

	$draggee: null,

	otherItems: null,
	totalOtherItems: null,

	helpers: null,
	helperTargets: null,
	helperPositions: null,
	helperLagIncrement: null,
	updateHelperPosProxy: null,
	updateHelperPosFrame: null,

	lastMouseX: null,
	lastMouseY: null,

	_returningHelpersToDraggees: false,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param mixed  items    Elements that should be draggable right away. (Can be skipped.)
	 * @param object settings Any settings that should override the defaults.
	 */
	init: function(items, settings)
	{
		// Param mapping
		if (!settings && $.isPlainObject(items))
		{
			// (settings)
			settings = items;
			items = null;
		}

		settings = $.extend({}, Garnish.Drag.defaults, settings);
		this.base(items, settings);
	},

	/**
	 * Returns whether dragging is allowed right now.
	 */
	allowDragging: function()
	{
		// Don't allow dragging if we're in the middle of animating the helpers back to the draggees
		return !this._returningHelpersToDraggees;
	},

	/**
	 * Start Dragging
	 */
	startDragging: function()
	{
		// Reset some things
		this.helpers = [];
		this.helperTargets = [];
		this.helperPositions = [];
		this.lastMouseX = this.lastMouseY = null;

		// Capture the target item's width/height
		this.targetItemWidth  = this.$targetItem.outerWidth();
		this.targetItemHeight = this.$targetItem.outerHeight();

		// Save the draggee's display style (block/table-row) so we can re-apply it later
		this.draggeeDisplay = this.$targetItem.css('display');

		// Set the $draggee
		this.setDraggee(this.findDraggee());

		// Create an array of all the other items
		this.otherItems = [];

		for (var i = 0; i < this.$items.length; i++)
		{
			var item = this.$items[i];

			if ($.inArray(item, this.$draggee) == -1)
			{
				this.otherItems.push(item);
			}
		};

		this.totalOtherItems = this.otherItems.length;

		// Keep the helpers following the cursor, with a little lag to smooth it out
		if (!this.updateHelperPosProxy)
		{
			this.updateHelperPosProxy = $.proxy(this, '_updateHelperPos');
		}

		this.helperLagIncrement = this.helpers.length == 1 ? 0 : this.settings.helperLagIncrementDividend / (this.helpers.length-1);
		this.updateHelperPosFrame = Garnish.requestAnimationFrame(this.updateHelperPosProxy);

		this.base();
	},

	/**
	 * Sets the draggee.
	 */
	setDraggee: function($draggee)
	{
		// Keep the target item at the front of the list
		this.$draggee = $([ this.$targetItem[0] ].concat($draggee.not(this.$targetItem).toArray()));

		// Create the helper(s)
		if (this.settings.collapseDraggees)
		{
			this._createHelper(0);
		}
		else
		{
			for (var i = 0; i < this.$draggee.length; i++)
			{
				this._createHelper(i);
			}
		}

		this._hideDraggee();
	},

	/**
	 * Appends additional items to the draggee.
	 */
	appendDraggee: function($newDraggee)
	{
		if (!$newDraggee.length)
		{
			return;
		}

		if (!this.settings.collapseDraggees)
		{
			var oldLength = this.$draggee.length;
		}

		this.$draggee = $(this.$draggee.toArray().concat($newDraggee.not(this.$draggee).toArray()));

		// Create new helpers?
		if (!this.settings.collapseDraggees)
		{
			var newLength = this.$draggee.length;

			for (var i = oldLength; i < newLength; i++)
			{
				this._createHelper(i);
			}
		}

		this._hideDraggee();
	},

	/**
	 * Drag
	 */
	drag: function(didMouseMove)
	{
		// Update the draggee's virtual midpoint
		this.draggeeVirtualMidpointX = this.mouseX - this.mouseOffsetX + (this.targetItemWidth / 2);
		this.draggeeVirtualMidpointY = this.mouseY - this.mouseOffsetY + (this.targetItemHeight / 2);

		this.base(didMouseMove);
	},

	/**
	 * Stop Dragging
	 */
	stopDragging: function()
	{
		// Clear the helper animation
		Garnish.cancelAnimationFrame(this.updateHelperPosFrame);

		this.base();
	},

	/**
	 * Identifies the item(s) that are being dragged.
	 */
	findDraggee: function()
	{
		switch (typeof this.settings.filter)
		{
			case 'function':
			{
				return this.settings.filter();
			}

			case 'string':
			{
				return this.$items.filter(this.settings.filter);
			}

			default:
			{
				return this.$targetItem;
			}
		}
	},

	/**
	 * Returns the helper’s target X position
	 */
	getHelperTargetX: function()
	{
		return this.mouseX - this.mouseOffsetX;
	},

	/**
	 * Returns the helper’s target Y position
	 */
	getHelperTargetY: function()
	{
		return this.mouseY - this.mouseOffsetY;
	},

	/**
	 * Return Helpers to Draggees
	 */
	returnHelpersToDraggees: function()
	{
		this._returningHelpersToDraggees = true;

		for (var i = 0; i < this.helpers.length; i++)
		{
			var $draggee = $(this.$draggee[i]),
				$helper = this.helpers[i];

			$draggee.css({
				display:    this.draggeeDisplay,
				visibility: 'hidden'
			});

			var draggeeOffset = $draggee.offset();

			if (i == 0)
			{
				var callback = $.proxy(this, '_showDraggee');
			}
			else
			{
				var callback = null;
			}

			$helper.velocity({left: draggeeOffset.left, top: draggeeOffset.top}, Garnish.FX_DURATION, callback);
		}
	},

	// Events
	// -------------------------------------------------------------------------

	onReturnHelpersToDraggees: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('returnHelpersToDraggees');
			this.settings.onReturnHelpersToDraggees();
		}, this));
	},

	// Private methods
	// =========================================================================

	/**
	 * Creates a helper.
	 */
	_createHelper: function(i)
	{
		var $draggee = $(this.$draggee[i]),
			$draggeeHelper = $draggee.clone().addClass('draghelper');

		$draggeeHelper.css({
			width: $draggee.width() + 1, // Prevent the brower from wrapping text if the width was actually a fraction of a pixel larger
			height: $draggee.height(),
			margin: 0
		});

		if (this.settings.helper)
		{
			if (typeof this.settings.helper == 'function')
			{
				$draggeeHelper = this.settings.helper($draggeeHelper);
			}
			else
			{
				$draggeeHelper = $(this.settings.helper).append($draggeeHelper);
			}
		}

		$draggeeHelper.appendTo(Garnish.$bod);

		var helperPos = this._getHelperTarget(i);

		$draggeeHelper.css({
			position: 'absolute',
			top: helperPos.top,
			left: helperPos.left,
			zIndex: this.settings.helperBaseZindex + this.$draggee.length - i,
			opacity: this.settings.helperOpacity
		});

		this.helperPositions[i] = {
			top:  helperPos.top,
			left: helperPos.left
		};

		this.helpers.push($draggeeHelper);
	},

	/**
	 * Update Helper Position
	 */
	_updateHelperPos: function()
	{
		// Has the mouse moved?
		if (this.mouseX !== this.lastMouseX || this.mouseY !== this.lastMouseY)
		{
			// Get the new target helper positions
			for (this._updateHelperPos._i = 0; this._updateHelperPos._i < this.helpers.length; this._updateHelperPos._i++)
			{
				this.helperTargets[this._updateHelperPos._i] = this._getHelperTarget(this._updateHelperPos._i);
			}

			this.lastMouseX = this.mouseX;
			this.lastMouseY = this.mouseY;
		}

		// Gravitate helpers toward their target positions
		for (this._updateHelperPos._j = 0; this._updateHelperPos._j < this.helpers.length; this._updateHelperPos._j++)
		{
			this._updateHelperPos._lag = this.settings.helperLagBase + (this.helperLagIncrement * this._updateHelperPos._j);

			this.helperPositions[this._updateHelperPos._j] = {
				left: this.helperPositions[this._updateHelperPos._j].left + ((this.helperTargets[this._updateHelperPos._j].left - this.helperPositions[this._updateHelperPos._j].left) / this._updateHelperPos._lag),
				top:  this.helperPositions[this._updateHelperPos._j].top  + ((this.helperTargets[this._updateHelperPos._j].top  - this.helperPositions[this._updateHelperPos._j].top) / this._updateHelperPos._lag)
			};

			this.helpers[this._updateHelperPos._j].css(this.helperPositions[this._updateHelperPos._j]);
		}

		// Let's do this again on the next frame!
		this.updateHelperPosFrame = Garnish.requestAnimationFrame(this.updateHelperPosProxy);
	},

	/**
	 * Get the helper position for a draggee helper
	 */
	_getHelperTarget: function(i)
	{
		return {
			left: this.getHelperTargetX() + (this.settings.helperSpacingX * i),
			top:  this.getHelperTargetY() + (this.settings.helperSpacingY * i)
		};
	},

	_hideDraggee: function()
	{
		if (this.settings.removeDraggee)
		{
			this.$draggee.hide();
		}
		else if (this.settings.collapseDraggees)
		{
			this.$targetItem.css('visibility', 'hidden');
			this.$draggee.not(this.$targetItem).hide();
		}
		else
		{
			this.$draggee.css('visibility', 'hidden');
		}
	},

	_showDraggee: function()
	{
		// Remove the helpers
		for (var i = 0; i < this.helpers.length; i++)
		{
			this.helpers[i].remove();
		}

		this.helpers = null;

		this.$draggee.show().css('visibility', 'inherit');

		this.onReturnHelpersToDraggees();

		this._returningHelpersToDraggees = false;
	}
},

// Static Properties
// =============================================================================

{
	defaults: {
		filter: null,
		collapseDraggees: false,
		removeDraggee: false,
		helperOpacity: 1,
		helper: null,
		helperBaseZindex: 1000,
		helperLagBase: 1,
		helperLagIncrementDividend: 1.5,
		helperSpacingX: 5,
		helperSpacingY: 5,
		onReturnHelpersToDraggees: $.noop
	}
});


/**
 * Drag-and-drop class
 *
 * Builds on the Drag class by allowing you to set up "drop targets"
 * which the dragged elemements can be dropped onto.
 */
Garnish.DragDrop = Garnish.Drag.extend({

	$dropTargets: null,
	$activeDropTarget: null,

	/**
	 * Constructor
	 */
	init: function(settings)
	{
		settings = $.extend({}, Garnish.DragDrop.defaults, settings);
		this.base(settings);
	},

	updateDropTargets: function()
	{
		if (this.settings.dropTargets)
		{
			if (typeof this.settings.dropTargets == 'function')
			{
				this.$dropTargets = $(this.settings.dropTargets());
			}
			else
			{
				this.$dropTargets = $(this.settings.dropTargets);
			}

			// Discard if it's an empty array
			if (!this.$dropTargets.length)
			{
				this.$dropTargets = null;
			}
		}
	},

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.updateDropTargets();
		this.$activeDropTarget = null;
		this.base();
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		if (this.$dropTargets)
		{
			this.onDrag._activeDropTarget = null;

			// is the cursor over any of the drop target?
			for (this.onDrag._i = 0; this.onDrag._i < this.$dropTargets.length; this.onDrag._i++)
			{
				this.onDrag._elem = this.$dropTargets[this.onDrag._i];

				if (Garnish.hitTest(this.mouseX, this.mouseY, this.onDrag._elem))
				{
					this.onDrag._activeDropTarget = this.onDrag._elem;
					break;
				}
			}

			// has the drop target changed?
			if (
				(this.$activeDropTarget && this.onDrag._activeDropTarget != this.$activeDropTarget[0]) ||
				(!this.$activeDropTarget && this.onDrag._activeDropTarget !== null)
			)
			{
				// was there a previous one?
				if (this.$activeDropTarget)
				{
					this.$activeDropTarget.removeClass(this.settings.activeDropTargetClass);
				}

				// remember the new one
				if (this.onDrag._activeDropTarget)
				{
					this.$activeDropTarget = $(this.onDrag._activeDropTarget).addClass(this.settings.activeDropTargetClass);
				}
				else
				{
					this.$activeDropTarget = null;
				}

				this.settings.onDropTargetChange(this.$activeDropTarget);
			}
		}

		this.base();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		if (this.$dropTargets && this.$activeDropTarget)
		{
			this.$activeDropTarget.removeClass(this.settings.activeDropTargetClass);
		}

		this.base();
	},

	/**
	 * Fade Out Helpers
	 */
	fadeOutHelpers: function()
	{
		for (var i = 0; i < this.helpers.length; i++)
		{
			(function($draggeeHelper)
			{
				$draggeeHelper.velocity('fadeOut', {
					duration: Garnish.FX_DURATION,
					complete: function() {
						$draggeeHelper.remove();
					}
				});
			})(this.helpers[i]);
		}
	}
},
{
	defaults: {
		dropTargets: null,
		onDropTargetChange: $.noop,
		activeDropTargetClass: 'active'
	}
});


/**
 * Drag-to-move clas
 *
 * Builds on the BaseDrag class by simply moving the dragged element(s) along with the mouse.
 */
Garnish.DragMove = Garnish.BaseDrag.extend({

	onDrag: function(items, settings)
	{
		this.$targetItem.css({
			left: this.mouseX - this.mouseOffsetX,
			top:  this.mouseY - this.mouseOffsetY
		});
	}

});


/**
 * Drag-to-sort class
 *
 * Builds on the Drag class by allowing you to sort the elements amongst themselves.
 */
Garnish.DragSort = Garnish.Drag.extend({

	// Properties
	// =========================================================================

	$heightedContainer: null,
	$insertion: null,
	insertionVisible: false,
	startDraggeeIndex: null,
	closestItem: null,

	_midpointVersion: 0,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param mixed  items    Elements that should be draggable right away. (Can be skipped.)
	 * @param object settings Any settings that should override the defaults.
	 */
	init: function(items, settings)
	{
		// Param mapping
		if (!settings && $.isPlainObject(items))
		{
			// (settings)
			settings = items;
			items = null;
		}

		settings = $.extend({}, Garnish.DragSort.defaults, settings);
		this.base(items, settings);
	},

	/**
	 * Creates the insertion element.
	 */
	createInsertion: function()
	{
		if (this.settings.insertion)
		{
			if (typeof this.settings.insertion == 'function')
			{
				return $(this.settings.insertion(this.$draggee));
			}
			else
			{
				return $(this.settings.insertion);
			}
		}
	},

	/**
	 * Returns the helper’s target X position
	 */
	getHelperTargetX: function()
	{
		if (this.settings.magnetStrength != 1)
		{
			this.getHelperTargetX._draggeeOffsetX = this.$draggee.offset().left;
			return this.getHelperTargetX._draggeeOffsetX + ((this.mouseX - this.mouseOffsetX - this.getHelperTargetX._draggeeOffsetX) / this.settings.magnetStrength);
		}
		else
		{
			return this.base();
		}
	},

	/**
	 * Returns the helper’s target Y position
	 */
	getHelperTargetY: function()
	{
		if (this.settings.magnetStrength != 1)
		{
			this.getHelperTargetY._draggeeOffsetY = this.$draggee.offset().top;
			return this.getHelperTargetY._draggeeOffsetY + ((this.mouseY - this.mouseOffsetY - this.getHelperTargetY._draggeeOffsetY) / this.settings.magnetStrength);
		}
		else
		{
			return this.base();
		}
	},

	/**
	 * Returns whether the draggee can be inserted before a given item.
	 */
	canInsertBefore: function($item)
	{
		return true;
	},

	/**
	 * Returns whether the draggee can be inserted after a given item.
	 */
	canInsertAfter: function($item)
	{
		return true;
	},

	// Events
	// -------------------------------------------------------------------------

	/**
	 * On Drag Start
	 */
	onDragStart: function()
	{
		this.$insertion = this.createInsertion();
		this._placeInsertionWithDraggee();

		this.closestItem = null;
		this._midpointVersion++;

		//  Get the closest container that has a height
		if (this.settings.container)
		{
			this.$heightedContainer = $(this.settings.container);

			while (! this.$heightedContainer.height())
			{
				this.$heightedContainer = this.$heightedContainer.parent();
			}
		}

		this.startDraggeeIndex = this._getDraggeeIndex();

		this.base();
	},

	/**
	 * On Drag
	 */
	onDrag: function()
	{
		// If there's a container set, make sure that we're hovering over it
		if (this.$heightedContainer && !Garnish.hitTest(this.mouseX, this.mouseY, this.$heightedContainer))
		{
			if (this.closestItem)
			{
				this.closestItem = null;
				this._removeInsertion();
			}
		}
		else
		{
			// Is there a new closest item?
			if (
				this.closestItem !== (this.closestItem = this._getClosestItem()) &&
				this.closestItem !== null
			)
			{
				this._updateInsertion();
			}
		}

		this.base();
	},

	/**
	 * On Drag Stop
	 */
	onDragStop: function()
	{
		this._removeInsertion();

		// Return the helpers to the draggees
		this.returnHelpersToDraggees();

		this.base();

		// Has the item actually moved?
		this.$items = $().add(this.$items);

		if (this._getDraggeeIndex() != this.startDraggeeIndex)
		{
			this.onSortChange();
		}
	},

	/**
	 * On Insertion Point Change event
	 */
	onInsertionPointChange: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('insertionPointChange');
			this.settings.onInsertionPointChange();
		}, this));
	},

	/**
	 * On Sort Change event
	 */
	onSortChange: function()
	{
		Garnish.requestAnimationFrame($.proxy(function()
		{
			this.trigger('sortChange');
			this.settings.onSortChange();
		}, this));
	},

	// Private methods
	// =========================================================================

	_getDraggeeIndex: function()
	{
		return $.inArray(this.$draggee[0], this.$items);
	},

	/**
	 * Returns the closest item to the cursor.
	 */
	_getClosestItem: function()
	{
		this._getClosestItem._closestItem = null;

		// Start by checking the draggee/insertion, if either are visible
		// ---------------------------------------------------------------------

		if (!this.settings.removeDraggee)
		{
			this._testForClosestItem(this.$draggee[0]);
		}
		else if (this.insertionVisible)
		{
			this._testForClosestItem(this.$insertion[0]);
		}

		// Check items before the draggee
		// ---------------------------------------------------------------------

		if (this._getClosestItem._closestItem) this._getClosestItem._midpoint = this._getItemMidpoint(this._getClosestItem._closestItem)
		if (this.settings.axis != Garnish.Y_AXIS) this._getClosestItem._startXDist = this._getClosestItem._lastXDist = this._getClosestItem._closestItem ? Math.abs(this._getClosestItem._midpoint.x - this.draggeeVirtualMidpointX) : null;
		if (this.settings.axis != Garnish.X_AXIS) this._getClosestItem._startYDist = this._getClosestItem._lastYDist = this._getClosestItem._closestItem ? Math.abs(this._getClosestItem._midpoint.y - this.draggeeVirtualMidpointY) : null;

		this._getClosestItem._$otherItem = this.$draggee.first().prev();

		while (this._getClosestItem._$otherItem.length)
		{
			// See if we're just getting further away
			this._getClosestItem._midpoint = this._getItemMidpoint(this._getClosestItem._$otherItem[0]);
			if (this.settings.axis != Garnish.Y_AXIS) this._getClosestItem._xDist = Math.abs(this._getClosestItem._midpoint.x - this.draggeeVirtualMidpointX);
			if (this.settings.axis != Garnish.X_AXIS) this._getClosestItem._yDist = Math.abs(this._getClosestItem._midpoint.y - this.draggeeVirtualMidpointY);

			if (
				(this.settings.axis == Garnish.Y_AXIS || (this._getClosestItem._lastXDist !== null && this._getClosestItem._xDist > this._getClosestItem._lastXDist)) &&
				(this.settings.axis == Garnish.X_AXIS || (this._getClosestItem._lastYDist !== null && this._getClosestItem._yDist > this._getClosestItem._lastYDist))
			)
			{
				break;
			}

			if (this.settings.axis != Garnish.Y_AXIS) this._getClosestItem._lastXDist = this._getClosestItem._xDist;
			if (this.settings.axis != Garnish.X_AXIS) this._getClosestItem._lastYDist = this._getClosestItem._yDist;

			// Give the extending class a chance to allow/disallow this item
			if (this.canInsertBefore(this._getClosestItem._$otherItem))
			{
				this._testForClosestItem(this._getClosestItem._$otherItem[0]);
			}

			// Prep the next item
			this._getClosestItem._$otherItem = this._getClosestItem._$otherItem.prev();
		}

		// Check items after the draggee
		// ---------------------------------------------------------------------

		if (this.settings.axis != Garnish.Y_AXIS) this._getClosestItem._lastXDist = this._getClosestItem._startXDist;
		if (this.settings.axis != Garnish.X_AXIS) this._getClosestItem._lastYDist = this._getClosestItem._startYDist;

		this._getClosestItem._$otherItem = this.$draggee.last().next();

		while (this._getClosestItem._$otherItem.length)
		{
			// See if we're just getting further away
			this._getClosestItem._midpoint = this._getItemMidpoint(this._getClosestItem._$otherItem[0]);
			if (this.settings.axis != Garnish.Y_AXIS) this._getClosestItem._xDist = Math.abs(this._getClosestItem._midpoint.x - this.draggeeVirtualMidpointX);
			if (this.settings.axis != Garnish.X_AXIS) this._getClosestItem._yDist = Math.abs(this._getClosestItem._midpoint.y - this.draggeeVirtualMidpointY);

			if (
				(this.settings.axis == Garnish.Y_AXIS || (this._getClosestItem._lastXDist !== null && this._getClosestItem._xDist > this._getClosestItem._lastXDist)) &&
				(this.settings.axis == Garnish.X_AXIS || (this._getClosestItem._lastYDist !== null && this._getClosestItem._yDist > this._getClosestItem._lastYDist))
			)
			{
				break;
			}

			if (this.settings.axis != Garnish.Y_AXIS) this._getClosestItem._lastXDist = this._getClosestItem._xDist;
			if (this.settings.axis != Garnish.X_AXIS) this._getClosestItem._lastYDist = this._getClosestItem._yDist;

			// Give the extending class a chance to allow/disallow this item
			if (this.canInsertAfter(this._getClosestItem._$otherItem))
			{
				this._testForClosestItem(this._getClosestItem._$otherItem[0]);
			}

			// Prep the next item
			this._getClosestItem._$otherItem = this._getClosestItem._$otherItem.next();
		}

		// Return the result
		// ---------------------------------------------------------------------

		// Ignore if it's the draggee or insertion
		if (
			this._getClosestItem._closestItem != this.$draggee[0] &&
			(!this.insertionVisible || this._getClosestItem._closestItem != this.$insertion[0])
		)
		{
			return this._getClosestItem._closestItem;
		}
		else
		{
			return null;
		}
	},

	_getItemMidpoint: function(item)
	{
		if ($.data(item, 'midpointVersion') != this._midpointVersion)
		{
			this._getItemMidpoint._$item = $(item);
			this._getItemMidpoint._offset = this._getItemMidpoint._$item.offset();

			$.data(item, 'midpoint', {
				x: this._getItemMidpoint._offset.left + this._getItemMidpoint._$item.outerWidth() / 2,
				y: this._getItemMidpoint._offset.top + this._getItemMidpoint._$item.outerHeight() / 2
			});

			$.data(item, 'midpointVersion', this._midpointVersion);

			delete this._getItemMidpoint._$item;
			delete this._getItemMidpoint._offset;
		}

		return $.data(item, 'midpoint');
	},

	_testForClosestItem: function(item)
	{
		this._testForClosestItem._midpoint = this._getItemMidpoint(item);

		this._testForClosestItem._mouseDist = Garnish.getDist(
			this._testForClosestItem._midpoint.x,
			this._testForClosestItem._midpoint.y,
			this.draggeeVirtualMidpointX,
			this.draggeeVirtualMidpointY
		);

		if (
			this._getClosestItem._closestItem === null ||
			this._testForClosestItem._mouseDist < this._getClosestItem._closestItemMouseDist
		)
		{
			this._getClosestItem._closestItem          = item;
			this._getClosestItem._closestItemMouseDist = this._testForClosestItem._mouseDist;
		}
	},

	/**
	 * Updates the position of the insertion point.
	 */
	_updateInsertion: function()
	{
		if (this.closestItem)
		{
			// Going down?
			if (this.$draggee.index() < $(this.closestItem).index())
			{
				this.$draggee.insertAfter(this.closestItem);
			}
			else
			{
				this.$draggee.insertBefore(this.closestItem);
			}

			this._placeInsertionWithDraggee();
		}

		// Now that things have shifted around, invalidate the midpoints
		this._midpointVersion++;

		this.onInsertionPointChange();
	},

	_placeInsertionWithDraggee: function()
	{
		if (this.$insertion)
		{
			this.$insertion.insertBefore(this.$draggee.first());
			this.insertionVisible = true;
		}
	},

	/**
	 * Removes the insertion, if it's visible.
	 */
	_removeInsertion: function()
	{
		if (this.insertionVisible)
		{
			this.$insertion.remove();
			this.insertionVisible = false;
		}
	}
},

// Static Properties
// =============================================================================

{
	defaults: {
		container: null,
		insertion: null,
		magnetStrength: 1,
		onInsertionPointChange: $.noop,
		onSortChange: $.noop
	}
});


/**
 * ESC key manager class
 */
Garnish.EscManager = Garnish.Base.extend({

	handlers: null,

	init: function()
	{
		this.handlers = [];

		this.addListener(Garnish.$bod, 'keyup', function(ev)
		{
			if (ev.keyCode == Garnish.ESC_KEY)
			{
				this.escapeLatest(ev);
			}
		});
	},

	register: function(obj, func)
	{
		this.handlers.push({
			obj: obj,
			func: func
		});
	},

	unregister: function(obj)
	{
		for (var i = this.handlers.length - 1; i >= 0; i--)
		{
			if (this.handlers[i].obj == obj)
			{
				this.handlers.splice(i, 1);
			}
		}
	},

	escapeLatest: function(ev)
	{
		if (this.handlers.length)
		{
			var handler = this.handlers.pop();

			if (typeof handler.func == 'function')
			{
				var func = handler.func;
			}
			else
			{
				var func = handler.obj[handler.func];
			}

			func.call(handler.obj, ev);

			if (typeof handler.obj.trigger == 'function')
			{
				handler.obj.trigger('escape');
			}
		}
	}

});

Garnish.escManager = new Garnish.EscManager();


/**
 * HUD
 */
Garnish.HUD = Garnish.Base.extend({

	$trigger: null,
	$hud: null,
	$tip: null,
	$body: null,
	$shade: null,

	windowWidth: null,
	windowHeight: null,
	windowScrollLeft: null,
	windowScrollTop: null,

	triggerWidth: null,
	triggerHeight: null,
	triggerOffset: null,

	width: null,
	height: null,

	showing: false,
	position: null,

	/**
	 * Constructor
	 */
	init: function(trigger, bodyContents, settings) {

		this.$trigger = $(trigger);

		this.setSettings(settings, Garnish.HUD.defaults);
		this.on('show', this.settings.onShow);
		this.on('hide', this.settings.onHide);

		if (typeof Garnish.HUD.activeHUDs == "undefined")
		{
			Garnish.HUD.activeHUDs = {};
		}

		this.$shade = $('<div class="hud-shade"/>');
		this.$hud = $('<div class="'+this.settings.hudClass+'" />');
		this.$tip = $('<div class="'+this.settings.tipClass+'" />').appendTo(this.$hud);
		this.$body = $('<div class="'+this.settings.bodyClass+'" />').appendTo(this.$hud).append(bodyContents);

		if (this.$body.find('.footer').length)
		{
			this.$hud.addClass('has-footer');
		}

		this.show();
	},

	/**
	 * Show
	 */
	show: function(ev)
	{
		if (ev && ev.stopPropagation)
		{
			ev.stopPropagation();
		}

		if (this.showing)
		{
			return;
		}

		if (this.settings.closeOtherHUDs)
		{
			for (var hudID in Garnish.HUD.activeHUDs)
			{
				Garnish.HUD.activeHUDs[hudID].hide();
			}
		}

		// Prevent the browser from jumping
		this.$hud.css('top', Garnish.$win.scrollTop());

		// Move it to the end of <body> so it gets the highest sub-z-index
		this.$shade.appendTo(Garnish.$bod);
		this.$hud.appendTo(Garnish.$bod);

		this.$hud.show();
		this.determineBestPosition();
		this.setPosition();

		this.$shade.show();

		this.showing = true;
		Garnish.HUD.activeHUDs[this._namespace] = this;

		Garnish.escManager.register(this, 'hide');

		this.addListener(this.$hud, 'resize', 'resetPosition');
		this.addListener(Garnish.$win, 'resize', 'resetPosition');

		this.addListener(this.$shade, 'click', 'hide');

		if (this.settings.closeBtn)
		{
			this.addListener(this.settings.closeBtn, 'activate', 'hide');
		}

		this.onShow();
	},

	onShow: function()
	{
		this.trigger('show');
	},

	updateElementProperties: function()
	{
		this.windowWidth = Garnish.$win.width();
		this.windowHeight = Garnish.$win.height();

		this.windowScrollLeft = Garnish.$win.scrollLeft();
		this.windowScrollTop = Garnish.$win.scrollTop();

		// get the trigger's dimensions
		this.triggerWidth = this.$trigger.outerWidth();
		this.triggerHeight = this.$trigger.outerHeight();

		// get the offsets for each side of the trigger element
		this.triggerOffset = this.$trigger.offset();
		this.triggerOffset.right = this.triggerOffset.left + this.triggerWidth;
		this.triggerOffset.bottom = this.triggerOffset.top + this.triggerHeight;

		// get the HUD dimensions
		this.width = this.$hud.outerWidth();
		this.height = this.$hud.outerHeight();
	},

	determineBestPosition: function()
	{
		// Get the window sizez and trigger offset
		this.updateElementProperties();

		// get the minimum horizontal/vertical clearance needed to fit the HUD
		this.minHorizontalClearance = this.width + this.settings.triggerSpacing + this.settings.windowSpacing;
		this.minVerticalClearance = this.height + this.settings.triggerSpacing + this.settings.windowSpacing;

		// find the actual available top/right/bottom/left clearances
		var clearances = [
			this.windowHeight + this.windowScrollTop - this.triggerOffset.bottom, // bottom
			this.triggerOffset.top - this.windowScrollTop,                        // top
			this.windowWidth + this.windowScrollLeft - this.triggerOffset.right,  // right
			this.triggerOffset.left - this.windowScrollLeft                       // left
		];

		// Find the first position that has enough room
		for (var i = 0; i < 4; i++)
		{
			var prop = (i < 2 ? 'height' : 'width');
			if (clearances[i] - (this.settings.windowSpacing + this.settings.triggerSpacing) >= this[prop])
			{
				var positionIndex = i;
				break;
			}
		}

		if (typeof positionIndex == 'undefined')
		{
			// Just figure out which one is the biggest
			var biggestClearance = Math.max.apply(null, clearances),
				positionIndex = $.inArray(biggestClearance, clearances);
		}

		this.position = Garnish.HUD.positions[positionIndex];

		// Update the tip class
		if (this.tipClass)
		{
			this.$tip.removeClass(this.tipClass);
		}

		this.tipClass = this.settings.tipClass+'-'+Garnish.HUD.tipClasses[positionIndex];
		this.$tip.addClass(this.tipClass);
	},

	setPosition: function()
	{
		if (this.position == 'top' || this.position == 'bottom')
		{
			// Center the HUD horizontally
			var maxLeft = (this.windowWidth + this.windowScrollLeft) - (this.width + this.settings.windowSpacing),
				minLeft = (this.windowScrollLeft + this.settings.windowSpacing),
				triggerCenter = this.triggerOffset.left + Math.round(this.triggerWidth / 2),
				left = triggerCenter - Math.round(this.width / 2);

			if (left > maxLeft) left = maxLeft;
			if (left < minLeft) left = minLeft;

			this.$hud.css('left', left);

			var tipLeft = (triggerCenter - left) - (this.settings.tipWidth / 2);
			this.$tip.css({ left: tipLeft, top: '' });

			if (this.position == 'top')
			{
				var top = this.triggerOffset.top - (this.height + this.settings.triggerSpacing);
				this.$hud.css('top', top);
			}
			else
			{
				var top = this.triggerOffset.bottom + this.settings.triggerSpacing;
				this.$hud.css('top', top);
			}
		}
		else
		{
			// Center the HUD vertically
			var maxTop = (this.windowHeight + this.windowScrollTop) - (this.height + this.settings.windowSpacing),
				minTop = (this.windowScrollTop + this.settings.windowSpacing),
				triggerCenter = this.triggerOffset.top + Math.round(this.triggerHeight / 2),
				top = triggerCenter - Math.round(this.height / 2);

			if (top > maxTop) top = maxTop;
			if (top < minTop) top = minTop;

			this.$hud.css('top', top);

			var tipTop = (triggerCenter - top) - (this.settings.tipWidth / 2);
			this.$tip.css({ top: tipTop, left: '' });


			if (this.position == 'left')
			{
				var left = this.triggerOffset.left - (this.width + this.settings.triggerSpacing);
				this.$hud.css('left', left);
			}
			else
			{
				var left = this.triggerOffset.right + this.settings.triggerSpacing;
				this.$hud.css('left', left);
			}
		}
	},

	resetPosition: function()
	{
		this.updateElementProperties();
		this.setPosition();
	},

	/**
	 * Hide
	 */
	hide: function()
	{
		this.$hud.hide();
		this.$shade.hide();
		this.showing = false;

		delete Garnish.HUD.activeHUDs[this._namespace];

		Garnish.escManager.unregister(this);

		this.onHide();
	},

	onHide: function()
	{
		this.trigger('hide');
	},

	toggle: function()
	{
		if (this.showing)
		{
			this.hide();
		}
		else
		{
			this.show();
		}
	}
},
{
	positions: ['bottom', 'top', 'right', 'left'],
	tipClasses: ['top', 'bottom', 'left', 'right'],

	defaults: {
		hudClass: 'hud',
		tipClass: 'tip',
		bodyClass: 'body',
		triggerSpacing: 10,
		windowSpacing: 10,
		tipWidth: 30,
		onShow: $.noop,
		onHide: $.noop,
		closeBtn: null,
		closeOtherHUDs: true
	}
});


/**
 * Light Switch
 */
Garnish.LightSwitch = Garnish.Base.extend({

	settings: null,
	$outerContainer: null,
	$innerContainer: null,
	$input: null,
	$toggleTarget: null,
	on: null,
	dragger: null,

	dragStartMargin: null,

	init: function(outerContainer, settings)
	{
		this.$outerContainer = $(outerContainer);

		// Is this already a switch?
		if (this.$outerContainer.data('lightswitch'))
		{
			Garnish.log('Double-instantiating a switch on an element');
			this.$outerContainer.data('lightswitch').destroy();
		}

		this.$outerContainer.data('lightswitch', this);

		this.setSettings(settings, Garnish.LightSwitch.defaults);

		this.$innerContainer = this.$outerContainer.find('.container:first');
		this.$input = this.$outerContainer.find('input:first');
		this.$toggleTarget = $(this.$outerContainer.attr('data-toggle'));

		this.on = this.$outerContainer.hasClass('on');

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
		this.$innerContainer.velocity('stop').velocity({marginLeft: 0}, Garnish.FX_DURATION);
		this.$input.val(Garnish.Y_AXIS);
		this.on = true;
		this.onChange();

		this.$toggleTarget.show();
		this.$toggleTarget.height('auto');
		var height = this.$toggleTarget.height();
		this.$toggleTarget.height(0);
		this.$toggleTarget.velocity('stop').velocity({height: height}, Garnish.FX_DURATION, $.proxy(function() {
			this.$toggleTarget.height('auto');
		}, this));
	},

	turnOff: function()
	{
		this.$innerContainer.velocity('stop').velocity({marginLeft: Garnish.LightSwitch.offMargin}, Garnish.FX_DURATION);
		this.$input.val('');
		this.on = false;
		this.onChange();

		this.$toggleTarget.velocity('stop').velocity({height: 0}, Garnish.FX_DURATION);
	},

	toggle: function(ev)
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
		this.addListener(Garnish.$doc, 'mouseup', '_onMouseUp')
	},

	_onMouseUp: function()
	{
		this.removeListener(Garnish.$doc, 'mouseup');

		// Was this a click?
		if (!this.dragger.dragging)
			this.toggle();
	},

	_onKeyDown: function(ev)
	{
		switch (ev.keyCode)
		{
			case Garnish.SPACE_KEY:
			{
				this.toggle();
				ev.preventDefault();
				break;
			}

			case Garnish.RIGHT_KEY:
			{
				if (Garnish.ltr)
				{
					this.turnOn();
				}
				else
				{
					this.turnOff();
				}

				ev.preventDefault();
				break;
			}

			case Garnish.LEFT_KEY:
			{
				if (Garnish.ltr)
				{
					this.turnOff();
				}
				else
				{
					this.turnOn();
				}

				ev.preventDefault();
				break;
			}
		}
	},

	_getMargin: function()
	{
		return parseInt(this.$innerContainer.css('marginLeft'))
	},

	_onDragStart: function()
	{
		this.dragStartMargin = this._getMargin();
	},

	_onDrag: function()
	{
		var margin = this.dragStartMargin + this.dragger.mouseDistX;

		if (margin < Garnish.LightSwitch.offMargin)
		{
			margin = Garnish.LightSwitch.offMargin;
		}
		else if (margin > 0)
		{
			margin = 0;
		}

		this.$innerContainer.css('marginLeft', margin);
	},

	_onDragStop: function()
	{
		var margin = this._getMargin();

		if (margin > -16)
		{
			this.turnOn();
		}
		else
		{
			this.turnOff();
		}
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$outerContainer.removeData('lightswitch');
		this.dragger.destroy();
		this.base();
	}
},
{
	offMargin: -50,
	defaults: {
		onChange: $.noop
	}
});


/**
 * Menu
 */
Garnish.Menu = Garnish.Base.extend({

	settings: null,

	$container: null,
	$options: null,
	$trigger: null,

	_windowWidth: null,
	_windowHeight: null,
	_windowScrollLeft: null,
	_windowScrollTop: null,

	_triggerOffset: null,
	_triggerWidth: null,
	_triggerHeight: null,
	_triggerOffsetRight: null,
	_triggerOffsetBottom: null,

	_menuWidth: null,
	_menuHeight: null,

	/**
	 * Constructor
	 */
	init: function(container, settings)
	{
		this.setSettings(settings, Garnish.Menu.defaults);

		this.$container = $(container);
		this.$options = $();
		this.addOptions(this.$container.find('a'));

		if (this.settings.attachToElement)
		{
			this.$trigger = $(this.settings.attachToElement);
		}

		// Prevent clicking on the container from hiding the menu
		this.addListener(this.$container, 'mousedown', function(ev)
		{
			ev.stopPropagation();
		});
	},

	addOptions: function($options)
	{
		this.$options = this.$options.add($options);
		$options.data('menu', this);
		this.addListener($options, 'click', 'selectOption');
	},

	setPositionRelativeToTrigger: function()
	{
		this._windowWidth = Garnish.$win.width();
		this._windowHeight = Garnish.$win.height();
		this._windowScrollLeft = Garnish.$win.scrollLeft();
		this._windowScrollTop = Garnish.$win.scrollTop();

		this._triggerOffset = this.$trigger.offset();
		this._triggerWidth = this.$trigger.outerWidth();
		this._triggerHeight = this.$trigger.outerHeight();
		this._triggerOffsetRight = this._triggerOffset.left + this._triggerHeight;
		this._triggerOffsetBottom = this._triggerOffset.top + this._triggerHeight;

		this.$container.css('minWidth', 0);
		this.$container.css('minWidth', this._triggerWidth - (this.$container.outerWidth() - this.$container.width()));

		this._menuWidth = this.$container.outerWidth();
		this._menuHeight = this.$container.outerHeight();

		// Is there room for the menu below the trigger?
		var topClearance = this._triggerOffset.top - this._windowScrollTop,
			bottomClearance = this._windowHeight + this._windowScrollTop - this._triggerOffsetBottom;

		if (bottomClearance >= this._menuHeight || bottomClearance >= topClearance)
		{
			this.$container.css('top', this._triggerOffsetBottom);
		}
		else
		{
			this.$container.css('top', this._triggerOffset.top - this._menuHeight);
		}

		// Figure out how we're aliging it
		var align = this.$container.data('align');

		if (align != 'left' && align != 'center' && align != 'right')
		{
			align = 'left';
		}

		if (align == 'center')
		{
			this._alignCenter();
		}
		else
		{
			// Figure out which options are actually possible
			var rightClearance = this._windowWidth + this._windowScrollLeft - (this._triggerOffset.left + this._menuWidth),
				leftClearance = this._triggerOffsetRight - this._menuWidth;

			if (align == 'right' && leftClearance >= 0 || rightClearance < 0)
			{
				this._alignRight();
			}
			else
			{
				this._alignLeft();
			}
		}

		delete this._windowWidth;
		delete this._windowHeight;
		delete this._windowScrollLeft;
		delete this._windowScrollTop;
		delete this._triggerOffset;
		delete this._triggerWidth;
		delete this._triggerHeight;
		delete this._triggerOffsetRight;
		delete this._triggerOffsetBottom;
		delete this._menuWidth;
		delete this._menuHeight;
	},

	show: function()
	{
		// Move the menu to the end of the DOM
		this.$container.appendTo(Garnish.$bod)

		if (this.$trigger)
		{
			this.setPositionRelativeToTrigger();
		}

		this.$container.velocity('stop');
		this.$container.css({
			opacity: 1,
			display: 'block'
		});

		Garnish.escManager.register(this, 'hide');
	},

	hide: function()
	{
		this.$container.velocity('fadeOut', { duration: Garnish.FX_DURATION });

		Garnish.escManager.unregister(this);

		this.trigger('hide');
	},

	selectOption: function(ev)
	{
		this.settings.onOptionSelect(ev.currentTarget);
		this.trigger('optionselect', { selectedOption: ev.currentTarget });
		this.hide();
	},

	_alignLeft: function()
	{
		this.$container.css({
			left: this._triggerOffset.left,
			right: 'auto'
		});
	},

	_alignRight: function()
	{
		this.$container.css({
			right: this._windowWidth - (this._triggerOffset.left + this._triggerWidth),
			left: 'auto'
		});
	},

	_alignCenter: function()
	{
		var left = Math.round((this._triggerOffset.left + this._triggerWidth / 2) - (this._menuWidth / 2));

		if (left < 0)
		{
			left = 0;
		}

		this.$container.css('left', left);
	}

},
{
	defaults: {
		attachToElement: null,
		onOptionSelect: $.noop
	}
});


/**
 * Menu Button
 */
Garnish.MenuBtn = Garnish.Base.extend({

	$btn: null,
	menu: null,
	showingMenu: false,
	disabled: true,

	/**
	 * Constructor
	 */
	init: function(btn, settings)
	{
		this.$btn = $(btn);

		// Is this already a menu button?
		if (this.$btn.data('menubtn'))
		{
			// Grab the old MenuBtn's menu container
			var $menu = this.$btn.data('menubtn').menu.$container;

			Garnish.log('Double-instantiating a menu button on an element');
			this.$btn.data('menubtn').destroy();
		}
		else
		{
			var $menu = this.$btn.next('.menu').detach();
		}

		this.$btn.data('menubtn', this);

		this.setSettings(settings, Garnish.MenuBtn.defaults);

		this.menu = new Garnish.Menu($menu, {
			attachToElement: this.$btn,
			onOptionSelect: $.proxy(this, 'onOptionSelect')
		});

		this.menu.on('hide', $.proxy(this, 'onMenuHide'));

		this.addListener(this.$btn, 'mousedown', 'onMouseDown');
		this.enable();
	},

	onMouseDown: function(ev)
	{
		if (ev.which != Garnish.PRIMARY_CLICK || ev.metaKey)
		{
			return;
		}

		ev.preventDefault();

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
		if (this.disabled)
		{
			return;
		}

		this.menu.show();
		this.$btn.addClass('active');
		this.showingMenu = true;

		setTimeout($.proxy(function() {
			this.addListener(Garnish.$doc, 'mousedown', 'onMouseDown');
		}, this), 1);

		if (!Garnish.isMobileBrowser())
		{
			this.addListener(Garnish.$win, 'resize', 'hideMenu');
		}
	},

	hideMenu: function()
	{
		if (!Garnish.isMobileBrowser())
		{
			this.removeListener(Garnish.$win, 'resize');
		}

		this.menu.hide();
	},

	onMenuHide: function()
	{
		this.$btn.removeClass('active');
		this.showingMenu = false;

		this.removeListener(Garnish.$doc, 'mousedown');

		if (!Garnish.isMobileBrowser())
		{
			this.removeListener(Garnish.$doc, 'resize');
		}
	},

	onOptionSelect: function(option)
	{
		this.settings.onOptionSelect(option);
	},

	enable: function ()
	{
		this.disabled = false;
	},

	disable: function ()
	{
		this.disabled = true;
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$btn.removeData('menubtn');
		this.base();
	}
},
{
	defaults: {
		onOptionSelect: $.noop
	}
});


/**
 * Mixed input
 *
 * @todo RTL support, in the event that the input doesn't have dir="ltr".
 */
Garnish.MixedInput = Garnish.Base.extend({

	$container: null,
	elements: null,
	focussedElement: null,
	blurTimeout: null,

	init: function(container, settings)
	{
		this.$container = $(container);
		this.setSettings(settings, Garnish.MixedInput.defaults);

		this.elements = [];

		// Allow the container to receive focus
		this.$container.attr('tabindex', 0);
		this.addListener(this.$container, 'focus', 'onFocus');
	},

	getElementIndex: function($elem)
	{
		return $.inArray($elem, this.elements);
	},

	isText: function($elem)
	{
		return ($elem.prop('nodeName') == 'INPUT');
	},

	onFocus: function(ev)
	{
		// Set focus to the first element
		if (this.elements.length)
		{
			var $elem = this.elements[0];
			this.setFocus($elem);
			this.setCarotPos($elem, 0);
		}
		else
		{
			this.addTextElement();
		}
	},

	addTextElement: function(index)
	{
		var text = new TextElement(this);
		this.addElement(text.$input, index);
		return text;
	},

	addElement: function($elem, index)
	{
		// Was a target index passed, and is it valid?
		if (typeof index == 'undefined')
		{
			if (this.focussedElement)
			{
				var focussedElement = this.focussedElement,
					focussedElementIndex = this.getElementIndex(focussedElement);

				// Is the focus on a text element?
				if (this.isText(focussedElement))
				{
					var selectionStart = focussedElement.prop('selectionStart'),
						selectionEnd = focussedElement.prop('selectionEnd'),
						val = focussedElement.val(),
						preVal = val.substring(0, selectionStart),
						postVal = val.substr(selectionEnd);

					if (preVal && postVal)
					{
						// Split the input into two
						focussedElement.val(preVal).trigger('change');
						var newText = new TextElement(this);
						newText.$input.val(postVal).trigger('change');
						this.addElement(newText.$input, focussedElementIndex+1);

						// Insert the new element in between them
						index = focussedElementIndex+1;
					}
					else if (!preVal)
					{
						// Insert the new element before this one
						index = focussedElementIndex;
					}
					else
					{
						// Insert it after this one
						index = focussedElementIndex + 1;
					}
				}
				else
				{
					// Just insert the new one after this one
					index = focussedElementIndex + 1;
				}
			}
			else
			{
				// Insert the new element at the end
				index = this.elements.length;
			}
		}

		// Add the element
		if (typeof this.elements[index] != 'undefined')
		{
			$elem.insertBefore(this.elements[index]);
			this.elements.splice(index, 0, $elem);
		}
		else
		{
			// Just for safe measure, set the index to what it really will be
			index = this.elements.length;

			this.$container.append($elem);
			this.elements.push($elem);
		}

		// Make sure that there are text elements surrounding all non-text elements
		if (!this.isText($elem))
		{
			// Add a text element before?
			if (index == 0 || !this.isText(this.elements[index-1]))
			{
				this.addTextElement(index);
				index++;
			}

			// Add a text element after?
			if (index == this.elements.length-1 || !this.isText(this.elements[index+1]))
			{
				this.addTextElement(index+1);
			}
		}

		// Add event listeners
		this.addListener($elem, 'click', function() {
			this.setFocus($elem);
		});

		// Set focus to the new element
		setTimeout($.proxy(function() {
			this.setFocus($elem);
		}, this), 1);
	},

	removeElement: function($elem)
	{
		var index = this.getElementIndex($elem);
		if (index != -1)
		{
			this.elements.splice(index, 1);

			if (!this.isText($elem))
			{
				// Combine the two now-adjacent text elements
				var $prevElem = this.elements[index-1],
					$nextElem = this.elements[index];

				if (this.isText($prevElem) && this.isText($nextElem))
				{
					var prevElemVal = $prevElem.val(),
						newVal = prevElemVal + $nextElem.val();
					$prevElem.val(newVal).trigger('change');
					this.removeElement($nextElem);
					this.setFocus($prevElem);
					this.setCarotPos($prevElem, prevElemVal.length);
				}
			}

			$elem.remove();
		}
	},

	setFocus: function($elem)
	{
		this.$container.addClass('focus');

		if (!this.focussedElement)
		{
			// Prevent the container from receiving focus
			// as long as one of its elements has focus
			this.$container.attr('tabindex', '-1');
		}
		else
		{
			// Blur the previously-focussed element
			this.blurFocussedElement();
		}

		$elem.attr('tabindex', '0');
		$elem.focus();
		this.focussedElement = $elem;

		this.addListener($elem, 'blur', function() {
			this.blurTimeout = setTimeout($.proxy(function() {
				if (this.focussedElement == $elem)
				{
					this.blurFocussedElement();
					this.focussedElement = null;
					this.$container.removeClass('focus');

					// Get ready for future focus
					this.$container.attr('tabindex', '0');
				}
			}, this), 1);
		});
	},

	blurFocussedElement: function()
	{
		this.removeListener(this.focussedElement, 'blur');
		this.focussedElement.attr('tabindex', '-1');
	},

	focusPreviousElement: function($from)
	{
		var index = this.getElementIndex($from);

		if (index > 0)
		{
			var $elem = this.elements[index-1];
			this.setFocus($elem);

			// If it's a text element, put the carot at the end
			if (this.isText($elem))
			{
				var length = $elem.val().length;
				this.setCarotPos($elem, length);
			}
		}
	},

	focusNextElement: function($from)
	{
		var index = this.getElementIndex($from);

		if (index < this.elements.length-1)
		{
			var $elem = this.elements[index+1];
			this.setFocus($elem);

			// If it's a text element, put the carot at the beginning
			if (this.isText($elem))
			{
				this.setCarotPos($elem, 0)
			}
		}
	},

	setCarotPos: function($elem, pos)
	{
		$elem.prop('selectionStart', pos);
		$elem.prop('selectionEnd', pos);
	}

});



var TextElement = Garnish.Base.extend({

	parentInput: null,
	$input: null,
	$stage: null,
	val: null,
	focussed: false,
	interval: null,

	init: function(parentInput)
	{
		this.parentInput = parentInput;

		this.$input = $('<input type="text"/>').appendTo(this.parentInput.$container);
		this.$input.css('margin-right', (2-TextElement.padding)+'px');

		this.setWidth();

		this.addListener(this.$input, 'focus', 'onFocus');
		this.addListener(this.$input, 'blur', 'onBlur');
		this.addListener(this.$input, 'keydown', 'onKeyDown');
		this.addListener(this.$input, 'change', 'checkInput');
	},

	getIndex: function()
	{
		return this.parentInput.getElementIndex(this.$input);
	},

	buildStage: function()
	{
		this.$stage = $('<stage/>').appendTo(Garnish.$bod);

		// replicate the textarea's text styles
		this.$stage.css({
			position: 'absolute',
			top: -9999,
			left: -9999,
			wordWrap: 'nowrap'
		});

		Garnish.copyTextStyles(this.$input, this.$stage);
	},

	getTextWidth: function(val)
	{
		if (!this.$stage)
		{
			this.buildStage();
		}

		if (val)
		{
			// Ampersand entities
			val = val.replace(/&/g, '&amp;');

			// < and >
			val = val.replace(/</g, '&lt;');
			val = val.replace(/>/g, '&gt;');

			// Spaces
			val = val.replace(/ /g, '&nbsp;');
		}

		this.$stage.html(val);
		this.stageWidth = this.$stage.width();
		return this.stageWidth;
	},

	onFocus: function()
	{
		this.focussed = true;
		this.interval = setInterval($.proxy(this, 'checkInput'), Garnish.NiceText.interval);
		this.checkInput();
	},

	onBlur: function()
	{
		this.focussed = false;
		clearInterval(this.interval);
		this.checkInput();
	},

	onKeyDown: function(ev)
	{
		setTimeout($.proxy(this, 'checkInput'), 1);

		switch (ev.keyCode)
		{
			case Garnish.LEFT_KEY:
			{
				if (this.$input.prop('selectionStart') == 0 && this.$input.prop('selectionEnd') == 0)
				{
					// Set focus to the previous element
					this.parentInput.focusPreviousElement(this.$input);
				}
				break;
			}

			case Garnish.RIGHT_KEY:
			{
				if (this.$input.prop('selectionStart') == this.val.length && this.$input.prop('selectionEnd') == this.val.length)
				{
					// Set focus to the next element
					this.parentInput.focusNextElement(this.$input);
				}
				break;
			}

			case Garnish.DELETE_KEY:
			{
				if (this.$input.prop('selectionStart') == 0 && this.$input.prop('selectionEnd') == 0)
				{
					// Set focus to the previous element
					this.parentInput.focusPreviousElement(this.$input);
					ev.preventDefault();
				}
			}
		}
	},

	getVal: function()
	{
		this.val = this.$input.val();
		return this.val;
	},

	setVal: function(val)
	{
		this.$input.val(val);
		this.checkInput();
	},

	checkInput: function()
	{
		// Has the value changed?
		var changed = (this.val !== this.getVal());
		if (changed)
		{
			this.setWidth();
			this.onChange();
		}

		return changed;
	},

	setWidth: function()
	{
		// has the width changed?
		if (this.stageWidth !== this.getTextWidth(this.val))
		{
			// update the textarea width
			var width = this.stageWidth + TextElement.padding;
			this.$input.width(width);
		}
	},

	onChange: $.noop
},
{
	padding: 20
});


/**
 * Modal
 */
Garnish.Modal = Garnish.Base.extend({

	$container: null,
	$shade: null,

	visible: false,

	dragger: null,

	desiredWidth: null,
	desiredHeight: null,
	resizeDragger: null,
	resizeStartWidth: null,
	resizeStartHeight: null,

	init: function(container, settings)
	{
		// Param mapping
		if (!settings && $.isPlainObject(container))
		{
			// (settings)
			settings = container;
			container = null;
		}

		this.setSettings(settings, Garnish.Modal.defaults);

		// Create the shade
		this.$shade = $('<div class="'+this.settings.shadeClass+'"/>');

		// If the container is already set, drop the shade below it.
		if (container)
		{
			this.$shade.insertBefore(container);
		}
		else
		{
			this.$shade.appendTo(Garnish.$bod);
		}

		if (container)
		{
			this.setContainer(container);

			if (this.settings.autoShow)
			{
				this.show();
			}
		}

		Garnish.Modal.instances.push(this);
	},

	setContainer: function(container)
	{
		this.$container = $(container);

		// Is this already a modal?
		if (this.$container.data('modal'))
		{
			Garnish.log('Double-instantiating a modal on an element');
			this.$container.data('modal').destroy();
		}

		this.$container.data('modal', this);

		if (this.settings.draggable)
		{
			this.dragger = new Garnish.DragMove(this.$container, {
				handle: (this.settings.dragHandleSelector ? this.$container.find(this.settings.dragHandleSelector) : this.$container)
			});
		}

		if (this.settings.resizable)
		{
			var $resizeDragHandle = $('<div class="resizehandle"/>').appendTo(this.$container);

			this.resizeDragger = new Garnish.BaseDrag($resizeDragHandle, {
				onDragStart:   $.proxy(this, '_onResizeStart'),
				onDrag:        $.proxy(this, '_onResize')
			});
		}

		this.addListener(this.$container, 'click', function(ev) {
			ev.stopPropagation();
		});

		// Show it if we're late to the party
		if (this.visible)
		{
			this.show();
		}
	},

	show: function()
	{
		// Close other modals as needed
		if (this.settings.closeOtherModals && Garnish.Modal.visibleModal && Garnish.Modal.visibleModal != this)
		{
			Garnish.Modal.visibleModal.hide();
		}

		if (this.$container)
		{
			// Move it to the end of <body> so it gets the highest sub-z-index
			this.$shade.appendTo(Garnish.$bod);
			this.$container.appendTo(Garnish.$bod);

			this.$container.show();
			this.updateSizeAndPosition();

			this.$shade.velocity('fadeIn', { duration: 50 });
			this.$container.delay(50).velocity('fadeIn', {
				complete: $.proxy(this, 'onFadeIn')
			});

			if (this.settings.hideOnShadeClick)
			{
				this.addListener(this.$shade, 'click', 'hide');
			}

			this.addListener(Garnish.$win, 'resize', 'updateSizeAndPosition');
		}

		if (this.settings.hideOnEsc)
		{
			Garnish.escManager.register(this, 'hide');
		}

		if (!this.visible)
		{
			this.visible = true;
			Garnish.Modal.visibleModal = this;

			this.settings.onShow();
		}
	},

	quickShow: function()
	{
		this.show();

		if (this.$container)
		{
			this.$container.velocity('stop');
			this.$container.show().css('opacity', 1);

			this.$shade.velocity('stop');
			this.$shade.show().css('opacity', 1);
		}
	},

	hide: function(ev)
	{
		if (ev)
		{
			ev.stopPropagation();
		}

		if (this.$container)
		{
			this.$container.velocity('fadeOut', { duration: Garnish.FX_DURATION });
			this.$shade.velocity('fadeOut', {
				duration: Garnish.FX_DURATION,
				complete: $.proxy(this, 'onFadeOut')
			});

			if (this.settings.hideOnShadeClick)
			{
				this.removeListener(this.$shade, 'click');
			}

			this.removeListener(Garnish.$win, 'resize');
		}

		this.visible = false;
		Garnish.Modal.visibleModal = null;

		if (this.settings.hideOnEsc)
		{
			Garnish.escManager.unregister(this);
		}

		this.settings.onHide();
	},

	quickHide: function()
	{
		this.hide();

		if (this.$container)
		{
			this.$container.velocity('stop');
			this.$container.css('opacity', 0).hide();

			this.$shade.velocity('stop');
			this.$shade.css('opacity', 0).hide();
		}
	},

	updateSizeAndPosition: function()
	{
		if (!this.$container)
		{
			return;
		}

		this.$container.css({
			'width':      (this.desiredWidth ? Math.max(this.desiredWidth, 200) : ''),
			'height':     (this.desiredHeight ? Math.max(this.desiredHeight, 200) : ''),
			'min-width':  '',
			'min-height': ''
		});

		// Set the width first so that the height can adjust for the width
		this.updateSizeAndPosition._windowWidth = Garnish.$win.width();
		this.updateSizeAndPosition._width = Math.min(this.getWidth(), this.updateSizeAndPosition._windowWidth - 20);

		this.$container.css({
			'width':      this.updateSizeAndPosition._width,
			'min-width':  this.updateSizeAndPosition._width,
			'left':       Math.round((this.updateSizeAndPosition._windowWidth - this.updateSizeAndPosition._width) / 2)
		});

		// Now set the height
		this.updateSizeAndPosition._windowHeight = Garnish.$win.height();
		this.updateSizeAndPosition._height = Math.min(this.getHeight(), this.updateSizeAndPosition._windowHeight - 20);

		this.$container.css({
			'height':     this.updateSizeAndPosition._height,
			'min-height': this.updateSizeAndPosition._height,
			'top':        Math.round((this.updateSizeAndPosition._windowHeight - this.updateSizeAndPosition._height) / 2)
		});
	},

	onFadeIn: function()
	{
		this.settings.onFadeIn();
	},

	onFadeOut: function()
	{
		this.settings.onFadeOut();
	},

	getHeight: function()
	{
		if (!this.$container)
		{
			throw 'Attempted to get the height of a modal whose container has not been set.';
		}

		if (!this.visible)
		{
			this.$container.show();
		}

		this.getHeight._height = this.$container.outerHeight();

		if (!this.visible)
		{
			this.$container.hide();
		}

		return this.getHeight._height;
	},

	getWidth: function()
	{
		if (!this.$container)
		{
			throw 'Attempted to get the width of a modal whose container has not been set.';
		}

		if (!this.visible)
		{
			this.$container.show();
		}

		this.getWidth._width = this.$container.outerWidth();

		if (!this.visible)
		{
			this.$container.hide();
		}

		return this.getWidth._width;
	},

	_onResizeStart: function()
	{
		this.resizeStartWidth = this.getWidth();
		this.resizeStartHeight = this.getHeight();
	},

	_onResize: function()
	{
		if (Garnish.ltr)
		{
			this.desiredWidth = this.resizeStartWidth + (this.resizeDragger.mouseDistX * 2);
		}
		else
		{
			this.desiredWidth = this.resizeStartWidth - (this.resizeDragger.mouseDistX * 2);
		}

		this.desiredHeight = this.resizeStartHeight + (this.resizeDragger.mouseDistY * 2);

		this.updateSizeAndPosition();
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		if (this.$container)
		{
			this.$container.removeData('modal');
		}

		if (this.dragger)
		{
			this.dragger.destroy();
		}

		if (this.resizeDragger)
		{
			this.resizeDragger.destroy();
		}

		this.base();
	}
},
{
	relativeElemPadding: 8,
	defaults: {
		autoShow: true,
		draggable: false,
		dragHandleSelector: null,
		resizable: false,
		onShow: $.noop,
		onHide: $.noop,
		onFadeIn: $.noop,
		onFadeOut: $.noop,
		closeOtherModals: true,
		hideOnEsc: true,
		hideOnShadeClick: true,
		shadeClass: 'modal-shade'
	},
	instances: [],
	visibleModal: null
});


/**
 * Nice Text
 */
Garnish.NiceText = Garnish.Base.extend({

	$input: null,
	$hint: null,
	$stage: null,
	$charsLeft: null,
	autoHeight: null,
	maxLength: null,
	showCharsLeft: false,
	showingHint: false,
	val: null,
	inputBoxSizing: 'content-box',
	height: null,
	minHeight: null,
	initialized: false,

	init: function(input, settings)
	{
		this.$input = $(input);
		this.settings = $.extend({}, Garnish.NiceText.defaults, settings);

		this.maxLength = this.$input.attr('maxlength');

		if (this.maxLength)
		{
			this.maxLength = parseInt(this.maxLength);
		}

		if (this.maxLength && (this.settings.showCharsLeft || Garnish.hasAttr(this.$input, 'data-show-chars-left')))
		{
			this.showCharsLeft = true;

			// Remove the maxlength attribute
			this.$input.removeAttr('maxlength');
		}

		// Is this already a transparent text input?
		if (this.$input.data('nicetext'))
		{
			Garnish.log('Double-instantiating a transparent text input on an element');
			this.$input.data('nicetext').destroy();
		}

		this.$input.data('nicetext', this);

		this.getVal();

		this.autoHeight = (this.settings.autoHeight && this.$input.prop('nodeName') == 'TEXTAREA');

		if (this.autoHeight)
		{
			this.minHeight = this.getHeightForValue('');
			this.updateHeight();

			this.addListener(Garnish.$win, 'resize', 'updateHeight');
		}

		if (this.settings.hint)
		{
			this.$hintContainer = $('<div class="texthint-container"/>').insertBefore(this.$input);
			this.$hint = $('<div class="texthint">'+this.settings.hint+'</div>').appendTo(this.$hintContainer);
			this.$hint.css({
				top:  (parseInt(this.$input.css('borderTopWidth'))  + parseInt(this.$input.css('paddingTop'))),
				left: (parseInt(this.$input.css('borderLeftWidth')) + parseInt(this.$input.css('paddingLeft')) + 1)
			});
			Garnish.copyTextStyles(this.$input, this.$hint);

			if (this.val)
			{
				this.$hint.hide();
			}
			else
			{
				this.showingHint = true;
			}

			// Focus the input when clicking on the hint
			this.addListener(this.$hint, 'mousedown', function(ev) {
				ev.preventDefault();
				this.$input.focus();
			});
		}

		if (this.showCharsLeft)
		{
			this.$charsLeft = $('<div class="'+this.settings.charsLeftClass+'"/>').insertAfter(this.$input);
			this.updateCharsLeft();
		}

		this.addListener(this.$input, 'textchange', 'onTextChange');

		this.initialized = true;
	},

	getVal: function()
	{
		this.val = this.$input.val();
		return this.val;
	},

	showHint: function()
	{
		this.$hint.velocity('fadeIn', {
			complete: Garnish.NiceText.hintFadeDuration
		});

		this.showingHint = true;
	},

	hideHint: function()
	{
		this.$hint.velocity('fadeOut', {
			complete: Garnish.NiceText.hintFadeDuration
		});

		this.showingHint = false;
	},

	onTextChange: function()
	{
		this.getVal();

		if (this.$hint)
		{
			if (this.showingHint && this.val)
			{
				this.hideHint();
			}
			else if (!this.showingHint && !this.val)
			{
				this.showHint();
			}
		}

		if (this.autoHeight)
		{
			this.updateHeight();
		}

		if (this.showCharsLeft)
		{
			this.updateCharsLeft();
		}
	},

	buildStage: function()
	{
		this.$stage = $('<stage/>').appendTo(Garnish.$bod);

		// replicate the textarea's text styles
		this.$stage.css({
			display: 'block',
			position: 'absolute',
			top: -9999,
			left: -9999
		});

		this.inputBoxSizing = this.$input.css('box-sizing');

		if (this.inputBoxSizing == 'border-box')
		{
			this.$stage.css({
				'border-top':          this.$input.css('border-top'),
				'border-right':        this.$input.css('border-right'),
				'border-bottom':       this.$input.css('border-bottom'),
				'border-left':         this.$input.css('border-left'),
				'padding-top':         this.$input.css('padding-top'),
				'padding-right':       this.$input.css('padding-right'),
				'padding-bottom':      this.$input.css('padding-bottom'),
				'padding-left':        this.$input.css('padding-left'),
				'-webkit-box-sizing':  this.inputBoxSizing,
				'-moz-box-sizing':     this.inputBoxSizing,
				'box-sizing':          this.inputBoxSizing
			});
		}

		Garnish.copyTextStyles(this.$input, this.$stage);
	},

	getHeightForValue: function(val)
	{
		if (!this.$stage)
		{
			this.buildStage();
		}

		if (this.inputBoxSizing == 'border-box')
		{
			this.$stage.css('width', this.$input.outerWidth());
		}
		else
		{
			this.$stage.css('width', this.$input.width());
		}

		if (!val)
		{
			val = '&nbsp;';
			for (var i = 1; i < this.$input.prop('rows'); i++)
			{
				val += '<br/>&nbsp;';
			}
		}
		else
		{
			// Ampersand entities
			val = val.replace(/&/g, '&amp;');

			// < and >
			val = val.replace(/</g, '&lt;');
			val = val.replace(/>/g, '&gt;');

			// Spaces
			val = val.replace(/ /g, '&nbsp;');

			// Line breaks
			val = val.replace(/[\n\r]$/g, '<br/>&nbsp;');
			val = val.replace(/[\n\r]/g, '<br/>');
		}

		this.$stage.html(val);

		if (this.inputBoxSizing == 'border-box')
		{
			this.getHeightForValue._height = this.$stage.outerHeight();
		}
		else
		{
			this.getHeightForValue._height = this.$stage.height();
		}

		if (this.minHeight && this.getHeightForValue._height < this.minHeight)
		{
			this.getHeightForValue._height = this.minHeight;
		}

		return this.getHeightForValue._height;
	},

	updateHeight: function()
	{
		// has the height changed?
		if (this.height !== (this.height = this.getHeightForValue(this.val)))
		{
			this.$input.css('min-height', this.height);

			if (this.initialized)
			{
				this.onHeightChange();
			}
		}
	},

	onHeightChange: function()
	{
		this.settings.onHeightChange();
	},

	updateCharsLeft: function()
	{
		this.updateCharsLeft._charsLeft = this.maxLength - this.val.length;
		this.$charsLeft.text(this.updateCharsLeft._charsLeft);

		if (this.updateCharsLeft._charsLeft >= 0)
		{
			this.$charsLeft.removeClass(this.settings.negativeCharsLeftClass);
		}
		else
		{
			this.$charsLeft.addClass(this.settings.negativeCharsLeftClass);
		}
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$input.removeData('nicetext');

		if (this.$hint)
		{
			this.$hint.remove();
		}

		if (this.$stage)
		{
			this.$stage.remove();
		}

		this.base();
	}
},
{
	interval: 100,
	hintFadeDuration: 50,
	defaults: {
		autoHeight:             true,
		showCharsLeft:          false,
		charsLeftClass:         'chars-left',
		negativeCharsLeftClass: 'negative-chars-left',
		onHeightChange:         $.noop
	}
});


/**
 * Pill
 */
Garnish.Pill = Garnish.Base.extend({

	$outerContainer: null,
	$innerContainer: null,
	$btns: null,
	$selectedBtn: null,
	$input: null,

	init: function(outerContainer)
	{
		this.$outerContainer = $(outerContainer);

		// Is this already a pill?
		if (this.$outerContainer.data('pill'))
		{
			Garnish.log('Double-instantiating a pill on an element');
			this.$outerContainer.data('pill').destroy();
		}

		this.$outerContainer.data('pill', this);

		this.$innerContainer = this.$outerContainer.find('.btngroup:first');
		this.$btns = this.$innerContainer.find('.btn');
		this.$selectedBtn = this.$btns.filter('.active:first');
		this.$input = this.$outerContainer.find('input:first');

		Garnish.preventOutlineOnMouseFocus(this.$innerContainer);
		this.addListener(this.$btns, 'mousedown', 'onMouseDown');
		this.addListener(this.$innerContainer, 'keydown', 'onKeyDown');
	},

	select: function(btn)
	{
		this.$selectedBtn.removeClass('active');
		var $btn = $(btn);
		$btn.addClass('active');
		this.$input.val($btn.attr('data-value'));
		this.$selectedBtn = $btn;
	},

	selectNext: function()
	{
		if (!this.$selectedBtn.length)
		{
			this.select(this.$btns[this.$btns.length-1]);
		}
		else
		{
			var nextIndex = this._getSelectedBtnIndex() + 1;

			if (typeof this.$btns[nextIndex] != 'undefined')
			{
				this.select(this.$btns[nextIndex]);
			}
		}
	},

	selectPrev: function()
	{
		if (!this.$selectedBtn.length)
		{
			this.select(this.$btns[0]);
		}
		else
		{
			var prevIndex = this._getSelectedBtnIndex() - 1;

			if (typeof this.$btns[prevIndex] != 'undefined')
			{
				this.select(this.$btns[prevIndex]);
			}
		}
	},

	onMouseDown: function(ev)
	{
		this.select(ev.currentTarget);
	},

	_getSelectedBtnIndex: function()
	{
		if (typeof this.$selectedBtn[0] != 'undefined')
		{
			return $.inArray(this.$selectedBtn[0], this.$btns);
		}
		else
		{
			return -1;
		}
	},

	onKeyDown: function(ev)
	{
		switch (ev.keyCode)
		{
			case Garnish.RIGHT_KEY:
			{
				if (Garnish.ltr)
				{
					this.selectNext();
				}
				else
				{
					this.selectPrev();
				}

				ev.preventDefault();
				break;
			}

			case Garnish.LEFT_KEY:
			{
				if (Garnish.ltr)
				{
					this.selectPrev();
				}
				else
				{
					this.selectNext();
				}

				ev.preventDefault();
				break;
			}
		}
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$outerContainer.removeData('pill');
		this.base();
	}
});


/**
 * Select
 */
Garnish.Select = Garnish.Base.extend({

	// Properties
	// =========================================================================

	$container: null,
	$items: null,
	$selectedItems: null,

	mousedownX: null,
	mousedownY: null,
	mouseUpTimeout: null,
	callbackFrame: null,

	$focusable: null,
	$first: null,
	first: null,
	$last: null,
	last: null,

	// Public methods
	// =========================================================================

	/**
	 * Constructor
	 */
	init: function(container, items, settings)
	{
		this.$container = $(container);

		// Param mapping
		if (!settings && $.isPlainObject(items))
		{
			// (container, settings)
			settings = items;
			items = null;
		}

		// Is this already a select?
		if (this.$container.data('select'))
		{
			Garnish.log('Double-instantiating a select on an element');
			this.$container.data('select').destroy();
		}

		this.$container.data('select', this);

		this.setSettings(settings, Garnish.Select.defaults);

		this.$items = $();
		this.$selectedItems = $();

		this.addItems(items);

		// --------------------------------------------------------------------

		if (this.settings.allowEmpty)
		{
			this.addListener(this.$container, 'click', function(ev)
			{
				if (this.ignoreClick)
				{
					this.ignoreClick = false;
				}
				else
				{
					// Deselect all items on container click
					this.deselectAll(true);
				}
			});
		}
	},

	// --------------------------------------------------------------------

	/**
	 * Get Item Index
	 */
	getItemIndex: function($item)
	{
		return this.$items.index($item[0]);
	},

	/**
	 * Is Selected?
	 */
	isSelected: function(item)
	{
		if (Garnish.isJquery(item))
		{
			if (!item[0])
			{
				return false;
			}

			item = item[0];
		}

		return ($.inArray(item, this.$selectedItems) != -1);
	},

	/**
	 * Select Item
	 */
	selectItem: function($item)
	{
		if (!this.settings.multi)
		{
			this.deselectAll();
		}

		this.$first = this.$last = $item;
		this.first = this.last = this.getItemIndex($item);

		this.setFocusableItem($item);
		$item.focus();

		this._selectItems($item);
	},

	selectAll: function()
	{
		if (!this.settings.multi || !this.$items.length)
		{
			return;
		}

		this.first = 0;
		this.last = this.$items.length-1;
		this.$first = $(this.$items[this.first]);
		this.$last = $(this.$items[this.last]);

		this._selectItems(this.$items);
	},

	/**
	 * Select Range
	 */
	selectRange: function($item)
	{
		if (!this.settings.multi)
		{
			return this.selectItem($item);
		}

		this.deselectAll();

		this.$last = $item;
		this.last = this.getItemIndex($item);

		this.setFocusableItem($item);
		$item.focus();

		// prepare params for $.slice()
		if (this.first < this.last)
		{
			var sliceFrom = this.first,
				sliceTo = this.last + 1;
		}
		else
		{
			var sliceFrom = this.last,
				sliceTo = this.first + 1;
		}

		this._selectItems(this.$items.slice(sliceFrom, sliceTo));
	},

	/**
	 * Deselect Item
	 */
	deselectItem: function($item)
	{
		var index = this.getItemIndex($item);
		if (this.first === index) this.$first = this.first = null;
		if (this.last === index) this.$last = this.last = null;

		this._deselectItems($item);
	},

	/**
	 * Deselect All
	 */
	deselectAll: function(clearFirst)
	{
		if (clearFirst)
		{
			this.$first = this.first = this.$last = this.last = null;
		}

		this._deselectItems(this.$items);
	},

	/**
	 * Deselect Others
	 */
	deselectOthers: function($item)
	{
		this.deselectAll();
		this.selectItem($item);
	},

	/**
	 * Toggle Item
	 */
	toggleItem: function($item)
	{
		if (!this.isSelected($item))
		{
			this.selectItem($item);
		}
		else
		{
			if (this._canDeselect($item))
			{
				this.deselectItem($item);
			}
		}
	},

	// --------------------------------------------------------------------

	clearMouseUpTimeout: function()
	{
		clearTimeout(this.mouseUpTimeout);
	},

	getFirstItem: function()
	{
		if (this.$items.length)
		{
			return $(this.$items[0]);
		}
	},

	getLastItem: function()
	{
		if (this.$items.length)
		{
			return $(this.$items[this.$items.length-1]);
		}
	},

	isPreviousItem: function(index)
	{
		return (index > 0);
	},

	isNextItem: function(index)
	{
		return (index < this.$items.length-1);
	},

	getPreviousItem: function(index)
	{
		if (this.isPreviousItem(index))
		{
			return $(this.$items[index-1]);
		}
	},

	getNextItem: function(index)
	{
		if (this.isNextItem(index))
		{
			return $(this.$items[index+1]);
		}
	},

	getItemToTheLeft: function(index)
	{
		var func = (Garnish.ltr ? 'Previous' : 'Next');

		if (this['is'+func+'Item'](index))
		{
			if (this.settings.horizontal)
			{
				return this['get'+func+'Item'](index);
			}
			if (!this.settings.vertical)
			{
				return this.getClosestItem(index, Garnish.X_AXIS, '<');
			}
		}
	},

	getItemToTheRight: function(index)
	{
		var func = (Garnish.ltr ? 'Next' : 'Previous');

		if (this['is'+func+'Item'](index))
		{
			if (this.settings.horizontal)
			{
				return this['get'+func+'Item'](index);
			}
			else if (!this.settings.vertical)
			{
				return this.getClosestItem(index, Garnish.X_AXIS, '>');
			}
		}
	},

	getItemAbove: function(index)
	{
		if (this.isPreviousItem(index))
		{
			if (this.settings.vertical)
			{
				return this.getPreviousItem(index);
			}
			else if (!this.settings.horizontal)
			{
				return this.getClosestItem(index, Garnish.Y_AXIS, '<');
			}
		}
	},

	getItemBelow: function(index)
	{
		if (this.isNextItem(index))
		{
			if (this.settings.vertical)
			{
				return this.getNextItem(index);
			}
			else if (!this.settings.horizontal)
			{
				return this.getClosestItem(index, Garnish.Y_AXIS, '>');
			}
		}
	},

	getClosestItem: function(index, axis, dir)
	{
		var axisProps = Garnish.Select.closestItemAxisProps[axis],
			dirProps = Garnish.Select.closestItemDirectionProps[dir];

		var $thisItem = $(this.$items[index]),
			thisOffset = $thisItem.offset(),
			thisMidpoint = thisOffset[axisProps.midpointOffset] + Math.round($thisItem[axisProps.midpointSizeFunc]()/2),
			otherRowPos = null,
			smallestMidpointDiff = null,
			$closestItem = null;

		// Go the other way if this is the X axis and a RTL page
		if (Garnish.rtl && axis == Garnish.X_AXIS)
		{
			var step = dirProps.step * -1;
		}
		else
		{
			var step = dirProps.step;
		}

		for (var i = index + step; (typeof this.$items[i] != 'undefined'); i += step)
		{
			var $otherItem = $(this.$items[i]),
				otherOffset = $otherItem.offset();

			// Are we on the next row yet?
			if (dirProps.isNextRow(otherOffset[axisProps.rowOffset], thisOffset[axisProps.rowOffset]))
			{
				// Is this the first time we've seen this row?
				if (otherRowPos === null)
				{
					otherRowPos = otherOffset[axisProps.rowOffset];
				}
				// Have we gone too far?
				else if (otherOffset[axisProps.rowOffset] != otherRowPos)
				{
					break;
				}

				var otherMidpoint = otherOffset[axisProps.midpointOffset] + Math.round($otherItem[axisProps.midpointSizeFunc]()/2),
					midpointDiff = Math.abs(thisMidpoint - otherMidpoint);

				// Are we getting warmer?
				if (smallestMidpointDiff === null || midpointDiff < smallestMidpointDiff)
				{
					smallestMidpointDiff = midpointDiff;
					$closestItem = $otherItem;
				}
				// Getting colder?
				else
				{
					break;
				}
			}
			// Getting colder?
			else if (dirProps.isWrongDirection(otherOffset[axisProps.rowOffset], thisOffset[axisProps.rowOffset]))
			{
				break;
			}
		}

		return $closestItem;
	},

	getFurthestItemToTheLeft: function(index)
	{
		return this.getFurthestItem(index, 'ToTheLeft');
	},

	getFurthestItemToTheRight: function(index)
	{
		return this.getFurthestItem(index, 'ToTheRight');
	},

	getFurthestItemAbove: function(index)
	{
		return this.getFurthestItem(index, 'Above');
	},

	getFurthestItemBelow: function(index)
	{
		return this.getFurthestItem(index, 'Below');
	},

	getFurthestItem: function(index, dir)
	{
		var $item, $testItem;

		while ($testItem = this['getItem'+dir](index))
		{
			$item = $testItem;
			index = this.getItemIndex($item);
		}

		return $item;
	},

	// --------------------------------------------------------------------

	/**
	 * totalSelected getter
	 */
	get totalSelected()
	{
		return this.getTotalSelected();
	},

	/**
	 * Get Total Selected
	 */
	getTotalSelected: function()
	{
		return this.$selectedItems.length;
	},

	/**
	 * Add Items
	 */
	addItems: function(items)
	{
		var $items = $(items);

		for (var i = 0; i < $items.length; i++)
		{
			var item = $items[i];

			// Make sure this element doesn't belong to another selector
			if ($.data(item, 'select'))
			{
				Garnish.log('Element was added to more than one selector');
				$.data(item, 'select').removeItems(item);
			}

			// Add the item
			$.data(item, 'select', this);
			this.$items = this.$items.add(item);

			// Get the handle
			if (this.settings.handle)
			{
				if (typeof this.settings.handle == 'object')
				{
					var $handle = $(this.settings.handle);
				}
				else if (typeof this.settings.handle == 'string')
				{
					var $handle = $(item).find(this.settings.handle);
				}
				else if (typeof this.settings.handle == 'function')
				{
					var $handle = $(this.settings.handle(item));
				}
			}
			else
			{
				var $handle = $(item);
			}

			$.data(item, 'select-handle', $handle);
			$handle.data('select-item', item);

			this.addListener($handle, 'mousedown', 'onMouseDown');
			this.addListener($handle, 'mouseup', 'onMouseUp');
			this.addListener($handle, 'keydown', 'onKeyDown');
			this.addListener($handle, 'click', function(ev)
			{
				this.ignoreClick = true;
			});
		}

		this.updateIndexes();
	},

	/**
	 * Remove Items
	 */
	removeItems: function(items)
	{
		items = $.makeArray(items);

		for (var i = 0; i < items.length; i++)
		{
			var item = items[i];

			// Make sure we actually know about this item
			var index = $.inArray(item, this.$items);
			if (index != -1)
			{
				this._deinitItem(item);
				this.$items.splice(index, 1);
				this.$selectedItems = this.$selectedItems.not(item);
			}
		}

		this.updateIndexes();
	},

	/**
	 * Remove All Items
	 */
	removeAllItems: function()
	{
		for (var i = 0; i < this.$items.length; i++)
		{
			this._deinitItem(this.$items[i]);
		}

		this.$items = $();
		this.$selectedItems = $();
		this.updateIndexes();
	},

	/**
	 * Update First/Last indexes
	 */
	updateIndexes: function()
	{
		if (this.first !== null)
		{
			this.first = this.getItemIndex(this.$first);
			this.last = this.getItemIndex(this.$last);
			this.setFocusableItem(this.$first);
		}
		else if (this.$items.length)
		{
			this.setFocusableItem($(this.$items[0]));
		}
	},

	/**
	 * Reset Item Order
	 */
	 resetItemOrder: function()
	 {
	 	this.$items = $().add(this.$items);
	 	this.$selectedItems = $().add(this.$selectedItems);
	 	this.updateIndexes();
	 },

	/**
	 * Sets the focusable item.
	 *
	 * We only want to have one focusable item per selection list, so that the user
	 * doesn't have to tab through a million items.
	 *
	 * @param object $item
	 */
	setFocusableItem: function($item)
	{
		if (this.$focusable)
		{
			this.$focusable.removeAttr('tabindex');
		}

		this.$focusable = $item.attr('tabindex', '0');
	},

	// --------------------------------------------------------------------

	/**
	 * Get Selected Items
	 */
	getSelectedItems: function()
	{
		return this.$selectedItems;
	},

	/**
	 * Destroy
	 */
	destroy: function()
	{
		this.$container.removeData('select');
		this.removeAllItems();
		this.base();
	},

	// Events
	// -------------------------------------------------------------------------

	/**
	 * On Mouse Down
	 */
	onMouseDown: function(ev)
	{
		// ignore right clicks
		if (ev.which != Garnish.PRIMARY_CLICK)
		{
			return;
		}

		// Enforce the filter
		if (this.settings.filter && !$(ev.target).is(this.settings.filter))
		{
			return;
		}

		this.mousedownX = ev.pageX;
		this.mousedownY = ev.pageY;

		var $item = $($.data(ev.currentTarget, 'select-item'));

		if (ev.metaKey || ev.ctrlKey)
		{
			this.toggleItem($item);
		}
		else if (this.first !== null && ev.shiftKey)
		{
			this.selectRange($item);
		}
	},

	/**
	 * On Mouse Up
	 */
	onMouseUp: function(ev)
	{
		// ignore right clicks
		if (ev.which != Garnish.PRIMARY_CLICK)
		{
			return;
		}

		// Enfore the filter
		if (this.settings.filter && !$(ev.target).is(this.settings.filter))
		{
			return;
		}

		var $item = $($.data(ev.currentTarget, 'select-item'));

		// was this a click?
		if (! (ev.metaKey || ev.ctrlKey) && ! ev.shiftKey && Garnish.getDist(this.mousedownX, this.mousedownY, ev.pageX, ev.pageY) < 1)
		{
			// If this is already selected, wait a moment to see if this is a double click before making any rash decisions
			if (this.isSelected($item))
			{
				this.clearMouseUpTimeout();

				this.mouseUpTimeout = setTimeout($.proxy(function() {
					this.deselectOthers($item);
				}, this), 300);
			}
			else
			{
				this.deselectAll();
				this.selectItem($item);
			}
		}
	},

	/**
	 * On Key Down
	 */
	onKeyDown: function(ev)
	{
		var metaKey = (ev.metaKey || ev.ctrlKey);

		if (this.settings.arrowsChangeSelection || !this.$focusable.length)
		{
			var anchor = ev.shiftKey ? this.last : this.first;
		}
		else
		{
			var anchor = $.inArray(this.$focusable[0], this.$items);

			if (anchor == -1)
			{
				anchor = 0;
			}
		}

		// Ok, what are we doing here?
		switch (ev.keyCode)
		{
			case Garnish.LEFT_KEY:
			{
				ev.preventDefault();

				// Select the last item if none are selected
				if (this.first === null)
				{
					if (Garnish.ltr)
					{
						var $item = this.getLastItem();
					}
					else
					{
						var $item = this.getFirstItem();
					}
				}
				else
				{
					if (metaKey)
					{
						var $item = this.getFurthestItemToTheLeft(anchor);
					}
					else
					{
						var $item = this.getItemToTheLeft(anchor);
					}
				}

				break;
			}

			case Garnish.RIGHT_KEY:
			{
				ev.preventDefault();

				// Select the first item if none are selected
				if (this.first === null)
				{
					if (Garnish.ltr)
					{
						var $item = this.getFirstItem();
					}
					else
					{
						var $item = this.getLastItem();
					}
				}
				else
				{
					if (metaKey)
					{
						var $item = this.getFurthestItemToTheRight(anchor);
					}
					else
					{
						var $item = this.getItemToTheRight(anchor);
					}
				}

				break;
			}

			case Garnish.UP_KEY:
			{
				ev.preventDefault();

				// Select the last item if none are selected
				if (this.first === null)
				{
					var $item = this.getLastItem();
				}
				else
				{
					if (metaKey)
					{
						var $item = this.getFurthestItemAbove(anchor);
					}
					else
					{
						var $item = this.getItemAbove(anchor);
					}

					if (!$item)
					{
						$item = this.getFirstItem();
					}
				}

				break;
			}

			case Garnish.DOWN_KEY:
			{
				ev.preventDefault();

				// Select the first item if none are selected
				if (this.first === null)
				{
					var $item = this.getFirstItem();
				}
				else
				{
					if (metaKey)
					{
						var $item = this.getFurthestItemBelow(anchor);
					}
					else
					{
						var $item = this.getItemBelow(anchor);
					}

					if (!$item)
					{
						$item = this.getLastItem();
					}
				}

				break;
			}

			case Garnish.SPACE_KEY:
			{
				if (!metaKey)
				{
					ev.preventDefault();

					if (this.isSelected(this.$focusable))
					{
						if (this._canDeselect(this.$focusable))
						{
							this.deselectItem(this.$focusable);
						}
					}
					else
					{
						this.selectItem(this.$focusable);
					}
				}

				break;
			}

			case Garnish.A_KEY:
			{
				if (metaKey)
				{
					ev.preventDefault();
					this.selectAll();
				}

				break;
			}
		}

		// Is there an item queued up for focus/selection?
		if ($item && $item.length)
		{
			if (this.settings.arrowsChangeSelection)
			{
				// select it
				if (this.first !== null && ev.shiftKey)
				{
					this.selectRange($item);
				}
				else
				{
					this.deselectAll();
					this.selectItem($item);
				}
			}
			else
			{
				// just set the new item to be focussable
				this.setFocusableItem($item);
				$item.focus();
			}
		}
	},

	/**
	 * Set Callback Timeout
	 */
	onSelectionChange: function()
	{
		if (this.callbackFrame)
		{
			Garnish.cancelAnimationFrame(this.callbackFrame);
			this.callbackFrame = null;
		}

		this.callbackFrame = Garnish.requestAnimationFrame($.proxy(function()
		{
			this.callbackFrame = null;
			this.trigger('selectionChange');
			this.settings.onSelectionChange();
		}, this));
	},

	// Private methods
	// =========================================================================

	_canDeselect: function($items)
	{
		return (this.settings.allowEmpty || this.totalSelected > $items.length);
	},

	_selectItems: function($items)
	{
		$items.addClass(this.settings.selectedClass);
		this.$selectedItems = this.$selectedItems.add($items);
		this.onSelectionChange();
	},

	_deselectItems: function($items)
	{
		$items.removeClass(this.settings.selectedClass);
		this.$selectedItems = this.$selectedItems.not($items);
		this.onSelectionChange();
	},

	/**
	 * Deinitialize an item.
	 */
	_deinitItem: function(item)
	{
		var $handle = $.data(item, 'select-handle');

		if ($handle)
		{
			$handle.removeData('select-item');
			this.removeAllListeners($handle);
		}

		$.removeData(item, 'select');
		$.removeData(item, 'select-handle');
	}
},

// Static Properties
// =============================================================================

{
	defaults: {
		selectedClass: 'sel',
		multi: false,
		allowEmpty: true,
		vertical: false,
		horizontal: false,
		arrowsChangeSelection: true,
		handle: null,
		filter: null,
		onSelectionChange: $.noop
	},

	closestItemAxisProps: {
		x: {
			midpointOffset:   'top',
			midpointSizeFunc: 'outerHeight',
			rowOffset:        'left'
		},
		y: {
			midpointOffset:   'left',
			midpointSizeFunc: 'outerWidth',
			rowOffset:        'top'
		}
	},

	closestItemDirectionProps: {
		'<': {
			step: -1,
			isNextRow: function(a, b) { return (a < b); },
			isWrongDirection: function(a, b) { return (a > b); }
		},
		'>': {
			step: 1,
			isNextRow: function(a, b) { return (a > b); },
			isWrongDirection: function(a, b) { return (a < b); }
		}
	}
});


/**
 * Select Menu
 */
Garnish.SelectMenu = Garnish.Menu.extend({

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

		settings = $.extend({}, Garnish.SelectMenu.defaults, settings);

		this.base(btn, options, settings, callback);

		this.selected = -1;
	},

	/**
	 * Build
	 */
	build: function()
	{
		this.base();

		if (this.selected != -1)
		{
			this._addSelectedOptionClass(this.selected);
		}
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
			{
				this.dom.options[this.selected].className = '';
			}

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

},
{
	defaults: {
		ulClass: 'menu select'
	}
});


})(jQuery);
