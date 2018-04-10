import Vue from 'vue'
import Vuex from 'vuex'
import cart from './modules/cart'
import developers from './modules/developers'
import pluginstore from './modules/pluginstore'
import craft from './modules/craft'

Vue.use(Vuex)

export default new Vuex.Store({
    modules: {
        cart,
        developers,
        pluginstore,
        craft
    },
})
