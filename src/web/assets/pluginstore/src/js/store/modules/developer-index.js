import api from '../../api/developerIndex';

/**
 * State
 */
const state = {
  developersResponseData: null,
  developers: [],
};

/**
 * Getters
 */
const getters = {
  hasMore(state) {
    return (
      state.developersResponseData.currentPage <
      state.developersResponseData.total
    );
  },

  getDeveloperIndexParams(state, getters, rootState) {
    return (context) => {
      if (!context) {
        context = {};
      }

      const sortOptions = rootState.pluginStore.sortOptions;
      const firstOptionKey = Object.keys(sortOptions)[0];

      const perPage = context.perPage ? context.perPage : null;
      const page = context.page ? context.page : 1;
      const orderBy = context.orderBy ? context.orderBy : firstOptionKey;
      const direction = context.direction
        ? context.direction
        : rootState.pluginStore.sortOptions[firstOptionKey];

      return {
        perPage,
        page,
        orderBy,
        direction,
      };
    };
  },
};

/**
 * Actions
 */
const actions = {
  cancelRequests() {
    return new Promise((resolve) => {
      api.cancelRequests();
      resolve();
    });
  },

  searchDevelopers({dispatch, getters}, context) {
    return new Promise((resolve, reject) => {
      const developerIndexParams = getters['getDeveloperIndexParams'](context);

      api
        .searchDevelopers({
          searchQuery: context.searchQuery,
          developerIndexParams,
        })
        .then((response) => {
          if (response.data && response.data.error) {
            reject(response.data.error);
          }

          dispatch('updateDeveloperIndex', {context, response}).then(() => {
            resolve(response);
          });
        })
        .catch((thrown) => {
          if (thrown.response && thrown.response.data) {
            if (thrown.response.data.message) {
              reject(thrown.response.data.message);
            } else if (thrown.response.data.error) {
              reject(thrown.response.data.error);
            } else {
              reject(thrown.response.data);
            }
          } else {
            reject(thrown);
          }
        });
    });
  },

  updateDeveloperIndex({commit}, {context, response}) {
    return new Promise((resolve) => {
      commit('updateDevelopersResponseData', response);

      if (context.appendData && context.appendData === true) {
        commit('appendDevelopers', response.data.developers);
        resolve();
      } else {
        setTimeout(function () {
          commit('updateDevelopers', response.data.developers);
          resolve();
        }, 1);
      }
    });
  },
};

/**
 * Mutations
 */
const mutations = {
  updateDevelopers(state, developers) {
    state.developers = developers;
  },

  updateDevelopersResponseData(state, response) {
    state.developersResponseData = response.data;
  },

  appendDevelopers(state, developers) {
    state.developers = [...state.developers, ...developers];
  },
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
