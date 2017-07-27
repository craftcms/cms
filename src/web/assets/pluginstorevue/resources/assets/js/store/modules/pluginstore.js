import api from '../../api'
import * as types from '../mutation-types'

// initial state
const state = {
    data: {},
}

// getters
const getters = {
    pluginStoreData: state => state.data,
    pluginStoreGetAllCategories(state) {
        return state.data.categories;
    }
}

// actions
const actions = {
    getPluginStoreData ({ commit }) {
        api.getPluginStoreData(data => {
            commit(types.RECEIVE_PLUGIN_STORE_DATA, { data })
        })
    }
}


// mutations
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
