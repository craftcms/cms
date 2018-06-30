/** global: Craft */
/** global: Garnish */
// Set all the standard Craft.* stuff
$.extend(Craft,
    {
        navHeight: 48,

        /**
         * Get a translated message.
         *
         * @param {string} category
         * @param {string} message
         * @param {object} params
         * @return string
         */
        t: function(category, message, params) {
            if (
                typeof Craft.translations[category] !== 'undefined' &&
                typeof Craft.translations[category][message] !== 'undefined'
            ) {
                message = Craft.translations[category][message];
            }

            if (params) {
                for (var key in params) {
                    if (!params.hasOwnProperty(key)) {
                        continue;
                    }

                    message = message.replace('{' + key + '}', params[key]);
                }
            }

            return message;
        },

        formatDate: function(date) {
            if (typeof date !== 'object') {
                date = new Date(date);
            }

            return $.datepicker.formatDate(Craft.datepickerOptions.dateFormat, date);
        },

        /**
         * Formats a number.
         *
         * @param {string} number
         * @return string D3 format
         */
        formatNumber: function(number, format) {
            if(typeof format == 'undefined') {
                format = ',.0f';
            }

            var formatter = d3.formatLocale(d3FormatLocaleDefinition).format(format);

            return formatter(number);
        },

        /**
         * Escapes some HTML.
         *
         * @param {string} str
         * @return string
         */
        escapeHtml: function(str) {
            return $('<div/>').text(str).html();
        },

        /**
         * Returns the text in a string that might contain HTML tags.
         *
         * @param {string} str
         * @return string
         */
        getText: function(str) {
            return $('<div/>').html(str).text();
        },

        /**
         * Encodes a URI copmonent. Mirrors PHP's rawurlencode().
         *
         * @param {string} str
         * @return string
         * @see http://stackoverflow.com/questions/1734250/what-is-the-equivalent-of-javascripts-encodeuricomponent-in-php
         */
        encodeUriComponent: function(str) {
            str = encodeURIComponent(str);

            var differences = {
                '!': '%21',
                '*': '%2A',
                "'": '%27',
                '(': '%28',
                ')': '%29'
            };

            for (var chr in differences) {
                var re = new RegExp('\\' + chr, 'g');
                str = str.replace(re, differences[chr]);
            }

            return str;
        },

        /**
         * Selects the full value of a given text input.
         *
         * @param input
         */
        selectFullValue: function(input) {
            var $input = $(input);
            var val = $input.val();

            // Does the browser support setSelectionRange()?
            if (typeof $input[0].setSelectionRange !== 'undefined') {
                // Select the whole value
                var length = val.length * 2;
                $input[0].setSelectionRange(0, length);
            }
            else {
                // Refresh the value to get the cursor positioned at the end
                $input.val(val);
            }
        },

        /**
         * Formats an ID out of an input name.
         *
         * @param {string} inputName
         * @return string
         */
        formatInputId: function(inputName) {
            return this.rtrim(inputName.replace(/[\[\]\\]+/g, '-'), '-');
        },

        /**
         * @return string
         * @param path
         * @param params
         * @param baseUrl
         */
        getUrl: function(path, params, baseUrl) {
            if (typeof path !== 'string') {
                path = '';
            }

            // Return path if it appears to be an absolute URL.
            if (path.search('://') !== -1 || path[0] === '/') {
                return path;
            }

            path = Craft.trim(path, '/');

            var anchor = '';

            // Normalize the params
            if ($.isPlainObject(params)) {
                var aParams = [];

                for (var name in params) {
                    if (!params.hasOwnProperty(name)) {
                        continue;
                    }

                    var value = params[name];

                    if (name === '#') {
                        anchor = value;
                    }
                    else if (value !== null && value !== '') {
                        aParams.push(name + '=' + value);
                    }
                }

                params = aParams;
            }

            if (Garnish.isArray(params)) {
                params = params.join('&');
            }
            else {
                params = Craft.trim(params, '&?');
            }

            // Were there already any query string params in the path?
            var qpos = path.indexOf('?');
            if (qpos !== -1) {
                params = path.substr(qpos + 1) + (params ? '&' + params : '');
                path = path.substr(0, qpos);
            }

            // Put it all together
            var url;

            if (baseUrl) {
                url = baseUrl;

                if (path) {
                    // Does baseUrl already contain a path?
                    var pathMatch = url.match(/[&\?]p=[^&]+/);
                    if (pathMatch) {
                        url = url.replace(pathMatch[0], pathMatch[0] + '/' + path);
                        path = '';
                    }
                }
            }
            else {
                url = Craft.baseUrl;
            }

            // Does the base URL already have a query string?
            qpos = url.indexOf('?');
            if (qpos !== -1) {
                params = url.substr(qpos + 1) + (params ? '&' + params : '');
                url = url.substr(0, qpos);
            }

            if (!Craft.omitScriptNameInUrls && path) {
                if (Craft.usePathInfo) {
                    // Make sure that the script name is in the URL
                    if (url.search(Craft.scriptName) === -1) {
                        url = Craft.rtrim(url, '/') + '/' + Craft.scriptName;
                    }
                }
                else {
                    // Move the path into the query string params

                    // Is the p= param already set?
                    if (params && params.substr(0, 2) === 'p=') {
                        var basePath,
                            endPath = params.indexOf('&');

                        if (endPath !== -1) {
                            basePath = params.substring(2, endPath);
                            params = params.substr(endPath + 1);
                        }
                        else {
                            basePath = params.substr(2);
                            params = null;
                        }

                        // Just in case
                        basePath = Craft.rtrim(basePath);

                        path = basePath + (path ? '/' + path : '');
                    }

                    // Now move the path into the params
                    params = 'p=' + path + (params ? '&' + params : '');
                    path = null;
                }
            }

            if (path) {
                url = Craft.rtrim(url, '/') + '/' + path;
            }

            if (params) {
                url += '?' + params;
            }

            if (anchor) {
                url += '#' + anchor;
            }

            return url;
        },

        /**
         * @return string
         * @param path
         * @param params
         */
        getCpUrl: function(path, params) {
            return this.getUrl(path, params, Craft.baseCpUrl);
        },

        /**
         * @return string
         * @param path
         * @param params
         */
        getSiteUrl: function(path, params) {
            return this.getUrl(path, params, Craft.baseSiteUrl);
        },

        /**
         * Returns an action URL.
         *
         * @param {string} path
         * @param {object|string|undefined} params
         * @return string
         */
        getActionUrl: function(path, params) {
            return Craft.getUrl(path, params, Craft.actionUrl);
        },

        /**
         * Redirects the window to a given URL.
         *
         * @param {string} url
         */
        redirectTo: function(url) {
            document.location.href = this.getUrl(url);
        },

        /**
         * Returns a hidden CSRF token input, if CSRF protection is enabled.
         *
         * @return string
         */
        getCsrfInput: function() {
            if (Craft.csrfTokenName) {
                return '<input type="hidden" name="' + Craft.csrfTokenName + '" value="' + Craft.csrfTokenValue + '"/>';
            }
            else {
                return '';
            }
        },

        /**
         * Posts an action request to the server.
         *
         * @param {string} action
         * @param {object|undefined} data
         * @param {function|undefined} callback
         * @param {object|undefined} options
         * @return jqXHR
         */
        postActionRequest: function(action, data, callback, options) {
            // Make 'data' optional
            if (typeof data === 'function') {
                options = callback;
                callback = data;
                data = {};
            }

            var headers = {
                'X-Registered-Asset-Bundles': Object.keys(Craft.registeredAssetBundles).join(','),
                'X-Registered-Js-Files': Object.keys(Craft.registeredJsFiles).join(',')
            };

            if (Craft.csrfTokenValue && Craft.csrfTokenName) {
                headers['X-CSRF-Token'] = Craft.csrfTokenValue;
            }

            var jqXHR = $.ajax($.extend({
                url: Craft.getActionUrl(action),
                type: 'POST',
                dataType: 'json',
                headers: headers,
                data: data,
                success: callback,
                error: function(jqXHR, textStatus) {
                    if (callback) {
                        callback(null, textStatus, jqXHR);
                    }
                },
                complete: function(jqXHR, textStatus) {
                    if (textStatus !== 'success') {
                        if (typeof Craft.cp !== 'undefined') {
                            Craft.cp.displayError();
                        }
                        else {
                            alert(Craft.t('app', 'An unknown error occurred.'));
                        }
                    }
                }
            }, options));

            // Call the 'send' callback
            if (options && typeof options.send === 'function') {
                options.send(jqXHR);
            }

            return jqXHR;
        },

        _waitingOnAjax: false,
        _ajaxQueue: [],

        /**
         * Queues up an action request to be posted to the server.
         */
        queueActionRequest: function(action, data, callback, options) {
            // Make 'data' optional
            if (typeof data === 'function') {
                options = callback;
                callback = data;
                data = undefined;
            }

            Craft._ajaxQueue.push([action, data, callback, options]);

            if (!Craft._waitingOnAjax) {
                Craft._postNextActionRequestInQueue();
            }
        },

        _postNextActionRequestInQueue: function() {
            Craft._waitingOnAjax = true;

            var args = Craft._ajaxQueue.shift();

            Craft.postActionRequest(args[0], args[1], function(data, textStatus, jqXHR) {
                if (args[2] && typeof args[2] === 'function') {
                    args[2](data, textStatus, jqXHR);
                }

                if (Craft._ajaxQueue.length) {
                    Craft._postNextActionRequestInQueue();
                }
                else {
                    Craft._waitingOnAjax = false;
                }
            }, args[3]);
        },

        /**
         * Converts a comma-delimited string into an array.
         *
         * @param {string} str
         * @return array
         */
        stringToArray: function(str) {
            if (typeof str !== 'string') {
                return str;
            }

            var arr = str.split(',');
            for (var i = 0; i < arr.length; i++) {
                arr[i] = $.trim(arr[i]);
            }
            return arr;
        },

        /**
         * Expands an array of POST array-style strings into an actual array.
         *
         * @param {object} arr
         * @return array
         */
        expandPostArray: function(arr) {
            var expanded = {};
            var i;

            for (var key in arr) {
                if (!arr.hasOwnProperty(key)) {
                    continue;
                }

                var value = arr[key],
                    m = key.match(/^(\w+)(\[.*)?/),
                    keys;

                if (m[2]) {
                    // Get all of the nested keys
                    keys = m[2].match(/\[[^\[\]]*\]/g);

                    // Chop off the brackets
                    for (i = 0; i < keys.length; i++) {
                        keys[i] = keys[i].substring(1, keys[i].length - 1);
                    }
                }
                else {
                    keys = [];
                }

                keys.unshift(m[1]);

                var parentElem = expanded;

                for (i = 0; i < keys.length; i++) {
                    if (i < keys.length - 1) {
                        if (typeof parentElem[keys[i]] !== 'object') {
                            // Figure out what this will be by looking at the next key
                            if (!keys[i + 1] || parseInt(keys[i + 1]) == keys[i + 1]) {
                                parentElem[keys[i]] = [];
                            }
                            else {
                                parentElem[keys[i]] = {};
                            }
                        }

                        parentElem = parentElem[keys[i]];
                    }
                    else {
                        // Last one. Set the value
                        if (!keys[i]) {
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
         * @param obj1
         * @param obj2
         * @param sortObjectKeys Whether object keys should be sorted before being compared. Default is true.
         * @return boolean
         */
        compare: function(obj1, obj2, sortObjectKeys) {
            // Compare the types
            if (typeof obj1 !== typeof obj2) {
                return false;
            }

            if (typeof obj1 === 'object') {
                // Compare the lengths
                if (obj1.length !== obj2.length) {
                    return false;
                }

                // Is one of them an array but the other is not?
                if ((obj1 instanceof Array) !== (obj2 instanceof Array)) {
                    return false;
                }

                // If they're actual objects (not arrays), compare the keys
                if (!(obj1 instanceof Array)) {
                    if (typeof sortObjectKeys === 'undefined' || sortObjectKeys === true) {
                        if (!Craft.compare(Craft.getObjectKeys(obj1).sort(), Craft.getObjectKeys(obj2).sort())) {
                            return false;
                        }
                    }
                    else {
                        if (!Craft.compare(Craft.getObjectKeys(obj1), Craft.getObjectKeys(obj2))) {
                            return false;
                        }
                    }
                }

                // Compare each value
                for (var i in obj1) {
                    if (!obj1.hasOwnProperty(i)) {
                        continue;
                    }

                    if (!Craft.compare(obj1[i], obj2[i])) {
                        return false;
                    }
                }

                // All clear
                return true;
            }
            else {
                return (obj1 === obj2);
            }
        },

        /**
         * Returns an array of an object's keys.
         *
         * @param {object} obj
         * @return string
         */
        getObjectKeys: function(obj) {
            var keys = [];

            for (var key in obj) {
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
         * @param {string|object} chars
         * @return string
         */
        escapeChars: function(chars) {
            if (!Garnish.isArray(chars)) {
                chars = chars.split();
            }

            var escaped = '';

            for (var i = 0; i < chars.length; i++) {
                escaped += "\\" + chars[i];
            }

            return escaped;
        },

        /**
         * Trim characters off of the beginning of a string.
         *
         * @param {string} str
         * @param {string|object|undefined} chars The characters to trim off. Defaults to a space if left blank.
         * @return string
         */
        ltrim: function(str, chars) {
            if (!str) {
                return str;
            }
            if (typeof chars === 'undefined') {
                chars = ' \t\n\r\0\x0B';
            }
            var re = new RegExp('^[' + Craft.escapeChars(chars) + ']+');
            return str.replace(re, '');
        },

        /**
         * Trim characters off of the end of a string.
         *
         * @param {string} str
         * @param {string|object|undefined} chars The characters to trim off. Defaults to a space if left blank.
         * @return string
         */
        rtrim: function(str, chars) {
            if (!str) {
                return str;
            }
            if (typeof chars === 'undefined') {
                chars = ' \t\n\r\0\x0B';
            }
            var re = new RegExp('[' + Craft.escapeChars(chars) + ']+$');
            return str.replace(re, '');
        },

        /**
         * Trim characters off of the beginning and end of a string.
         *
         * @param {string} str
         * @param {string|object|undefined} chars The characters to trim off. Defaults to a space if left blank.
         * @return string
         */
        trim: function(str, chars) {
            str = Craft.ltrim(str, chars);
            str = Craft.rtrim(str, chars);
            return str;
        },

        /**
         * Filters an array.
         *
         * @param {object} arr
         * @param {function} callback A user-defined callback function. If null, we'll just remove any elements that equate to false.
         * @return array
         */
        filterArray: function(arr, callback) {
            var filtered = [];

            for (var i = 0; i < arr.length; i++) {
                var include;

                if (typeof callback === 'function') {
                    include = callback(arr[i], i);
                }
                else {
                    include = arr[i];
                }

                if (include) {
                    filtered.push(arr[i]);
                }
            }

            return filtered;
        },

        /**
         * Returns whether an element is in an array (unlike jQuery.inArray(), which returns the element's index, or -1).
         *
         * @param elem
         * @param arr
         * @return boolean
         */
        inArray: function(elem, arr) {
            return ($.inArray(elem, arr) !== -1);
        },

        /**
         * Removes an element from an array.
         *
         * @param elem
         * @param {object} arr
         * @return boolean Whether the element could be found or not.
         */
        removeFromArray: function(elem, arr) {
            var index = $.inArray(elem, arr);
            if (index !== -1) {
                arr.splice(index, 1);
                return true;
            }
            else {
                return false;
            }
        },

        /**
         * Returns the last element in an array.
         *
         * @param {object} arr
         * @return mixed
         */
        getLast: function(arr) {
            if (!arr.length) {
                return null;
            } else {
                return arr[arr.length - 1];
            }
        },

        /**
         * Makes the first character of a string uppercase.
         *
         * @param {string} str
         * @return string
         */
        uppercaseFirst: function(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        /**
         * Makes the first character of a string lowercase.
         *
         * @param {string} str
         * @return string
         */
        lowercaseFirst: function(str) {
            return str.charAt(0).toLowerCase() + str.slice(1);
        },

        /**
         * Converts a number of seconds into a human-facing time duration.
         */
        secondsToHumanTimeDuration: function(seconds, showSeconds) {
            if (typeof showSeconds === 'undefined') {
                showSeconds = true;
            }

            var secondsInWeek = 604800,
                secondsInDay = 86400,
                secondsInHour = 3600,
                secondsInMinute = 60;

            var weeks = Math.floor(seconds / secondsInWeek);
            seconds = seconds % secondsInWeek;

            var days = Math.floor(seconds / secondsInDay);
            seconds = seconds % secondsInDay;

            var hours = Math.floor(seconds / secondsInHour);
            seconds = seconds % secondsInHour;

            var minutes;

            if (showSeconds) {
                minutes = Math.floor(seconds / secondsInMinute);
                seconds = seconds % secondsInMinute;
            }
            else {
                minutes = Math.round(seconds / secondsInMinute);
                seconds = 0;
            }

            var timeComponents = [];

            if (weeks) {
                timeComponents.push(weeks + ' ' + (weeks === 1 ? Craft.t('app', 'week') : Craft.t('app', 'weeks')));
            }

            if (days) {
                timeComponents.push(days + ' ' + (days === 1 ? Craft.t('app', 'day') : Craft.t('app', 'days')));
            }

            if (hours) {
                timeComponents.push(hours + ' ' + (hours === 1 ? Craft.t('app', 'hour') : Craft.t('app', 'hours')));
            }

            if (minutes || (!showSeconds && !weeks && !days && !hours)) {
                timeComponents.push(minutes + ' ' + (minutes === 1 ? Craft.t('app', 'minute') : Craft.t('app', 'minutes')));
            }

            if (seconds || (showSeconds && !weeks && !days && !hours && !minutes)) {
                timeComponents.push(seconds + ' ' + (seconds === 1 ? Craft.t('app', 'second') : Craft.t('app', 'seconds')));
            }

            return timeComponents.join(', ');
        },

        /**
         * Converts extended ASCII characters to ASCII.
         *
         * @param {string} str
         * @return string
         */
        asciiString: function(str) {
            var asciiStr = '';
            var char;

            for (var i = 0; i < str.length; i++) {
                char = str.charAt(i);
                asciiStr += Craft.asciiCharMap[char] || char;
            }

            return asciiStr;
        },

        /**
         * Prevents the outline when an element is focused by the mouse.
         *
         * @param elem Either an actual element or a jQuery collection.
         */
        preventOutlineOnMouseFocus: function(elem) {
            var $elem = $(elem),
                namespace = '.preventOutlineOnMouseFocus';

            $elem.on('mousedown' + namespace, function() {
                    $elem.addClass('no-outline');
                    $elem.trigger('focus');
                })
                .on('keydown' + namespace + ' blur' + namespace, function(event) {
                    if (event.keyCode !== Garnish.SHIFT_KEY && event.keyCode !== Garnish.CTRL_KEY && event.keyCode !== Garnish.CMD_KEY) {
                        $elem.removeClass('no-outline');
                    }
                });
        },

        /**
         * Creates a validation error list.
         *
         * @param {object} errors
         * @return jQuery
         */
        createErrorList: function(errors) {
            var $ul = $(document.createElement('ul')).addClass('errors');

            for (var i = 0; i < errors.length; i++) {
                var $li = $(document.createElement('li'));
                $li.appendTo($ul);
                $li.html(errors[i]);
            }

            return $ul;
        },

        appendHeadHtml: function(html) {
            if (!html) {
                return;
            }

            // Prune out any link tags that are already included
            var $existingCss = $('link[href]');

            if ($existingCss.length) {
                var existingCss = [];

                for (var i = 0; i < $existingCss.length; i++) {
                    var href = $existingCss.eq(i).attr('href');
                    existingCss.push(href.replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&"));
                }

                var regexp = new RegExp('<link\\s[^>]*href="(?:' + existingCss.join('|') + ')".*?></script>', 'g');

                html = html.replace(regexp, '');
            }

            $('head').append(html);
        },

        appendFootHtml: function(html) {
            if (!html) {
                return;
            }

            // Prune out any script tags that are already included
            var $existingJs = $('script[src]');

            if ($existingJs.length) {
                var existingJs = [];

                for (var i = 0; i < $existingJs.length; i++) {
                    var src = $existingJs.eq(i).attr('src');
                    existingJs.push(src.replace(/[.?*+^$[\]\\(){}|-]/g, "\\$&"));
                }

                var regexp = new RegExp('<script\\s[^>]*src="(?:' + existingJs.join('|') + ')".*?></script>', 'g');

                html = html.replace(regexp, '');
            }

            Garnish.$bod.append(html);
        },

        /**
         * Initializes any common UI elements in a given container.
         *
         * @param {object} $container
         */
        initUiElements: function($container) {
            $('.grid', $container).grid();
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
        _elementEditorClasses: {},

        /**
         * Registers an element index class for a given element type.
         *
         * @param {string} elementType
         * @param {function} func
         */
        registerElementIndexClass: function(elementType, func) {
            if (typeof this._elementIndexClasses[elementType] !== 'undefined') {
                throw 'An element index class has already been registered for the element type “' + elementType + '”.';
            }

            this._elementIndexClasses[elementType] = func;
        },

        /**
         * Registers an element selector modal class for a given element type.
         *
         * @param {string} elementType
         * @param {function} func
         */
        registerElementSelectorModalClass: function(elementType, func) {
            if (typeof this._elementSelectorModalClasses[elementType] !== 'undefined') {
                throw 'An element selector modal class has already been registered for the element type “' + elementType + '”.';
            }

            this._elementSelectorModalClasses[elementType] = func;
        },

        /**
         * Registers an element editor class for a given element type.
         *
         * @param {string} elementType
         * @param {function} func
         */
        registerElementEditorClass: function(elementType, func) {
            if (typeof this._elementEditorClasses[elementType] !== 'undefined') {
                throw 'An element editor class has already been registered for the element type “' + elementType + '”.';
            }

            this._elementEditorClasses[elementType] = func;
        },

        /**
         * Creates a new element index for a given element type.
         *
         * @param {string} elementType
         * @param $container
         * @param {object} settings
         * @return BaseElementIndex
         */
        createElementIndex: function(elementType, $container, settings) {
            var func;

            if (typeof this._elementIndexClasses[elementType] !== 'undefined') {
                func = this._elementIndexClasses[elementType];
            }
            else {
                func = Craft.BaseElementIndex;
            }

            return new func(elementType, $container, settings);
        },

        /**
         * Creates a new element selector modal for a given element type.
         *
         * @param {string} elementType
         * @param {object} settings
         */
        createElementSelectorModal: function(elementType, settings) {
            var func;

            if (typeof this._elementSelectorModalClasses[elementType] !== 'undefined') {
                func = this._elementSelectorModalClasses[elementType];
            }
            else {
                func = Craft.BaseElementSelectorModal;
            }

            return new func(elementType, settings);
        },

        /**
         * Creates a new element editor HUD for a given element type.
         *
         * @param {string} elementType
         * @param element $element
         * @param {object} settings
         */
        createElementEditor: function(elementType, element, settings) {
            var func;

            if (typeof this._elementEditorClasses[elementType] !== 'undefined') {
                func = this._elementEditorClasses[elementType];
            }
            else {
                func = Craft.BaseElementEditor;
            }

            return new func(element, settings);
        },

        /**
         * Retrieves a value from localStorage if it exists.
         *
         * @param {string} key
         * @param defaultValue
         */
        getLocalStorage: function(key, defaultValue) {
            key = 'Craft-' + Craft.systemUid + '.' + key;

            if (typeof localStorage !== 'undefined' && typeof localStorage[key] !== 'undefined') {
                return JSON.parse(localStorage[key]);
            }
            else {
                return defaultValue;
            }
        },

        /**
         * Saves a value to localStorage.
         *
         * @param {string} key
         * @param value
         */
        setLocalStorage: function(key, value) {
            if (typeof localStorage !== 'undefined') {
                key = 'Craft-' + Craft.systemUid + '.' + key;

                // localStorage might be filled all the way up.
                // Especially likely if this is a private window in Safari 8+, where localStorage technically exists,
                // but has a max size of 0 bytes.
                try {
                    localStorage[key] = JSON.stringify(value);
                }
                catch (e) {
                }
            }
        },

        /**
         * Returns element information from it's HTML.
         *
         * @param element
         * @returns object
         */
        getElementInfo: function(element) {
            var $element = $(element);

            if (!$element.hasClass('element')) {
                $element = $element.find('.element:first');
            }

            return {
                id: $element.data('id'),
                siteId: $element.data('site-id'),
                label: $element.data('label'),
                status: $element.data('status'),
                url: $element.data('url'),
                hasThumb: $element.hasClass('hasthumb'),
                $element: $element
            };
        },

        /**
         * Changes an element to the requested size.
         *
         * @param element
         * @param size
         */
        setElementSize: function(element, size) {
            var $element = $(element);

            if (size !== 'small' && size !== 'large') {
                size = 'small';
            }

            if ($element.hasClass(size)) {
                return;
            }

            var otherSize = (size === 'small' ? 'large' : 'small');

            $element
                .addClass(size)
                .removeClass(otherSize);

            if ($element.hasClass('hasthumb')) {
                var $oldImg = $element.find('> .elementthumb > img'),
                    imgSize = (size === 'small' ? '30' : '100'),
                    $newImg = $('<img/>', {
                        sizes: imgSize + 'px',
                        srcset: $oldImg.attr('srcset') || $oldImg.attr('data-pfsrcset')
                    });

                $oldImg.replaceWith($newImg);

                picturefill({
                    elements: [$newImg[0]]
                });
            }
        }
    });


// -------------------------------------------
//  Custom jQuery plugins
// -------------------------------------------

$.extend($.fn,
    {
        animateLeft: function(pos, duration, easing, complete) {
            if (Craft.orientation === 'ltr') {
                return this.velocity({left: pos}, duration, easing, complete);
            }
            else {
                return this.velocity({right: pos}, duration, easing, complete);
            }
        },

        animateRight: function(pos, duration, easing, complete) {
            if (Craft.orientation === 'ltr') {
                return this.velocity({right: pos}, duration, easing, complete);
            }
            else {
                return this.velocity({left: pos}, duration, easing, complete);
            }
        },

        /**
         * Disables elements by adding a .disabled class and preventing them from receiving focus.
         */
        disable: function() {
            return this.each(function() {
                var $elem = $(this);
                $elem.addClass('disabled');

                if ($elem.data('activatable')) {
                    $elem.removeAttr('tabindex');
                }
            });
        },

        /**
         * Enables elements by removing their .disabled class and allowing them to receive focus.
         */
        enable: function() {
            return this.each(function() {
                var $elem = $(this);
                $elem.removeClass('disabled');

                if ($elem.data('activatable')) {
                    $elem.attr('tabindex', '0');
                }
            });
        },

        /**
         * Sets the element as the container of a grid.
         */
        grid: function() {
            return this.each(function() {
                var $container = $(this),
                    settings = {};

                if ($container.data('item-selector')) {
                    settings.itemSelector = $container.data('item-selector');
                }
                if ($container.data('cols')) {
                    settings.cols = parseInt($container.data('cols'));
                }
                if ($container.data('max-cols')) {
                    settings.maxCols = parseInt($container.data('max-cols'));
                }
                if ($container.data('min-col-width')) {
                    settings.minColWidth = parseInt($container.data('min-col-width'));
                }
                if ($container.data('mode')) {
                    settings.mode = $container.data('mode');
                }
                if ($container.data('fill-mode')) {
                    settings.fillMode = $container.data('fill-mode');
                }
                if ($container.data('col-class')) {
                    settings.colClass = $container.data('col-class');
                }
                if ($container.data('snap-to-grid')) {
                    settings.snapToGrid = !!$container.data('snap-to-grid');
                }

                new Craft.Grid(this, settings);
            });
        },

        infoicon: function() {
            return this.each(function() {
                new Craft.InfoIcon(this);
            });
        },

        /**
         * Sets the element as a container for a checkbox select.
         */
        checkboxselect: function() {
            return this.each(function() {
                if (!$.data(this, 'checkboxselect')) {
                    new Garnish.CheckboxSelect(this);
                }
            });
        },

        /**
         * Sets the element as a field toggle trigger.
         */
        fieldtoggle: function() {
            return this.each(function() {
                if (!$.data(this, 'fieldtoggle')) {
                    new Craft.FieldToggle(this);
                }
            });
        },

        lightswitch: function(settings, settingName, settingValue) {
            // param mapping
            if (settings === 'settings') {
                if (typeof settingName === 'string') {
                    settings = {};
                    settings[settingName] = settingValue;
                }
                else {
                    settings = settingName;
                }

                return this.each(function() {
                    var obj = $.data(this, 'lightswitch');
                    if (obj) {
                        obj.setSettings(settings);
                    }
                });
            }
            else {
                if (!$.isPlainObject(settings)) {
                    settings = {};
                }

                return this.each(function() {
                    var thisSettings = $.extend({}, settings);

                    if (Garnish.hasAttr(this, 'data-value')) {
                        thisSettings.value = $(this).attr('data-value');
                    }

                    if (!$.data(this, 'lightswitch')) {
                        new Craft.LightSwitch(this, thisSettings);
                    }
                });
            }
        },

        nicetext: function() {
            return this.each(function() {
                if (!$.data(this, 'nicetext')) {
                    new Garnish.NiceText(this);
                }
            });
        },

        pill: function() {
            return this.each(function() {
                if (!$.data(this, 'pill')) {
                    new Garnish.Pill(this);
                }
            });
        },

        formsubmit: function() {
            // Secondary form submit buttons
            this.on('click', function(ev) {
                var $btn = $(ev.currentTarget);

                if ($btn.attr('data-confirm')) {
                    if (!confirm($btn.attr('data-confirm'))) {
                        return;
                    }
                }

                var $anchor = $btn.data('menu') ? $btn.data('menu').$anchor : $btn;
                var $form = $anchor.attr('data-form') ? $('#'+$anchor.attr('data-form')) : $anchor.closest('form');

                if ($btn.attr('data-action')) {
                    $('<input type="hidden" name="action"/>')
                        .val($btn.attr('data-action'))
                        .appendTo($form);
                }

                if ($btn.attr('data-redirect')) {
                    $('<input type="hidden" name="redirect"/>')
                        .val($btn.attr('data-redirect'))
                        .appendTo($form);
                }

                if ($btn.attr('data-param')) {
                    $('<input type="hidden"/>')
                        .attr({
                            name: $btn.attr('data-param'),
                            value: $btn.attr('data-value')
                        })
                        .appendTo($form);
                }

                $form.trigger('submit');
            });
        },

        menubtn: function() {
            return this.each(function() {
                var $btn = $(this);

                if (!$btn.data('menubtn') && $btn.next().hasClass('menu')) {
                    var settings = {};

                    if ($btn.data('menu-anchor')) {
                        settings.menuAnchor = $btn.data('menu-anchor');
                    }

                    new Garnish.MenuBtn($btn, settings);
                }
            });
        }
    });


Garnish.$doc.ready(function() {
    Craft.initUiElements();
});
