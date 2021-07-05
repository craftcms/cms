/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = new CraftWebpackConfig({
    type: 'vue',
    config: {
        entry: { app: './main.js'},
        output: {
            filename: 'js/app.js',
            chunkFilename: 'js/[name].js',
        },
        plugins: [
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: './images',
                        to: './images'
                    }
                ]
            }),
        ],
    },
});
