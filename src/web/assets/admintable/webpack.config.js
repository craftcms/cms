/* jshint esversion: 6 */
/* globals module, require */
const {ConfigFactory} = require('@craftcms/webpack');

module.exports = new ConfigFactory({
    type: 'vue',
    config: {
        entry: { app: './main.js'},
        output: {
            filename: 'js/app.js',
            chunkFilename: 'js/[name].js',
        },
    }
});
