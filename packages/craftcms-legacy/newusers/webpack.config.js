/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {NewUsersWidget: './NewUsersWidget.js'},
    output: {
      path: __dirname + '/../../../resources/legacy/newusers/dist',
    },
  },
});
