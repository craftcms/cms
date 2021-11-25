/*jshint esversion: 6 */
/* globals module, require, __dirname */
const {configFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const JSONMinifyPlugin = require('node-json-minify');
const pkgDir = require('pkg-dir');

module.exports = configFactory({
    context: __dirname,
    config: {
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: require.resolve('d3/build/d3.js'),
                    },
                    {
                        context: pkgDir.sync(require.resolve('d3-format')),
                        from: 'locale/*.json',
                        to: 'd3-format/',
                        transform: function(content) {
                            return JSONMinifyPlugin(content.toString());
                        }
                    },
                    {
                        context: pkgDir.sync(require.resolve('d3-time-format')),
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
