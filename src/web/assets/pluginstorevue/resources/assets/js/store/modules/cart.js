import * as types from '../mutation-types'

// initial state
const state = {
    activeTrials: [],
    items: [],
    checkoutStatus: null
}

// getters
const getters = {
    isInTrial(state) {
        return function(plugin) {
            return state.activeTrials.find(p => p.id == plugin.id)
        }
    },
    isInCart(state) {
        return function(plugin) {
            return state.items.find(p => p.id == plugin.id)
        }
    }
}

// actions
const actions = {

}

// mutations
const mutations = {
    [types.ADD_TO_CART] (state, { id }) {
        const record = state.items.find(p => p.id === id)

        if (!record) {
            state.items.push({
                id,
            })
        }
    },
    [types.REMOVE_FROM_CART] (state, { id }) {
        const record = state.items.find(p => p.id === id)

        const index = state.items.indexOf(record);

        state.items.splice(index, 1);
    },
    [types.ADD_TO_ACTIVE_TRIALS] (state, { id }) {
        const record = state.activeTrials.find(p => p.id === id)

        if (!record) {
            state.activeTrials.push({
                id,
            })
        }
    },
    [types.REMOVE_FROM_ACTIVE_TRIALS] (state, { id }) {
        const record = state.activeTrials.find(p => p.id === id)

        const index = state.activeTrials.indexOf(record);

        state.activeTrials.splice(index, 1);
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
