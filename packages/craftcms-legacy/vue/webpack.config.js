/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig, resolvePackageFile} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');
const path = require('path');

// Vue 2 is in legacy node_modules (root has Vue 3)
const legacyNodeModules = path.resolve(__dirname, '../node_modules');
const resolveLegacyPackage = (pkg, file) => path.join(legacyNodeModules, pkg, file);

module.exports = getConfig({
  context: __dirname,
  config: {
    plugins: [
      new MergeIntoSingleFilePlugin({
        files: {
          'vue.js': [
            // Vue 2 from legacy node_modules (root has Vue 3)
            resolveLegacyPackage('vue', 'dist/vue.min.js'),
            // vue-router, vuex, vue-autosuggest from root (Vue 2 compatible versions)
            resolvePackageFile('vue-router', 'dist/vue-router.min.js'),
            resolvePackageFile('vuex', 'dist/vuex.min.js'),
            resolvePackageFile('vue-autosuggest', 'dist/vue-autosuggest.js'),
          ],
        },
      }),
    ],
  },
});
