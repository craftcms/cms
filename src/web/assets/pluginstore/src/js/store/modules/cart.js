import api from '../../api/cart'
import pluginStoreApi from '../../api/pluginstore'
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

/**
 * State
 */
const state = {
    activeTrialPlugins: [],
    cart: null,
    cartPlugins: [],
    checkoutStatus: null,
    identityMode: 'craftid',
    selectedExpiryDates: {},
    stripePublicKey: null,
}

/**
 * Getters
 */
const getters = {
    cartItems(state) {
        let cartItems = []

        if (state.cart) {
            const lineItems = state.cart.lineItems

            lineItems.forEach(lineItem => {
                let cartItem = {}

                cartItem.lineItem = lineItem

                if (lineItem.purchasable.type === 'plugin-edition') {
                    cartItem.plugin = state.cartPlugins.find(p => p.handle === lineItem.purchasable.plugin.handle)
                }

                cartItems.push(cartItem)
            })
        }

        return cartItems
    },

    cartItemsData(state) {
        return utils.getCartItemsData(state.cart)
    },

    isCmsEditionInCart(state) {
        return cmsEdition => {
            if (!state.cart) {
                return false
            }

            return state.cart.lineItems.find(lineItem => lineItem.purchasable.type === 'cms-edition' && lineItem.purchasable.handle === cmsEdition)
        }
    },

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

    getActiveTrialPluginEdition(state, getters, rootState, rootGetters) {
        return plugin => {
            const pluginHandle = plugin.handle
            const pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](pluginHandle)
            const pluginEdition = plugin.editions.find(edition => edition.handle === pluginLicenseInfo.edition)

            if (!pluginEdition) {
                return null
            }

            return pluginEdition
        }
    },
}

/**
 * Actions
 */
