/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = getConfig({
  context: __dirname,
  config: {
    output: {
      path: __dirname + '/../../../resources/legacy/jquerypayment/dist',
    },
    plugins: [
      new CopyWebpackPlugin({
        patterns: [
          {
            from: require.resolve('jquery.payment/lib/jquery.payment.min.js'),
            to: 'jquery.payment.js',
          },
        ],
      }),
    ],
  },
});
