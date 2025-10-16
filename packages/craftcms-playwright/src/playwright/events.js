/* jshint esversion: 11, strict: false */
const EventEmitter = require('events');

class AsyncEventEmitter extends EventEmitter {
  async emit(type, ...args) {
    const handler = this._events?.[type];
    if (!handler || (Array.isArray(handler) && handler.length === 0)) {
      return false;
    }

    const promises = [];

    if (typeof handler === 'function') {
      promises.push(Reflect.apply(handler, this, args));
    } else {
      const listeners = Array.from(handler);
      for (let i = 0; i < listeners.length; i += 1) {
        promises.push(Reflect.apply(listeners[i], this, args));
      }
    }

    await Promise.all(promises);

    return true;
  }
}

const globalSetup = new AsyncEventEmitter();
const cleanAll = new AsyncEventEmitter();

module.exports = {
  globalSetup,
  cleanAll,
};
