import api from '../../api'
import * as types from '../mutation-types'
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex);

const state = {
    checkoutStatus: null,
    remoteCart: null,
    cartForm: null,
};

/**
 * Getters
 */
const getters = {

    isInTrial(state, rootState) {
        return plugin => {
            return rootState.activeTrialPlugins.find(p => p.id == plugin.id)
        }
    },

    isInCart(state) {
        return plugin => {
            return state.remoteCart.lineItems.find(lineItem => lineItem.purchasable.pluginId == plugin.id)
        }
    },

    cartTotal(state) {
        if(state.remoteCart) {
            return state.remoteCart.totalPrice;
        }

        return 0;
    },

    activeTrialPlugins(state, rootState) {
        if (!rootState.craftData.installedPlugins) {
            return [];
        }

        let plugins = rootState.craftData.installedPlugins.map(installedPlugin => {
            if (rootState.pluginStoreData.plugins) {
                return rootState.pluginStoreData.plugins.find(p => p.handle == installedPlugin.handle)
            }
        });

        return plugins.filter(p => {
            if (p) {
                return p.editions[0].price > 0;
            }
        });
    },

    remoteCart(state) {
        return state.remoteCart
    },

    cartItems(state, rootState) {
        const lineItems = state.remoteCart.lineItems

        let cartItems = []

        lineItems.forEach(lineItem => {
            let cartItem = {};

            cartItem.lineItem = lineItem;

            if (lineItem.purchasable.type === 'plugin-edition') {
                cartItem.plugin = rootState.pluginStoreData.plugins.find(p => p.handle === lineItem.purchasable.plugin.handle);
            }

            cartItems.push(cartItem)
        })
        
        return cartItems
    }

};

/**
 * Actions
 */
const actions = {

    addToCart({commit, state}, newItems) {
        return new Promise((resolve, reject) => {
            const cart = state.remoteCart
            let items = utils.getCartItemsData(cart)

            newItems.forEach(newItem => {
                const alreadyInCart = items.find(item => item.plugin === newItem.plugin)

                if(!alreadyInCart) {
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
            const cart = state.remoteCart

            let items = utils.getCartItemsData(cart)
            items.splice(lineItemKey, 1)

            let data = {
                items,
            };

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
                    commit(types.CHECKOUT, {response})
                    dispatch('resetCart')
                        .then(() => {
                            resolve(response)
                        })
                        .catch(() => {
                            reject(response)
                        })

                })
                .catch(response => {
                    reject(response)
                });
        })
    },

    getCart({dispatch, commit}) {
        return new Promise((resolve, reject) => {
            dispatch('getOrderNumber')
                .then(orderNumber => {
                    if (orderNumber) {
                        api.getCart(orderNumber, response => {
                            commit(types.RECEIVE_CART, {response})
                            resolve(response)
                        }, response => {
                            reject(response)
                        })
                    } else {
                        const data = {
                            email: 'ben@pixelandtonic.com',
                            billingAddress: {
                                firstName: 'Benjamin',
                                lastName: 'David',
                            },
                            items: [],
                            licenseKey: 'cmsLicenseKey',
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
            const remoteCart = state.remoteCart

            api.updateCart(remoteCart.number, data, response => {
                commit(types.RECEIVE_CART, {response})
                resolve(response)
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
                    resolve(response);
                })
                .catch(response => {
                    reject(response);
                })
        })
    },

    getOrderNumber({state}) {
        return new Promise((resolve, reject) => {
            if (state.remoteCart && state.remoteCart.number) {
                const orderNumber = state.remoteCart.number
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

};

/**
 * Mutations
 */
const mutations = {

    [types.RECEIVE_CART](state, {response}) {
        state.remoteCart = response.cart
    },

    [types.RESET_CART](state) {
        state.remoteCart = null;
    },

    [types.CHECKOUT](state, {order}) {

    },

};

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
            licenseKey: 'cmsLicenseKey',
        }

        data.items = this.getCartItemsData(cart);

        return data;
    },

    getCartItemsData(cart) {
        let lineItems = []
        for (let i = 0; i < cart.lineItems.length; i++) {
            let lineItem = cart.lineItems[i]

            switch(lineItem.purchasable.type) {
                case 'plugin-edition':
                    lineItems.push({
                        type: lineItem.purchasable.type,
                        plugin: lineItem.purchasable.plugin.handle,
                        edition: 'standard',
                        autoRenew: true,
                    })
                    break;
                case 'cms-edition':
                    lineItems.push({
                        type: lineItem.purchasable.type,
                        edition: lineItem.purchasable.handle,
                        licenseKey: lineItem.cmsLicenseKey,
                        autoRenew: true,
                    })
                    break;
            }
        }

        return lineItems;
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
