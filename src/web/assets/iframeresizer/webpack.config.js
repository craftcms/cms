/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {ConfigFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = new ConfigFactory({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        context: path.join(path.dirname(require.resolve('iframe-resizer/package.json')), 'js'),
                        from: 'iframeResizer.*',
                        to: '.',
                    }
                ],
            }),
        ]
    }
});
