import Vue from 'vue'
import Vuex from 'vuex'
import cart from './modules/cart'
import pluginStore from './modules/pluginStore'
import craft from './modules/craft'

Vue.use(Vuex)

export default new Vuex.Store({
    strict: true,
    modules: {
        cart,
        pluginStore,
        craft
    },
})
