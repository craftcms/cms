/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {configFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');
const modulePath = path.dirname(require.resolve('jquery-ui/package.json'));

module.exports = configFactory({
    context: __dirname,
    config: {
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        context: path.join(modulePath, 'ui', 'i18n'),
                        from: '*',
                        to: '.'
                    },
                ],
            }),
        ]
    }
});
