import api from '../../api/cart'
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
    selectedExpiryDates: {},
}

/**
 * Getters
 */
const getters = {
    isInCart(state) {
        return (plugin, edition) => {
            if (!state.cart) {
                return false
            }

            return state.cart.lineItems.find(lineItem => {
                if (lineItem.purchasable.pluginId !== plugin.id) {
                    return false
                }

                if (edition && lineItem.purchasable.handle !== edition.handle) {
                    return false
                }

                return true
            })
        }
    },

    isCmsEditionInCart(state) {
        return cmsEdition => {
            if (!state.cart) {
                return false
            }

            return state.cart.lineItems.find(lineItem => lineItem.purchasable.type === 'cms-edition' && lineItem.purchasable.handle === cmsEdition)
        }
    },

    activeTrialPlugins(state, getters, rootState, rootGetters) {
        return rootState.pluginStore.plugins.filter(plugin => {
            const pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](plugin.handle)

            if (!pluginLicenseInfo) {
                return false
            }

            if (pluginLicenseInfo.licenseKey && pluginLicenseInfo.edition === pluginLicenseInfo.licensedEdition) {
                return false
            }

            if (pluginLicenseInfo.edition) {
                const pluginEdition = rootGetters['pluginStore/getPluginEdition'](plugin.handle, pluginLicenseInfo.edition)

                if(pluginEdition && rootGetters['pluginStore/isPluginEditionFree'](pluginEdition)) {
                    return false
                }
            }

            if (!rootGetters['craft/isPluginInstalled'](plugin.handle)) {
                return false
            }

            return true
        })
    },

    activeTrialPluginEditions(state, getters, rootState, rootGetters) {
        const plugins = getters.activeTrialPlugins

        const pluginEditions = {}

        plugins.forEach(plugin => {
            const pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](plugin.handle)
            const edition = rootGetters['pluginStore/getPluginEdition'](plugin.handle, pluginLicenseInfo.edition)
            pluginEditions[plugin.handle] = edition
        })

        return pluginEditions
    },

    getActiveTrialPluginEdition(state, getters) {
        return pluginHandle => {
            const pluginEditions = getters.activeTrialPluginEditions

            if (!pluginEditions[pluginHandle]) {
                return null
            }

            return pluginEditions[pluginHandle]
        }
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

    cartItemsData(state) {
        return utils.getCartItemsData(state.cart)
    }
}

/**
 * Actions
 */
