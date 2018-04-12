import api from '../../api/developers'
import * as types from '../mutation-types'

/**
 * State
 */
const state = {
    developer: [],
}

/**
 * Getters
 */
const getters = {
    developer: state => state.developer,
}

/**
 * Actions
 */
const actions = {

    getDeveloper({commit}, developerId) {
        return new Promise((resolve, reject) => {
            api.getDeveloper(developerId, developer => {
                commit(types.RECEIVE_DEVELOPER, {developer})
                resolve(developer)
            }, response => {
                reject(response)
            })
        })
    }

}

/**
 * Mutations
 */
const mutations = {

    [types.RECEIVE_DEVELOPER](state, {developer}) {
        state.developer = developer
    },

}

export default {
    state,
    getters,
    actions,
    mutations
}
