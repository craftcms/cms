import Vue from 'vue'
import Vuex from 'vuex'
import app from './modules/app'
import cart from './modules/cart'
import pluginStore from './modules/plugin-store'
import craft from './modules/craft'

Vue.use(Vuex)

export default new Vuex.Store({
    strict: true,
    modules: {
        app,
        cart,
        pluginStore,
        craft
    },
})
