/*jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const JSONMinifyPlugin = require('node-json-minify');
const path = require('path');

module.exports = new CraftWebpackConfig({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: require.resolve('d3/build/d3.min.js'),
                        to: 'd3.js',
                    },
                    {
                        context: path.dirname(require.resolve('d3-format/package.json')),
                        from: 'locale/*.json',
                        to: 'd3-format/',
                        transform: function(content) {
                            return JSONMinifyPlugin(content.toString());
                        }
                    },
                    {
                        context: path.dirname(require.resolve('d3-time-format/package.json')),
                        from: 'locale/*.json',
                        to: 'd3-time-format/',
                        transform: function(content) {
                            return JSONMinifyPlugin(content.toString());
                        }
                    },
                ],
            }),
        ]
    }
});
