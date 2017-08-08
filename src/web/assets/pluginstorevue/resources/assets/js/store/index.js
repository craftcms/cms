import Vue from 'vue'
import Vuex from 'vuex'
import * as actions from './actions'
import * as getters from './getters'
import cart from './modules/cart'
import plugins from './modules/plugins'
import categories from './modules/categories'
import developers from './modules/developers'
import pluginstore from './modules/pluginstore'
import craft from './modules/craft'
import vuexplugins from './vuexplugins'

Vue.use(Vuex);

export default new Vuex.Store({
    actions,
    getters,

    plugins: [vuexplugins],
    modules: {
        cart,
        plugins,
        categories,
        developers,
        pluginstore,
        craft
    },
})
