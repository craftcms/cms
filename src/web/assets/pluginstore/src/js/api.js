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
            developerId: developerId,
            enableCraftId: window.enableCraftId,
            cms: window.cmsInfo,
            [Craft.csrfTokenName]: Craft.csrfTokenValue
        })

        axios.post(Craft.getActionUrl('plugin-store/developer'), params)
            .then(response => {
                let developer = response.data;
                return cb(developer);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginStoreData(cb, errorCb) {
        let data = qs.stringify({
            enableCraftId: window.enableCraftId,
            cms: window.cmsInfo,
            [Craft.csrfTokenName]: Craft.csrfTokenValue
        });

        axios.post(Craft.getActionUrl('plugin-store/plugin-store-data'), data)
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginDetails(pluginId, cb, errorCb) {
        let params = qs.stringify({
            pluginId: pluginId,
            enableCraftId: window.enableCraftId,
            cms: window.cmsInfo,
            [Craft.csrfTokenName]: Craft.csrfTokenValue
        });

        axios.post(Craft.getActionUrl('plugin-store/plugin-details'), params)
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

        return axios.post(Craft.getActionUrl('plugin-store/checkout'), params);
    }

}
