import axios from 'axios'

// create a cancel token for axios
let CancelToken = axios.CancelToken
let cancelTokenSource = CancelToken.source()

// create an axios instance
const _axios = axios.create({
    baseURL: process.env.VUE_APP_CRAFT_API_ENDPOINT,
    headers: window.apiHeaders,
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
     * Get plugin store data.
     *
     * @returns {AxiosPromise<any>}
     */
    getCoreData() {
        return new Promise((resolve, reject) => {
            _axios.get('plugin-store/core-data')
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
     * Get developer.
     *
     * @param developerId
     * @returns {AxiosPromise<any>}
     */
    getDeveloper(developerId) {
        return new Promise((resolve, reject) => {
            _axios.get('developer/' + developerId)
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
     * Get featured section by handle.
     *
     * @param featuredSectionHandle
     * @returns {AxiosPromise<any>}
     */
    getFeaturedSectionByHandle(featuredSectionHandle) {
        return new Promise((resolve, reject) => {
            _axios.get('plugin-store/featured-section/' + featuredSectionHandle)
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
     * Get featured sections.
     *
     * @returns {AxiosPromise<any>}
     */
    getFeaturedSections() {
        return new Promise((resolve, reject) => {
            _axios.get('plugin-store/featured-sections')
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
     * Get plugin changelog.
     *
     * @param pluginId
     * @returns {AxiosPromise<any>}
     */
    getPluginChangelog(pluginId) {
        return new Promise((resolve, reject) => {
            _axios.get('plugin/' + pluginId + '/changelog')
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
     * Get plugin details.
     *
     * @param pluginId
     * @returns {AxiosPromise<any>}
     */
    getPluginDetails(pluginId) {
        return new Promise((resolve, reject) => {
            _axios.get('plugin/' + pluginId)
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
     * Get plugin details by handle.
     *
     * @param pluginHandle
     * @returns {AxiosPromise<any>}
     */
    getPluginDetailsByHandle(pluginHandle) {
        return new Promise((resolve, reject) => {
            _axios.get('plugin-store/plugin/' + pluginHandle)
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
     * Get plugins by category.
     *
     * @param categoryId
     * @param pluginIndexParams
     * @returns {AxiosPromise<any>}
     */
    getPluginsByCategory(categoryId, pluginIndexParams) {
        return new Promise((resolve, reject) => {
            const params = this._getPluginIndexParams(pluginIndexParams)
            params.categoryId = categoryId

            _axios.get('plugin-store/plugins', {
                    params
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
     * Get plugins by developer ID.
     *
     * @param developerId
     * @param pluginIndexParams
     * @returns {AxiosPromise<any>}
     */
    getPluginsByDeveloperId(developerId, pluginIndexParams) {
        return new Promise((resolve, reject) => {
            const params = this._getPluginIndexParams(pluginIndexParams)
            params.developerId = developerId

            _axios.get('plugin-store/plugins', {
                    params
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
     * Get plugins by featured section handle.
     *
     * @param featuredSectionHandle
     * @param pluginIndexParams
     * @returns {AxiosPromise<any>}
     */
    getPluginsByFeaturedSectionHandle(featuredSectionHandle, pluginIndexParams) {
        return new Promise((resolve, reject) => {
            const params = this._getPluginIndexParams(pluginIndexParams)

            _axios.get('plugin-store/plugins-by-featured-section/' + featuredSectionHandle, {
                    params
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
     * Get plugins by handles.
     *
     * @param pluginHandles
     * @returns {AxiosPromise<any>}
     */
    getPluginsByHandles(pluginHandles) {
        return new Promise((resolve, reject) => {
            let pluginHandlesString

            if (Array.isArray(pluginHandles)) {
                pluginHandlesString = pluginHandles.join(',')
            } else {
                pluginHandlesString = pluginHandles
            }

            _axios.get('plugin-store/plugins-by-handles', {
                params: {
                    pluginHandles: pluginHandlesString
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
     * Get plugins by IDs.
     *
     * @param pluginIds
     * @returns {AxiosPromise<any>}
     */
    getPluginsByIds(pluginIds) {
        return new Promise((resolve, reject) => {
            let pluginIdsString

            if (Array.isArray(pluginIds)) {
                pluginIdsString = pluginIds.join(',')
            } else {
                pluginIdsString = pluginIds
            }

            _axios.get('plugins', {
                params: {
                    ids: pluginIdsString
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
     * Search plugins.
     *
     * @param searchQuery
     * @param pluginIndexParams
     * @returns {AxiosPromise<any>}
     */
    searchPlugins(searchQuery, pluginIndexParams) {
        return new Promise((resolve, reject) => {
            const params = this._getPluginIndexParams(pluginIndexParams)
            params.searchQuery = searchQuery

            _axios.get('plugin-store/plugins', {
                params
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
     * Get plugin index params.
     *
     * @param limit
     * @param offset
     * @param orderBy
     * @param direction
     * @returns {{offset: *, limit: *, orderBy: *, direction: *}}
     * @private
     */
    _getPluginIndexParams({perPage, page, orderBy, direction}) {
        if (!perPage) {
            perPage = 96
        }

        if (!page) {
            page = 1
        }

        return {
            perPage,
            page,
            orderBy,
            direction
        }
    },
}
