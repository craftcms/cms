/* jshint esversion: 6 */
/* globals module, require */
const {ConfigFactory} = require('@craftcms/webpack');

module.exports = new ConfigFactory({
    type: 'vue',
    config: {
        entry: {'queue-manager': './queue-manager.js'},
    }
});
