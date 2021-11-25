/* jshint esversion: 6 */
/* globals module, require */
const {getConfig} = require('@craftcms/webpack');

module.exports = getConfig({
    context: __dirname,
    config: {
        entry: {
            'jquery.inputmask.bundle': require.resolve('inputmask/dist/jquery.inputmask.bundle.js')
        },
    }
});
