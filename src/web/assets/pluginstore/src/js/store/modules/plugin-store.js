import api from '../../api/pluginstore'

/**
 * State
 */
const state = {
    categories: [],
    developer: null,
    featuredPlugins: [],
    plugin: null,
    pluginChangelog: null,
    plugins: [],
    expiryDateOptions: [],
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

    getPluginEdition(state, getters) {
        return (pluginHandle, editionHandle) => {
            const plugin = getters.getPluginByHandle(pluginHandle)

            if (!plugin) {
                return false
            }

            return plugin.editions.find(edition => edition.handle === editionHandle)
        }
    },

    isPluginEditionFree() {
        return edition => {
            return edition.price === null
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

    getPluginChangelog({commit}, pluginId) {
        return new Promise((resolve, reject) => {
            api.getPluginChangelog(pluginId, response => {
                commit('updatePluginChangelog', response.data)
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
        state.expiryDateOptions = response.data.expiryDateOptions
    },

    updatePluginDetails(state, pluginDetails) {
        state.plugin = pluginDetails
    },

    updatePluginChangelog(state, changelog) {
        state.pluginChangelog = changelog
    },

}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
