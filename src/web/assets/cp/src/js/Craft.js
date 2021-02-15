/** global: Craft */
/** global: Garnish */

// Use old jQuery prefilter behavior
// see https://jquery.com/upgrade-guide/3.5/
var rxhtmlTag = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi;
jQuery.htmlPrefilter = function(html) {
    return html.replace(rxhtmlTag, "<$1></$2>");
};

// Set all the standard Craft.* stuff
$.extend(Craft,
    {
        navHeight: 48,

        /**
         * @callback indexKeyCallback
         * @param {object} currentValue
         * @param {number} [index]
         * @return {string}
         */
        /**
         * Indexes an array of objects by a specified key
         *
         * @param {object[]} arr
         * @param {(string|indexKeyCallback)} key
         */
        index: function(arr, key) {
            if (!$.isArray(arr)) {
                throw 'The first argument passed to Craft.index() must be an array.';
            }

            return arr.reduce((index, obj, i) => {
                index[typeof key === 'string' ? obj[key] : key(obj, i)] = obj;
                return index;
            }, {});
        },

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
                return this.formatMessage(message, params);
            }

            return message;
        },

        formatMessage: function(pattern, args) {
            let tokens;
            if ((tokens = this._tokenizePattern(pattern)) === false) {
                throw 'Message pattern is invalid.';
            }
            for (let i = 0; i < tokens.length; i++) {
                let token = tokens[i];
                if (typeof token === 'object') {
                    if ((tokens[i] = this._parseToken(token, args)) === false) {
                        throw 'Message pattern is invalid.';
                    }
                }
            }
            return tokens.join('');
        },

        _tokenizePattern: function(pattern) {
            let depth = 1, start, pos;
            // Get an array of the string characters (factoring in 3+ byte chars)
            const chars = [...pattern];
            if ((start = pos = chars.indexOf('{')) === -1) {
                return [pattern];
            }
            let tokens = [chars.slice(0, pos).join('')];
            while (true) {
                let open = chars.indexOf('{', pos + 1);
                let close = chars.indexOf('}', pos + 1);
                if (open === -1) {
                    open = false;
                }
                if (close === -1) {
                    close = false;
                }
                if (open === false && close === false) {
                    break;
                }
                if (open === false) {
                    open = chars.length;
                }
                if (close > open) {
                    depth++;
                    pos = open;
                } else {
                    depth--;
                    pos = close;
                }
                if (depth === 0) {
                    tokens.push(chars.slice(start + 1, pos).join('').split(',', 3));
                    start = pos + 1;
                    tokens.push(chars.slice(start, open).join(''));
                    start = open;
                }

                if (depth !== 0 && (open === false || close === false)) {
                    break;
                }
            }
            if (depth !== 0) {
                return false;
            }

            return tokens;
        },

        _parseToken: function(token, args) {
            // parsing pattern based on ICU grammar:
            // http://icu-project.org/apiref/icu4c/classMessageFormat.html#details
            const param = Craft.trim(token[0]);
            if (typeof args[param] === 'undefined') {
                return `{${token.join(',')}}`;
            }
            const arg = args[param];
            const type = typeof token[1] !== 'undefined' ? Craft.trim(token[1]) : 'none';
            switch (type) {
                case 'number':
                    let format = typeof token[2] !== 'undefined' ? Craft.trim(token[2]) : null;
                    if (format !== null && format !== 'integer') {
                        throw `Message format 'number' is only supported for integer values.`;
                    }
                    let number = Craft.formatNumber(arg);
                    let pos;
                    if (format === null && (pos = `${arg}`.indexOf('.')) !== -1) {
                        number += `.${arg.substr(pos + 1)}`;
                    }

                    return number;
                case 'none':
                    return arg;
                case 'plural':
                    /* http://icu-project.org/apiref/icu4c/classicu_1_1PluralFormat.html
                    pluralStyle = [offsetValue] (selector '{' message '}')+
                    offsetValue = "offset:" number
                    selector = explicitValue | keyword
                    explicitValue = '=' number  // adjacent, no white space in between
                    keyword = [^[[:Pattern_Syntax:][:Pattern_White_Space:]]]+
                    message: see MessageFormat
                    */
                    if (typeof token[2] === 'undefined') {
                        return false;
                    }
                    let plural = this._tokenizePattern(token[2]);
                    const c = plural.length;
                    let message = false;
                    let offset = 0;
                    for (let i = 0; i + 1 < c; i++) {
                        if (typeof plural[i] === 'object' || typeof plural[i + 1] !== 'object') {
                            return false;
                        }
                        let selector = Craft.trim(plural[i++]);
                        let selectorChars = [...selector];

                        if (i === 1 && selector.substring(0, 7) === 'offset:') {
                            let pos = [...selector.replace(/[\n\r\t]/g, ' ')].indexOf(' ', 7);
                            if (pos === -1) {
                                throw 'Message pattern is invalid.';
                            }
                            let offset = parseInt(Craft.trim(selectorChars.slice(7, pos).join('')));
                            selector = Craft.trim(selectorChars.slice(pos + 1, pos + 1 + selectorChars.length).join(''));
                        }
                        if (
                            message === false &&
                            selector === 'other' ||
                            selector[0] === '=' && parseInt(selectorChars.slice(1, 1 + selectorChars.length).join('')) === arg ||
                            selector === 'one' && arg - offset === 1
                        ) {
                            message = (typeof plural[i] === 'string' ? [plural[i]] : plural[i]).map((p) => {
                                return p.replace('#', arg - offset);
                            }).join(',');
                        }
                    }
                    if (message !== false) {
                        return this.formatMessage(message, args);
                    }
                    break;
                default:
                    throw `Message format '${type}' is not supported.`;
            }

            return false;
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
            if (typeof format == 'undefined') {
                format = ',.0f';
            }

            var formatter = d3.formatLocale(d3FormatLocaleDefinition).format(format);

            return formatter(number);
        },

        /**
         * @param {string} key
         * @param {boolean} shift
         * @param {boolean} alt
         */
        shortcutText: function (key, shift, alt) {
            if (Craft.clientOs === 'Mac') {
                return (alt ? '⌥' : '') + (shift ? '⇧' : '') + '⌘' + key;
            }
            return 'Ctrl+' + (alt ? 'Alt+' : '') + (shift ? 'Shift+' : '') + key;
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
         * Escapes special regular expression characters.
         *
         * @param {string} str
         * @return string
         */
        escapeRegex: function(str) {
            // h/t https://stackoverflow.com/a/9310752
            return str.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
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
            } else {
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

            // Normalize the params
            var anchor = '';

            if ($.isPlainObject(params)) {
                var aParams = [];

                for (var name in params) {
                    if (!params.hasOwnProperty(name)) {
                        continue;
                    }

                    var value = params[name];

                    if (name === '#') {
                        anchor = value;
                    } else if (value !== null && value !== '') {
                        aParams.push(name + '=' + value);
                    }
                }

                params = aParams;
            }

            if (Garnish.isArray(params)) {
                params = params.join('&');
            } else {
                params = Craft.trim(params, '&?');
            }

            // Was there already an anchor on the path?
            var apos = path.indexOf('#');
            if (apos !== -1) {
                // Only keep it if the params didn't specify a new anchor
                if (!anchor) {
                    anchor = path.substr(apos + 1);
                }
                path = path.substr(0, apos);
            }

            // Were there already any query string params in the path?
            var qpos = path.indexOf('?');
            if (qpos !== -1) {
                params = path.substr(qpos + 1) + (params ? '&' + params : '');
                path = path.substr(0, qpos);
            }

            // Return path if it appears to be an absolute URL.
            if (path.search('://') !== -1 || path[0] === '/') {
                return path + (params ? '?' + params : '') + (anchor ? '#' + anchor : '');
            }

            path = Craft.trim(path, '/');

            // Put it all together
            var url;

            if (baseUrl) {
                url = baseUrl;

                if (path && Craft.pathParam) {
                    // Does baseUrl already contain a path?
                    var pathMatch = url.match(new RegExp('[&\?]' + Craft.escapeRegex(Craft.pathParam) + '=[^&]+'));
                    if (pathMatch) {
                        url = url.replace(pathMatch[0], Craft.rtrim(pathMatch[0], '/') + '/' + path);
                        path = '';
                    }
                }
            } else {
                url = Craft.baseUrl;
            }

            // Does the base URL already have a query string?
            qpos = url.indexOf('?');
            if (qpos !== -1) {
                params = url.substr(qpos + 1) + (params ? '&' + params : '');
                url = url.substr(0, qpos);
            }

            if (!Craft.omitScriptNameInUrls && path) {
                if (Craft.usePathInfo || !Craft.pathParam) {
                    // Make sure that the script name is in the URL
                    if (url.search(Craft.scriptName) === -1) {
                        url = Craft.rtrim(url, '/') + '/' + Craft.scriptName;
                    }
                } else {
                    // Move the path into the query string params

                    // Is the path param already set?
                    if (params && params.substr(0, Craft.pathParam.length + 1) === Craft.pathParam + '=') {
                        var basePath,
                            endPath = params.indexOf('&');

                        if (endPath !== -1) {
                            basePath = params.substring(2, endPath);
                            params = params.substr(endPath + 1);
                        } else {
                            basePath = params.substr(2);
                            params = null;
                        }

                        // Just in case
                        basePath = Craft.rtrim(basePath);

                        path = basePath + (path ? '/' + path : '');
                    }

                    // Now move the path into the params
                    params = Craft.pathParam + '=' + path + (params ? '&' + params : '');
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
            } else {
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
         * @deprecated in 3.4.6. sendActionRequest() should be used instead
         */
        postActionRequest: function(action, data, callback, options) {
            // Make 'data' optional
            if (typeof data === 'function') {
                options = callback;
                callback = data;
                data = {};
            }

            options = options || {};

            if (options.contentType && options.contentType.match(/\bjson\b/)) {
                if (typeof data === 'object') {
                    data = JSON.stringify(data);
                }
                options.contentType = 'application/json; charset=utf-8';
            }

            var jqXHR = $.ajax($.extend({
                url: Craft.getActionUrl(action),
                type: 'POST',
                dataType: 'json',
                headers: this._actionHeaders(),
                data: data,
                success: callback,
                error: function(jqXHR, textStatus, errorThrown) {
                    // Ignore incomplete requests, likely due to navigating away from the page
                    // h/t https://stackoverflow.com/a/22107079/1688568
                    if (jqXHR.readyState !== 4) {
                        return;
                    }

                    if (typeof Craft.cp !== 'undefined') {
                        Craft.cp.displayError();
                    } else {
                        alert(Craft.t('app', 'A server error occurred.'));
                    }

                    if (callback) {
                        callback(null, textStatus, jqXHR);
                    }
                }
            }, options));

            // Call the 'send' callback
            if (typeof options.send === 'function') {
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
                } else {
                    Craft._waitingOnAjax = false;
                }
            }, args[3]);
        },

        _actionHeaders: function() {
            let headers = {
                'X-Registered-Asset-Bundles': Object.keys(Craft.registeredAssetBundles).join(','),
                'X-Registered-Js-Files': Object.keys(Craft.registeredJsFiles).join(',')
            };

            if (Craft.csrfTokenValue) {
                headers['X-CSRF-Token'] = Craft.csrfTokenValue;
            }

            return headers;
        },

        /**
         * Sends a request to a Craft/plugin action
         * @param {string} method The request action to use ('GET' or 'POST')
         * @param {string} action The action to request
         * @param {Object} options Axios request options
         * @returns {Promise}
         * @since 3.4.6
         */
        sendActionRequest: function(method, action, options) {
            return new Promise((resolve, reject) => {
                options = options ? $.extend({}, options) : {};
                options.method = method;
                options.url = Craft.getActionUrl(action);
                options.headers = $.extend({
                    'X-Requested-With': 'XMLHttpRequest',
                }, options.headers || {}, this._actionHeaders());
                options.params = $.extend({}, options.params || {}, {
                    // Force Safari to not load from cache
                    v: new Date().getTime(),
                });
                axios.request(options).then(resolve).catch(reject);
            });
        },

        /**
         * Sends a request to the Craftnet API.
         * @param {string} method The request action to use ('GET' or 'POST')
         * @param {string} uri The API endpoint URI
         * @param {Object} options Axios request options
         * @returns {Promise}
         * @since 3.3.16
         */
        sendApiRequest: function(method, uri, options) {
            return new Promise((resolve, reject) => {
                options = options ? $.extend({}, options) : {};
                let cancelToken = options.cancelToken || null;

                // Get the latest headers
                this._getApiHeaders(cancelToken).then(apiHeaders => {
                    // Send the API request
                    options.method = method;
                    options.baseURL = Craft.baseApiUrl;
                    options.url = uri;
                    options.headers = $.extend(apiHeaders, options.headers || {});
                    options.params = $.extend(Craft.apiParams || {}, options.params || {}, {
                        // Force Safari to not load from cache
                        v: new Date().getTime(),
                    });

                    // Force the API to process the Craft headers if this is the first API request
                    if (!this._apiHeaders) {
                        options.params.processCraftHeaders = 1;
                    }

                    axios.request(options).then((apiResponse) => {
                        // Process the response headers
                        this._processApiHeaders(apiResponse.headers, cancelToken).then(() => {
                            // Finally return the API response data
                            resolve(apiResponse.data);
                        }).catch(reject);
                    }).catch(reject);
                }).catch(reject);
            });
        },

        _loadingApiHeaders: false,
        _apiHeaders: null,
        _apiHeaderWaitlist: [],

        /**
         * Returns the headers that should be sent with API requests.
         *
         * @param {Object|null} cancelToken
         * @return {Promise}
         */
        _getApiHeaders: function(cancelToken) {
            return new Promise((resolve, reject) => {
                // Are we already loading them?
                if (this._loadingApiHeaders) {
                    this._apiHeaderWaitlist.push([resolve, reject]);
                    return;
                }

                // Are the headers already cached?
                if (this._apiHeaders) {
                    resolve(this._apiHeaders);
                    return;
                }

                this._loadingApiHeaders = true;
                this.sendActionRequest('POST', 'app/api-headers', {
                    cancelToken: cancelToken,
                }).then(response => {
                    // Make sure we even are waiting for these anymore
                    if (!this._loadingApiHeaders) {
                        reject(e);
                        return;
                    }

                    resolve(response.data);
                }).catch(e => {
                    this._rejectApiRequests(reject, e);
                });
            });
        },

        _processApiHeaders: function(headers, cancelToken) {
            return new Promise((resolve, reject) => {
                // Have we already processed them?
                if (this._apiHeaders) {
                    resolve();
                    return;
                }

                this.sendActionRequest('POST', 'app/process-api-response-headers', {
                    data: {
                        headers: headers,
                    },
                    cancelToken: cancelToken,
                }).then(response => {
                    // Make sure we even are waiting for these anymore
                    if (!this._loadingApiHeaders) {
                        reject(e);
                        return;
                    }

                    this._apiHeaders = response.data;
                    this._loadingApiHeaders = false;

                    resolve();

                    // Was anything else waiting for them?
                    while (this._apiHeaderWaitlist.length) {
                        this._apiHeaderWaitlist.shift()[0](this._apiHeaders);
                    }
                }).catch(e => {
                    this._rejectApiRequests(reject, e);
                });
            });
        },

        _rejectApiRequests: function(reject, e) {
            this._loadingApiHeaders = false;
            reject(e);
            while (this._apiHeaderWaitlist.length) {
                this._apiHeaderWaitlist.shift()[1](e);
            }
        },

        /**
         * Clears the cached API headers.
         */
        clearCachedApiHeaders: function() {
            this._apiHeaders = null;
            this._loadingApiHeaders = false;

            // Reject anything in the header waitlist
            while (this._apiHeaderWaitlist.length) {
                this._apiHeaderWaitlist.shift()[1]();
            }
        },

        /**
         * Requests a URL and downloads the response.
         *
         * @param {string} method the request method to use
         * @param {string} url the URL
         * @param {string|Object} [body] the request body, if method = POST
         * @return {Promise}
         */
        downloadFromUrl: function(method, url, body) {
            return new Promise((resolve, reject) => {
                // h/t https://nehalist.io/downloading-files-from-post-requests/
                let request = new XMLHttpRequest();
                request.open(method, url, true);
                if (typeof body === 'object') {
                    request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
                    body = JSON.stringify(body);
                } else {
                    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                }
                request.responseType = 'blob';

                request.onload = function() {
                    // Only handle status code 200
                    if (request.status === 200) {
                        // Try to find out the filename from the content disposition `filename` value
                        let disposition = request.getResponseHeader('content-disposition');
                        let matches = /"([^"]*)"/.exec(disposition);
                        let filename = (matches != null && matches[1] ? matches[1] : 'Download');

                        // Encode the download into an anchor href
                        let contentType = request.getResponseHeader('content-type');
                        let blob = new Blob([request.response], {type: contentType});
                        let link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        resolve();
                    } else {
                        reject();
                    }
                }.bind(this);

                request.send(body);
            });
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
         * Compares old and new post data, and removes any values that haven't
         * changed within the given list of delta namespaces.
         *
         * @param {string} oldData
         * @param {string} newData
         * @param {object} deltaNames
         */
        findDeltaData: function(oldData, newData, deltaNames) {
            // Sort the delta namespaces from least -> most specific
            deltaNames.sort(function(a, b) {
                if (a.length === b.length) {
                    return 0;
                }
                return a.length > b.length ? 1 : -1;
            });

            // Group all of the old & new params by namespace
            var groupedOldParams = this._groupParamsByDeltaNames(oldData.split('&'), deltaNames, false, true);
            var groupedNewParams = this._groupParamsByDeltaNames(newData.split('&'), deltaNames, true, false);

            // Figure out which of the new params should actually be posted
            var params = groupedNewParams.__root__;
            var modifiedDeltaNames = [];
            for (var n = 0; n < deltaNames.length; n++) {
                if (Craft.inArray(deltaNames[n], Craft.modifiedDeltaNames) || (
                    typeof groupedNewParams[deltaNames[n]] === 'object' &&
                    (
                        typeof groupedOldParams[deltaNames[n]] !== 'object' ||
                        JSON.stringify(groupedOldParams[deltaNames[n]]) !== JSON.stringify(groupedNewParams[deltaNames[n]])
                    )
                )) {
                    params = params.concat(groupedNewParams[deltaNames[n]]);
                    params.push('modifiedDeltaNames[]=' + deltaNames[n]);
                }
            }

            return params.join('&');
        },

        _groupParamsByDeltaNames: function(params, deltaNames, withRoot, useInitialValues) {
            var grouped = {};

            if (withRoot) {
                grouped.__root__ = [];
            }

            var n, paramName;

            paramLoop: for (var p = 0; p < params.length; p++) {
                // loop through the delta names from most -> least specific
                for (n = deltaNames.length - 1; n >= 0; n--) {
                    paramName = decodeURIComponent(params[p]).substr(0, deltaNames[n].length + 1);
                    if (
                        paramName === deltaNames[n] + '=' ||
                        paramName === deltaNames[n] + '['
                    ) {
                        if (typeof grouped[deltaNames[n]] === 'undefined') {
                            grouped[deltaNames[n]] = [];
                        }
                        grouped[deltaNames[n]].push(params[p]);
                        continue paramLoop;
                    }
                }

                if (withRoot) {
                    grouped.__root__.push(params[p]);
                }
            }

            if (useInitialValues) {
                for (let name in Craft.initialDeltaValues) {
                    if (Craft.initialDeltaValues.hasOwnProperty(name)) {
                        grouped[name] = [encodeURIComponent(name) + '=' + $.param(Craft.initialDeltaValues[name])];
                    }
                }
            }

            return grouped;
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
                } else {
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
                            } else {
                                parentElem[keys[i]] = {};
                            }
                        }

                        parentElem = parentElem[keys[i]];
                    } else {
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
         * Creates a form element populated with hidden inputs based on a string of serialized form data.
         *
         * @param {string} data
         * @returns {jQuery|HTMLElement}
         */
        createForm: function(data) {
            var $form = $('<form/>', {
                attr: {
                    method: 'post',
                    action: '',
                    'accept-charset': 'UTF-8',
                },
            });

            if (typeof data === 'string') {
                var values = data.split('&');
                var chunks;
                for (var i = 0; i < values.length; i++) {
                    chunks = values[i].split('=', 2);
                    $('<input/>', {
                        type: 'hidden',
                        name: decodeURIComponent(chunks[0]),
                        value: decodeURIComponent(chunks[1] || '')
                    }).appendTo($form);
                }
            }

            return $form;
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
                    } else {
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
            } else {
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
         * Returns whether a string starts with another string.
         *
         * @param {string} str
         * @param {string} substr
         * @return boolean
         */
        startsWith: function(str, substr) {
            return str.substr(0, substr.length) === substr;
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
                } else {
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
            if ($.isPlainObject(arr)) {
                arr = Object.values(arr);
            }
            return arr.includes(elem);
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
            } else {
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

        parseUrl: function(url) {
            var m = url.match(/^(?:(https?):\/\/|\/\/)([^\/\:]*)(?:\:(\d+))?(\/[^\?]*)?(?:\?([^#]*))?(#.*)?/);
            if (!m) {
                return {};
            }
            return {
                scheme: m[1],
                host: m[2] + (m[3] ? ':' + m[3] : ''),
                hostname: m[2],
                port: m[3] || null,
                path: m[4] || '/',
                query: m[5] || null,
                hash: m[6] || null,
            };
        },

        isSameHost: function(url) {
            var requestUrlInfo = this.parseUrl(document.location.href);
            if (!requestUrlInfo) {
                return false;
            }
            var urlInfo = this.parseUrl(url);
            if (!urlInfo) {
                return false;
            }
            return requestUrlInfo.host === urlInfo.host;
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
            } else {
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
         * @param {object|undefined} charMap
         * @return string
         */
        asciiString: function(str, charMap) {
            // Normalize NFD chars to NFC
            str = str.normalize('NFC');

            var asciiStr = '';
            var char;

            for (var i = 0; i < str.length; i++) {
                char = str.charAt(i);
                asciiStr += typeof (charMap || Craft.asciiCharMap)[char] === 'string' ? (charMap || Craft.asciiCharMap)[char] : char;
            }

            return asciiStr;
        },

        randomString: function(length) {
            // h/t https://stackoverflow.com/a/1349426/1688568
            var result = '';
            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            for (var i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * 62));
            }
            return result;
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
                var href;

                for (var i = 0; i < $existingCss.length; i++) {
                    href = $existingCss.eq(i).attr('href').replace(/&/g, '&amp;');
                    existingCss.push(Craft.escapeRegex(href));
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
                var src;

                for (var i = 0; i < $existingJs.length; i++) {
                    src = $existingJs.eq(i).attr('src').replace(/&/g, '&amp;');
                    existingJs.push(Craft.escapeRegex(src));
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
            $('.datetimewrapper', $container).datetime();
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
            } else {
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
            } else {
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
            // Param mapping
            if (typeof settings === 'undefined' && $.isPlainObject(element)) {
                // (settings)
                settings = element;
                element = null;
            } else if (typeof settings !== 'object') {
                settings = {};
            }

            if (!settings.elementType) {
                settings.elementType = elementType;
            }

            var func;
            if (typeof this._elementEditorClasses[elementType] !== 'undefined') {
                func = this._elementEditorClasses[elementType];
            } else {
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
            } else {
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
                } catch (e) {
                }
            }
        },

        /**
         * Removes a value from localStorage.
         * @param key
         */
        removeLocalStorage: function(key) {
            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem(`Craft-${Craft.systemUid}.${key}`);
            }
        },

        /**
         * Returns a cookie value, if it exists, otherwise returns `false`
         * @return {(string|boolean)}
         */
        getCookie: function(name) {
            // Adapted from https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie
            return document.cookie.replace(new RegExp(`(?:(?:^|.*;\\s*)Craft-${Craft.systemUid}:${name}\\s*\\=\\s*([^;]*).*$)|^.*$`), "$1");
        },

        /**
         * Sets a cookie value.
         * @param {string} name
         * @param {string} value
         * @param {Object} [options]
         * @param {string} [options.path] The cookie path.
         * @param {string} [options.domain] The cookie domain. Defaults to the `defaultCookieDomain` config setting.
         * @param {number} [options.maxAge] The max age of the cookie (in seconds)
         * @param {Date} [options.expires] The expiry date of the cookie. Defaults to none (session-based cookie).
         * @param {boolean} [options.secure] Whether this is a secure cookie. Defaults to the `useSecureCookies`
         * config setting.
         * @param {string} [options.sameSite] The SameSite value (`lax` or `strict`). Defaults to the
         * `sameSiteCookieValue` config setting.
         */
        setCookie: function(name, value, options) {
            options = $.extend({}, this.defaultCookieOptions, options);
            let cookie = `Craft-${Craft.systemUid}:${name}=${encodeURIComponent(value)}`;
            if (options.path) {
                cookie += `;path=${options.path}`;
            }
            if (options.domain) {
                cookie += `;domain=${options.domain}`;
            }
            if (options.maxAge) {
                cookie += `;max-age-in-seconds=${options.maxAge}`;
            } else if (options.expires) {
                cookie += `;expires=${options.expires.toUTCString()}`;
            }
            if (options.secure) {
                cookie += ';secure';
            }
            document.cookie = cookie;
        },

        /**
         * Removes a cookie
         * @param {string} name
         */
        removeCookie: function(name) {
            this.setCookie(name, '', new Date('1970-01-01T00:00:00'));
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
        },

        /**
         * Submits a form.
         * @param {Object} $form
         * @param {Object} [options]
         * @param {string} [options.action] The `action` param value override
         * @param {string} [options.redirect] The `redirect` param value override
         * @param {string} [options.confirm] A confirmation message that should be shown to the user before submit
         * @param {Object} [options.params] Additional params that should be added to the form, defined as name/value pairs
         * @param {Object} [options.data] Additional data to be passed to the submit event
         * @param {boolean} [options.retainScroll] Whether the scroll position should be stored and reapplied on the next page load
         */
        submitForm: function($form, options) {
            if (typeof options === 'undefined') {
                options = {};
            }

            if (options.confirm && !confirm(options.confirm)) {
                return;
            }

            if (options.action) {
                $('<input/>', {
                    type: 'hidden',
                    name: 'action',
                    val: options.action,
                })
                    .appendTo($form);
            }

            if (options.redirect) {
                $('<input/>', {
                    type: 'hidden',
                    name: 'redirect',
                    val: options.redirect,
                })
                    .appendTo($form);
            }

            if (options.params) {
                for (let name in options.params) {
                    let value = options.params[name];
                    $('<input/>', {
                        type: 'hidden',
                        name: name,
                        val: value,
                    })
                        .appendTo($form);
                }
            }

            if (options.retainScroll) {
                this.setLocalStorage('scrollY', window.scrollY);
            }

            $form.trigger($.extend({type: 'submit'}, options.data));
        },
    });

// -------------------------------------------
//  Custom jQuery plugins
// -------------------------------------------

$.extend($.fn,
    {
        animateLeft: function(pos, duration, easing, complete) {
            if (Craft.orientation === 'ltr') {
                return this.velocity({left: pos}, duration, easing, complete);
            } else {
                return this.velocity({right: pos}, duration, easing, complete);
            }
        },

        animateRight: function(pos, duration, easing, complete) {
            if (Craft.orientation === 'ltr') {
                return this.velocity({right: pos}, duration, easing, complete);
            } else {
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
                } else {
                    settings = settingName;
                }

                return this.each(function() {
                    var obj = $.data(this, 'lightswitch');
                    if (obj) {
                        obj.setSettings(settings);
                    }
                });
            } else {
                if (!$.isPlainObject(settings)) {
                    settings = {};
                }

                return this.each(function() {
                    var thisSettings = $.extend({}, settings);

                    if (Garnish.hasAttr(this, 'data-value')) {
                        thisSettings.value = $(this).attr('data-value');
                    }

                    if (Garnish.hasAttr(this, 'data-indeterminate-value')) {
                        thisSettings.indeterminateValue = $(this).attr('data-indeterminate-value');
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
            return this.on('click', function(ev) {
                let $btn = $(ev.currentTarget);
                let params = $btn.data('params') || {};
                if ($btn.data('param')) {
                    params[$btn.data('param')] = $btn.data('value');
                }

                let $anchor = $btn.data('menu') ? $btn.data('menu').$anchor : $btn;
                let $form = $anchor.attr('data-form') ? $('#' + $anchor.attr('data-form')) : $anchor.closest('form');

                Craft.submitForm($form, {
                    confirm: $btn.data('confirm'),
                    action: $btn.data('action'),
                    redirect: $btn.data('redirect'),
                    params: params,
                    data: {
                        customTrigger: $btn,
                    }
                });
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
        },

        datetime: function() {
            return this.each(function() {
                let $wrapper = $(this);
                let $inputs = $wrapper.find('input:not([name$="[timezone]"])');
                let checkValue = () => {
                    let hasValue = false;
                    for (let i = 0; i < $inputs.length; i++) {
                        if ($inputs.eq(i).val()) {
                            hasValue = true;
                            break;
                        }
                    }
                    if (hasValue) {
                        if (!$wrapper.children('.clear-btn').length) {
                            let $btn = $('<button/>', {
                                type: 'button',
                                class: 'clear-btn',
                                title: Craft.t('app', 'Clear'),
                                'aria-label': Craft.t('app', 'Clear'),
                            })
                                .appendTo($wrapper)
                                .on('click', () => {
                                    for (let i = 0; i < $inputs.length; i++) {
                                        $inputs.eq(i).val('');
                                    }
                                    $btn.remove();
                                    $inputs.first().focus();
                                })
                        }
                    } else {
                        $wrapper.children('.clear-btn').remove();
                    }
                };
                $inputs.on('change', checkValue);
                checkValue();
            });
        },
    });

Garnish.$doc.ready(function() {
    Craft.initUiElements();
});
