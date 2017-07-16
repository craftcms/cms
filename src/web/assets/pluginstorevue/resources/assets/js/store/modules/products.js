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
    allProducts: state => state.all,
    staffPicks: state => state.staffPicks,
    activeTrials: state => state.activeTrials,
}

// actions
const actions = {
    getAllProducts ({ commit }) {
        shop.getProducts(products => {
            commit(types.RECEIVE_PRODUCTS, { products })
        })
    },
    getStaffPicks ({ commit }) {
        shop.getStaffPicks(products => {
            commit(types.RECEIVE_STAFF_PICKS, { products })
        })
    }
}

// mutations
const mutations = {
    [types.RECEIVE_PRODUCTS] (state, { products }) {
        state.all = products
    },
    [types.RECEIVE_STAFF_PICKS] (state, { products }) {
        state.staffPicks = products
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
