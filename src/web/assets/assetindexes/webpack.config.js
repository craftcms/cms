/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('../../../../packages/craftcms-webpack/CraftWebpackConfig');

module.exports = new CraftWebpackConfig({
    config: {
        entry: {'AssetIndexesUtility': './AssetIndexesUtility.js'},
    }
});