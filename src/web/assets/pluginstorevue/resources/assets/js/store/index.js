import Vue from 'vue'
import Vuex from 'vuex'
import * as actions from './actions'
import * as getters from './getters'
import activeTrials from './modules/activeTrials'
import cart from './modules/cart'
import products from './modules/products'
import categories from './modules/categories'
import developers from './modules/developers'

Vue.use(Vuex);


export default new Vuex.Store({
    actions,
    getters,
    modules: {
        activeTrials,
        cart,
        products,
        categories,
        developers,
    },
})