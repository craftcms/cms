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
        return new Promise((resolve, reject) => {
            _axios.get(Craft.getActionUrl('plugin-store/craft-data'))
                .then((response) => {
                    resolve(response)
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        // request cancelled
                    } else {
                        reject(error)
                    }
                })
        })
    },


    /**
     * Get countries.
     */
    getCountries() {
        return new Promise((resolve, reject) => {
            _axios.get('countries', {
                    baseURL: process.env.VUE_APP_CRAFT_API_ENDPOINT,
                    headers: window.apiHeaders,
                })
                .then((response) => {
                    resolve(response)
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        // request cancelled
                    } else {
                        reject(error)
                    }
                })
        })
    },

    /**
     * Get Plugin License Info.
     */
    getPluginLicenseInfo() {
        return new Promise((resolve, reject) => {
            _axios.get(Craft.getActionUrl('app/get-plugin-license-info'))
                .then((response) => {
                    resolve(response)
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        // request cancelled
                    } else {
                        reject(error)
                    }
                })
        })
    },

    /**
     * Switch plugin edition.
     */
    switchPluginEdition(pluginHandle, edition) {
        return new Promise((resolve, reject) => {
            const data = 'pluginHandle=' + pluginHandle + '&edition=' + edition

            _axios.post(Craft.getActionUrl('plugins/switch-edition'), data, {
                    headers: {
                        'X-CSRF-Token': Craft.csrfTokenValue,
                    },
                })
                .then((response) => {
                    resolve(response)
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        // request cancelled
                    } else {
                        reject(error)
                    }
                })
        })
    },

    /**
     * Try edition.
     */
    tryEdition(edition) {
        return new Promise((resolve, reject) => {
            _axios.post(Craft.getActionUrl('app/try-edition'), 'edition=' + edition, {
                    headers: {
                        'X-CSRF-Token': Craft.csrfTokenValue,
                    }
                })
                .then((response) => {
                    resolve(response)
                })
                .catch((error) => {
                    if (axios.isCancel(error)) {
                        // request cancelled
                    } else {
                        reject(error)
                    }
                })
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
