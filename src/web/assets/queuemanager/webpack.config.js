/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');

module.exports = configFactory({
    context: __dirname,
    type: 'vue',
    config: {
        entry: {'queue-manager': './queue-manager.js'},
    }
});
