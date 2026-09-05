/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      'component-select-input': './component-select-input.js',
      dashboard: require('path').resolve(
        __dirname,
        '../../../yii2-adapter/resources/js/dashboard.js'
      ),
      'cp-compat': './cp-compat.js',
      'legacy-html-control': './legacy-html-control.js',
    },
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/cpcompat/dist',
    },
  },
});
