import * as d3 from 'd3';

/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */
/** global: d3FormatLocaleDefinition */

// Use old jQuery prefilter behavior
// see https://jquery.com/upgrade-guide/3.5/
var rxhtmlTag =
  /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([a-z][^\/\0>\x20\t\r\n\f]*)[^>]*)\/>/gi;
jQuery.htmlPrefilter = function (html) {
  return html.replace(rxhtmlTag, '<$1></$2>');
};

// Set all the standard Craft.* stuff
$.extend(Craft, {
  navHeight: 48,

  isIterable(obj) {
    return obj && typeof obj[Symbol.iterator] === 'function';
  },

  /**
   * @callback indexKeyCallback
   * @param {Object} currentValue
   * @param {number} [index]
   * @returns {string}
   */
  /**
   * Indexes an array of objects by a specified key
   *
   * @param {Object[]} arr
   * @param {(string|indexKeyCallback)} key
   */
  index: function (arr, key) {
    if (arr instanceof NodeList || this.isIterable(arr)) {
      arr = Array.from(arr);
    } else if (!Array.isArray(arr)) {
      throw 'The first argument passed to Craft.index() must be an array, NodeList, or iterable object.';
    }

    if (typeof key === 'string') {
      const k = key;
      key = (item) => item[k];
    }

    return Object.fromEntries(arr.map((item) => [key(item), item]));
  },

  /**
   * Groups an array of objects by a specified key
   *
   * @param {Object[]} arr
   * @param {(string|indexKeyCallback)} key
   */
  group: function (arr, key) {
    if (!Array.isArray(arr)) {
      throw 'The first argument passed to Craft.group() must be an array.';
    }

    let index = {};

    return arr.reduce((grouped, obj, i) => {
      const thisKey = typeof key === 'string' ? obj[key] : key(obj, i);
      if (!index.hasOwnProperty(thisKey)) {
        index[thisKey] = [[], thisKey];
        grouped.push(index[thisKey]);
      }
      index[thisKey][0].push(obj);
      return grouped;
    }, []);
  },

  /**
   * Get a translated message.
   *
   * @param {string} category
   * @param {string} message
   * @param {Object} params
   * @returns {string}
   */
  t: function (category, message, params) {
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

  formatMessage: function (pattern, args) {
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

  _tokenizePattern: function (pattern) {
    let depth = 1,
      start,
      pos;
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
        tokens.push(
          chars
            .slice(start + 1, pos)
            .join('')
            .split(',', 3)
        );
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

  _parseToken: function (token, args) {
    // parsing pattern based on ICU grammar:
    // http://icu-project.org/apiref/icu4c/classMessageFormat.html#details
    const param = token[0].trim();
    if (typeof args[param] === 'undefined') {
      return `{${token.join(',')}}`;
    }
    const arg = args[param];
    const type = typeof token[1] !== 'undefined' ? token[1].trim() : 'none';
    switch (type) {
      case 'number':
        return (() => {
          let format = typeof token[2] !== 'undefined' ? token[2].trim() : null;
          if (format !== null && format !== 'integer') {
            throw `Message format 'number' is only supported for integer values.`;
          }
          let number = Craft.formatNumber(arg);
          let pos;
          if (format === null && (pos = `${arg}`.indexOf('.')) !== -1) {
            number += `.${arg.substring(pos + 1)}`;
          }
          return number;
        })();
      case 'none':
        return arg;
      case 'select':
        return (() => {
          /* http://icu-project.org/apiref/icu4c/classicu_1_1SelectFormat.html
                        selectStyle = (selector '{' message '}')+
                        */
          if (typeof token[2] === 'undefined') {
            return false;
          }
          let select = this._tokenizePattern(token[2]);
          let c = select.length;
          let message = false;
          for (let i = 0; i + 1 < c; i++) {
            if (Array.isArray(select[i]) || !Array.isArray(select[i + 1])) {
              return false;
            }
            let selector = select[i++].trim();
            if (
              (message === false && selector === 'other') ||
              selector == arg
            ) {
              message = select[i].join(',');
            }
          }
          if (message === false) {
            return false;
          }
          return this.formatMessage(message, args);
        })();
      case 'plural':
        return (() => {
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
            if (
              typeof plural[i] === 'object' ||
              typeof plural[i + 1] !== 'object'
            ) {
              return false;
            }
            let selector = plural[i++].trim();
            let selectorChars = [...selector];

            if (i === 1 && selector.substring(0, 7) === 'offset:') {
              let pos = [...selector.replace(/[\n\r\t]/g, ' ')].indexOf(' ', 7);
              if (pos === -1) {
                throw 'Message pattern is invalid.';
              }
              offset = parseInt(selectorChars.slice(7, pos).join('').trim());
              selector = selectorChars
                .slice(pos + 1, pos + 1 + selectorChars.length)
                .join('')
                .trim();
            }
            if (
              (message === false && selector === 'other') ||
              (selector[0] === '=' &&
                parseInt(
                  selectorChars.slice(1, 1 + selectorChars.length).join('')
                ) === arg) ||
              (selector === 'one' && arg - offset === 1)
            ) {
              message = (
                typeof plural[i] === 'string' ? [plural[i]] : plural[i]
              )
                .map((p) => {
                  return p.replace('#', arg - offset);
                })
                .join(',');
            }
          }
          if (message === false) {
            return false;
          }
          return this.formatMessage(message, args);
        })();
      default:
        throw `Message format '${type}' is not supported.`;
    }
  },

  formatDate: function (date) {
    if (typeof date !== 'object') {
      date = new Date(date);
    }

    return $.datepicker.formatDate(Craft.datepickerOptions.dateFormat, date);
  },

  /**
   * Formats a number.
   *
   * @param {string} number
   * @param {string} [format] D3 format
   * @returns {string}
   */
  formatNumber: function (number, format) {
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
   * @returns {string}
   */
  escapeHtml: function (str) {
    return $('<div/>').text(str).html();
  },

  /**
   * Escapes special regular expression characters.
   *
   * @param {string} str
   * @returns {string}
   */
  escapeRegex: function (str) {
    // h/t https://stackoverflow.com/a/9310752
    return str.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
  },

  /**
   * Returns the text in a string that might contain HTML tags.
   *
   * @param {string} str
   * @returns {string}
   */
  getText: function (str) {
    return $('<div/>').html(str).text();
  },

  /**
   * Encodes a URI copmonent. Mirrors PHP's rawurlencode().
   *
   * @param {string} str
   * @returns {string}
   * @see http://stackoverflow.com/questions/1734250/what-is-the-equivalent-of-javascripts-encodeuricomponent-in-php
   */
  encodeUriComponent: function (str) {
    str = encodeURIComponent(str);

    var differences = {
      '!': '%21',
      '*': '%2A',
      "'": '%27',
      '(': '%28',
      ')': '%29',
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
   * @param {(jQuery|HTMLElement|string)} input
   */
  selectFullValue: function (input) {
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
   * @returns {string}
   */
  formatInputId: function (inputName) {
    // IDs must begin with a letter
    let id = inputName.replace(/^[^A-Za-z]+/, '');
    id = this.rtrim(id.replace(/[^A-Za-z0-9_.]+/g, '-'), '-');
    return id || this.randomString(10);
  },

  /**
   * @param {string} [path]
   * @param {(Object|string)} [params]
   * @param {string} [baseUrl]
   * @returns {string}
   */
  getUrl: function (path, params, baseUrl) {
    if (typeof path !== 'string') {
      path = '';
    }

    // Normalize the params
    let anchor = null;
    if ($.isPlainObject(params)) {
      if (typeof params['#'] !== 'undefined') {
        anchor = params['#'];
        delete params['#'];
      }
    } else if (typeof params === 'string') {
      let anchorPos = params.indexOf('#');
      if (anchorPos !== -1) {
        anchor = params.substring(anchorPos + 1);
        params = params.substring(0, anchorPos);
      }
      params = Object.fromEntries(new URLSearchParams(params).entries());
    } else {
      params = {};
    }

    // Was there already an anchor on the path?
    let anchorPos = path.indexOf('#');
    if (anchorPos !== -1) {
      // Only keep it if the params didn't specify a new anchor
      if (!anchor) {
        anchor = path.substring(anchorPos + 1);
      }
      path = path.substring(0, anchorPos);
    }

    // Were there already any query string params in the path?
    let qsPos = path.indexOf('?');
    if (qsPos !== -1) {
      params = $.extend(
        Object.fromEntries(
          new URLSearchParams(path.substring(qsPos + 1)).entries()
        ),
        params
      );
      path = path.substring(0, qsPos);
    }

    // Return path if it appears to be an absolute URL.
    if (path.search('://') !== -1 || path[0] === '/') {
      return (
        path +
        (!$.isEmptyObject(params) ? `?${$.param(params)}` : '') +
        (anchor ? `#${anchor}` : '')
      );
    }

    path = Craft.trim(path, '/');

    // Put it all together
    let url;

    if (baseUrl) {
      url = baseUrl;

      if (path && Craft.pathParam) {
        // Does baseUrl already contain a path?
        var pathMatch = url.match(
          new RegExp('[&?]' + Craft.escapeRegex(Craft.pathParam) + '=[^&]+')
        );
        if (pathMatch) {
          url = url.replace(
            pathMatch[0],
            Craft.rtrim(pathMatch[0], '/') + '/' + path
          );
          path = '';
        }
      }
    } else {
      url = Craft.baseUrl;
    }

    // Does the base URL already have a query string?
    qsPos = url.indexOf('?');
    if (qsPos !== -1) {
      params = $.extend(
        Object.fromEntries(
          new URLSearchParams(url.substring(qsPos + 1)).entries()
        ),
        params
      );
      url = url.substring(0, qsPos);
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
        if (typeof params[Craft.pathParam] !== 'undefined') {
          let basePath = params[Craft.pathParam].trimEnd();
          path = basePath + (path ? '/' + path : '');
        }

        params[Craft.pathParam] = path;
        path = null;
      }
    }

    if (path) {
      url = Craft.rtrim(url, '/') + '/' + path;
    }

    if (!$.isEmptyObject(params)) {
      url += `?${$.param(params)}`;
    }

    if (anchor) {
      url += `#${anchor}`;
    }

    return url;
  },

  /**
   * @param {string} [path]
   * @param {(Object|string)} [params]
   * @returns {string}
   */
  getCpUrl: function (path, params) {
    return this.getUrl(path, params, Craft.baseCpUrl);
  },

  /**
   * @param {string} [path]
   * @param {(Object|string)} [params]
   * @returns {string}
   */
  getSiteUrl: function (path, params) {
    return this.getUrl(path, params, Craft.baseSiteUrl);
  },

  /**
   * Returns an action URL.
   *
   * @param {string} action
   * @param {(Object|string)} [params]
   * @returns {string}
   */
  getActionUrl: function (action, params) {
    return Craft.getUrl(action, params, Craft.actionUrl);
  },

  /**
   * Redirects the window to a given URL.
   *
   * @param {string} url
   */
  redirectTo: function (url) {
    document.location.href = this.getUrl(url);
  },

  /**
   * Replaces the page’s current URL.
   *
   * The location hash will be left intact, unless the given URL specifies one.
   *
   * @param {string} url
   */
  setUrl: function (url) {
    if (typeof history === 'undefined') {
      return;
    }

    if (!url.match(/#/)) {
      url += document.location.hash;
    }

    history.replaceState({}, '', url);

    // If there's a site crumb menu, update each of its URLs
    const siteLinks = document.querySelectorAll('#site-crumb-menu a[href]');
    for (const link of siteLinks) {
      const site = this.getQueryParam('site', link.href);
      link.href = this.getUrl(url, {site});
    }
  },

  /**
   * Replaces the page’s current URL based on the given path, leaving the current query string and hash intact.
   *
   * @param {string} path
   */
  setPath: function (path) {
    this.path = path;
    this.setUrl(Craft.getUrl(path, document.location.search));
  },

  /**
   * Replaces the page’s current URL based on the given query param name and value, leaving the current URI, other query params, and hash intact.
   *
   * @param {string} name
   * @param {*} value
   */
  setQueryParam(name, value) {
    const baseUrl = document.location.origin + document.location.pathname;
    const params = this.getQueryParams();

    if (typeof value !== 'undefined' && value !== null && value !== false) {
      params[name] = value;
    } else {
      delete params[name];
    }

    this.setUrl(Craft.getUrl(baseUrl, params));
  },

  /**
   * Returns the current URL with a certain page added to it.
   *
   * @param {int} page
   * @returns {string}
   */
  getPageUrl: function (page) {
    let url = document.location.origin + document.location.pathname;
    url = Craft.rtrim(url, '/');

    let qs = document.location.search
      ? document.location.search.substring(1)
      : '';

    // query string-based pagination?
    if (Craft.pageTrigger[0] === '?') {
      const pageParam = Craft.pageTrigger.substring(1);
      // remove the existing page param
      if (document.location.search) {
        const params = Object.fromEntries(new URLSearchParams(qs).entries());
        delete params[pageParam];
        qs = $.param(params);
      }
      if (page !== 1) {
        qs += (qs !== '' ? '&' : '') + `${pageParam}=${page}`;
      }
    } else {
      // Remove the existing page segment(s)
      url = url.replace(
        new RegExp('/' + Craft.escapeRegex(Craft.pageTrigger) + '\\d+$'),
        ''
      );

      if (page !== 1) {
        url += `/${Craft.pageTrigger}${page}`;
      }
    }

    return url + (qs ? `?${qs}` : '') + document.location.hash;
  },

  /**
   * Returns a hidden CSRF token input, if CSRF protection is enabled.
   *
   * @returns {string}
   */
  getCsrfInput: function () {
    if (Craft.csrfTokenName) {
      return (
        '<input type="hidden" name="' +
        Craft.csrfTokenName +
        '" value="' +
        Craft.csrfTokenValue +
        '"/>'
      );
    } else {
      return '';
    }
  },

  /**
   * @callback postActionRequestCallback
   * @param {?Object} response
   * @param {string} textStatus
   * @param {Object} jqXHR
   */
  /**
   * Posts an action request to the server.
   *
   * @param {string} action
   * @param {Object} [data]
   * @param {postActionRequestCallback} [callback]
   * @param {Object} [options]
   * @returns {Object}
   * @deprecated in 3.4.6. sendActionRequest() should be used instead
   */
  postActionRequest: function (action, data, callback, options) {
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

    var jqXHR = $.ajax(
      $.extend(
        {
          url: Craft.getActionUrl(action),
          type: 'POST',
          dataType: 'json',
          headers: this._actionHeaders(),
          data: data,
          success: callback,
          error: function (jqXHR, textStatus, errorThrown) {
            // Ignore incomplete requests, likely due to navigating away from the page
            // h/t https://stackoverflow.com/a/22107079/1688568
            if (jqXHR.readyState !== 4) {
              return;
            }

            if (jqXHR.status !== 400) {
              if (typeof Craft.cp !== 'undefined') {
                Craft.cp.displayError();
              } else {
                alert(Craft.t('app', 'A server error occurred.'));
              }
            }

            if (callback) {
              callback(
                jqXHR.status === 400 ? jqXHR.responseJSON : null,
                textStatus,
                jqXHR
              );
            }
          },
        },
        options
      )
    );

    // Call the 'send' callback
    if (typeof options.send === 'function') {
      options.send(jqXHR);
    }

    return jqXHR;
  },

  _actionHeaders: function () {
    let headers = {
      'X-Registered-Asset-Bundles': [
        ...new Set(Craft.registeredAssetBundles),
      ].join(','),
      'X-Registered-Js-Files': [...new Set(Craft.registeredJsFiles)].join(','),
    };

    if (Craft.csrfTokenValue) {
      headers['X-CSRF-Token'] = Craft.csrfTokenValue;
    }

    return headers;
  },

  /**
   * Sends a request to a Craft/plugin action
   * @param {string} method The request action to use ('GET' or 'POST')
   * @param {?string} [action] The action to request
   * @param {Object} [options] Axios request options
   * @returns {Promise}
   * @since 3.4.6
   */
  sendActionRequest: function (method, action, options = {}) {
    if ($.isPlainObject(action)) {
      options = action;
      action = null;
    }

    if (method.toUpperCase() === 'POST' && action && options.data) {
      // Avoid conflicting `action` params
      if (typeof options.data === 'string') {
        const namespace =
          options && options.headers && options.headers['X-Craft-Namespace'];
        const actionName = this.namespaceInputName('action', namespace);
        options.data += `&${actionName}=${action}`;
      } else {
        delete options.data.action;
      }
    }

    return new Promise((resolve, reject) => {
      options = options ? $.extend({}, options) : {};
      options.method = method;
      options.url = action ? Craft.getActionUrl(action) : Craft.getCpUrl();
      options.headers = $.extend(
        {
          'X-Requested-With': 'XMLHttpRequest',
        },
        options.headers || {},
        this._actionHeaders()
      );
      options.params = $.extend({}, options.params || {}, {
        // Force Safari to not load from cache
        v: new Date().getTime(),
      });
      axios
        .request(options)
        .then((response) => {
          if (response.headers['x-csrf-token']) {
            Craft.csrfTokenValue = response.headers['x-csrf-token'];
          }
          resolve(response);
        })
        .catch(reject);
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
  sendApiRequest: function (method, uri, options = {}) {
    return new Promise((resolve, reject) => {
      options = options ? $.extend({}, options) : {};
      let cancelToken = options.cancelToken || null;

      // Get the latest headers
      this._getApiHeaders(cancelToken)
        .then((apiHeaders) => {
          // Send the API request
          options.method = method;
          options.baseURL = Craft.baseApiUrl;
          options.url = uri;
          options.headers = $.extend(apiHeaders, options.headers || {});
          options.params = $.extend(
            Craft.apiParams || {},
            options.params || {},
            {
              // Force Safari to not load from cache
              v: new Date().getTime(),
            }
          );

          // Force the API to process the Craft headers if this is the first API request
          if (!this._apiHeaders) {
            options.params.processCraftHeaders = 1;
          }

          if (Craft.httpProxy) {
            options.proxy = Craft.httpProxy;
          }

          axios
            .request(options)
            .then((apiResponse) => {
              // Process the response headers
              this._processApiHeaders(apiResponse.headers, cancelToken)
                .then(() => {
                  // Finally return the API response data
                  resolve(apiResponse.data);
                })
                .catch(reject);
            })
            .catch(reject);
        })
        .catch(reject);
    });
  },

  _loadingApiHeaders: false,
  _apiHeaders: null,
  _apiHeaderWaitlist: [],

  /**
   * Returns the headers that should be sent with API requests.
   *
   * @param {Object} [cancelToken]
   * @returns {Promise}
   */
  _getApiHeaders: function (cancelToken) {
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
      })
        .then((response) => {
          // Make sure we even are waiting for these anymore
          if (!this._loadingApiHeaders) {
            reject(e);
            return;
          }

          resolve(response.data);
        })
        .catch((e) => {
          this._rejectApiRequests(reject, e);
        });
    });
  },

  _processApiHeaders: function (headers, cancelToken) {
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
      })
        .then((response) => {
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
        })
        .catch((e) => {
          this._rejectApiRequests(reject, e);
        });
    });
  },

  _rejectApiRequests: function (reject, e) {
    this._loadingApiHeaders = false;
    reject(e);
    while (this._apiHeaderWaitlist.length) {
      this._apiHeaderWaitlist.shift()[1](e);
    }
  },

  /**
   * Clears the cached API headers.
   */
  clearCachedApiHeaders: function () {
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
   * @param {(string|Object)} [body] the request body, if method = POST
   * @returns {Promise}
   */
  downloadFromUrl: function (method, url, body) {
    return new Promise((resolve, reject) => {
      // h/t https://nehalist.io/downloading-files-from-post-requests/
      let request = new XMLHttpRequest();
      request.open(method, url, true);
      if (typeof body === 'object') {
        request.setRequestHeader(
          'Content-Type',
          'application/json; charset=UTF-8'
        );
        body = JSON.stringify(body);
      } else {
        request.setRequestHeader(
          'Content-Type',
          'application/x-www-form-urlencoded; charset=UTF-8'
        );
      }
      request.responseType = 'blob';

      request.onload = () => {
        // Only handle status code 200
        if (request.status === 200) {
          // Try to find out the filename from the content disposition `filename` value
          let disposition = request.getResponseHeader('content-disposition');
          let matches = /"([^"]*)"/.exec(disposition);
          let filename =
            matches != null && matches[1] ? matches[1] : 'Download';

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
      };

      request.send(body);
    });
  },

  /**
   * Converts a comma-delimited string into an array.
   *
   * @param {string} str
   * @returns array
   */
  stringToArray: function (str) {
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
   * @callback findDeltaDataCallback
   * @param {string} deltaName
   * @param {Array} params
   */
  /**
   * Compares old and new post data, and removes any values that haven't
   * changed within the given list of delta namespaces.
   *
   * @param {string} oldData
   * @param {string} newData
   * @param {Object} deltaNames
   * @param {findDeltaDataCallback|null} [callback] Callback function that should be called whenever a new group of modified params has been found
   * @param {Object} [initialDeltaValues] Initial delta values. If undefined, `Craft.initialDeltaValues` will be used.
   * @param {Object} [forceModifiedDeltaNames] List of delta names that should be considered modified regardless of their param values
   * @param {boolean} [asArray] Whether the params should be returned as an array
   * @returns {string}
   */
  findDeltaData: function (
    oldData,
    newData,
    deltaNames,
    callback = null,
    initialDeltaValues = {},
    forceModifiedDeltaNames = [],
    asArray = false
  ) {
    const [modifiedDeltaNames, groupedNewParams] = this.findModifiedDeltaNames(
      oldData,
      newData,
      deltaNames,
      initialDeltaValues,
      forceModifiedDeltaNames
    );

    // Figure out which of the new params should actually be posted
    let params = groupedNewParams.__root__;
    for (let name of modifiedDeltaNames) {
      params = params.concat(groupedNewParams[name]);
      params.push(`modifiedDeltaNames[]=${name}`);
      if (callback) {
        callback(name, groupedNewParams[name]);
      }
    }

    return asArray ? params : params.join('&');
  },

  /**
   * Returns the delta names that have been modified, given old and new form data.
   *
   * @param {string} oldData
   * @param {string} newData
   * @param {Object} deltaNames
   * @param {Object} [initialDeltaValues] Initial delta values. If undefined, `Craft.initialDeltaValues` will be used.
   * @param {Object} [modifiedDeltaNames] List of delta names that should be considered modified regardless of their param values
   * @param {boolean} [mostSpecific] Whether the most specific modified delta names should be returned
   * @returns {Array}
   */
  findModifiedDeltaNames: function (
    oldData,
    newData,
    deltaNames,
    initialDeltaValues = {},
    modifiedDeltaNames = [],
    mostSpecific = false
  ) {
    // Make sure oldData and newData are always strings. This is important because further below String.split is called.
    oldData = typeof oldData === 'string' ? oldData : '';
    newData = typeof newData === 'string' ? newData : '';
    if (!Array.isArray(deltaNames)) {
      deltaNames = [];
    }
    if (!$.isPlainObject(initialDeltaValues)) {
      initialDeltaValues = {};
    }
    if (!Array.isArray(modifiedDeltaNames)) {
      modifiedDeltaNames = [];
    }

    // Sort the delta namespaces from least -> most specific
    deltaNames.sort((a, b) => {
      if (a.length === b.length) {
        return 0;
      }
      if (mostSpecific) {
        return a.length < b.length ? 1 : -1;
      }
      return a.length > b.length ? 1 : -1;
    });

    // Group all the old & new params by namespace
    const groupedOldParams = this._groupParamsByDeltaNames(
      oldData.split('&'),
      deltaNames,
      false,
      initialDeltaValues
    );
    const groupedNewParams = this._groupParamsByDeltaNames(
      newData.split('&'),
      deltaNames,
      true,
      false
    );

    for (let name of deltaNames) {
      if (
        !modifiedDeltaNames.includes(name) &&
        typeof groupedNewParams[name] === 'object' &&
        (typeof groupedOldParams[name] !== 'object' ||
          JSON.stringify(groupedOldParams[name]) !==
            JSON.stringify(groupedNewParams[name]))
      ) {
        modifiedDeltaNames.push(name);
      }
    }

    // Sort the delta namespaces from least -> most specific
    modifiedDeltaNames.sort((a, b) => {
      if (a.length === b.length) {
        return 0;
      }
      if (mostSpecific) {
        return a.length < b.length ? 1 : -1;
      }
      return a.length > b.length ? 1 : -1;
    });

    return [modifiedDeltaNames, groupedNewParams];
  },

  /**
   * @param {Object} params
   * @param {Object} deltaNames
   * @param {boolean} withRoot
   * @param {(boolean|Object)} initialValues
   * @returns {Object}
   * @private
   */
  _groupParamsByDeltaNames: function (
    params,
    deltaNames,
    withRoot,
    initialValues
  ) {
    const grouped = {};

    if (withRoot) {
      grouped.__root__ = [];
    }

    // sort delta names from most to least specific
    deltaNames = deltaNames.sort((a, b) => b.length - a.length);

    for (let name of deltaNames) {
      grouped[name] = [];
    }

    const encodeURIComponentExceptEqualChar = (o) =>
      encodeURIComponent(o).replace('%3D', '=');

    params = params.map((p) => decodeURIComponent(p));

    paramLoop: for (let param of params) {
      for (let name of deltaNames) {
        const paramName = param.substring(0, name.length + 1);
        if ([`${name}=`, `${name}[`].includes(paramName)) {
          if (typeof grouped[name] === 'undefined') {
            grouped[name] = [];
          }
          grouped[name].push(encodeURIComponentExceptEqualChar(param));
          continue paramLoop;
        }
      }

      if (withRoot) {
        grouped.__root__.push(encodeURIComponentExceptEqualChar(param));
      }
    }

    if (initialValues) {
      const serializeParam = (name, value) => {
        if (Array.isArray(value) || $.isPlainObject(value)) {
          value = $.param(value);
        } else if (typeof value === 'string') {
          value = encodeURIComponent(value);
        } else if (value === null) {
          value = '';
        }
        return `${encodeURIComponent(name)}=${value}`;
      };

      for (let name in initialValues) {
        if (initialValues.hasOwnProperty(name)) {
          if ($.isPlainObject(initialValues[name])) {
            grouped[name] = [];
            for (let subName in initialValues[name]) {
              if (initialValues[name].hasOwnProperty(subName)) {
                grouped[name].push(
                  serializeParam(
                    `${name}[${subName}]`,
                    initialValues[name][subName]
                  )
                );
              }
            }
          } else {
            grouped[name] = [serializeParam(name, initialValues[name])];
          }
        }
      }
    }

    return grouped;
  },

  /**
   * Expands an object of POST array-style strings into an actual array.
   *
   * @param {Object} arr
   * @returns {Array}
   */
  expandPostArray: function (arr) {
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
   * @param {string} [data]
   * @returns {(jQuery|HTMLElement)}
   */
  createForm: function (data) {
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
          value: decodeURIComponent(chunks[1] || ''),
        }).appendTo($form);
      }
    }

    return $form;
  },

  /**
   * Compares two variables and returns whether they are equal in value.
   * Recursively compares array and object values.
   *
   * @param {*} obj1
   * @param {*} obj2
   * @param {boolean} [sortObjectKeys] Whether object keys should be sorted before being compared. Default is true.
   * @returns boolean
   */
  compare: function (obj1, obj2, sortObjectKeys) {
    // Compare the types
    if (typeof obj1 !== typeof obj2) {
      return false;
    }

    if (typeof obj1 === 'object' && obj1 !== null && obj2 !== null) {
      // Compare the lengths
      if (obj1.length !== obj2.length) {
        return false;
      }

      // Is one of them an array but the other is not?
      if (Array.isArray(obj1) !== Array.isArray(obj2)) {
        return false;
      }

      // If they're actual objects (not arrays), compare the keys
      if (!Array.isArray(obj1)) {
        if (typeof sortObjectKeys === 'undefined' || sortObjectKeys === true) {
          if (
            !Craft.compare(
              Craft.getObjectKeys(obj1).sort(),
              Craft.getObjectKeys(obj2).sort()
            )
          ) {
            return false;
          }
        } else {
          if (
            !Craft.compare(Craft.getObjectKeys(obj1), Craft.getObjectKeys(obj2))
          ) {
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
      return obj1 === obj2;
    }
  },

  /**
   * Returns an array of an object's keys.
   *
   * @param {Object} obj
   * @returns {string[]}
   */
  getObjectKeys: function (obj) {
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
   * @param {(string|Object)} chars
   * @returns {string}
   */
  escapeChars: function (chars) {
    if (!Array.isArray(chars)) {
      chars = chars.split();
    }

    var escaped = '';

    for (var i = 0; i < chars.length; i++) {
      escaped += '\\' + chars[i];
    }

    return escaped;
  },

  /**
   * Trim characters off of the beginning of a string.
   *
   * @param {string} str
   * @param {(string|Object)} [chars] The characters to trim off. Defaults to a space if left blank.
   * @returns {string}
   */
  ltrim: function (str, chars) {
    if (!str) {
      return str;
    }
    if (typeof chars === 'undefined') {
      return str.trimStart();
    }
    const re = new RegExp('^[' + Craft.escapeChars(chars) + ']+');
    return str.replace(re, '');
  },

  /**
   * Trim characters off of the end of a string.
   *
   * @param {string} str
   * @param {(string|Object)} [chars] The characters to trim off. Defaults to a space if left blank.
   * @returns {string}
   */
  rtrim: function (str, chars) {
    if (!str) {
      return str;
    }
    if (typeof chars === 'undefined') {
      return str.trimEnd();
    }
    const re = new RegExp('[' + Craft.escapeChars(chars) + ']+$');
    return str.replace(re, '');
  },

  /**
   * Trim characters off of the beginning and end of a string.
   *
   * @param {string} str
   * @param {(string|Object)} [chars] The characters to trim off. Defaults to a space if left blank.
   * @returns {string}
   */
  trim: function (str, chars) {
    if (!str) {
      return str;
    }
    if (typeof chars === 'undefined') {
      return str.trim();
    }
    str = Craft.ltrim(str, chars);
    str = Craft.rtrim(str, chars);
    return str;
  },

  /**
   * Returns whether a string starts with another string.
   *
   * @param {string} str
   * @param {string} substr
   * @param {boolean} [caseInsensitive=false]
   * @returns {boolean}
   */
  startsWith: function (str, substr, caseInsensitive = false) {
    if (caseInsensitive) {
      return str.toLowerCase().startsWith(substr.toLowerCase());
    }
    return str.startsWith(substr);
  },

  /**
   * Returns whether a string ends with another string.
   *
   * @param {string} str
   * @param {string} substr
   * @param {boolean} [caseInsensitive=false]
   * @returns {boolean}
   */
  endsWith: function (str, substr, caseInsensitive = false) {
    if (caseInsensitive) {
      return str.toLowerCase().endsWith(substr.toLowerCase());
    }
    return str.endsWith(substr);
  },

  /**
   * Ensures a string starts with another string.
   *
   * @param {string} str
   * @param {string} substr
   * @param {boolean} [caseInsensitive=false]
   * @return {string}
   */
  ensureStartsWith: function (str, substr, caseInsensitive = false) {
    if (!Craft.startsWith(str, substr, caseInsensitive)) {
      str = substr + str;
    }
    return str;
  },

  /**
   * Ensures a string ends with another string.
   *
   * @param {string} str
   * @param {string} substr
   * @param {boolean} [caseInsensitive=false]
   * @return {string}
   */
  ensureEndsWith: function (str, substr, caseInsensitive = false) {
    if (!Craft.endsWith(str, substr, caseInsensitive)) {
      str += substr;
    }
    return str;
  },

  /**
   * Removes a string from the beginning of another string.
   *
   * @param {string} str
   * @param {string} substr
   * @param {boolean} [caseInsensitive=false]
   * @return {string}
   */
  removeLeft: function (str, substr, caseInsensitive = false) {
    if (Craft.startsWith(str, substr, caseInsensitive)) {
      return str.slice(substr.length);
    }
    return str;
  },

  /**
   * Removes a string from the end of another string.
   *
   * @param {string} str
   * @param {string} substr
   * @param {boolean} [caseInsensitive=false]
   * @return {string}
   */
  removeRight: function (str, substr, caseInsensitive = false) {
    if (Craft.endsWith(str, substr, caseInsensitive)) {
      return str.slice(0, -substr.length);
    }
    return str;
  },

  /**
   * @callback filterArrayCallback
   * @param {*} value
   * @param {number} index
   * @return {boolean}
   */
  /**
   * Filters an array.
   *
   * @param {Object} arr
   * @param {filterArrayCallback} [callback] A user-defined callback function. If null, we'll just remove any elements that equate to false.
   * @returns {Array}
   */
  filterArray: function (arr, callback) {
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
   * @callback filterObjectCallback
   * @param {*} value
   * @param {string} key
   * @return {boolean}
   */
  /**
   * Filters an object by a callback method.
   *
   * @param {Object} obj
   * @param {filterObjectCallback} [callback] A user-defined callback function. If null, values that equate to false will be removed.
   * @returns {Object}
   */
  filterObject(obj, callback) {
    if (typeof callback === 'undefined') {
      callback = (v) => !!v;
    }
    return Object.fromEntries(Object.entries(obj).filter(callback));
  },

  /**
   * Returns whether an element is in an array (unlike jQuery.inArray(), which returns the element’s index, or -1).
   *
   * @param {*} elem
   * @param {(Object|Array)} arr
   * @returns {boolean}
   */
  inArray: function (elem, arr) {
    if ($.isPlainObject(arr)) {
      arr = Object.values(arr);
    }
    return arr.includes(elem);
  },

  /**
   * Removes an element from an array.
   *
   * @param {*} elem
   * @param {Array} arr
   * @returns {boolean} Whether the element could be found or not.
   */
  removeFromArray: function (elem, arr) {
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
   * @param {Array} arr
   * @returns {*}
   */
  getLast: function (arr) {
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
   * @returns {string}
   */
  uppercaseFirst: function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  },

  /**
   * Makes the first character of a string lowercase.
   *
   * @param {string} str
   * @returns {string}
   */
  lowercaseFirst: function (str) {
    return str.charAt(0).toLowerCase() + str.slice(1);
  },

  parseUrl: function (url) {
    var m = url.match(
      /^(?:(https?):\/\/|\/\/)([^\/\:]*)(?:\:(\d+))?(\/[^\?]*)?(?:\?([^#]*))?(#.*)?/
    );
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

  /**
   * Returns a URL’s query params as an object.
   * @param {string} [url] The URL. The window’s URL will be used by default.
   * @returns Object
   */
  getQueryParams: function (url) {
    let qs;
    if (url) {
      const m = url.match(/\?.+/);
      if (!m) {
        return {};
      }
      qs = m[0];
    } else {
      qs = window.location.search;
    }
    return Object.fromEntries(new URLSearchParams(qs).entries());
  },

  /**
   * Returns a query param.
   * @param {string} name The param name
   * @param {string} [url] The URL. The window’s URL will be used by default.
   * @returns Object
   */
  getQueryParam: function (name, url) {
    return this.getQueryParams(url)[name];
  },

  isSameHost: function (url) {
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
  secondsToHumanTimeDuration: function (seconds, showSeconds) {
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
      timeComponents.push(
        weeks +
          ' ' +
          (weeks === 1 ? Craft.t('app', 'week') : Craft.t('app', 'weeks'))
      );
    }

    if (days) {
      timeComponents.push(
        days +
          ' ' +
          (days === 1 ? Craft.t('app', 'day') : Craft.t('app', 'days'))
      );
    }

    if (hours) {
      timeComponents.push(
        hours +
          ' ' +
          (hours === 1 ? Craft.t('app', 'hour') : Craft.t('app', 'hours'))
      );
    }

    if (minutes || (!showSeconds && !weeks && !days && !hours)) {
      timeComponents.push(
        minutes +
          ' ' +
          (minutes === 1 ? Craft.t('app', 'minute') : Craft.t('app', 'minutes'))
      );
    }

    if (seconds || (showSeconds && !weeks && !days && !hours && !minutes)) {
      timeComponents.push(
        seconds +
          ' ' +
          (seconds === 1 ? Craft.t('app', 'second') : Craft.t('app', 'seconds'))
      );
    }

    return timeComponents.join(', ');
  },

  /**
   * Converts extended ASCII characters to ASCII.
   *
   * @param {string} str
   * @param {Object} [charMap]
   * @returns {string}
   */
  asciiString: function (str, charMap) {
    // Normalize NFD chars to NFC
    str = str.normalize('NFC');

    var asciiStr = '';
    var char;

    for (var i = 0; i < str.length; i++) {
      char = str.charAt(i);
      asciiStr +=
        typeof (charMap || Craft.asciiCharMap)[char] === 'string'
          ? (charMap || Craft.asciiCharMap)[char]
          : char;
    }

    return asciiStr;
  },

  uuid: function () {
    if (typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }

    // h/t https://stackoverflow.com/a/2117523/1688568
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
      (
        c ^
        (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))
      ).toString(16)
    );
  },

  /**
   * @param {string} name
   * @param {string} [namespace]
   * @returns {string}
   */
  namespaceInputName: function (name, namespace) {
    if (!namespace) {
      return name;
    }

    return name.replace(/([^'"\[\]]+)([^'"]*)/, `${namespace}[$1]$2`);
  },

  /**
   * @param {string} id
   * @param {string} [namespace]
   * @returns {string}
   */
  namespaceId: function (id, namespace) {
    return (
      (namespace ? `${Craft.formatInputId(namespace)}-` : '') +
      Craft.formatInputId(id)
    );
  },

  randomString: function (length) {
    // h/t https://stackoverflow.com/a/1349426/1688568
    var result = '';
    var characters =
      'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (var i = 0; i < length; i++) {
      result += characters.charAt(Math.floor(Math.random() * 62));
    }
    return result;
  },

  /**
   * Creates a validation error list.
   *
   * @param {Object} errors
   * @returns {jQuery}
   */
  createErrorList: function (errors) {
    var $ul = $(document.createElement('ul')).addClass('errors');

    for (var i = 0; i < errors.length; i++) {
      var $li = $(document.createElement('li'));
      $li.appendTo($ul);
      $li.html(errors[i]);
    }

    return $ul;
  },

  _existingCss: null,
  _existingJs: null,

  _appendHtml: async function (html, $parent) {
    if (!html) {
      return;
    }

    const nodes = $.parseHTML(html.trim(), true).filter((node) => {
      if (node.nodeName === 'LINK' && node.href) {
        if (!this._existingCss) {
          this._existingCss = $('link[href]')
            .toArray()
            .map((n) => n.href.replace(/&/g, '&amp;'));
        }

        if (this._existingCss.includes(node.href)) {
          return false;
        }

        this._existingCss.push(node.href);
        return true;
      }

      if (node.nodeName === 'SCRIPT' && node.src) {
        if (!this._existingJs) {
          this._existingJs = $('script[src]')
            .toArray()
            .map((n) => n.src.replace(/&/g, '&amp;'));
        }

        // if this is a cross-domain JS resource, use our app/resource-js proxy to load it
        if (
          node.src.startsWith(this.resourceBaseUrl) &&
          !this.isSameHost(node.src)
        ) {
          node.src = this.getActionUrl('app/resource-js', {
            url: node.src,
          });
        }

        if (this._existingJs.includes(node.src)) {
          return false;
        }

        this._existingJs.push(node.src);
      }

      return true;
    });

    $parent.append(nodes);
  },

  /**
   * Appends HTML to the page `<head>`.
   *
   * @param {string} html
   * @returns {Promise}
   */
  appendHeadHtml: async function (html) {
    await this._appendHtml(html, $('head'));
  },

  /**
   * Appends HTML to the page `<body>`.
   *
   * @param {string} html
   * @returns {Promise}
   */
  appendBodyHtml: async function (html) {
    await this._appendHtml(html, Garnish.$bod);
  },

  /**
   * Appends HTML to the page `<body>`.
   *
   * @deprecated in 4.0.0. `appendBodyHtml()` should be used instead
   */
  appendFootHtml: function (html) {
    console.warn(
      'Craft.appendFootHtml() is deprecated. Craft.appendBodyHtml() should be used instead.'
    );
    this.appendBodyHtml(html);
  },

  /**
   * Initializes any common UI elements in a given container.
   *
   * @param {Object} $container
   */
  initUiElements: function ($container) {
    $('.grid', $container).grid();
    $('.info', $container).infoicon();
    $('.checkbox-select', $container).checkboxselect();
    $('.fieldtoggle', $container).fieldtoggle();
    $('.lightswitch', $container).lightswitch();
    $('.nicetext', $container).nicetext();
    $('.datetimewrapper', $container).datetime();
    $(
      '.datewrapper > input[type="date"], .timewrapper > input[type="time"]',
      $container
    ).datetimeinput();
    $('.formsubmit', $container).formsubmit();
    // menus last, since they can mess with the DOM
    $('.menubtn:not([data-disclosure-trigger])', $container).menubtn();
    $('[data-disclosure-trigger]', $container).disclosureMenu();

    // Open outbound links in new windows
    // hat tip: https://stackoverflow.com/a/2911045/1688568
    $('a', $container).each(function () {
      if (
        this.hostname.length &&
        this.hostname !== location.hostname &&
        typeof $(this).attr('target') === 'undefined'
      ) {
        $(this).attr('rel', 'noopener').attr('target', '_blank');
      }
    });
  },

  _elementIndexClasses: {},
  _elementSelectorModalClasses: {},
  _elementEditorClasses: {},
  _uploaderClasses: {},
  _authFormHandlers: {},

  /**
   * Registers an element index class for a given element type.
   *
   * @param {string} elementType
   * @param {function} func
   */
  registerElementIndexClass: function (elementType, func) {
    if (typeof this._elementIndexClasses[elementType] !== 'undefined') {
      throw (
        'An element index class has already been registered for the element type “' +
        elementType +
        '”.'
      );
    }

    this._elementIndexClasses[elementType] = func;
  },

  /**
   * Registers a file uploader class for a given filesystem type.
   *
   * @param {string} fsType
   * @param {function} func
   */
  registerUploaderClass: function (fsType, func) {
    if (typeof this._uploaderClasses[fsType] !== 'undefined') {
      throw (
        'An asset uploader class has already been registered for the filesystem type “' +
        fsType +
        '”.'
      );
    }

    this._uploaderClasses[fsType] = func;
  },

  /**
   * Registers an element selector modal class for a given element type.
   *
   * @param {string} elementType
   * @param {function} func
   */
  registerElementSelectorModalClass: function (elementType, func) {
    if (typeof this._elementSelectorModalClasses[elementType] !== 'undefined') {
      throw (
        'An element selector modal class has already been registered for the element type “' +
        elementType +
        '”.'
      );
    }

    this._elementSelectorModalClasses[elementType] = func;
  },

  registerAuthFormHandler(method, func) {
    if (typeof this._authFormHandlers[method] !== 'undefined') {
      throw `An authentication form handler has already been registered for the method “${method}”.`;
    }

    this._authFormHandlers[method] = func;
  },

  /**
   * Creates a new element index for a given element type.
   *
   * @param {string} elementType
   * @param {jQuery} $container
   * @param {Object} settings
   * @returns {BaseElementIndex}
   */
  createElementIndex: function (elementType, $container, settings) {
    var func;

    if (typeof this._elementIndexClasses[elementType] !== 'undefined') {
      func = this._elementIndexClasses[elementType];
    } else {
      func = Craft.BaseElementIndex;
    }

    return new func(elementType, $container, settings);
  },

  /**
   * Creates a file uploader for a given filesystem type.
   *
   * @param {string} fsType
   * @param {jQuery} $container
   * @param {Object} settings
   * @returns {Uploader}
   */
  createUploader: function (fsType, $container, settings) {
    const func =
      typeof this._uploaderClasses[fsType] !== 'undefined'
        ? this._uploaderClasses[fsType]
        : Craft.Uploader;

    const uploader = new func($container, settings);
    uploader.fsType = fsType;

    return uploader;
  },

  /**
   * Creates a new element selector modal for a given element type.
   *
   * @param {string} elementType
   * @param {Object} settings
   */
  createElementSelectorModal: function (elementType, settings) {
    var func;

    if (typeof this._elementSelectorModalClasses[elementType] !== 'undefined') {
      func = this._elementSelectorModalClasses[elementType];
    } else {
      func = Craft.BaseElementSelectorModal;
    }

    return new func(elementType, settings);
  },

  createAuthFormHandler(method, container, onSuccess, showError) {
    if (typeof this._authFormHandlers[method] === 'undefined') {
      throw `No authentication form has been registered for the method "${method}".`;
    }

    if (container instanceof jQuery) {
      if (!container.length) {
        throw 'No form element specified.';
      }
      container = container[0];
    }

    if (!showError) {
      showError = (error) => {
        Craft.cp.displayError(error);
      };
    }

    return new this._authFormHandlers[method](container, onSuccess, showError);
  },

  /**
   * Creates a new element editor slideout for a given element type.
   *
   * @param {string} elementType
   * @param {(jQuery|HTMLElement|string)} element
   * @param {Object} settings
   */
  createElementEditor: function (elementType, element, settings) {
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

    return new Craft.ElementEditorSlideout(element, settings);
  },

  /**
   * Retrieves a value from localStorage if it exists.
   *
   * @param {string} key
   * @param {*} [defaultValue]
   */
  getLocalStorage: function (key, defaultValue) {
    key = 'Craft-' + Craft.systemUid + '.' + key;

    if (
      typeof localStorage !== 'undefined' &&
      typeof localStorage[key] !== 'undefined'
    ) {
      return JSON.parse(localStorage[key]);
    } else {
      return defaultValue;
    }
  },

  /**
   * Saves a value to localStorage.
   *
   * @param {string} key
   * @param {*} value
   */
  setLocalStorage: function (key, value) {
    if (typeof localStorage !== 'undefined') {
      key = 'Craft-' + Craft.systemUid + '.' + key;

      // localStorage might be filled all the way up.
      // Especially likely if this is a private window in Safari 8+, where localStorage technically exists,
      // but has a max size of 0 bytes.
      try {
        localStorage[key] = JSON.stringify(value);
      } catch (e) {}
    }
  },

  /**
   * Removes a value from localStorage.
   * @param {string} key
   */
  removeLocalStorage: function (key) {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem(`Craft-${Craft.systemUid}.${key}`);
    }
  },

  /**
   * Returns a cookie value, if it exists, otherwise returns `false`
   * @returns {(string|boolean)}
   */
  getCookie: function (name) {
    // Adapted from https://developer.mozilla.org/en-US/docs/Web/API/Document/cookie
    return document.cookie.replace(
      new RegExp(
        `(?:(?:^|.*;\\s*)Craft-${Craft.systemUid}:${name}\\s*\\=\\s*([^;]*).*$)|^.*$`
      ),
      '$1'
    );
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
  setCookie: function (name, value, options) {
    options = $.extend({}, this.defaultCookieOptions, options);
    let cookie = `Craft-${Craft.systemUid}:${name}=${encodeURIComponent(
      value
    )}`;
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
  removeCookie: function (name) {
    this.setCookie(name, '', new Date('1970-01-01T00:00:00'));
  },

  /**
   * Returns element information from its DOM element.
   *
   * @param {(jQuery|HTMLElement|string)} element
   * @returns {Object}
   */
  getElementInfo: function (element) {
    let $element = $(element);

    if (!$element.hasClass('element')) {
      $element = $element.find('.element:first');
    }

    return {
      id: $element.data('id'),
      siteId: $element.data('site-id'),
      label: $element.data('label'),
      status: $element.data('status'),
      url: $element.data('url'),
      hasThumb: $element.hasClass('has-thumb'),
      $element: $element,
    };
  },

  /**
   * Changes an element to the requested size.
   *
   * @param {(jQuery|HTMLElement|string))} element
   * @param {string} size
   */
  setElementSize: function (element, size) {
    const $element = $(element);

    if (size !== 'small' && size !== 'large') {
      size = 'small';
    }

    if ($element.hasClass(size)) {
      return;
    }

    const otherSize = size === 'small' ? 'large' : 'small';

    $element.addClass(size).removeClass(otherSize);

    if ($element.hasClass('has-thumb')) {
      const $oldImg = $element.find('> .thumb > img'),
        imgSize = size === 'small' ? '30' : '100',
        $newImg = $('<img/>', {
          sizes: imgSize + 'px',
          srcset: $oldImg.attr('srcset') || $oldImg.attr('data-pfsrcset'),
        });

      $oldImg.replaceWith($newImg);

      picturefill({
        elements: [$newImg[0]],
      });
    }
  },

  refreshElementInstances(elementId) {
    const $elements = $(`div.element[data-id="${elementId}"][data-settings]`);
    if (!$elements.length) {
      return;
    }
    const elementsBySite = {};
    for (let i = 0; i < $elements.length; i++) {
      const $element = $elements.eq(i);
      const siteId = $element.data('site-id');
      if (typeof elementsBySite[siteId] === 'undefined') {
        elementsBySite[siteId] = {
          key: i,
          type: $element.data('type'),
          id: elementId,
          fieldId: $element.data('field-id'),
          ownerId: $element.data('owner-id'),
          siteId,
          instances: [],
        };
      }
      elementsBySite[siteId].instances.push($element.data('settings'));
    }
    const data = {
      elements: Object.values(elementsBySite),
    };
    Craft.sendActionRequest('POST', 'app/render-elements', {data}).then(
      ({data}) => {
        const instances = data.elements[elementId] || {};
        for (let key of Object.keys(instances)) {
          const $element = $elements.eq(key);
          const $replacement = $(instances[key]);
          for (let attribute of $replacement[0].attributes) {
            if (attribute.name === 'class') {
              $element.addClass(attribute.value);
            } else {
              $element.attr(attribute.name, attribute.value);
            }
          }
          const $actions = $element
            .find(
              '> .chip-content .chip-actions,> .card-actions-container .card-actions'
            )
            .detach();
          const $inputs = $element.find('input,button').detach();
          $element.html($replacement.html()).removeClass('error');

          if ($actions.length) {
            const $oldStatus = $actions.find('span.status');
            const $newStatus = $replacement.find(
              '> .chip-content .chip-actions span.status,> .card-actions-container .card-actions span.status'
            );

            if (
              $oldStatus.length &&
              $newStatus.length &&
              $oldStatus[0].classList !== $newStatus[0].classList
            ) {
              $actions.find('span.status').replaceWith($newStatus);
            }

            $element
              .find(
                '> .chip-content .chip-actions,> .card-actions-container .card-actions'
              )
              .replaceWith($actions);
          }
          if ($inputs.length) {
            $inputs.appendTo($element);
          }
        }
        Craft.cp.elementThumbLoader.load($elements);
      }
    );
  },

  refreshComponentInstances(type, id) {
    const $chips = $(
      `div.chip[data-type="${$.escapeSelector(
        type
      )}"][data-id="${id}"][data-settings]`
    );
    if (!$chips.length) {
      return;
    }
    const instances = [];
    for (let i = 0; i < $chips.length; i++) {
      instances.push($chips.eq(i).data('settings'));
    }
    const data = {
      components: [{type, id, instances}],
    };
    Craft.sendActionRequest('POST', 'app/render-components', {data}).then(
      ({data}) => {
        for (let i = 0; i < data.components[type][id].length; i++) {
          const $chip = $chips.eq(i);
          const $replacement = $(data.components[type][id][i]);
          for (let attribute of $replacement[0].attributes) {
            if (attribute.name === 'class') {
              $chip.addClass(attribute.value);
            } else {
              $chip.attr(attribute.name, attribute.value);
            }
          }
          const $actions = $chip.find('.chip-actions').detach();
          const $inputs = $chip.find('input,button').detach();
          $chip.html($replacement.html());
          if ($actions.length) {
            $chip.find('.chip-actions').replaceWith($actions);
          }
          if ($inputs.length) {
            $inputs.appendTo($chip);
          }
        }
      }
    );
  },

  /**
   * Adds actions to a chip or card.
   *
   * @param {jQuery|HTMLElement} chip
   * @param {Array} actions
   */
  addActionsToChip(chip, actions) {
    if (!actions?.length) {
      return;
    }

    const $actions = $(chip).find(
      '> .chip-content > .chip-actions, > .card-actions-container > .card-actions'
    );
    let $actionMenuBtn = $actions.find('.action-btn');

    if (!$actionMenuBtn.length) {
      // the chip/card doesn't have an action menu yet, so add one
      const menuId = `actions-${Math.floor(Math.random() * 1000000)}`;
      const labelId = `${menuId}-label`;
      const $label = $('<label/>', {
        id: labelId,
        class: 'visually-hidden',
        text: Craft.t('app', 'Actions'),
      }).appendTo($actions);
      $actionMenuBtn = $('<button/>', {
        class: 'btn action-btn',
        type: 'button',
        title: Craft.t('app', 'Actions'),
        'aria-controls': menuId,
        'aria-describedby': labelId,
        'data-disclosure-trigger': 'true',
      }).insertAfter($label);
      $('<div/>', {
        id: menuId,
        class: 'menu menu--disclosure',
      }).insertAfter($actionMenuBtn);
    }

    const disclosureMenu = $actionMenuBtn
      .disclosureMenu()
      .data('disclosureMenu');

    const safeActions = actions.filter((a) => !a.destructive);
    const destructiveActions = actions.filter((a) => a.destructive);

    if (safeActions.length) {
      disclosureMenu.addItems(safeActions, disclosureMenu.addGroup());
    }

    if (destructiveActions.length) {
      disclosureMenu.addItems(destructiveActions, disclosureMenu.addGroup());
    }

    Craft.initUiElements(disclosureMenu.$container);
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
   * @param {boolean} [options.requireElevatedSession] Whether an elevated session is required
   */
  submitForm: function ($form, options) {
    if (typeof options === 'undefined') {
      options = {};
    }

    if (options.confirm && !confirm(options.confirm)) {
      return;
    }

    if (options.requireElevatedSession) {
      Craft.elevatedSessionManager.requireElevatedSession(() => {
        this._submitFormInternal($form, options);
      });
    } else {
      this._submitFormInternal($form, options);
    }
  },

  _submitFormInternal($form, options) {
    const namespace = options.namespace ?? null;

    if (options.action) {
      $('<input/>', {
        type: 'hidden',
        name: this.namespaceInputName('action', namespace),
        val: options.action,
      }).appendTo($form);
    }

    if (options.redirect) {
      $('<input/>', {
        type: 'hidden',
        name: this.namespaceInputName('redirect', namespace),
        val: options.redirect,
      }).appendTo($form);
    }

    if (options.params) {
      for (let name in options.params) {
        let value = options.params[name];
        $('<input/>', {
          type: 'hidden',
          name: this.namespaceInputName(name, namespace),
          val: value,
        }).appendTo($form);
      }
    }

    if (options.retainScroll) {
      this.setLocalStorage('scrollY', window.scrollY);
    }

    $form.trigger($.extend({type: 'submit'}, options.data));
  },

  /**
   * Traps focus within a container, so when focus is tabbed out of it, it’s cycled back into it.
   * @param {Object} container
   */
  trapFocusWithin: function (container) {
    Garnish.trapFocusWithin(container);
  },

  /**
   * Releases focus within a container.
   * @param {Object} container
   */
  releaseFocusWithin: function (container) {
    Garnish.releaseFocusWithin(container);
  },

  /**
   * Sets focus to the first focusable element within a container.
   * @param {Object} container
   */
  setFocusWithin: function (container) {
    Garnish.setFocusWithin(container);
  },

  /**
   * Reduces an input’s value to characters that match the given regex pattern.
   * @param {jQuery|HTMLElement} input
   * @param {RegExp} regex
   */
  filterInputVal: function (input, regex) {
    const $input = $(input);
    const val = $input.val();
    let selectionStart = $input[0].selectionStart;
    let newVal = '';
    for (let i = 0; i < val.length; i++) {
      if (val[i].match(regex)) {
        newVal += val[i];
      } else if (i < selectionStart) {
        selectionStart--;
      }
    }
    if (newVal !== val) {
      $input.val(newVal);
      $input[0].setSelectionRange(selectionStart, selectionStart);
    }
  },

  /**
   * Reduces an input’s value to numeric characters.
   * @param {jQuery|HTMLElement} input
   * @param {RegExp} regex
   */
  filterNumberInputVal: function (input) {
    this.filterInputVal(input, /[0-9.,\-]/);
  },

  /**
   * Sets/removes attributes on an element.
   *
   * Attributes set to `null` or `false` will be removed.
   *
   * @param {(jQuery|HTMLElement|string)} element
   * @param {Object} attributes
   */
  setElementAttributes: function (element, attributes) {
    const $element = $(element);

    for (let name in attributes) {
      if (!attributes.hasOwnProperty(name)) {
        continue;
      }

      let value = attributes[name];

      if (value === null || value === false) {
        $element.removeAttr(name);
      } else if (value === true) {
        $element.attr(name, '');
      } else if (Array.isArray(value) || $.isPlainObject(value)) {
        if (Craft.dataAttributes.includes(name)) {
          // Make sure it's an object
          value = Object.assign({}, value);
          for (let n in value) {
            if (!value.hasOwnProperty(n)) {
              continue;
            }
            let subValue = value[n];
            if (subValue === null || subValue === false) {
              continue;
            }
            if ($.isPlainObject(subValue) || Array.isArray(subValue)) {
              subValue = JSON.stringify(subValue);
            } else if (subValue === true) {
              subValue = '';
            } else {
              subValue = this.escapeHtml(subValue);
            }
            $element.attr(`${name}-${n}`, subValue);
          }
        } else if (name === 'class') {
          // Make sure it's an array
          if ($.isPlainObject(value)) {
            value = Object.values(value);
          }
          for (let c of value) {
            $element.addClass(c);
          }
        } else if (name === 'style') {
          $element.css(value);
        } else {
          $element.attr(name, JSON.stringify(value));
        }
      } else {
        $element.attr(name, this.escapeHtml(value));
      }
    }
  },

  isVisible: function () {
    return (
      typeof document.visibilityState === 'undefined' ||
      document.visibilityState === 'visible'
    );
  },

  useMobileStyles: function () {
    return Garnish.isMobileBrowser() || document.body.clientWidth < 600;
  },
});

// -------------------------------------------
//  Broadcast channel
// -------------------------------------------

Craft.pageId = Craft.uuid();

if (typeof BroadcastChannel !== 'undefined') {
  const channelName = `CraftCMS:${Craft.appId}`;
  Craft.broadcaster = new BroadcastChannel(channelName);
  Craft.messageReceiver = new BroadcastChannel(channelName);

  Craft.broadcaster.addEventListener('message', (ev) => {
    switch (ev.data.event) {
      case 'beforeTrackJobProgress':
        Craft.cp.cancelJobTracking();
        break;

      case 'trackJobProgress':
        Craft.cp.setJobData(ev.data.jobData);

        if (Craft.cp.jobInfo.length) {
          // Check again after a longer delay than usual,
          // as it looks like another browser tab is driving for now
          const delay = Craft.cp.getNextJobDelay() + 1000;
          Craft.cp.trackJobProgress(delay);
        }

        break;
    }
  });

  Craft.messageReceiver.addEventListener('message', (ev) => {
    if (ev.data.event === 'saveElement') {
      Craft.refreshElementInstances(ev.data.id);
    }
  });
}

// -------------------------------------------
//  Custom jQuery plugins
// -------------------------------------------

$.extend($.fn, {
  animateLeft: function (pos, duration, easing, complete) {
    if (Craft.orientation === 'ltr') {
      return this.velocity({left: pos}, duration, easing, complete);
    } else {
      return this.velocity({right: pos}, duration, easing, complete);
    }
  },

  animateRight: function (pos, duration, easing, complete) {
    if (Craft.orientation === 'ltr') {
      return this.velocity({right: pos}, duration, easing, complete);
    } else {
      return this.velocity({left: pos}, duration, easing, complete);
    }
  },

  /**
   * Disables elements by adding a .disabled class and preventing them from receiving focus.
   */
  disable: function () {
    return this.each(function () {
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
  enable: function () {
    return this.each(function () {
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
  grid: function () {
    return this.each(function () {
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

  infoicon: function () {
    return this.each(function () {
      new Craft.InfoIcon(this);
    });
  },

  /**
   * Sets the element as a container for a checkbox select.
   */
  checkboxselect: function () {
    return this.each(function () {
      if (!$.data(this, 'checkboxSelect')) {
        new Garnish.CheckboxSelect(this);
      }
    });
  },

  /**
   * Sets the element as a field toggle trigger.
   */
  fieldtoggle: function () {
    return this.each(function () {
      if (!$.data(this, 'fieldtoggle')) {
        new Craft.FieldToggle(this);
      }
    });
  },

  lightswitch: function (settings, settingName, settingValue) {
    // param mapping
    if (settings === 'settings') {
      if (typeof settingName === 'string') {
        settings = {};
        settings[settingName] = settingValue;
      } else {
        settings = settingName;
      }

      return this.each(function () {
        var obj = $.data(this, 'lightswitch');
        if (obj) {
          obj.setSettings(settings);
        }
      });
    } else {
      if (!$.isPlainObject(settings)) {
        settings = {};
      }

      return this.each(function () {
        var thisSettings = $.extend({}, settings);

        if (Garnish.hasAttr(this, 'data-value')) {
          thisSettings.value = $(this).attr('data-value');
        }

        if (Garnish.hasAttr(this, 'data-indeterminate-value')) {
          thisSettings.indeterminateValue = $(this).attr(
            'data-indeterminate-value'
          );
        }

        if (!$.data(this, 'lightswitch')) {
          new Craft.LightSwitch(this, thisSettings);
        }
      });
    }
  },

  nicetext: function () {
    return this.each(function () {
      if (!$.data(this, 'nicetext')) {
        new Garnish.NiceText(this);
      }
    });
  },

  formsubmit: function () {
    // Secondary form submit buttons
    return this.on('activate', function (ev) {
      const $btn = $(ev.currentTarget);
      const params = $btn.data('params') || {};
      if ($btn.data('param')) {
        params[$btn.data('param')] = $btn.data('value');
      }

      let $form;
      let namespace = null;

      if ($btn.attr('data-form') === 'false') {
        $form = Craft.createForm()
          .addClass('hidden')
          .append(Craft.getCsrfInput())
          .appendTo(Garnish.$bod);
      } else {
        let $anchor = $btn.closest('.menu--disclosure').length
          ? $btn.closest('.menu--disclosure').data('trigger').$trigger
          : $btn.data('menu')
            ? $btn.data('menu').$anchor
            : $btn;

        let isFullPage = $anchor.parents('.slideout').length == 0;

        if (isFullPage) {
          $form = $anchor.attr('data-form')
            ? $('#' + $anchor.attr('data-form'))
            : $btn.attr('data-form')
              ? $('#' + $btn.attr('data-form'))
              : $anchor.closest('form');
        } else {
          $form = $anchor.closest('form');
          namespace = $anchor.parents('.slideout').data('cpScreen').namespace;
        }

        if ($anchor.data('disclosureMenu')) {
          $anchor.data('disclosureMenu').hide();
        }
      }

      Craft.submitForm($form, {
        confirm: $btn.data('confirm'),
        action: $btn.data('action'),
        redirect: $btn.data('redirect'),
        retainScroll: Garnish.hasAttr($btn, 'data-retain-scroll'),
        requireElevatedSession: Garnish.hasAttr(
          $btn,
          'data-require-elevated-session'
        ),
        namespace: namespace,
        params: params,
        data: $.extend(
          {
            customTrigger: $btn,
          },
          $btn.data('event-data')
        ),
      });
    });
  },

  menubtn: function () {
    return this.each(function () {
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

  disclosureMenu: function (settings) {
    return this.each(function () {
      const $trigger = $(this);
      // Only instantiate if it's not already a disclosure trigger, and it references a disclosure content
      if (!$trigger.data('trigger') && $trigger.attr('aria-controls')) {
        new Garnish.DisclosureMenu($trigger, settings);
      }
    });
  },

  datetime: function () {
    return this.each(function () {
      let $wrapper = $(this);
      let $inputs = $wrapper.find('input:not([name$="[timezone]"])');
      let checkValue = () => {
        let hasValue = false;
        for (let i = 0; i < $inputs.length; i++) {
          if ($inputs.eq(i).val() && !$inputs.eq(i).is(':disabled')) {
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
                  $inputs.eq(i).val('').trigger('input').trigger('change');
                }
                $btn.remove();
                $inputs.first().filter('[type="text"]').focus();
              });
          }
        } else {
          $wrapper.children('.clear-btn').remove();
        }
      };
      $inputs.on('change', checkValue);
      checkValue();
    });
  },

  datetimeinput: function () {
    return this.each(function () {
      const $input = $(this);
      const checkValue = () => {
        if ($input.val() === '') {
          $input.addClass('empty-value');
        } else {
          $input.removeClass('empty-value');
        }
      };
      $input.on('input', checkValue);
      checkValue();
    });
  },
});

// Override Garnish.NiceText.charsLeftHtml() to be more accessible
Garnish.NiceText.charsLeftHtml = (charsLeft) => {
  return Craft.t(
    'app',
    '<span class="visually-hidden">Characters left:</span> {chars, number}',
    {
      chars: charsLeft,
    }
  );
};

Garnish.$doc.ready(function () {
  Craft.initUiElements();
});
