/* global Craft */

import axios from 'axios'

// create a cancel token for axios
let CancelToken = axios.CancelToken
let cancelTokenSource = CancelToken.source()

// create an axios instance
const _axios = axios.create({
    cancelToken: cancelTokenSource.token,
})

export default {
    /**
     * Cancel requests.
     */
    cancelRequests() {
        // cancel requests
        cancelTokenSource.cancel()

        // create a new cancel token
        cancelTokenSource = CancelToken.source()

        // update axios with the new cancel token
        _axios.defaults.cancelToken = cancelTokenSource.token
    },

    /**
     * Get Craft data.
     */
    getCraftData() {
        return _axios.get(Craft.getActionUrl('plugin-store/craft-data'))
    },

    /**
     * Get Plugin License Info.
     */
    getPluginLicenseInfo() {
        return _axios.get(Craft.getActionUrl('app/get-plugin-license-info'))
    },

    /**
     * Switch plugin edition.
     */
    switchPluginEdition(pluginHandle, edition) {
        const data = 'pluginHandle=' + pluginHandle + '&edition=' + edition

        return _axios.post(Craft.getActionUrl('plugins/switch-edition'), data, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            },
        })
    },

    /**
     * Try edition.
     */
    tryEdition(edition) {
        return _axios.post(Craft.getActionUrl('app/try-edition'), 'edition=' + edition, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    /**
     * Get API headers.
     *
     * @returns {AxiosPromise<any>}
     */
    getApiHeaders() {
        return _axios.get(Craft.getActionUrl('plugin-store/get-api-headers'))
    }
}
