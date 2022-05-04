/* global Craft */

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
   * Get Craft data.
   */
  getCraftData() {
    return new Promise((resolve, reject) => {
      api
        .sendActionRequest('GET', 'plugin-store/craft-data')
        .then((response) => {
          resolve(response);
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
   * Get Craft ID data.
   */
  getCraftIdData({accessToken}) {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'account', {
          headers: {
            Authorization: 'Bearer ' + accessToken,
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
   * Get countries.
   */
  getCountries() {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'countries')
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
   * Get Plugin License Info.
   */
  getPluginLicenseInfo() {
    return new Promise((resolve, reject) => {
      api
        .sendApiRequest('GET', 'cms-licenses', {
          params: {
            include: 'plugins',
          },
        })
        .then((response) => {
          api
            .sendActionRequest('POST', 'app/get-plugin-license-info', {
              data: {
                pluginLicenses: response.license.pluginLicenses || [],
              },
              headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
              },
            })
            .then((response) => {
              resolve(response);
            })
            .catch((error) => {
              if (axios.isCancel(error)) {
                // request cancelled
              } else {
                reject(error);
              }
            });
        });
    });
  },

  /**
   * Switch plugin edition.
   */
  switchPluginEdition(pluginHandle, edition) {
    return new Promise((resolve, reject) => {
      const data = 'pluginHandle=' + pluginHandle + '&edition=' + edition;

      api
        .sendActionRequest('POST', 'plugins/switch-edition', {
          data,
          headers: {
            'X-CSRF-Token': Craft.csrfTokenValue,
          },
        })
        .then((response) => {
          Craft.clearCachedApiHeaders();
          resolve(response);
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
   * Try edition.
   */
  tryEdition(edition) {
    return new Promise((resolve, reject) => {
      api
        .sendActionRequest('POST', 'app/try-edition', {
          data: 'edition=' + edition,
          headers: {
            'X-CSRF-Token': Craft.csrfTokenValue,
          },
        })
        .then((response) => {
          Craft.clearCachedApiHeaders();
          resolve(response);
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
};
