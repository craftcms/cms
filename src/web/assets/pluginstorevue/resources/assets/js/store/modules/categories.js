import shop from '../../api/shop'
import * as types from '../mutation-types'

// initial state
const state = {
    _allCategories: [],
    _categoryPlugins: [],
}

// getters
const getters = {
    allCategories: state => state._allCategories,
    categoryPlugins: state => state._categoryPlugins,
    getCategoryById(state) {
        return function(id) {
            return state._allCategories.find(c => c.id == id)
        };
    }
}

// actions
const actions = {
    getAllCategories ({ commit }) {
        shop.getCategories(categories => {
            commit(types.RECEIVE_CATEGORIES, { categories })
        })
    },
    getCategoryPlugins({ commit }, categoryId) {
        shop.getCategoryPlugins(plugins => {
            commit(types.RECEIVE_CATEGORY_PLUGINS, { plugins });
        }, categoryId)
    }
}

// mutations
const mutations = {
    [types.RECEIVE_CATEGORIES] (state, { categories }) {
        state._allCategories = categories
    },
    [types.RECEIVE_CATEGORY_PLUGINS] (state, { plugins }) {
        state._categoryPlugins = plugins
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
