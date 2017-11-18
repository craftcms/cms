import api from '../../api'
import * as types from '../mutation-types'

const state = {
    developer: [],
}

const getters = {
    developer: state => state.developer,
}

const actions = {

    getDeveloper({ commit }, developerId) {
        return new Promise((resolve, reject) => {
            api.getDeveloper(developerId, developer => {
                commit(types.RECEIVE_DEVELOPER, { developer });
                resolve(developer);
            }, response => {
                reject(response);
            })
        })
    }

}

const mutations = {

    [types.RECEIVE_DEVELOPER] (state, { developer }) {
        state.developer = developer
    },

}

export default {
    state,
    getters,
    actions,
    mutations
}
