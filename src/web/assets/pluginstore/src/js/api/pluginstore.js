import axios from 'axios'

export default {

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
                return cb(response.data)
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
                let pluginDetails = response.data
                return cb(pluginDetails)
            })
            .catch(response => {
                return errorCb(response)
            })
    },
}
