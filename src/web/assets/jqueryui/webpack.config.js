/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('../../../../packages/craftcms-webpack/CraftWebpackConfig');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');
const NODE_MODULES = __dirname + '/../../../../node_modules/';

module.exports = new CraftWebpackConfig({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new MergeIntoSingleFilePlugin({
                files: {
                    'jquery-ui.js': [
                        NODE_MODULES + '/jquery-ui/ui/version.js',
                        NODE_MODULES + '/jquery-ui/ui/widget.js',
                        NODE_MODULES + '/jquery-ui/ui/position.js',
                        NODE_MODULES + '/jquery-ui/ui/focusable.js',
                        NODE_MODULES + '/jquery-ui/ui/keycode.js',
                        NODE_MODULES + '/jquery-ui/ui/scroll-parent.js',
                        NODE_MODULES + '/jquery-ui/ui/widgets/datepicker.js',
                        NODE_MODULES + '/jquery-ui/ui/widgets/mouse.js',
                    ],
                },
                transform: {
                    'jquery-ui.js': code => require("uglify-js").minify(code).code
                }
            }),
        ]
    }
});