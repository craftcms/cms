/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const NODE_MODULES = __dirname + '/../../../../node_modules/';

module.exports = new CraftWebpackConfig({
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        context: NODE_MODULES + '/iframe-resizer/js',
                        from: 'iframeResizer.min.js',
                        to: 'iframeResizer.js',
                    },
                    {
                        context: NODE_MODULES + '/iframe-resizer/js',
                        from: 'iframeResizer.map',
                        to: 'iframeResizer.map',
                    },
                    {
                        context: NODE_MODULES + '/iframe-resizer/js',
                        from: 'iframeResizer.contentWindow.min.js',
                        to: 'iframeResizer.contentWindow.js',
                    },
                ],
            }),
        ]
    }
});