/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {UserPermissions: './UserPermissions.js'},
    output: {
      path: __dirname + '/../../../resources/legacy/userpermissions/dist',
    },
  },
});
