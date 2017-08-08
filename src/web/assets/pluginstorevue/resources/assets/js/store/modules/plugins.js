import api from '../../api'
import * as types from '../mutation-types'

const state = {
    all: [],
}

const getters = {
    allPlugins: state => state.all,
    getPluginById(state) {
        return function(id) {
            return state.all.find(p => p.id == id)
        };
    },
    getPluginsByIds(state) {
        return function(ids) {
            return state.all.filter(p => {
                return ids.find(id => id == p.id)
            })
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

const actions = {
    getAllPlugins ({ commit }) {
        return new Promise((resolve, reject) => {
            api.getPlugins(plugins => {
                commit(types.RECEIVE_PRODUCTS, { plugins });
                resolve(plugins);
            })
        })
    },
}

const mutations = {
    [types.RECEIVE_PRODUCTS] (state, { plugins }) {
        state.all = plugins
    },
}

export default {
    state,
    getters,
    actions,
    mutations
}
