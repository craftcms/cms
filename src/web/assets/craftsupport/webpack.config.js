/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = configFactory({
    context: __dirname,
    config: {
        entry: {
            CraftSupportWidget: './CraftSupportWidget.js'
        },
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
