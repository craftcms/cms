/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');

module.exports = new CraftWebpackConfig({
    config: {
        entry: {'utilities': './utilities.js'},
    },
    removeFiles: { test: [/\.js(\.map)*$/] },
});