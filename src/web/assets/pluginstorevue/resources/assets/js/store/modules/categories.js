import shop from '../../api/shop'
import * as types from '../mutation-types'

// initial state
const state = {
    _allCategories: [],
}

// getters
const getters = {
    allCategories: state => state._allCategories,
}

// actions
const actions = {
    getAllCategories ({ commit }) {
        shop.getCategories(categories => {
            commit(types.RECEIVE_CATEGORIES, { categories })
        })
    },
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
