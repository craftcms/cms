import api from '../../api/cart';

/**
 * State
 */
const state = {
    searchQuery: ''
}

/**
 * Getters
 */
const getters = {

}

/**
 * Actions
 */
const actions = {

}

/**
 * Mutations
 */
const mutations = {

    updateSearchQuery(state, searchQuery) {
        state.searchQuery = searchQuery
    },

}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
