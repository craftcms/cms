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

    getDeveloper(developerId, cb, errorCb) {
        let body = { enableCraftId: window.enableCraftId };
        let options = { emulateJSON: true };

        Vue.http.post(window.craftApiEndpoint+'/developer/'+developerId, body, options)
            .then(data => {
                let developer = data.body;
                return cb(developer);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginStoreData(cb, errorCb) {
        let body = { enableCraftId: window.enableCraftId };
        let options = { emulateJSON: true };
        Vue.http.post(window.craftApiEndpoint+'/plugin-store', body, options)
            .then(response => {
                return cb(response.body);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginDetails(pluginId, cb, errorCb) {
        let body = { enableCraftId: window.enableCraftId };
        let options = { emulateJSON: true };

        Vue.http.post(window.craftApiEndpoint+'/plugin/'+pluginId, body, options)
            .then(data => {
                let pluginDetails = data.body;
                return cb(pluginDetails);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getCraftData(cb, cbError) {
        Vue.http.get(Craft.getActionUrl('plugin-store/craft-data'))
            .then(data => {
                let craftData = data.body;
                return cb(craftData);
            })
            .catch(response => {
                return cbError(response);
            });
    },

    checkout(order) {
        return Vue.http.post(window.craftApiEndpoint+'/checkout', order, {emulateJSON: true});
    }

}
