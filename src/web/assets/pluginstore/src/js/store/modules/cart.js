import api from '../../api/cart'
import * as types from '../mutation-types'
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

/**
 * State
 */
const state = {
    checkoutStatus: null,
    cart: null,
    stripePublicKey: null,
    identityMode: 'craftid',
}

/**
 * Getters
 */
const getters = {

    isInCart(state) {
        return plugin => {
            return state.cart.lineItems.find(lineItem => lineItem.purchasable.pluginId == plugin.id)
        }
    },

    isCmsEditionInCart(state) {
        return cmsEdition => {
            return state.cart.lineItems.find(lineItem => lineItem.purchasable.type === 'cms-edition' && lineItem.purchasable.handle === cmsEdition)
        }
    },

    activeTrialPlugins(state, getters, rootState) {
        return rootState.pluginStore.plugins.filter(plugin => {
            if (plugin.editions[0].price > 0 && !getters.pluginHasLicenseKey(plugin.handle)) {
                return getters.installedPlugins.find(installedPlugin => plugin.handle === installedPlugin.handle)
            }
        })
    },

    cartItems(state, getters, rootState) {
        let cartItems = []

        if (state.cart) {
            const lineItems = state.cart.lineItems

            lineItems.forEach(lineItem => {
                let cartItem = {}

                cartItem.lineItem = lineItem

                if (lineItem.purchasable.type === 'plugin-edition') {
                    cartItem.plugin = rootState.pluginStore.plugins.find(p => p.handle === lineItem.purchasable.plugin.handle)
                }

                cartItems.push(cartItem)
            })
        }

        return cartItems
    },

}

/**
 * Actions
 */
