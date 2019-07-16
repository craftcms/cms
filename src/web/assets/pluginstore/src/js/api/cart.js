/* global Craft */

import axios from 'axios'

export default {
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
    getCart(orderNumber) {
        const data = {
            orderNumber
        }

        return axios.get(Craft.getActionUrl('plugin-store/get-cart', data))
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
