/* jshint esversion: 6 */
/* globals module, require, webpack */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      ConditionBuilder: './ConditionBuilder.js',
    },
    output: {
      path: __dirname + '/../../../resources/legacy/conditionbuilder/dist',
    },
  },
});
