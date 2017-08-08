import api from '../../api'
import * as types from '../mutation-types'

const state = {
    craftData: {},
    // installedPlugins: JSON.parse(window.localStorage.getItem('craft.installedPlugins') || '[]')
}

const getters = {
    installedPlugins: (state, rootState) => {
        return rootState.allPlugins.filter(p => {
            return state.craftData.installedPlugins.find(pluginId => pluginId == p.id);
        })
    },
}

const actions = {
    getCraftData ({ commit }) {
        return new Promise((resolve, reject) => {
            api.getCraftData(data => {
                commit(types.RECEIVE_CRAFT_DATA, { data })
                resolve(data);
            })
        })
    }
}

const mutations = {
    installPlugin(state, { plugin }) {
        const record = state.craftData.installedPlugins.find(pluginId => pluginId === plugin.id)

        if (!record) {
            state.craftData.installedPlugins.push(plugin.id)
        }
    },
    [types.RECEIVE_CRAFT_DATA] (state, { data }) {
        state.craftData = data
    },
}

export default {
    state,
    getters,
    actions,
    mutations,
}
