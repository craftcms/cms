/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {configFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = configFactory({
    context: __dirname,
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: require.resolve('selectize/dist/js/standalone/selectize.min.js'),
                        to: 'selectize.js'
                    },
                    {
                        from: require.resolve('selectize/dist/css/selectize.css'),
                        to: './css/.',
                    }
                ]
            })
        ]
    }
});
