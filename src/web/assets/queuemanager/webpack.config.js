/* jshint esversion: 6 */
/* globals module, require */
const CraftWebpackConfig = require('../../../../CraftWebpackConfig');

module.exports = new CraftWebpackConfig({
    type: 'vue',
    config: {
        entry: { 'queue-manager': './queue-manager.js'},
        optimization: {
            // splitChunks: {
            //     name: false,
            //     cacheGroups: {
            //         commons: {
            //             test: /[\\/]node_modules[\\/]/,
            //             name: 'chunk-vendors',
            //             chunks: 'all'
            //         }
            //     }
            // }
        },
    }
});
