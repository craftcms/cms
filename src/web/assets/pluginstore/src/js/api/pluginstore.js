/* global Craft */

import axios from 'axios'

// create a cancel token for axios
let CancelToken = axios.CancelToken
let cancelTokenSource = CancelToken.source()

export default {
    /**
     * Cancel requests.
     */
    cancelRequests() {
        // cancel requests
        cancelTokenSource.cancel()

        // create a new cancel token
        cancelTokenSource = CancelToken.source()
    },

    /**
     * Get plugin store data.
     *
     * @returns {AxiosPromise<any>}
     */
    getCoreData() {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'plugin-store/core-data', {
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
     * Get CMS editions.
     *
     * @returns {AxiosPromise<any>}
     */
    getCmsEditions() {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'cms-editions', {
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
     * Get developer.
     *
     * @param developerId
     * @returns {AxiosPromise<any>}
     */
    getDeveloper(developerId) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'developer/' + developerId, {
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
     * Get featured section by handle.
     *
     * @param featuredSectionHandle
     * @returns {AxiosPromise<any>}
     */
    getFeaturedSectionByHandle(featuredSectionHandle) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'plugin-store/featured-section/' + featuredSectionHandle, {
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
     * Get featured sections.
     *
     * @returns {AxiosPromise<any>}
     */
    getFeaturedSections() {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'plugin-store/featured-sections', {
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
     * Get plugin changelog.
     *
     * @param pluginId
     * @returns {AxiosPromise<any>}
     */
    getPluginChangelog(pluginId) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'plugin/' + pluginId + '/changelog', {
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
     * Get plugin details.
     *
     * @param pluginId
     * @returns {AxiosPromise<any>}
     */
    getPluginDetails(pluginId) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'plugin/' + pluginId, {
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
     * Get plugin details by handle.
     *
     * @param pluginHandle
     * @returns {AxiosPromise<any>}
     */
    getPluginDetailsByHandle(pluginHandle) {
        return new Promise((resolve, reject) => {
            Craft.sendApiRequest('GET', 'plugin-store/plugin/' + pluginHandle, {
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

            Craft.sendApiRequest('GET', 'plugin-store/plugins', {
                    cancelToken: cancelTokenSource.token,
                    params,
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

            Craft.sendApiRequest('GET', 'plugin-store/plugins', {
                    cancelToken: cancelTokenSource.token,
                    params,
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
     * Get plugins by featured section handle.
     *
     * @param featuredSectionHandle
     * @param pluginIndexParams
     * @returns {AxiosPromise<any>}
     */
    getPluginsByFeaturedSectionHandle(featuredSectionHandle, pluginIndexParams) {
        return new Promise((resolve, reject) => {
            const params = this._getPluginIndexParams(pluginIndexParams)

            Craft.sendApiRequest('GET', 'plugin-store/plugins-by-featured-section/' + featuredSectionHandle, {
                    cancelToken: cancelTokenSource.token,
                    params,
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

            Craft.sendApiRequest('GET', 'plugin-store/plugins-by-handles', {
                    cancelToken: cancelTokenSource.token,
                    params: {
                        pluginHandles: pluginHandlesString
                    },
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

            Craft.sendApiRequest('GET', 'plugins', {
                    cancelToken: cancelTokenSource.token,
                    params: {
                        ids: pluginIdsString
                    },
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
            
            Craft.sendApiRequest('GET', 'plugin-store/plugins', {
                    cancelToken: cancelTokenSource.token,
                    params,
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
