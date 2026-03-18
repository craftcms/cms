/* jshint esversion: 6 */
/* globals module, require, __dirname */
const path = require('path');
const {getConfig} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

// Resolve dist files directly to bypass Node's strict package exports enforcement
const nm = path.join(__dirname, '..', '..', '..', '..', 'node_modules');

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'vue.js': [
            path.join(nm, 'vue', 'dist', 'vue.min.js'),
            path.join(nm, 'vue-router', 'dist', 'vue-router.min.js'),
            path.join(nm, 'vuex', 'dist', 'vuex.min.js'),
            path.join(nm, 'vue-autosuggest', 'dist', 'vue-autosuggest.js'),
          ],
        },
      }),
    ],
  },
});
