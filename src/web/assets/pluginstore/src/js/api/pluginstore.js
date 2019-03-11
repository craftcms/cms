/* global Craft */

import axios from 'axios'

export default {
    /**
     * Get developer.
     */
    getDeveloper(developerId) {
        return axios.get(Craft.getActionUrl('plugin-store/developer'), {
                params: {
                    developerId: developerId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
    },

    /**
     * Get plugin store data.
     */
    getPluginStoreData() {
        return axios.get(Craft.getActionUrl('plugin-store/plugin-store-data'), '', {
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
    },

    /**
     * Get plugin details.
     */
    getPluginDetails(pluginId) {
        return axios.get(Craft.getActionUrl('plugin-store/plugin-details'), {
                params: {
                    pluginId: pluginId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
    },

    /**
     * Get plugin changelog.
     */
    getPluginChangelog(pluginId) {
        return axios.get(Craft.getActionUrl('plugin-store/plugin-changelog'), {
                params: {
                    pluginId: pluginId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
    },
}
