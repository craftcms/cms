/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = getConfig({
  context: __dirname,
  config: {
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
    ],
  },
});
