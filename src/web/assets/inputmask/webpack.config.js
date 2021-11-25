/* jshint esversion: 6 */
/* globals module, require */
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
                        context: path.join(path.dirname(require.resolve('inputmask/package.json')), 'dist'),
                        from: './jquery.inputmask.bundle.js*',
                    }
                ]
            }),
        ]
    }
});
