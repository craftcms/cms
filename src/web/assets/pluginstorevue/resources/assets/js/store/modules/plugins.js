import shop from '../../api/shop'
import * as types from '../mutation-types'

// initial state
const state = {
    all: [],
    staffPicks: [],
    activeTrials: [],
}

// getters
const getters = {
    allPlugins: state => state.all,
    staffPicks: state => state.staffPicks,
    activeTrials: state => state.activeTrials,
}

// actions
const actions = {
    getAllPlugins ({ commit }) {
        shop.getPlugins(plugins => {
            commit(types.RECEIVE_PRODUCTS, { plugins })
        })
    },
    getStaffPicks ({ commit }) {
        shop.getStaffPicks(plugins => {
            commit(types.RECEIVE_STAFF_PICKS, { plugins })
        })
    }
}

// mutations
const mutations = {
    [types.RECEIVE_PRODUCTS] (state, { plugins }) {
        state.all = plugins
    },
    [types.RECEIVE_STAFF_PICKS] (state, { plugins }) {
        state.staffPicks = plugins
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
