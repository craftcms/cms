/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {ConfigFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new ConfigFactory({
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
