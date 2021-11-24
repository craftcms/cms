/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');

module.exports = configFactory({
  context: __dirname,
  config: {
    entry: {
      'PluginStoreOauthCallback': './PluginStoreOauthCallback.js',
      'parseFragmentString': './parseFragmentString.js',
    },
  }
});
