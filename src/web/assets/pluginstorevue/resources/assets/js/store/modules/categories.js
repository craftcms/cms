import api from '../../api'
import * as types from '../mutation-types'

// initial state
const state = {
    _allCategories: [],
}

// getters
const getters = {
    allCategories: state => state._allCategories,
    getCategoryById(state) {
        return function(id) {
            return state._allCategories.find(c => c.id == id)
        };
    }
}

// actions
const actions = {
    getAllCategories ({ commit }) {
        api.getCategories(categories => {
            commit(types.RECEIVE_CATEGORIES, { categories })
        })
    }
}

// mutations
const mutations = {
    [types.RECEIVE_CATEGORIES] (state, { categories }) {
        state._allCategories = categories
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
