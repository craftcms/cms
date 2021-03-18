/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const NODE_MODULES = __dirname + '/../../../../node_modules/';

module.exports = new CraftWebpackConfig({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        context: NODE_MODULES + '/garnishjs/dist',
                        from: 'garnish.min.js',
                        to: './garnish.js',
                    },
                    {
                        context: NODE_MODULES + '/garnishjs/dist',
                        from: 'garnish.min.js.map',
                        to: './garnish.min.js.map',
                    },
                ],
            }),
        ]
    }
});