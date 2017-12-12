import axios from 'axios';
import qs from 'qs';

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
        let params = qs.stringify({
            enableCraftId: window.enableCraftId,
            cms: window.cmsInfo,
        });

        axios.post(window.craftApiEndpoint+'/developer/'+developerId, params)
            .then(response => {
                let developer = response.data;
                return cb(developer);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginStoreData(cb, errorCb) {
        let params = qs.stringify({
            enableCraftId: window.enableCraftId,
            cms: window.cmsInfo,
        });

        axios.post(window.craftApiEndpoint+'/plugin-store', params)
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginDetails(pluginId, cb, errorCb) {
        let params = qs.stringify({
            enableCraftId: window.enableCraftId,
            cms: window.cmsInfo,
        });

        axios.post(window.craftApiEndpoint+'/plugin/'+pluginId, params)
            .then(response => {
                let pluginDetails = response.data;
                return cb(pluginDetails);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getCraftData(cb, cbError) {
        axios.get(Craft.getActionUrl('plugin-store/craft-data'))
            .then(response => {
                let craftData = response.data;
                return cb(craftData);
            })
            .catch(response => {
                return cbError(response);
            });
    },

    checkout(order) {
        let params = qs.stringify(order);

        return axios.post(window.craftApiEndpoint+'/checkout', params);
    }

}
