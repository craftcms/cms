/* jshint esversion: 6 */
/* globals module, require, webpack */
const {configFactory} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = configFactory({
    context: __dirname,
    config: {
        entry: {
            'Craft': './Craft.js',
            'charts': './charts.js'
        },
        output: { filename: 'js/[name].min.js' },
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    /*
                    * Only some of the images are used in the CSS. So we need to make
                    * sure we have them all
                    * */
                    {
                        from: './images/**/*',
                        to: '.'
                    }
                ]
            }),
        ]
    },
    removeFiles: { include: ['js/charts.min.js', 'js/charts.min.js.map'] },
});
