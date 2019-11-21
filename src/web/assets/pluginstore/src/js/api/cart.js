/* global Craft */

import axios from 'axios'

export default {
    /**
     * Checkout.
     */
    checkout(data) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('POST', 'payments', {
                    data,
                })
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch((error) => {
                    reject(error)
                })
        })
    },

    /**
     * Create cart.
     */
    createCart(data) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('POST', 'carts', {
                    data,
                })
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch((error) => {
                    reject(error)
                })
        })
    },

    /**
     * Get cart.
     */
    getCart(orderNumber) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'carts/' + orderNumber)
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch((error) => {
                    reject(error)
                })
        })
    },

    /**
     * Get order number.
     */
    getOrderNumber(cb) {
        const orderNumber = localStorage.getItem('orderNumber')

        return cb(orderNumber)
    },

    /**
     * Reset order number.
     */
    resetOrderNumber() {
        localStorage.removeItem('orderNumber')
    },

    /**
     * Save order number.
     */
    saveOrderNumber(orderNumber) {
        localStorage.setItem('orderNumber', orderNumber)
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

    /**
     * Update cart.
     */
    updateCart(orderNumber, data) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('POST', 'carts/' + orderNumber, {data})
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch((error) => {
                    reject(error)
                })
        })
    },
}
