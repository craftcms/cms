import axios from 'axios';
import api from '../utils/api';

export default {
  /**
   * Cancel requests.
   */
  cancelRequests() {
    api.cancelRequests();
  },

  /**
   * Get plugin store data.
   *
   * @returns {AxiosPromise<any>}
   */
  getCoreData() {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'plugin-store/core-data')
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get CMS editions.
   *
   * @returns {AxiosPromise<any>}
   */
  getCmsEditions() {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'cms-editions')
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get developer.
   *
   * @param developerId
   * @returns {AxiosPromise<any>}
   */
  getDeveloper(developerId) {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'developer/' + developerId)
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get featured section by handle.
   *
   * @param featuredSectionHandle
   * @returns {AxiosPromise<any>}
   */
  getFeaturedSectionByHandle(featuredSectionHandle) {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest(
          'GET',
          'plugin-store/featured-section/' + featuredSectionHandle
        )
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get featured sections.
   *
   * @returns {AxiosPromise<any>}
   */
  getFeaturedSections() {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'plugin-store/featured-sections')
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get plugin changelog.
   *
   * @param pluginId
   * @returns {AxiosPromise<any>}
   */
  getPluginChangelog(pluginId) {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'plugin/' + pluginId + '/changelog')
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get plugin details.
   *
   * @param pluginId
   * @returns {AxiosPromise<any>}
   */
  getPluginDetails(pluginId) {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'plugin/' + pluginId)
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get plugin details by handle.
   *
   * @param pluginHandle
   * @returns {AxiosPromise<any>}
   */
  getPluginDetailsByHandle(pluginHandle) {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'plugin-store/plugin/' + pluginHandle, {
          params: {
            withInstallHistory: true,
            withIssueStats: true,
          },
        })
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
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
      const params = this._getPluginIndexParams(pluginIndexParams);
      params.categoryId = categoryId;

      api
        .sendApiRequest('GET', 'plugin-store/plugins', {
          params,
        })
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // Request was cancelled, silently fail
          } else {
            reject(error);
          }
        });
    });
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
      const params = this._getPluginIndexParams(pluginIndexParams);
      params.developerId = developerId;

      api
        .sendApiRequest('GET', 'plugin-store/plugins', {
          params,
        })
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
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
      const params = this._getPluginIndexParams(pluginIndexParams);

      api
        .sendApiRequest(
          'GET',
          'plugin-store/plugins-by-featured-section/' + featuredSectionHandle,
          {
            params,
          }
        )
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get plugins by handles.
   *
   * @param pluginHandles
   * @returns {AxiosPromise<any>}
   */
  getPluginsByHandles(pluginHandles) {
    return new Promise((resolve, reject) => {
      let pluginHandlesString;

      if (Array.isArray(pluginHandles)) {
        pluginHandlesString = pluginHandles.join(',');
      } else {
        pluginHandlesString = pluginHandles;
      }

      api
        .sendApiRequest('GET', 'plugin-store/plugins-by-handles', {
          params: {
            pluginHandles: pluginHandlesString,
          },
        })
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
  },

  /**
   * Get plugins by IDs.
   *
   * @param pluginIds
   * @returns {AxiosPromise<any>}
   */
  getPluginsByIds(pluginIds) {
    return new Promise((resolve, reject) => {
      let pluginIdsString;

      if (Array.isArray(pluginIds)) {
        pluginIdsString = pluginIds.join(',');
      } else {
        pluginIdsString = pluginIds;
      }

      api
        .sendApiRequest('GET', 'plugins', {
          params: {
            ids: pluginIdsString,
          },
        })
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
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
      const params = this._getPluginIndexParams(pluginIndexParams);
      params.searchQuery = searchQuery;

      api
        .sendApiRequest('GET', 'plugin-store/plugins', {
          params,
        })
        .then((responseData) => {
          resolve(responseData);
        })
        .catch((error) => {
          if (axios.isCancel(error)) {
            // request cancelled
          } else {
            reject(error);
          }
        });
    });
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
      perPage = 96;
    }

    if (!page) {
      page = 1;
    }

    return {
      perPage,
      page,
      orderBy,
      direction,
    };
  },
};
