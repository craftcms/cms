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
    getIndexPluginGroup(state) {
        return function(id) {
            if(state.data.indexPluginGroups) {
                return state.data.indexPluginGroups.find(g => g.id == id)
            }
        };
    },
}

const actions = {
    getPluginStoreData ({ commit }) {
        api.getPluginStoreData(data => {
            commit(types.RECEIVE_PLUGIN_STORE_DATA, { data })
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
