/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = configFactory({
    context: __dirname,
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        context: path.dirname(require.resolve('punycode/package.json')),
                        from: './punycode.*',
                        to: '.'
                    }
                ]
            }),
        ]
    }
});
