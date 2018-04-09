import api from '../../api'
import * as types from '../mutation-types'

const state = {
    data: {},
    plugin: null,
}

const getters = {

    pluginStoreData: state => state.data,

    pluginStoreGetAllCategories(state) {
        return state.data.categories
    },

    getFeaturedPlugin(state) {
        return id => {
            if (state.data.featuredPlugins) {
                return state.data.featuredPlugins.find(g => g.id == id)
            }
        }
    },

    getAllCategories(state) {
        return () => {
            return state.data.categories
        }
    },

    getCategoryById(state) {
        return id => {
            if (state.data.categories) {
                return state.data.categories.find(c => c.id == id)
            }
        }
    },

    isInstalled(state, rootState) {
        return plugin => {
            return rootState.installedPlugins.find(p => p.id == plugin.id)
        }
    },

    allPlugins: (state, rootState) => {
        return state.data.plugins
    },

    getPluginById(state, rootState) {
        return id => {
            if (state.data.plugins) {
                return state.data.plugins.find(p => p.id == id)
            }

            return false
        }
    },

    getPluginsByIds(state, rootState) {
        return ids => {
            return state.data.plugins.filter(p => {
                return ids.find(id => id == p.id)
            })
        }
    },

    getPluginsByCategory(state, rootState) {
        return categoryId => {
            return state.data.plugins.filter(p => {
                return p.categoryIds.find(c => c == categoryId)
            })
        }
    },

    getPluginsByDeveloperId(state, rootState) {
        return developerId => {
            if (state.data.plugins) {
                return state.data.plugins.filter(p => p.developerId == developerId)
            }
        }
    },

}

const actions = {

    getPluginStoreData({commit}) {
        return new Promise((resolve, reject) => {
            api.getPluginStoreData(data => {
                commit(types.RECEIVE_PLUGIN_STORE_DATA, {data})
                resolve(data)
            }, response => {
                reject(response)
            })
        })
    },

    getPluginDetails({commit}, pluginId) {
        return new Promise((resolve, reject) => {
            api.getPluginDetails(pluginId, data => {
                commit(types.RECEIVE_PLUGIN_DETAILS, {data})
                resolve(data)
            }, response => {
                reject(response)
            })
        })
    },

}

const mutations = {

    [types.RECEIVE_PLUGIN_STORE_DATA](state, {data}) {
        state.data = data
    },

    [types.RECEIVE_PLUGIN_DETAILS](state, {data}) {
        state.plugin = data
    },

}

export default {
    state,
    getters,
    actions,
    mutations
}
