/* global Craft */

import axios from 'axios'

export default {

    /**
     * Get Craft data.
     */
    getCraftData(cb, cbError) {
        axios.get(Craft.getActionUrl('plugin-store/craft-data'))
            .then(response => {
                return cb(response)
            })
            .catch(response => {
                return cbError(response)
            })
    },

    /**
     * Get Plugin License Info.
     */
    getPluginLicenseInfo(cb, cbError) {
        axios.get(Craft.getActionUrl('app/get-plugin-license-info'))
            .then(response => {
                return cb(response)
            })
            .catch(response => {
                return cbError(response)
            })
    },

    /**
     * Try edition.
     */
    tryEdition(edition) {
        return axios.post(Craft.getActionUrl('app/try-edition'), 'edition=' + edition, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    /**
     * Switch plugin edition.
     */
    switchPluginEdition(pluginHandle, edition) {
        const data = 'pluginHandle=' + pluginHandle + '&edition=' + edition

        return axios.post(Craft.getActionUrl('plugins/switch-edition'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            },
        })
    }

}
