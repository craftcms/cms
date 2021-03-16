/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');

module.exports = new CraftWebpackConfig({
    type: 'vue',
    config: {
        entry: { app: './main.js'},
        output: {
            filename: 'js/app.js',
            chunkFilename: 'js/[name].js',
        },
    }
});