const actions = {

    addToCart({commit, state}, newItems) {
        return new Promise((resolve, reject) => {
            const cart = state.cart
            let items = utils.getCartItemsData(cart)

            newItems.forEach(newItem => {
                const alreadyInCart = items.find(item => item.plugin === newItem.plugin)

                if (!alreadyInCart) {
                    items.push(newItem)
                }
            })

            let data = {
                items,
            }

            api.updateCart(cart.number, data, response => {
                commit(types.RECEIVE_CART, {response})
                resolve(response)
            }, response => {
                reject(response)
            })
        })
    },

    removeFromCart({commit, state}, lineItemKey) {
        return new Promise((resolve, reject) => {
            const cart = state.cart

            let items = utils.getCartItemsData(cart)
            items.splice(lineItemKey, 1)

            let data = {
                items,
            }

            api.updateCart(cart.number, data, response => {
                commit(types.RECEIVE_CART, {response})
                resolve(response)
            }, response => {
                reject(response)
            })
        })
    },

    checkout({dispatch, commit}, data) {
        return new Promise((resolve, reject) => {
            api.checkout(data)
                .then(response => {
                    resolve(response)
                })
                .catch(response => {
                    reject(response)
                })
        })
    },

    getCart({dispatch, commit, rootState}) {
        return new Promise((resolve, reject) => {
            dispatch('getOrderNumber')
                .then(orderNumber => {
                    if (orderNumber) {
                        api.getCart(orderNumber, response => {
                            if (!response.error) {
                                commit(types.RECEIVE_CART, {response})
                                resolve(response)
                            } else {
                                // Couldn’t get cart for this order number? Try to create a new one.
                                const data = {}

                                if (!rootState.craft.craftId) {
                                    data.email = rootState.craft.currentUser.email
                                }

                                api.createCart(data, response2 => {
                                    commit(types.RECEIVE_CART, {response: response2})
                                    dispatch('saveOrderNumber', {orderNumber: response2.cart.number})
                                    resolve(response)
                                }, response => {
                                    reject(response)
                                })
                            }
                        }, response => {
                            reject(response)
                        })
                    } else {
                        // No order number yet? Create a new cart.
                        const data = {}

                        if (!rootState.craft.craftId) {
                            data.email = rootState.craft.currentUser.email
                        }

                        api.createCart(data, response => {
                            commit(types.RECEIVE_CART, {response})
                            dispatch('saveOrderNumber', {orderNumber: response.cart.number})
                            resolve(response)
                        }, response => {
                            reject(response)
                        })
                    }
                })
        })
    },

    saveCart({commit, state}, data) {
        return new Promise((resolve, reject) => {
            const cart = state.cart

            api.updateCart(cart.number, data, response => {
                if (!response.errors) {
                    commit(types.RECEIVE_CART, {response})
                    resolve(response)
                } else {
                    reject(response)
                }
            }, response => {
                reject(response)
            })
        })
    },

    resetCart({commit, dispatch}) {
        return new Promise((resolve, reject) => {
            commit(types.RESET_CART)
            dispatch('resetOrderNumber')
            dispatch('getCart')
                .then(response => {
                    resolve(response)
                })
                .catch(response => {
                    reject(response)
                })
        })
    },

    getOrderNumber({state}) {
        return new Promise((resolve, reject) => {
            if (state.cart && state.cart.number) {
                const orderNumber = state.cart.number
                resolve(orderNumber)
            } else {
                api.getOrderNumber(orderNumber => {
                    resolve(orderNumber)
                }, response => {
                    reject(response)
                })
            }
        })
    },

    resetOrderNumber() {
        api.resetOrderNumber()
    },

    saveOrderNumber({state}, {orderNumber}) {
        api.saveOrderNumber(orderNumber)
    },

    savePluginLicenseKeys({state, rootState}, cart) {
        return new Promise((resolve, reject) => {
            let pluginLicenseKeys = []

            cart.lineItems.forEach(lineItem => {
                if (lineItem.purchasable.type === 'plugin-edition') {
                    if (rootState.craft.installedPlugins.find(installedPlugin => installedPlugin.handle === lineItem.purchasable.plugin.handle)) {
                        pluginLicenseKeys.push({
                            handle: lineItem.purchasable.plugin.handle,
                            key: lineItem.options.licenseKey.substr(4)
                        })
                    }
                }
            })

            const data = {
                pluginLicenseKeys
            }

            api.savePluginLicenseKeys(data)
                .then(response => {
                    resolve(response)
                })
                .catch(response => {
                    reject(response)
                })
        })
    }

}

/**
 * Mutations
 */
const mutations = {

    [types.RECEIVE_CART](state, {response}) {
        state.cart = response.cart
        state.stripePublicKey = response.stripePublicKey
    },

    [types.RESET_CART](state) {
        state.cart = null
    },

    [types.CHANGE_IDENTITY_MODE](state, mode) {
        state.identityMode = mode
    }

}

/**
 * Utils
 */
const utils = {

    getCartData(cart) {
        let data = {
            email: cart.email,
            billingAddress: {
                firstName: cart.billingAddress.firstName,
                lastName: cart.billingAddress.lastName,
            },
            items: [],
        }

        data.items = this.getCartItemsData(cart)

        return data
    },

    getCartItemsData(cart) {
        let lineItems = []
        for (let i = 0; i < cart.lineItems.length; i++) {
            let lineItem = cart.lineItems[i]

            switch (lineItem.purchasable.type) {
                case 'plugin-edition':
                    lineItems.push({
                        type: lineItem.purchasable.type,
                        plugin: lineItem.purchasable.plugin.handle,
                        edition: lineItem.purchasable.handle,
                        autoRenew: lineItem.options.autoRenew,
                        cmsLicenseKey: lineItem.options.cmsLicenseKey,
                    })
                    break
                case 'cms-edition':
                    lineItems.push({
                        type: lineItem.purchasable.type,
                        edition: lineItem.purchasable.handle,
                        licenseKey: lineItem.options.licenseKey,
                        autoRenew: lineItem.options.autoRenew,
                    })
                    break
            }
        }

        return lineItems
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
