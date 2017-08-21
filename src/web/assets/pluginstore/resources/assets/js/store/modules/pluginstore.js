import api from '../../api'
import * as types from '../mutation-types'

const state = {
    data: {},
}

const getters = {
    pluginStoreData: state => state.data,
    pluginStoreGetAllCategories(state) {
        return state.data.categories;
    },
    getFeaturedPlugin(state) {
        return function(id) {
            if(state.data.featuredPlugins) {
                return state.data.featuredPlugins.find(g => g.id == id)
            }
        };
    },
}

const actions = {
    getPluginStoreData ({ commit }) {
        return new Promise((resolve, reject) => {
            api.getPluginStoreData(data => {
                commit(types.RECEIVE_PLUGIN_STORE_DATA, { data })
                resolve(data);
            })
        })
    }
}

const mutations = {
    [types.RECEIVE_PLUGIN_STORE_DATA] (state, { data }) {
        state.data = data
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
