/* jshint esversion: 6 */
/* globals module, require */
const {configFactory} = require('@craftcms/webpack');

module.exports = configFactory({
    context: __dirname,
    config: {
        entry: {
            'jquery.inputmask.bundle': require.resolve('inputmask/dist/jquery.inputmask.bundle.js')
        },
    }
});
