import api from '../../api/craft'

/**
 * State
 */
const state = {
    canTestEditions: null,
    countries: null,
    craftId: null,
    craftLogo: null,
    currentUser: null,
    defaultPluginSvg: null,
    licensedEdition: null,
    pluginLicenseInfo: {},
    poweredByStripe: null,

    // Craft editions
    CraftEdition: null,
    CraftPro: null,
    CraftSolo: null,
}

/**
 * Getters
 */
const getters = {
    getCmsEditionFeatures() {
        return editionHandle => {
            const features = {
                "solo": [
                    {
                        name: "Ultra-flexible content modeling",
                        description: "Define custom content types, fields, and relations needed to perfectly contain your unique content requirements."
                    }, {
                        name: "Powerful front-end tools",
                        description: "Develop custom front-end templates with Twig, or use Craft as a headless CMS."
                    }, {
                        name: "Multi-Site",
                        description: "Run multiple related sites from a single installation, with shared content and user accounts."
                    }, {
                        name: "Localization",
                        description: "Cater to distinct audiences from around the world with Craft’s best-in-class localization capabilities."
                    }, {
                        name: "Single admin account",
                        description: "The Solo edition is limited to a single admin account."
                    }
                ],
                "pro": [
                    {
                        name: "Unlimited user accounts",
                        description: "Create unlimited user accounts, user groups, user permissions, and public user registration.",
                    }, {
                        name: "Enhanced content previewing",
                        description: "Preview your content from multiple targets, including single-page applications.",
                    }, {
                        name: "GraphQL API",
                        description: "Make your content available to other applications with a self-generating GraphQL API.",
                    }, {
                        name: "System branding",
                        description: "Personalize the control panel for your brand.",
                    }, {
                        name: "Basic developer support",
                        description: "Get developer-to-developer support right from the Craft core development team.",
                    },
                ]
            }

            if (!features[editionHandle]) {
                return null
            }

            return features[editionHandle]
        }
    },

    getPluginLicenseInfo(state) {
        return pluginHandle => {
            if (!state.pluginLicenseInfo) {
                return null
            }

            if (!state.pluginLicenseInfo[pluginHandle]) {
                return null
            }

            return state.pluginLicenseInfo[pluginHandle]
        }
    },

    isPluginInstalled(state) {
        return pluginHandle => {
            if (!state.pluginLicenseInfo) {
                return false
            }

            if (!state.pluginLicenseInfo[pluginHandle]) {
                return false
            }

            if (!state.pluginLicenseInfo[pluginHandle].isInstalled) {
                return false
            }

            return true
        }
    },

    getCmsEditionIndex(state) {
        return editionHandle => {
            switch (editionHandle) {
                case 'solo':
                    return state.CraftSolo
                case 'pro':
                    return state.CraftPro
                default:
                    return null
            }
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

    getCraftData({commit}) {
        return new Promise((resolve, reject) => {
            api.getCraftData()
                .then(response => {
                    commit('updateCraftData', {response})
                    api.getCountries()
                        .then(responseData => {
                            commit('updateCountries', {responseData})
                            resolve()
                        })
                        .catch(error => {
                            reject(error)
                        })
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getCraftIdData({commit}, {accessToken}) {
        return new Promise((resolve, reject) => {
            api.getCraftIdData({accessToken})
                .then(responseData => {
                    commit('updateCraftIdData', {responseData})
                    resolve()
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    getPluginLicenseInfo({commit}) {
        return new Promise((resolve, reject) => {
            api.getPluginLicenseInfo()
                .then(response => {
                    commit('updatePluginLicenseInfo', {response})
                    resolve(response)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    switchPluginEdition({dispatch}, {pluginHandle, edition}) {
        return new Promise((resolve, reject) => {
            api.switchPluginEdition(pluginHandle, edition)
                .then(switchPluginEditionResponse => {
                    dispatch('getPluginLicenseInfo')
                        .then(getPluginLicenseInfoResponse => {
                            resolve({
                                switchPluginEditionResponse,
                                getPluginLicenseInfoResponse,
                            })
                        })
                        .catch(response => reject(response))
                })
                .catch(response => reject(response))
        })
    },

    tryEdition(context, edition) {
        return new Promise((resolve, reject) => {
            api.tryEdition(edition)
                .then(response => {
                    resolve(response)
                })
                .catch(response => {
                    reject(response)
                })
        })
    },
}

/**
 * Mutations
 */
const mutations = {
    updateCraftData(state, {response}) {
        state.canTestEditions = response.data.canTestEditions
        state.craftLogo = response.data.craftLogo
        state.currentUser = response.data.currentUser
        state.defaultPluginSvg = response.data.defaultPluginSvg
        state.licensedEdition = response.data.licensedEdition
        state.poweredByStripe = response.data.poweredByStripe

        // Craft editions
        state.CraftEdition = response.data.CraftEdition
        state.CraftPro = response.data.CraftPro
        state.CraftSolo = response.data.CraftSolo
    },

    updateCraftIdData(state, {responseData}) {
        state.craftId = responseData
    },

    updateCountries(state, {responseData}) {
        state.countries = responseData.countries
    },

    updateCraftId(state, craftId) {
        state.craftId = craftId
    },

    updatePluginLicenseInfo(state, {response}) {
        state.pluginLicenseInfo = response.data
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations,
}
