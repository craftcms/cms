import api from '../../api'
import * as types from '../mutation-types'

// initial state
const state = {
    _developer: [],
}

// getters
const getters = {
    developer: state => state._developer,
}

// actions
const actions = {

    // Inspired from: https://stackoverflow.com/a/40167499/1686828

    getDeveloper({ commit }, developerId) {
        return new Promise((resolve, reject) => {
            api.getDeveloper(developer => {
                commit(types.RECEIVE_DEVELOPER, { developer });
                resolve(developer);
            }, developerId)
        })
    }
}

// mutations
const mutations = {
    [types.RECEIVE_DEVELOPER] (state, { developer }) {
        state._developer = developer
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
