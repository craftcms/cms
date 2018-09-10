import api from '../../api/pluginstore'

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

    isInstalled(state, getters, rootState, rootGetters) {
        return plugin => {
            return rootGetters['craft/installedPlugins'].find(p => p.id == plugin.id)
        }
    },

    getPluginById(state) {
        return id => {
                return state.plugins.find(p => p.id == id)
        }
    },

    getPluginsByIds(state) {
        return ids => {
            let plugins = [];

            ids.forEach(function(id) {
                const plugin = state.plugins.find(p => p.id === id)
                plugins.push(plugin)
            })

            return plugins;
        }
    },

    getPluginsByCategory(state) {
        return categoryId => {
            return state.plugins.filter(p => {
                return p.categoryIds.find(c => c == categoryId)
            })
        }
    },

    getPluginsByDeveloperId(state) {
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
                commit('updateDeveloper', {developer})
                resolve(developer)
            }, response => {
                reject(response)
            })
        })
    },

    getPluginStoreData({commit}) {
        return new Promise((resolve, reject) => {
            api.getPluginStoreData(response => {
                commit('updatePluginStoreData', {response})
                resolve(response)
            }, response => {
                reject(response)
            })
        })
    },

    getPluginDetails({commit}, pluginId) {
        return new Promise((resolve, reject) => {
            api.getPluginDetails(pluginId, response => {
                commit('updatePluginDetails', response.data)
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

    updateDeveloper(state, {developer}) {
        state.developer = developer
    },

    updatePluginStoreData(state, {response}) {
        state.categories = response.data.categories
        state.featuredPlugins = response.data.featuredPlugins
        state.plugins = response.data.plugins
    },

    updatePluginDetails(state, pluginDetails) {
        state.plugin = pluginDetails
    },

}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
