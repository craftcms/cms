/* jshint esversion: 6 */
/* globals module, require */
const {ConfigFactory} = require('@craftcms/webpack');

module.exports = new ConfigFactory({
    config: {
        entry: {
            'AccountSettingsForm': './AccountSettingsForm.js',
            'profile': './profile.js'
        },
    }
});
