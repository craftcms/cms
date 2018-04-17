import axios from 'axios'

export default {

    /**
     * Create cart.
     */
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

    /**
     * Update cart.
     */
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

    /**
     * Reset order number.
     */
    resetOrderNumber() {
        localStorage.removeItem('orderNumber')
    },

    /**
     * Save order number
     */
    saveOrderNumber(orderNumber) {
        localStorage.setItem('orderNumber', orderNumber)
    },

    /**
     * Get order number.
     */
    getOrderNumber(cb) {
        const orderNumber = localStorage.getItem('orderNumber')

        return cb(orderNumber)
    },

    /**
     * Get cart.
     */
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

    /**
     * Checkout.
     */
    checkout(data) {
        return axios.post(Craft.getActionUrl('plugin-store/checkout'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    /**
     * Save plugin license keys
     */
    savePluginLicenseKeys(data) {
        return axios.post(Craft.getActionUrl('plugin-store/save-plugin-license-keys'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

}
