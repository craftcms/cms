/* jshint esversion: 6 */
/* globals module, require,  __dirname */
const path = require('path');
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');

module.exports = new CraftWebpackConfig({
    postCssConfig: path.resolve(__dirname, 'postcss.config.js'),
    config: {
        entry: {'TailwindCss': './TailwindCss.js'},
    }
});