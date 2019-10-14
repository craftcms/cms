import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
    state: {
        currentPage: null,
        page: null,
        pagination: null,
        table: null
    },
    actions: {
        updatePage(context, page) {
            this.state.page = page
            this.state.table.changePage(page)
            this.state.currentPage = this.state.table.currentPage
        }
    },
    mutations: {
        updatePagination(state, data) {
            state.pagination = data
        },

        updateTable(state, data) {
            state.table = data
            state.currentPage = state.table.currentPage
        }
    }
})