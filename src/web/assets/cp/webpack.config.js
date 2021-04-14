/* jshint esversion: 6 */
/* globals module, require, webpack */
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new CraftWebpackConfig({
    config: {
        entry: {
            'Craft': './_entry.js',
            'charts': './_charts.js'
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
                        from: './images',
                        to: './images',
                    }
                ]
            }),
        ]
    }
});