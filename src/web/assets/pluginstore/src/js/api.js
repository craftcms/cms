import axios from 'axios';
import qs from 'qs';

export default {

    createCart(data, cb, errorCb) {
        axios.post(Craft.getActionUrl('plugin-store/create-cart'), data, {
                headers: {
                    'X-CSRF-Token':  Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    updateCart(orderNumber, data, cb, errorCb) {
        data.orderNumber = orderNumber;

        axios.post(Craft.getActionUrl('plugin-store/update-cart'), data, {
                headers: {
                    'X-CSRF-Token':  Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    resetOrderNumber() {
        localStorage.removeItem('orderNumber');
    },

    saveOrderNumber(orderNumber) {
        localStorage.setItem('orderNumber', orderNumber);
    },

    getOrderNumber(cb) {
        const orderNumber = localStorage.getItem('orderNumber');

        return cb(orderNumber);
    },

    getCart(orderNumber, cb, errorCb) {
        const data = {
            orderNumber
        }

        axios.get(Craft.getActionUrl('plugin-store/get-cart', data))
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getDeveloper(developerId, cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/developer'), {
                params: {
                    developerId: developerId,
                },
                headers: {
                    'X-CSRF-Token':  Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginStoreData(cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/plugin-store-data'), '', {
                headers: {
                    'X-CSRF-Token':  Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data);
            })
            .catch(response => {
                return errorCb(response);
            });
    },

    getPluginDetails(pluginId, cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/plugin-details'), {
                params: {
                    pluginId: pluginId,
                },
                headers: {
                    'X-CSRF-Token':  Craft.csrfTokenValue,
                }
            })
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

    checkout(data) {
        return axios.post(Craft.getActionUrl('plugin-store/checkout'), data, {
            headers: {
                'X-CSRF-Token':  Craft.csrfTokenValue,
            }
        });
    },

    savePluginLicenseKeys(data) {
        return axios.post(Craft.getActionUrl('plugin-store/save-plugin-license-keys'), data, {
            headers: {
                'X-CSRF-Token':  Craft.csrfTokenValue,
            }
        })
    },

    tryEdition(edition) {
        return axios.post(Craft.getActionUrl('app/try-edition'), 'edition='+edition, {
            headers: {
                'X-CSRF-Token':  Craft.csrfTokenValue,
            }
        })
    }

}
