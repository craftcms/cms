/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');
const {join} = require('path');

module.exports = getConfig({
  context: __dirname,
  watchPaths: [join(__dirname, 'src')],
  config: {
    entry: {
      garnish: './index.js',
    },
    output: {
      path: __dirname + '/../../../cms-assets/resources/legacy/garnish/dist',
    },
    module: {
      rules: [
        {
          test: require.resolve('./src/index.js'),
          loader: 'expose-loader',
          options: {
            exposes: [
              {
                globalName: 'Garnish',
                // `@craftcms/garnish/compat` may have installed `window.Garnish`
                // already (it guards on `typeof window.Garnish === 'undefined'`,
                // yielding to this bundle during the coexistence period). Without
                // `override`, expose-loader throws instead of taking the slot,
                // which aborts the rest of this bundle's init.
                override: true,
                moduleLocalName: 'default',
              },
            ],
          },
        },
      ],
    },
  },
});
