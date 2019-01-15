/* global Craft */

import axios from 'axios'

export default {

    /**
     * Get developer.
     */
    getDeveloper(developerId, cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/developer'), {
                params: {
                    developerId: developerId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response.data)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

    /**
     * Get plugin store data.
     */
    getPluginStoreData(cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/plugin-store-data'), '', {
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

    /**
     * Get plugin details.
     */
    getPluginDetails(pluginId, cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/plugin-details'), {
                params: {
                    pluginId: pluginId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

    /**
     * Get plugin changelog.
     */
    getPluginChangelog(pluginId, cb, errorCb) {
        axios.get(Craft.getActionUrl('plugin-store/plugin-changelog'), {
                params: {
                    pluginId: pluginId,
                },
                headers: {
                    'X-CSRF-Token': Craft.csrfTokenValue,
                }
            })
            .then(response => {
                return cb(response)
            })
            .catch(response => {
                return errorCb(response)
            })
    },

}