const actions = {
    addToCart({state, dispatch, rootGetters}, newItems) {
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
                            const pluginLicenseInfo = rootGetters['craft/getPluginLicenseInfo'](item.plugin)

                            // Check that the current plugin license exists and is `valid`
                            if (
                                pluginLicenseInfo &&
                                pluginLicenseInfo.licenseKey &&
                                (pluginLicenseInfo.licenseKeyStatus === 'valid')
                            ) {
                                // Check if the license has issues other than `wrong_edition` or `astray`
                                let hasIssues = false

                                if (pluginLicenseInfo.licenseIssues.length > 0) {
                                    pluginLicenseInfo.licenseIssues.forEach((issue) => {
                                        if (issue !== 'wrong_edition' && issue !== 'astray') {
                                            hasIssues = true
                                        }
                                    })
                                }

                                // If we don’t have issues for this license, we can attach its key to the item
                                if (!hasIssues) {
                                    item.licenseKey = pluginLicenseInfo.licenseKey
                                }
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

            const cartNumber = cart.number

            dispatch('updateCart', {cartNumber, data})
                .then((responseData) => {
                    if (typeof responseData.errors !== 'undefined') {
                        return reject(responseData)
                    }

                    resolve(responseData)
                })
                .catch(error => {
                    return reject(error)
                })
        })
    },

    checkout(context, data) {
        return new Promise((resolve, reject) => {
            api.checkout(data)
                .then(responseData => {
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    createCart({dispatch, rootState}) {
        return new Promise((resolve, reject) => {
            const data = {}

            if (!rootState.craft.craftId) {
                data.email = rootState.craft.currentUser.email
            }

            api.createCart(data)
                .then(cartResponseData => {
                    dispatch('updateCartPlugins', {cartResponseData})
                        .then(() => {
                            dispatch('saveOrderNumber', {orderNumber: cartResponseData.cart.number})
                            resolve(cartResponseData)
                        })
                        .catch((error) => {
                            reject(error)
                        })
                })
                .catch(cartError => {
                    reject(cartError)
                })
        })
    },

    getActiveTrialPlugins({commit, rootState, rootGetters}) {
        return new Promise((resolve, reject) => {
            // get plugin license info and find active trial plugin handles
            const pluginHandles = []
            const pluginLicenseInfo = rootState.craft.pluginLicenseInfo

            for (let pluginHandle in pluginLicenseInfo) {
                if (Object.prototype.hasOwnProperty.call(pluginLicenseInfo, pluginHandle)) {
                    pluginHandles.push(pluginHandle)
                }
            }

            // request plugins by plugin handle
            pluginStoreApi.getPluginsByHandles(pluginHandles)
                .then((responseData) => {
                    if (responseData && responseData.error) {
                        throw responseData.error
                    }

                    const data = responseData
                    const plugins = []

                    for (let i = 0; i < data.length; i++) {
                        const plugin = data[i]

                        if (!plugin) {
                            continue
                        }

                        const info = pluginLicenseInfo[plugin.handle]

                        if (!info) {
                            continue
                        }

                        if (info.licenseKey && info.edition === info.licensedEdition) {
                            continue
                        }

                        if (info.edition) {
                            const pluginEdition = plugin.editions.find(edition => edition.handle === info.edition)

                            if(pluginEdition && rootGetters['pluginStore/isPluginEditionFree'](pluginEdition)) {
                                continue
                            }
                        }

                        if (!rootGetters['craft/isPluginInstalled'](plugin.handle)) {
                            continue
                        }

                        plugins.push(plugin)
                    }

                    commit('updateActiveTrialPlugins', plugins)
                    resolve(responseData)
                })
                .catch((error) => {
                    reject(error)
                })
        })
    },

    getCart({dispatch}) {
        return new Promise((resolve, reject) => {
            // retrieve the order number
            dispatch('getOrderNumber')
                .then(orderNumber => {
                    if (orderNumber) {
                        // get cart by order number
                        api.getCart(orderNumber)
                            .then(cartResponseData => {
                                dispatch('updateCartPlugins', {cartResponseData})
                                    .then(() => {
                                        resolve(cartResponseData)
                                    })
                                    .catch((error) => {
                                        reject(error)
                                    })
                            })
                            .catch(() => {
                                // Cart already completed or has errors? Create a new one.
                                dispatch('createCart')
                                    .then((cartResponseData) => {
                                        resolve(cartResponseData)
                                    })
                                    .catch(cartError => {
                                        reject(cartError)
                                    })
                            })
                    } else {
                        // No order number yet? Create a new cart.
                        dispatch('createCart')
                            .then((cartResponseData) => {
                                resolve(cartResponseData)
                            })
                            .catch(cartError => {
                                reject(cartError)
                            })
                    }
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

    removeFromCart({dispatch, state}, lineItemKey) {
        return new Promise((resolve, reject) => {
            const cart = state.cart

            let items = utils.getCartItemsData(cart)
            items.splice(lineItemKey, 1)

            let data = {
                items,
            }

            const cartNumber = cart.number

            dispatch('updateCart', {cartNumber, data})
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    resetCart({commit, dispatch}) {
        return new Promise((resolve, reject) => {
            commit('resetCart')
            dispatch('resetOrderNumber')
            dispatch('getCart')
                .then(responseData => {
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    resetOrderNumber() {
        api.resetOrderNumber()
    },

    saveCart({dispatch, state}, data) {
        return new Promise((resolve, reject) => {
            const cart = state.cart
            const cartNumber = cart.number

            dispatch('updateCart', {cartNumber, data})
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    saveOrderNumber(context, {orderNumber}) {
        api.saveOrderNumber(orderNumber)
    },

    savePluginLicenseKeys({rootGetters}, cart) {
        return new Promise((resolve, reject) => {
            let pluginLicenseKeys = []

            cart.lineItems.forEach(lineItem => {
                if (lineItem.purchasable.type === 'plugin-edition') {
                    if (rootGetters['craft/isPluginInstalled'](lineItem.purchasable.plugin.handle)) {
                        let licenseKey = lineItem.options.licenseKey

                        if (licenseKey.substr(0, 4) === 'new:') {
                            licenseKey = licenseKey.substr(4)
                        }

                        pluginLicenseKeys.push({
                            handle: lineItem.purchasable.plugin.handle,
                            key: licenseKey,
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
    },

    updateCart({dispatch}, {cartNumber, data}) {
        return new Promise((resolve, reject) => {
            api.updateCart(cartNumber, data)
                .then(cartResponseData => {
                    if (cartResponseData && cartResponseData.errors) {
                        reject({response: cartResponseData})
                        return null
                    }

                    dispatch('updateCartPlugins', {cartResponseData})
                        .then(() => {
                            resolve(cartResponseData)
                        })
                        .catch((error) => {
                            reject(error)
                        })
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    updateCartPlugins({commit}, {cartResponseData}) {
        return new Promise((resolve, reject) => {
            const cart = cartResponseData.cart

            const cartItemPluginIds = []

            cart.lineItems.forEach((lineItem) => {
                if (lineItem.purchasable.type === 'plugin-edition') {
                    cartItemPluginIds.push(lineItem.purchasable.plugin.id)
                }
            })

            if (cartItemPluginIds.length > 0) {
                pluginStoreApi.getPluginsByIds(cartItemPluginIds)
                    .then((pluginsResponseData) => {
                        commit('updateCart', {cartResponseData})
                        commit('updateCartPlugins', {pluginsResponseData})
                        resolve(pluginsResponseData)
                    })
                    .catch((error) => {
                        reject(error)
                    })
            } else {
                const pluginsResponseData = []
                commit('updateCart', {cartResponseData})
                commit('updateCartPlugins', {pluginsResponseData})
                resolve(pluginsResponseData)
            }
        })
    },

    updateItem({dispatch, state}, {itemKey, item}) {
        return new Promise((resolve, reject) => {
            const cart = state.cart
            const cartNumber = cart.number

            let items = utils.getCartItemsData(cart)

            items[itemKey] = item

            let data = {
                items,
            }

            dispatch('updateCart', {cartNumber, data})
                .then((responseData) => {
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },
}

/**
 * Mutations
 */
const mutations = {
    changeIdentityMode(state, mode) {
        state.identityMode = mode
    },

    resetCart(state) {
        state.cart = null
    },

    updateActiveTrialPlugins(state, plugins) {
        state.activeTrialPlugins = plugins
    },

    updateCart(state, {cartResponseData}) {
        state.cart = cartResponseData.cart
        state.stripePublicKey = cartResponseData.stripePublicKey

        const selectedExpiryDates = {}
        state.cart.lineItems.forEach((lineItem, key) => {
            selectedExpiryDates[key] = lineItem.options.expiryDate
        })

        state.selectedExpiryDates = selectedExpiryDates
    },

    updateCartPlugins(state, {pluginsResponseData}) {
        state.cartPlugins = pluginsResponseData
    },

    updateSelectedExpiryDates(state, selectedExpiryDates) {
        state.selectedExpiryDates = selectedExpiryDates
    },
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
