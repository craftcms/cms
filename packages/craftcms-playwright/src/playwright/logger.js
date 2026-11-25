const {Signale} = require('signale');

const logger = new Signale({interactive: true, scope: 'Craft Playwright'});

module.exports = logger;
