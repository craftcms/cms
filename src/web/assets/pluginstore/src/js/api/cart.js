import axios from 'axios'

export default {

    createCart(data, cb, errorCb) {
        axios.post(Craft.getActionUrl('plugin-store/create-cart'), data, {
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

    updateCart(orderNumber, data, cb, errorCb) {
        data.orderNumber = orderNumber

        axios.post(Craft.getActionUrl('plugin-store/update-cart'), data, {
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

    resetOrderNumber() {
        localStorage.removeItem('orderNumber')
    },

    saveOrderNumber(orderNumber) {
        localStorage.setItem('orderNumber', orderNumber)
    },

    getOrderNumber(cb) {
        const orderNumber = localStorage.getItem('orderNumber')

        return cb(orderNumber)
    },

    getCart(orderNumber, cb, errorCb) {
        const data = {
            orderNumber
        }

        axios.get(Craft.getActionUrl('plugin-store/get-cart', data))
            .then(response => {
                return cb(response.data)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

    checkout(data) {
        return axios.post(Craft.getActionUrl('plugin-store/checkout'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    savePluginLicenseKeys(data) {
        return axios.post(Craft.getActionUrl('plugin-store/save-plugin-license-keys'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

}
