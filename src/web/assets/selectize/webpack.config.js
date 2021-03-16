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
                        context: NODE_MODULES + 'selectize/dist/js/standalone',
                        from: 'selectize.min.js',
                        to: 'selectize.js'
                    },
                    {
                        context: NODE_MODULES + 'selectize/dist/css',
                        from: 'selectize.css',
                    }
                ]
            })
        ]
    }
});