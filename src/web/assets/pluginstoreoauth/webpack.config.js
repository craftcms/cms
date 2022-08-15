/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      PluginStoreOauthCallback: './PluginStoreOauthCallback.js',
      parseFragmentString: './parseFragmentString.js',
    },
  },
});
