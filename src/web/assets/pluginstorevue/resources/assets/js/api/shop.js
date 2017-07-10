/**
 * Mocking client-server processing
 */

import Vue from 'vue';
import Resource from 'vue-resource';

Vue.use(Resource);

export default {
    getProducts (cb) {
        Vue.http.get('https://craftid.dev/api/plugins').then(function(data) {
            let plugins = data.body.data;

            return cb(plugins);
        });
    },
    getStaffPicks (cb) {
        Vue.http.get('https://craftid.dev/api/plugins/staff-picks').then(function(data) {
            let plugins = data.body.plugins;

            return cb(plugins);
        });
    },
}
