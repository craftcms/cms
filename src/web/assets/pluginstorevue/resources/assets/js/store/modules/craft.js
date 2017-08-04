import api from '../../api'
import * as types from '../mutation-types'

const state = {
    installedPlugins: [6, 143]
}

const getters = {
    installedPlugins: (state, rootState) => {
        return rootState.allPlugins.filter(p => {
            return state.installedPlugins.find(pluginId => pluginId == p.id);
        })
    },
}

const actions = {

}

const mutations = {

}

export default {
    state,
    getters,
    actions,
    mutations
}
