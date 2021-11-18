/* jshint esversion: 6 */
/* globals module, require, __dirname */
const CraftWebpackConfig = require('@craftcms/webpack/CraftWebpackConfig');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = new CraftWebpackConfig({
    type: 'lib',
    config: {
        entry: {'entry': './entry.js'},
        plugins: [
            new MergeIntoSingleFilePlugin({
                files: {
                    'jquery-ui.js': [
                        require.resolve('jquery-ui/ui/version.js'),
                        require.resolve('jquery-ui/ui/widget.js'),
                        require.resolve('jquery-ui/ui/position.js'),
                        require.resolve('jquery-ui/ui/focusable.js'),
                        require.resolve('jquery-ui/ui/keycode.js'),
                        require.resolve('jquery-ui/ui/scroll-parent.js'),
                        require.resolve('jquery-ui/ui/widgets/datepicker.js'),
                        require.resolve('jquery-ui/ui/widgets/mouse.js'),
                    ],
                },
                transform: {
                    'jquery-ui.js': code => require("uglify-js").minify(code).code
                }
            }),
        ]
    }
});
