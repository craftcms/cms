const getters = {
    isInstalled(state, rootState) {
        return function(plugin) {
            return rootState.installedPlugins.find(p => p.id == plugin.id)
        }
    },
    allPlugins: (state, rootState) => {
        return rootState.pluginStoreData.plugins;
    },

    getPluginById(state, rootState) {
        return id => {
            if(rootState.pluginStoreData.plugins) {
                return rootState.pluginStoreData.plugins.find(p => p.id == id)
            }

            return false;
        };
    },

    getPluginsByIds(state, rootState) {
        return ids => {
            return rootState.pluginStoreData.plugins.filter(p => {
                return ids.find(id => id == p.id)
            })
        };
    },

    getPluginsByCategory(state, rootState) {
        return categoryId => {
            return rootState.pluginStoreData.plugins.filter(p => {
                return p.categories.find(c =>  c == categoryId);
            })
        }
    }
};

export default {
    getters,
}
