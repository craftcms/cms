import api from '../../api'
import * as types from '../mutation-types'

const state = {
    installedPlugins: JSON.parse(window.localStorage.getItem('craft.installedPlugins') || '[]')
}

const getters = {
    installedPlugins: (state, rootState) => {
        return rootState.allPlugins.filter(p => {
            return state.installedPlugins.find(pluginId => pluginId == p.id);
        })
    },
}

const mutations = {
    installPlugin(state, { plugin }) {
        const record = state.installedPlugins.find(pluginId => pluginId === plugin.id)

        if (!record) {
            state.installedPlugins.push(plugin.id)
        }
    },
}

export default {
    state,
    getters,
    mutations
}
