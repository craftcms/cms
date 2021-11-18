/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new CraftWebpackConfig({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: require.resolve('@benmajor/jquery-touch-events/src/jquery.mobile-events.min.js'),
                        to: 'jquery.mobile-events.js',
                    },
                ],
            }),
        ]
    }
});
