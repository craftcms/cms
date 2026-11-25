/* jshint esversion: 6 */
/* globals module, require */
const path = require('path');
const {getConfig} = require('@craftcms/webpack');

const config = getConfig({
  context: __dirname,
  config: {
    entry: {graphiql: './graphiql.js'},
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-react'],
            },
          },
        },
      ],
    },
  },
});

module.exports = config;
