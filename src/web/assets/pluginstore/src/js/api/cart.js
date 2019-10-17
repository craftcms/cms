/* global Craft */

import axios from 'axios'

export default {
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
     * Create cart.
     */
    createCart(data) {
        return axios.post(Craft.getActionUrl('plugin-store/create-cart'), data, {
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
    },

    /**
     * Get cart.
     */
    getCart(orderNumber) {
        const data = {
            orderNumber
        }

        return axios.get(Craft.getActionUrl('plugin-store/get-cart', data))
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
        data.orderNumber = orderNumber

        return axios.post(Craft.getActionUrl('plugin-store/update-cart'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },
}
