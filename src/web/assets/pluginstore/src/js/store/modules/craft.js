import api from '../../api'
import * as types from '../mutation-types'

const state = {
    craftData: {},
}

const getters = {

    craftData: (state) => {
        return state.craftData
    },

    installedPlugins: (state, rootState) => {
        if (!rootState.allPlugins) {
            return []
        }

        return rootState.allPlugins.filter(p => {
            if (state.craftData.installedPlugins) {
                return state.craftData.installedPlugins.find(plugin => plugin.packageName === p.packageName && plugin.handle === p.handle)
            }
            return false
        })
    },

    currentUser: state => {
        return state.craftData.currentUser
    },

    craftIdAccount: state => {
        return state.craftData.craftId
    },

    countries: state => {
        return state.craftData.countries
    },

    states: state => {
        return state.craftData.states
    },

    pluginHasLicenseKey(state) {
        return pluginHandle => {
            return state.craftData.installedPlugins.find(plugin => plugin.handle === pluginHandle && plugin.hasLicenseKey) ? true : false
        }
    },

}

const actions = {

    getCraftData({commit}) {
        return new Promise((resolve, reject) => {
            api.getCraftData(data => {
                commit(types.RECEIVE_CRAFT_DATA, {data})
                resolve(data)
            }, response => {
                reject(response)
            })
        })
    },

    updateCraftId({commit}, craftId) {
        commit(types.UPDATE_CRAFT_ID, craftId)
    },

    tryEdition({commit}, edition) {
        return new Promise((resolve, reject) => {
            api.tryEdition(edition)
                .then(response => {
                    resolve(response)
                })
                .catch(response => {
                    reject(response)
                })
        })
    }

}

const mutations = {

    [types.RECEIVE_CRAFT_DATA](state, {data}) {
        state.craftData = data
    },

    [types.UPDATE_CRAFT_ID](state, {craftId}) {
        state.craftData.craftId = craftId
    },

}

export default {
    state,
    getters,
    actions,
    mutations,
}
