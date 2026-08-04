import api from '../../api/craft';

/**
 * State
 */
const state = {
  canTestEditions: null,
  countries: null,
  craftId: null,
  craftLogo: null,
  currentUser: null,
  licensedEdition: null,
  pluginLicenseInfo: {},

  // Craft editions
  CraftEdition: null,
  CraftSolo: null,
  CraftTeam: null,
  CraftPro: null,
  CraftEnterprise: null,
};

/**
 * Getters
 */
const getters = {
  getCmsEditionFeatures() {
    return (editionHandle) => {
      const features = {
        solo: [
          {
            name: 'One user account',
            description:
              'The Solo edition is limited to a single admin account.',
          },
          {
            name: 'Flexible content modeling',
            description:
              'Define custom content types, fields, and relations needed to perfectly contain your unique content requirements.',
          },
          {
            name: 'Multi-site + localization',
            description:
              'Serve multiple related/localized sites from a single Craft installation.',
          },
          {
            name: 'Advanced previewing',
            description:
              'Preview your content from multiple targets, including single-page applications.',
          },
          {
            name: 'Twig + GraphQL',
            description:
              'Define custom front-end templates, or use Craft headlessly with the auto-generated GraphQL API.',
          },
        ],
        team: [
          {
            name: 'Up to five user accounts',
            description:
              'Create up to five user accounts (including admin accounts).',
          },
          {
            name: 'One user group',
            description:
              'All accounts belong to a “Team” user group with customizable permissions for non-admins.',
          },
          {
            name: 'Developer support',
            description:
              'Get developer-to-developer support right from the Craft core development team.',
          },
        ],
        pro: [
          {
            name: 'Unlimited user accounts',
            description:
              'Create unlimited user accounts with per-user permissions and user group assignments.',
          },
          {
            name: 'Unlimited user groups',
            description: 'Create multiple user groups with custom permissions.',
          },
          {
            name: 'Branded control panel',
            description: 'Personalize the control panel for your brand.',
          },
          {
            name: 'Branded communication',
            description:
              'Customize system email messages and provide a custom email template.',
          },
        ],
      };

      if (!features[editionHandle]) {
        return null;
      }

      return features[editionHandle];
    };
  },

  getPluginLicenseInfo(state) {
    return (pluginHandle) => {
      if (!state.pluginLicenseInfo) {
        return null;
      }

      if (!state.pluginLicenseInfo[pluginHandle]) {
        return null;
      }

      return state.pluginLicenseInfo[pluginHandle];
    };
  },

  isPluginInstalled(state) {
    return (pluginHandle) => {
      if (!state.pluginLicenseInfo) {
        return false;
      }

      if (!state.pluginLicenseInfo[pluginHandle]) {
        return false;
      }

      if (!state.pluginLicenseInfo[pluginHandle].isInstalled) {
        return false;
      }

      return true;
    };
  },

  getCmsEditionIndex(state) {
    return (editionHandle) => {
      switch (editionHandle) {
        case 'solo':
          return state.CraftSolo;
        case 'team':
          return state.CraftTeam;
        case 'pro':
          return state.CraftPro;
        case 'enterprise':
          return state.CraftEnterprise;
        default:
          return null;
      }
    };
  },
};

/**
 * Actions
 */
const actions = {
  cancelRequests() {
    return api.cancelRequests();
  },

  getCraftData({commit}) {
    return new Promise((resolve, reject) => {
      api
        .getCraftData()
        .then((response) => {
          commit('updateCraftData', {response});
          api
            .getCountries()
            .then((responseData) => {
              commit('updateCountries', {responseData});
              resolve();
            })
            .catch((error) => {
              reject(error);
            });
        })
        .catch((error) => {
          reject(error);
        });
    });
  },

  getCraftIdData({commit}, {accessToken}) {
    return new Promise((resolve, reject) => {
      api
        .getCraftIdData({accessToken})
        .then((responseData) => {
          commit('updateCraftIdData', {responseData});
          resolve();
        })
        .catch((error) => {
          reject(error);
        });
    });
  },

  getPluginLicenseInfo({commit}) {
    return new Promise((resolve, reject) => {
      api
        .getPluginLicenseInfo()
        .then((response) => {
          commit('updatePluginLicenseInfo', {response});
          resolve(response);
        })
        .catch((error) => {
          reject(error);
        });
    });
  },

  switchPluginEdition({dispatch}, {pluginHandle, edition}) {
    return new Promise((resolve, reject) => {
      api
        .switchPluginEdition(pluginHandle, edition)
        .then((switchPluginEditionResponse) => {
          dispatch('getPluginLicenseInfo')
            .then((getPluginLicenseInfoResponse) => {
              resolve({
                switchPluginEditionResponse,
                getPluginLicenseInfoResponse,
              });
            })
            .catch((response) => reject(response));
        })
        .catch((response) => reject(response));
    });
  },

  tryEdition(context, edition) {
    return new Promise((resolve, reject) => {
      api
        .tryEdition(edition)
        .then((response) => {
          resolve(response);
        })
        .catch((response) => {
          reject(response);
        });
    });
  },
};

/**
 * Mutations
 */
const mutations = {
  updateCraftData(state, {response}) {
    state.canTestEditions = response.data.canTestEditions;
    state.craftLogo = response.data.craftLogo;
    state.currentUser = response.data.currentUser;
    state.licensedEdition = response.data.licensedEdition;

    // Craft editions
    state.CraftEdition = response.data.CraftEdition;
    state.CraftSolo = response.data.CraftSolo;
    state.CraftTeam = response.data.CraftTeam;
    state.CraftPro = response.data.CraftPro;
    state.CraftEnterprise = response.data.CraftEnterprise;
  },

  updateCraftIdData(state, {responseData}) {
    state.craftId = responseData;
  },

  updateCountries(state, {responseData}) {
    state.countries = responseData.countries;
  },

  updateCraftId(state, craftId) {
    state.craftId = craftId;
  },

  updatePluginLicenseInfo(state, {response}) {
    state.pluginLicenseInfo = response.data;
  },
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
