/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = new CraftWebpackConfig({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new MergeIntoSingleFilePlugin({
                files: {
                    'vue.js': [
                        require.resolve('vue/dist/vue.min.js'),
                        require.resolve('vue-router/dist/vue-router.min.js'),
                        require.resolve('vuex/dist/vuex.min.js'),
                        require.resolve('vue-autosuggest/dist/vue-autosuggest.js'),
                    ],
                },
            }),
        ]
    }
});