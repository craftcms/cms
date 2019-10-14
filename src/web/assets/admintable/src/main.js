/* global Craft */
/* global Garnish */

import Vue from 'vue'
import {t} from '../../pluginstore/src/js/filters/craft'
// import {mapState} from 'vuex'
import store from './js/store/adminTable'
import App from './App'
import AdminTablePagination from './js/components/AdminTablePagination'

Vue.filter('t', t)

Garnish.$doc.ready(function() {
    Craft.initUiElements()
    const ADMIN_TABLE = document.querySelector('#admin-table');
    const ADMIN_TABLE_PAGINATION = document.querySelector('#admin-table-pagination');

    window.adminTableApp = new Vue({
        render: createElement => {
            let context = {
                props: {...ADMIN_TABLE.dataset}
            };

            return createElement(App, context);
        },
        store,

        components: {
            App,
        },

        data() {
            return {

            }
        },

        computed: {

        },

        watch: {

        },

        methods: {

        }
    }).$mount('#admin-table')

    window.adminTablePagination = new Vue({
        render: createElement => {
            let context = {
                props: {...ADMIN_TABLE_PAGINATION.dataset}
            };

            return createElement(AdminTablePagination, context);
        },
        store,

        components: {
            AdminTablePagination,
        },

        data() {
            return {

            }
        },

        computed: {

        },

        watch: {

        },

        methods: {

        }
    }).$mount('#admin-table-pagination')
})
