import Vue from 'vue';
import Resource from 'vue-resource';

Vue.use(Resource);

export default {
    saveCartState(cb, cartState) {
        localStorage.setItem('cartState', JSON.stringify(cartState));

        return cb();
    },

    getCartState(cb) {
        let cartState = localStorage.getItem('cartState');

        if(cartState) {
            cartState = JSON.parse(cartState);
        }

        return cb(cartState);
    },

    getDeveloper(cb, developerId) {
        Vue.http.get('https://craftid.dev/api/developer/'+developerId).then(function(data) {
            let developer = data.body;

            return cb(developer);
        });
    },

    getPluginStoreData(cb) {
        Vue.http.get('https://craftid.dev/api/plugin-store').then(function(data) {
            let pluginStoreData = data.body;

            return cb(pluginStoreData);
        });
    },

    getCraftData(cb) {
        Vue.http.get(Craft.getActionUrl('plugin-store/craft-data')).then(function(data) {
            let craftData = data.body;

            return cb(craftData);
        });
    }
}
