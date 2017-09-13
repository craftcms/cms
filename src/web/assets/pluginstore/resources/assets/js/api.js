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
        Vue.http.get(window.craftApiEndpoint+'/developer/'+developerId).then(data => {
            let developer = data.body;

            return cb(developer);
        });
    },

    getPluginStoreData(cb, cbError) {
        Vue.http.get(window.craftApiEndpoint+'/plugin-store')
            .then(response => {
                return cb(response.body);
            })
            .catch(response => {
                return cbError(response);
            });
    },

    getCraftData(cb) {
        Vue.http.get(Craft.getActionUrl('plugin-store/craft-data')).then(data => {
            let craftData = data.body;

            return cb(craftData);
        });
    },

    saveCraftData(cb, craftData) {
        Vue.http.post(Craft.getActionUrl('plugin-store/save-craft-data'), { craftData: craftData }, {emulateJSON: true}).then(data => {
            let craftData = data.body;

            return cb(craftData);
        });
    },

    clearCraftData(cb) {
        return Vue.http.get(Craft.getActionUrl('plugin-store/clear-craft-data'));
    },

    checkout(order) {
        return Vue.http.post(window.craftApiEndpoint+'/checkout', order, {emulateJSON: true});
    }
}
