/* jshint esversion: 6 */
/* globals module, require */
const {ConfigFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new ConfigFactory({
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
