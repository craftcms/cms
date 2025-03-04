import Base from './lib/Base.js';
import Garnish from './Garnish.js';
import $ from 'jquery';

/**
 * Garnish base class
 */
export default Base.extend({
  settings: null,

  _eventHandlers: null,
  _namespace: null,
  _$listeners: null,
  _disabled: false,

  constructor: function () {
    this._eventHandlers = [];
    this._namespace = '.Garnish' + Math.floor(Math.random() * 1000000000);
    this._listeners = [];
    this.init.apply(this, arguments);
  },

  init: $.noop,

  setSettings: function (settings, defaults) {
    var baseSettings =
      typeof this.settings === 'undefined' ? {} : this.settings;
    this.settings = $.extend({}, baseSettings, defaults, settings);
  },

  on: function (events, data, handler) {
    if (typeof data === 'function') {
      handler = data;
      data = {};
    }

    events = Garnish._normalizeEvents(events);

    for (var i = 0; i < events.length; i++) {
      var ev = events[i];
      this._eventHandlers.push({
        type: ev[0],
        namespace: ev[1],
        data: data,
        handler: handler,
      });
    }
  },

  off: function (events, handler) {
    events = Garnish._normalizeEvents(events);

    for (var i = 0; i < events.length; i++) {
      var ev = events[i];

      for (var j = this._eventHandlers.length - 1; j >= 0; j--) {
        var eventHandler = this._eventHandlers[j];

        if (
          eventHandler.type === ev[0] &&
          (!ev[1] || eventHandler.namespace === ev[1]) &&
          eventHandler.handler === handler
        ) {
          this._eventHandlers.splice(j, 1);
        }
      }
    }
  },

  once: function (events, data, handler) {
    if (typeof data === 'function') {
      handler = data;
      data = {};
    }

    const onceler = (event) => {
      this.off(events, onceler);
      handler(event);
    };
    this.on(events, data, onceler);
  },

  trigger: function (type, data) {
    const ev = {
      type: type,
      target: this,
    };

    // instance level event handlers
    this._eventHandlers
      .filter((handler) => handler.type === type)
      .forEach((handler) => {
        const _ev = $.extend({data: handler.data}, data, ev);
        handler.handler(_ev);
      });

    // class level event handlers
    Garnish._eventHandlers
      .filter(
        (handler) =>
          handler &&
          handler.target &&
          this instanceof handler.target &&
          handler.type === type
      )
      .forEach((handler) => {
        const _ev = $.extend({data: handler.data}, data, ev);
        handler.handler(_ev);
      });
  },

  _splitEvents: function (events) {
    if (typeof events === 'string') {
      events = events.split(',');

      for (var i = 0; i < events.length; i++) {
        events[i] = $.trim(events[i]);
      }
    }

    return events;
  },

  _formatEvents: function (events) {
    events = this._splitEvents(events).slice(0);

    for (var i = 0; i < events.length; i++) {
      events[i] += this._namespace;
    }

    return events.join(' ');
  },

  addListener: function (elem, events, data, func) {
    var $elem = $(elem);

    // Ignore if there aren't any elements
    if (!$elem.length) {
      return;
    }

    events = this._splitEvents(events);

    // Param mapping
    if (typeof func === 'undefined' && typeof data !== 'object') {
      // (elem, events, func)
      func = data;
      data = {};
    }

    if (typeof func === 'function') {
      func = func.bind(this);
    } else {
      func = this[func].bind(this);
    }

    $elem.on(
      this._formatEvents(events),
      data,
      $.proxy(function () {
        if (!this._disabled) {
          return func.apply(this, arguments);
        }
      }, this)
    );

    // Remember that we're listening to this element
    if ($.inArray(elem, this._listeners) === -1) {
      this._listeners.push(elem);
    }
  },

  removeListener: function (elem, events) {
    $(elem).off(this._formatEvents(events));
  },

  removeAllListeners: function (elem) {
    $(elem).off(this._namespace);
  },

  disable: function () {
    this._disabled = true;
  },

  enable: function () {
    this._disabled = false;
  },

  destroy: function () {
    this.trigger('destroy');
    this.removeAllListeners(this._listeners);
  },
});
