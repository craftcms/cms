/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');

module.exports = new CraftWebpackConfig({
    type: 'vue',
    config: {
        entry: {'queue-manager': './queue-manager.js'},
    }
});