const actions = {
    updateItem({commit, state}, {itemKey, item}) {
        return new Promise((resolve, reject) => {
            const cart = state.cart

            let items = utils.getCartItemsData(cart)

            items[itemKey] = item

            let data = {
                items,
            }

            api.updateCart(cart.number, data)
                .then(response => {
                    commit('updateCart', {response})
                    resolve(response)
                })
                .catch(error => {
                    reject(error.response)
                })
        })
    },

    addToCart({commit, state, rootGetters}, newItems) {
        return new Promise((resolve, reject) => {
            const cart = JSON.parse(JSON.stringify(state.cart))
            let items = utils.getCartItemsData(cart)

            newItems.forEach(newItem => {
                const alreadyInCart = items.find(item => item.plugin === newItem.plugin)

                if (!alreadyInCart) {
                    let item = {...newItem}
                    item.expiryDate = '1y'

                    // Set default values
                    item.autoRenew = false

                    switch(item.type) {
                        case 'plugin-edition': {
                            // Set the license key if we have a valid one
                            const pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](item.plugin)

                            if (pluginLicenseInfo && pluginLicenseInfo.licenseKeyStatus === 'valid' && pluginLicenseInfo.licenseIssues.length === 0 && pluginLicenseInfo.licenseKey) {
                                item.licenseKey = pluginLicenseInfo.licenseKey
                            }

                            item.cmsLicenseKey = window.cmsLicenseKey

                            break
                        }

                        case 'cms-edition': {
                            item.licenseKey = window.cmsLicenseKey

                            break
                        }
                    }

                    items.push(item)
                }
            })

            let data = {
                items,
            }

            api.updateCart(cart.number, data)
                .then(response => {
                    if (typeof response.data.errors !== 'undefined') {
                        return reject(response)
                    }

                    commit('updateCart', {response})
                    return resolve(response)
                })
                .catch(error => {
                    return reject(error.response)
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

            api.updateCart(cart.number, data)
                .then(response => {
                    commit('updateCart', {response})
                    resolve(response)
                })
                .catch(error => {
                    reject(error.response)
                })
        })
    },

    // eslint-disable-next-line
    checkout({}, data) {
        return new Promise((resolve, reject) => {
            api.checkout(data)
                .then(response => {
                    resolve(response)
                })
                .catch(error => {
                    reject(error.response)
                })
        })
    },

    getCart({dispatch, commit, rootState}) {
        return new Promise((resolve, reject) => {
            dispatch('getOrderNumber')
                .then(orderNumber => {
                    if (orderNumber) {
                        api.getCart(orderNumber)
                            .then(response => {
                                if (!response.data.error) {
                                    commit('updateCart', {response})
                                    resolve(response)
                                } else {
                                    // Couldn’t get cart for this order number? Try to create a new one.
                                    const data = {}

                                    if (!rootState.craft.craftId) {
                                        data.email = rootState.craft.currentUser.email
                                    }

                                    api.createCart(data)
                                        .then(createCartResponse => {
                                            commit('updateCart', {response: createCartResponse})
                                            dispatch('saveOrderNumber', {orderNumber: createCartResponse.data.cart.number})
                                            resolve(response)
                                        })
                                        .catch(createCartError => {
                                            reject(createCartError.response)
                                        })
                                }
                            })
                            .catch(error => {
                                reject(error.response)
                            })
                    } else {
                        // No order number yet? Create a new cart.
                        const data = {}

                        if (!rootState.craft.craftId) {
                            data.email = rootState.craft.currentUser.email
                        }

                        api.createCart(data)
                            .then(createCartResponse => {
                                commit('updateCart', {response: createCartResponse})
                                dispatch('saveOrderNumber', {orderNumber: createCartResponse.data.cart.number})
                                resolve(createCartResponse)
                            })
                            .catch(createCartError => {
                                reject(createCartError.response)
                            })
                    }
                })
        })
    },

    saveCart({commit, state}, data) {
        return new Promise((resolve, reject) => {
            const cart = state.cart

            api.updateCart(cart.number, data)
                .then(response => {
                    if (!response.data.errors) {
                        commit('updateCart', {response})
                        resolve(response)
                    } else {
                        reject(response)
                    }
                })
                .catch(error => {
                    reject(error.response)
                })
        })
    },

    resetCart({commit, dispatch}) {
        return new Promise((resolve, reject) => {
            commit('resetCart')
            dispatch('resetOrderNumber')
            dispatch('getCart')
                .then(response => {
                    resolve(response)
                })
                .catch(error => {
                    reject(error.response)
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

    // eslint-disable-next-line
    saveOrderNumber({}, {orderNumber}) {
        api.saveOrderNumber(orderNumber)
    },

    savePluginLicenseKeys({rootGetters}, cart) {
        return new Promise((resolve, reject) => {
            let pluginLicenseKeys = []

            cart.lineItems.forEach(lineItem => {
                if (lineItem.purchasable.type === 'plugin-edition') {
                    if (rootGetters['craft/isPluginInstalled'](lineItem.purchasable.plugin.handle)) {
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
                .catch(error => {
                    reject(error.response)
                })
        })
    }
}

/**
 * Mutations
 */
const mutations = {
    updateCart(state, {response}) {
        state.cart = response.data.cart
        state.stripePublicKey = response.data.stripePublicKey

        const selectedExpiryDates = {}
        state.cart.lineItems.forEach((lineItem, key) => {
            selectedExpiryDates[key] = lineItem.options.expiryDate
        })

        state.selectedExpiryDates = selectedExpiryDates
    },

    resetCart(state) {
        state.cart = null
    },

    changeIdentityMode(state, mode) {
        state.identityMode = mode
    },

    updateSelectedExpiryDates(state, selectedExpiryDates) {
        state.selectedExpiryDates = selectedExpiryDates
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
        if (!cart) {
            return []
        }

        let lineItems = []
        for (let i = 0; i < cart.lineItems.length; i++) {
            let lineItem = cart.lineItems[i]

            switch (lineItem.purchasable.type) {
                case 'plugin-edition': {
                    const item = {
                        type: lineItem.purchasable.type,
                        plugin: lineItem.purchasable.plugin.handle,
                        edition: lineItem.purchasable.handle,
                        cmsLicenseKey: window.cmsLicenseKey,
                        expiryDate: lineItem.options.expiryDate,
                        autoRenew: lineItem.options.autoRenew,
                    }

                    let licenseKey = lineItem.options.licenseKey

                    if (licenseKey && licenseKey.substr(0, 3) !== 'new') {
                        item.licenseKey = licenseKey
                    }

                    lineItems.push(item)

                    break
                }

                case 'cms-edition': {
                    const item = {
                        type: lineItem.purchasable.type,
                        edition: lineItem.purchasable.handle,
                        expiryDate: lineItem.options.expiryDate,
                        autoRenew: lineItem.options.autoRenew,
                    }

                    let licenseKey = lineItem.options.licenseKey

                    if (licenseKey && licenseKey.substr(0, 3) !== 'new') {
                        item.licenseKey = licenseKey
                    }

                    lineItems.push(item)

                    break
                }
            }
        }

        return lineItems
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
