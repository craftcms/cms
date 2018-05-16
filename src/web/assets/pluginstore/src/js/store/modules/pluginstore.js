import api from '../../api/pluginstore'
import * as types from '../mutation-types'

/**
 * State
 */
const state = {
    categories: [],
    developer: null,
    featuredPlugins: [],
    plugin: null,
    plugins: [],
}

/**
 * Getters
 */
const getters = {

    getFeaturedPlugin(state) {
        return id => {
            return state.featuredPlugins.find(g => g.id == id)
        }
    },

    getCategoryById(state) {
        return id => {
            return state.categories.find(c => c.id == id)
        }
    },

    isInstalled(state, rootState) {
        return plugin => {
            return rootState.installedPlugins.find(p => p.id == plugin.id)
        }
    },

    getPluginById(state, rootState) {
        return id => {
                return state.plugins.find(p => p.id == id)
        }
    },

    getPluginsByIds(state, rootState) {
        return ids => {
            let plugins = [];

            ids.forEach(function(id) {
                const plugin = state.plugins.find(p => p.id === id)
                plugins.push(plugin)
            })

            return plugins;
        }
    },

    getPluginsByCategory(state, rootState) {
        return categoryId => {
            return state.plugins.filter(p => {
                return p.categoryIds.find(c => c == categoryId)
            })
        }
    },

    getPluginsByDeveloperId(state, rootState) {
        return developerId => {
            return state.plugins.filter(p => p.developerId == developerId)
        }
    },

    getPluginByHandle(state) {
        return handle => {
            return state.plugins.find(plugin => plugin.handle === handle)
        }
    },

}

/**
 * Actions
 */
const actions = {

    getDeveloper({commit}, developerId) {
        return new Promise((resolve, reject) => {
            api.getDeveloper(developerId, developer => {
                commit(types.RECEIVE_DEVELOPER, {developer})
                resolve(developer)
            }, response => {
                reject(response)
            })
        })
    },

    getPluginStoreData({commit}) {
        return new Promise((resolve, reject) => {
            api.getPluginStoreData(response => {
                commit(types.RECEIVE_PLUGIN_STORE_DATA, {response})
                resolve(response)
            }, response => {
                reject(response)
            })
        })
    },

    getPluginDetails({commit}, pluginId) {
        return new Promise((resolve, reject) => {
            api.getPluginDetails(pluginId, response => {
                commit(types.UPDATE_PLUGIN_DETAILS, response.data)
                resolve(response)
            }, response => {
                reject(response)
            })
        })
    },

}

/**
 * Mutations
 */
const mutations = {

    [types.RECEIVE_DEVELOPER](state, {developer}) {
        state.developer = developer
    },

    [types.RECEIVE_PLUGIN_STORE_DATA](state, {response}) {
        state.categories = response.data.categories
        state.featuredPlugins = response.data.featuredPlugins
        state.plugins = response.data.plugins
    },

    [types.UPDATE_PLUGIN_DETAILS](state, pluginDetails) {
        state.plugin = pluginDetails
    },

}

export default {
    state,
    getters,
    actions,
    mutations
}
