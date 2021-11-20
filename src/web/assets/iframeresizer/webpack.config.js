/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = new CraftWebpackConfig({
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
