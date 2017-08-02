import api from '../../api'
import * as types from '../mutation-types'

const state = {
    _allCategories: [],
}

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

const actions = {

}

const mutations = {

}

export default {
    state,
    getters,
    actions,
    mutations
}
