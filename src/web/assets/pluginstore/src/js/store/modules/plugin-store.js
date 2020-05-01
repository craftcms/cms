import api from '../../api/pluginstore'

/**
 * State
 */
const state = {
    categories: [],
    cmsEditions: null,
    developer: null,
    expiryDateOptions: [],
    featuredPlugins: [],
    featuredSection: null,
    featuredSections: [],
    plugin: null,
    pluginChangelog: null,

    // plugin index
    plugins: [],
}

/**
 * Getters
 */
const getters = {
    getCategoryById(state) {
        return id => {
            return state.categories.find(c => c.id == id)
        }
    },

    getPluginEdition() {
        return (plugin, editionHandle) => {
            return plugin.editions.find(edition => edition.handle === editionHandle)
        }
    },

    getPluginIndexParams() {
        return context => {
            const perPage = context.perPage ? context.perPage : null
            const page = context.page ? context.page : 1
            const orderBy = context.orderBy
            const direction = context.direction

            return {
                perPage,
                page,
                orderBy,
                direction,
            }
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
    cancelRequests() {
        return api.cancelRequests()
    },

    getCoreData({commit}) {
        return new Promise((resolve, reject) => {
            api.getCoreData()
                .then(responseData => {
                    commit('updateCoreData', {responseData})
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getCmsEditions({commit}) {
        return new Promise((resolve, reject) => {
            api.getCmsEditions()
                .then(responseData => {
                    commit('updateCmsEditions', {responseData})
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getDeveloper({commit}, developerId) {
        return api.getDeveloper(developerId)
            .then(responseData => {
                commit('updateDeveloper', responseData)
            })
    },

    getFeaturedSectionByHandle({commit}, featuredSectionHandle) {
        return api.getFeaturedSectionByHandle(featuredSectionHandle)
            .then(responseData => {
                commit('updateFeaturedSection', responseData)
            })
    },

    getFeaturedSections({commit}) {
        return api.getFeaturedSections()
            .then(responseData => {
                commit('updateFeaturedSections', responseData)
            })
    },

    getPluginChangelog({commit}, pluginId) {
        return new Promise((resolve, reject) => {
            api.getPluginChangelog(pluginId)
                .then(responseData => {
                    commit('updatePluginChangelog', responseData)
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getPluginDetails({commit}, pluginId) {
        return new Promise((resolve, reject) => {
            api.getPluginDetails(pluginId)
                .then(responseData => {
                    commit('updatePluginDetails', responseData)
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getPluginDetailsByHandle({commit}, pluginHandle) {
        return api.getPluginDetailsByHandle(pluginHandle)
            .then(responseData => {
                commit('updatePluginDetails', responseData)
            })
    },

    getPluginsByCategory({getters, dispatch}, context) {
        return new Promise((resolve, reject) => {
            const pluginIndexParams = getters['getPluginIndexParams'](context)

            api.getPluginsByCategory(context.categoryId, pluginIndexParams)
                .then(responseData => {
                    dispatch('updatePluginIndex', {context, responseData})
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getPluginsByDeveloperId({getters, dispatch}, context) {
        return new Promise((resolve, reject) => {
            const pluginIndexParams = getters['getPluginIndexParams'](context)

            api.getPluginsByDeveloperId(context.developerId, pluginIndexParams)
                .then(responseData => {
                    dispatch('updatePluginIndex', {context, responseData})
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getPluginsByFeaturedSectionHandle({getters, dispatch}, context) {
        return new Promise((resolve, reject) => {
            const pluginIndexParams = getters['getPluginIndexParams'](context)
            
            return api.getPluginsByFeaturedSectionHandle(context.featuredSectionHandle, pluginIndexParams)
                .then(responseData => {
                    dispatch('updatePluginIndex', {context, responseData})
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    searchPlugins({getters, dispatch}, context) {
        return new Promise((resolve, reject) => {
            const pluginIndexParams = getters['getPluginIndexParams'](context)

            api.searchPlugins(context.searchQuery, pluginIndexParams)
                .then(responseData => {
                    dispatch('updatePluginIndex', {context, responseData})
                    resolve(responseData)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    updatePluginIndex({commit}, {context, responseData}) {
        if (context.appendData && context.appendData === true) {
            commit('appendPlugins', responseData.plugins)
        } else {
            commit('updatePlugins', responseData.plugins)
        }
    },
}

/**
 * Mutations
 */
const mutations = {
    appendPlugins(state, plugins) {
        state.plugins = [...state.plugins, ...plugins]
    },

    updateCoreData(state, {responseData}) {
        state.categories = responseData.categories
        state.expiryDateOptions = responseData.expiryDateOptions
        state.sortOptions = responseData.sortOptions
    },

    updateCmsEditions(state, {responseData}) {
        state.cmsEditions = responseData.editions
    },

    updateDeveloper(state, developer) {
        state.developer = developer
    },

    updateFeaturedSection(state, featuredSection) {
        state.featuredSection = featuredSection
    },

    updateFeaturedSections(state, featuredSections) {
        state.featuredSections = featuredSections
    },

    updatePluginChangelog(state, changelog) {
        state.pluginChangelog = changelog
    },

    updatePluginDetails(state, pluginDetails) {
        state.plugin = pluginDetails
    },

    updatePlugins(state, plugins) {
        state.plugins = plugins
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
