import api from '../../api/pluginstore';

const state = {
  reviews: [],
  status: 'idle',
  fetchStatus: 'idle',
  meta: {
    current_page: 1,
    from: 0,
    to: 10,
    last_page: 1,
    per_page: 12,
    total: 0,
  },
};

const getters = {};

const actions = {
  getPluginReviews({commit, state}, {handle, params}) {
    if (state.fetchStatus === 'fetching') {
      return;
    }

    const originalMeta = state.meta;

    if (state.status !== 'success') {
      commit('setStatus', 'loading');
    }

    commit('setFetchStatus', 'fetching');
    commit('updateMeta', params);

    api
      .getPluginReviews(handle, params)
      .then((data) => {
        commit('updateReviews', data.data);
        commit('updateMeta', data);

        commit('setFetchStatus', 'idle');
        if (state.status !== 'success') {
          commit('setStatus', 'success');
        }
      })
      .catch(() => {
        commit('setFetchStatus', 'idle');
        commit('setStatus', 'error');
        commit('updateMeta', originalMeta);
      });
  },
};

const mutations = {
  updateReviews(state, data = []) {
    state.reviews = data;
  },

  updateMeta(state, meta) {
    state.meta = {
      ...state.meta,
      ...meta,
    };
  },

  setFetchStatus(state, newStatus) {
    state.fetchStatus = newStatus;
  },

  setStatus(state, newStatus) {
    state.status = newStatus;
  },
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
};
