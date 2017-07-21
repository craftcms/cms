import api from '../../api'
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
    getPluginById(state) {
        return function(id) {
            return state.all.find(p => p.id == id)
        };
    },
    getPluginsByCategory(state) {
        return function(categoryId) {
            return state.all.filter(p => {
                return p.categories.find(c =>  c == categoryId);
            })
        }
    }
}

// actions
const actions = {
    getAllPlugins ({ commit }) {
        api.getPlugins(plugins => {
            commit(types.RECEIVE_PRODUCTS, { plugins })
        })
    },
    getStaffPicks ({ commit }) {
        api.getStaffPicks(plugins => {
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
