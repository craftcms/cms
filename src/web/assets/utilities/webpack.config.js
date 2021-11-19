/* jshint esversion: 6 */
/* globals module, require */
const {ConfigFactory} = require('@craftcms/webpack');

module.exports = new ConfigFactory({
    config: {
        entry: {'utilities': './utilities.js'},
    },
    removeFiles: { test: [/\.js(\.map)*$/] },
});
