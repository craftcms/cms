/* global Craft */

import axios from 'axios'

export default {
    /**
     * Get Craft data.
     */
    getCraftData() {
        return axios.get(Craft.getActionUrl('plugin-store/craft-data'))
    },

    /**
     * Get Plugin License Info.
     */
    getPluginLicenseInfo() {
        return axios.get(Craft.getActionUrl('app/get-plugin-license-info'))
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
