/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('../../../../packages/craftcms-webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new CraftWebpackConfig({
    config: {
        entry: {'CraftSupportWidget': './CraftSupportWidget.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [{
                    from: './logos',
                    to: './logos',
                }]
            }),
        ]
    }
});