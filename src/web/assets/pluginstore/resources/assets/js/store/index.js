import Vue from 'vue'
import Vuex from 'vuex'
import cart from './modules/cart'
import developers from './modules/developers'
import pluginstore from './modules/pluginstore'
import craft from './modules/craft'
import vuexplugins from './vuexplugins'

Vue.use(Vuex);

export default new Vuex.Store({
    plugins: [vuexplugins],
    modules: {
        cart,
        developers,
        pluginstore,
        craft
    },
})
