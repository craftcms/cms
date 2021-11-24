/* jshint esversion: 6 */
/* globals module, require, __dirname */
const {configFactory} = require('@craftcms/webpack');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = configFactory({
    context: __dirname,
    config: {
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
            }),
        ]
    }
});
