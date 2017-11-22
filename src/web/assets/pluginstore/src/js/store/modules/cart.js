import api from '../../api'
import * as types from '../mutation-types'
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex);

const state = {
    items: [],
    checkoutStatus: null
};

const getters = {

    cartItems(state) {
        return state.items;
    },

    isInTrial(state, rootState) {
        return plugin => {
            return rootState.activeTrialPlugins.find(p => p.id == plugin.id)
        }
    },

    isInCart(state) {
        return plugin => {
            return state.items.find(p => p.id == plugin.id)
        }
    },

    cartTotal(state, rootState) {
        return () => {
            return rootState.cartPlugins.reduce((total, p) => {
                if(p) {
                    return total + parseFloat(p.price)
                }

                return total;
            }, 0)
        }
    },

    cartPlugins(state, rootState) {

        let items = state.items.filter(({ id }) => {
            if(rootState.pluginStoreData.plugins) {
                return rootState.pluginStoreData.plugins.find(p => p.id === id)
            }
        });

        return items.map(({ id }) => {
            if(rootState.pluginStoreData.plugins) {
                return rootState.pluginStoreData.plugins.find(p => p.id === id)
            }
        })
    },

    activeTrialPlugins(state, rootState) {
        if(!rootState.craftData.installedPlugins) {
            return [];
        }

        let plugins = rootState.craftData.installedPlugins.map( id  => {
            if(rootState.pluginStoreData.plugins) {
                return rootState.pluginStoreData.plugins.find(p => p.id == id)
            }
        });

        return plugins.filter(p => {
            if(p) {
                return p.price > 0;
            }
        });
    }

};

const actions = {

    addToCart({dispatch, commit}, plugin) {
        commit(types.ADD_TO_CART, {
            id: plugin.id
        })
        dispatch('saveCartState');
    },

    removeFromCart({dispatch, commit}, plugin) {
        commit(types.REMOVE_FROM_CART, {
            id: plugin.id
        })
        dispatch('saveCartState');
    },

    saveCartState({ commit, state }) {
        api.saveCartState(() => {
            commit(types.SAVE_CART_STATE);
        }, state)
    },

    getCartState({ commit }) {
        api.getCartState(cartState => {
            commit(types.RECEIVE_CART_STATE, { cartState });
        })
    },

    checkout({ commit }, order) {
        return new Promise((resolve, reject) => {
            api.checkout(order)
                .then(response => {
                    let body = response.body;
                    commit(types.CHECKOUT, { order: body });
                    resolve(body);
                })
                .catch(response => {
                    reject(response)
                });
        })
    },

    resetCart({dispatch, commit}) {
        commit(types.RESET_CART);
        dispatch('saveCartState');
    }

};

const mutations = {

    [types.ADD_TO_CART] (state, { id }) {
        const record = state.items.find(p => p.id === id)

        if (!record) {
            state.items.push({
                id,
            })
        }
    },

    [types.REMOVE_FROM_CART] (state, { id }) {
        const record = state.items.find(p => p.id === id)

        const index = state.items.indexOf(record);

        state.items.splice(index, 1);
    },

    [types.SAVE_CART_STATE] (state) {

    },

    [types.RECEIVE_CART_STATE] (state, { cartState }) {
        if(cartState) {
            state.items = cartState.items;
            state.checkoutStatus = cartState.checkoutStatus;
        }
    },

    [types.RESET_CART] (state) {
        state.items = [];
    },

    [types.CHECKOUT] (state, { order }) {
    }

};

export default {
    state,
    getters,
    actions,
    mutations
}
