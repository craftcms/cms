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
                        name: "All core CMS features",
                        description: "Core Craft CMS features include live preview, muti-site, matrix, localization, one-click updating, custom fields, section types, image editor, relations, plugin store, debug toolbar, categories and tags"
                    },
                    {
                        name: "Single admin account",
                        description: "Admin/user account"
                    }
                ],
                "pro": [
                    {
                        name: "Unlimited user accounts",
                        description: "Create unlimited user accounts, user groups, user permissions, and public user registration.",
                    },
                    {
                        name: "System branding features",
                        description: "Customize your dashboard specifically to your brand.",
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
            api.getCraftData(response => {
                commit('updateCraftData', {response})
                resolve(response)
            }, response => {
                reject(response)
            })
        })
    },

    getPluginLicenseInfo({commit}) {
        return new Promise((resolve, reject) => {
            api.getPluginLicenseInfo(response => {
                commit('updatePluginLicenseInfo', {response})
                resolve(response)
            }, response => {
                reject(response)
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

    /**
     * Switch plugin edition.
     */
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
