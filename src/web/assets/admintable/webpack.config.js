/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');

module.exports = configFactory({
    cwd: __dirname,
    type: 'vue',
    config: {
        entry: { app: './main.js'},
        output: {
            filename: 'js/app.js',
            chunkFilename: 'js/[name].js',
        },
    }
});
