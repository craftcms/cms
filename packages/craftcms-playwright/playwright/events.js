/* jshint esversion: 9, strict: false */
/* globals module, require */
const EventEmitter = require('events');
const globalSetup = new EventEmitter();
const cleanAll = new EventEmitter();

module.exports = {
  globalSetup,
  cleanAll,
};
