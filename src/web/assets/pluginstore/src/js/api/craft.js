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
     * Get Craft ID data.
     */
    getCraftIdData({accessToken}) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'account', {
                    cancelToken: cancelTokenSource.token,
                    headers: {
                        'Authorization': 'Bearer ' + accessToken,
                    }
                })
                .then((responseData) => {
                    resolve(responseData)
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
            Craft.sendApiRequest('GET', 'countries', {
                    cancelToken: cancelTokenSource.token,
                })
                .then((responseData) => {
                    resolve(responseData)
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
            Craft.sendApiRequest('GET', 'cms-licenses', {
                    params: {
                        include: 'plugins',
                    },
                })
                .then(function(response) {
                    _axios.post(Craft.getActionUrl('app/get-plugin-license-info'), {
                            pluginLicenses: response.license.pluginLicenses || [],
                        }, {
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
                    Craft.clearCachedApiHeaders()
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
                    Craft.clearCachedApiHeaders()
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
}
