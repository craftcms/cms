import * as types from '../mutation-types'

// initial state
const state = {
    activeTrials: [],
}

// getters
const getters = {
}

// actions
const actions = {

}

// mutations
const mutations = {
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
