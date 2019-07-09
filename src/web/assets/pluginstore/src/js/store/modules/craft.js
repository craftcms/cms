import api from '../../api/craft'

/**
 * State
 */
const state = {
    CraftEdition: null,
    CraftPro: null,
    CraftSolo: null,
    canTestEditions: null,
    countries: null,
    craftId: null,
    craftLogo: null,
    currentUser: null,
    editions: null,
    licensedEdition: null,
    poweredByStripe: null,
    defaultPluginSvg: null,
    pluginLicenseInfo: {},
}

/**
 * Getters
 */
const getters = {
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

    getCmsEditionFeatures() {
        return editionHandle => {
            const features = {
                "solo": [
                    {
                        name: "Ultra-flexible content modeling",
                        description: "Define custom content types, fields, and relations needed to perfectly contain your unique content requirements."
                    },
                    {
                        name: "Powerful front-end tools",
                        description: "Develop custom front-end templates with Twig, or use Craft as a headless CMS."
                    },
                    {
                        name: "Multi-Site",
                        description: "Run multiple related sites from a single installation, with shared content and user accounts."
                    },
                    {
                        name: "Localization",
                        description: "Cater to distinct audiences from around the world with Craft’s best-in-class localization capabilities."
                    },
                    {
                        name: "Single admin account",
                        description: "The Solo edition is limited to a single admin account."
                    }
                ],
                "pro": [
                    {
                        name: "Enhanced content previewing",
                        description: "Preview your content from multiple targets, including single-page applications.",
                    },
                    {
                        name: "Unlimited user accounts",
                        description: "Create unlimited user accounts, user groups, user permissions, and public user registration.",
                    },
                    {
                        name: "System branding",
                        description: "Personalize the Control Panel for your brand.",
                    },
                    {
                        name: "Developer support",
                        description: "Get developer-to-developer support right from the Craft core development team.",
                    },
                ]
            }

            if (!features[editionHandle]) {
                return null
            }

            return features[editionHandle]
        }
    }
}

/**
 * Actions
 */
const actions = {
    getCraftData({commit}) {
        return new Promise((resolve, reject) => {
            api.getCraftData()
                .then(response => {
                    commit('updateCraftData', {response})
                    resolve(response)
                })
                .catch(error => {
                    reject(error.response)
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
                    reject(error.response)
                })
        })
    },

    updateCraftId({commit}, craftId) {
        commit('updateCraftId', craftId)
    },

    // eslint-disable-next-line
    tryEdition({}, edition) {
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
    }
}

/**
 * Mutations
 */
const mutations = {
    updateCraftData(state, {response}) {
        state.CraftEdition = response.data.CraftEdition
        state.CraftPro = response.data.CraftPro
        state.CraftSolo = response.data.CraftSolo
        state.canTestEditions = response.data.canTestEditions
        state.countries = response.data.countries
        state.craftId = response.data.craftId
        state.craftLogo = response.data.craftLogo
        state.currentUser = response.data.currentUser
        state.editions = response.data.editions
        state.licensedEdition = response.data.licensedEdition
        state.poweredByStripe = response.data.poweredByStripe
        state.defaultPluginSvg = response.data.defaultPluginSvg
    },

    updatePluginLicenseInfo(state, {response}) {
        state.pluginLicenseInfo = response.data
    },

    updateCraftId(state, {craftId}) {
        state.craftId = craftId
    },
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations,
}
