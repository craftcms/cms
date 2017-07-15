import * as types from '../mutation-types'

// initial state
// shape: [{ id, quantity }]
const state = {
    added: [],
    checkoutStatus: null
}

// getters
const getters = {
}

// actions
const actions = {

}

// mutations
const mutations = {
    [types.ADD_TO_CART] (state, { id }) {
        const record = state.added.find(p => p.id === id)

        if (!record) {
            state.added.push({
                id,
            })
        }
    },
    [types.REMOVE_FROM_CART] (state, { id }) {
        const record = state.added.find(p => p.id === id)

        const index = state.added.indexOf(record);

        state.added.splice(index, 1);
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
