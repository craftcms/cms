import api from '../../api'
import * as types from '../mutation-types'

// initial state
const state = {
    _allCategories: [],
}

// getters
const getters = {
    getAllCategories(state, rootState) {
        return function() {
            return rootState.pluginStoreGetAllCategories;
        }
    },
    getCategoryById(state, rootState) {
        return function(id) {
            if(rootState.pluginStoreGetAllCategories) {
                return rootState.pluginStoreGetAllCategories.find(c => c.id == id)
            }
        };
    }
}

// actions
const actions = {

}

// mutations
const mutations = {

}

export default {
    state,
    getters,
    actions,
    mutations
}
