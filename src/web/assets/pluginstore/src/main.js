/* global Craft */
/* global Garnish */
/* global $ */

import Vue from 'vue'
import axios from 'axios'
import {currency} from './js/filters/currency'
import {escapeHtml, formatDate, formatNumber, t} from './js/filters/craft'
import router from './js/router'
import store from './js/store'
import {mapState} from 'vuex'
import Modal from './js/components/modal/Modal'
import StatusMessage from './js/components/StatusMessage'
import App from './App'
import './js/plugins/craftui'
import './js/plugins/vue-awesome-swiper'
import outerComponents from './js/mixins/outerComponents';

Vue.filter('currency', currency)
Vue.filter('escapeHtml', escapeHtml)
Vue.filter('formatDate', formatDate)
Vue.filter('formatNumber', formatNumber)
Vue.filter('t', t)

Garnish.$doc.ready(function() {
    Craft.initUiElements()

    window.pluginStoreApp = new Vue({
        router,
        store,
        render: h => h(App),

        mixins: [outerComponents],

        components: {
            Modal,
            StatusMessage,
            App,
        },

        data() {
            return {
                pageTitle: 'Plugin Store',
                plugin: null,
                pluginId: null,
                modalStep: null,
                coreDataLoaded: false,
                pluginStoreDataError: false,
                craftIdDataLoaded: false,
                pluginLicenseInfoLoaded: false,
                cartDataLoaded: false,
                allDataLoaded: false,
                showModal: false,
                statusMessage: null,
            }
        },

        computed: {
            ...mapState({
                cart: state => state.cart.cart,
                craftId: state => state.craft.craftId,
            }),

            pluginStoreDataLoaded() {
                return this.coreDataLoaded && this.pluginLicenseInfoLoaded
            },

            craftDataLoaded() {
                return this.craftIdDataLoaded && this.cartDataLoaded
            },
        },

        watch: {
            cart(cart) {
                this.$emit('cartChange', cart)
            },

            craftId() {
                this.$emit('craftIdChange')
            }
        },

        methods: {
            displayNotice(message) {
                Craft.cp.displayNotice(message)
            },

            displayError(message) {
                Craft.cp.displayError(message)
            },

            showPlugin(plugin) {
                this.plugin = plugin
                this.pluginId = plugin.id
                this.openModal('plugin-details')
            },

            openModal(modalStep) {
                this.modalStep = modalStep

                this.showModal = true
            },

            closeModal() {
                this.showModal = false
            },

            updateCraftId(craftIdJson) {
                const craftId = JSON.parse(craftIdJson)
                this.$store.commit('craft/updateCraftId', craftId)
                this.$store.commit('craft')
                this.$emit('craftIdUpdated')
            },

            loadCartData() {
                this.$store.dispatch('cart/getCart')
                    .then(() => {
                        this.cartDataLoaded = true
                        this.$emit('dataLoaded')
                    })
            },

            loadCraftData() {
                this.$store.dispatch('craft/getCraftData')
                    .then(() => {
                        this.craftIdDataLoaded = true
                        this.$emit('dataLoaded')
                    })
                    .catch(() => {
                        this.craftIdDataLoaded = true
                    })
            },

            loadData() {
                this.loadPluginStoreData()
                this.loadCraftData()
                this.loadCartData()
            },

            loadPluginStoreData() {
                // core data
                this.$store.dispatch('pluginStore/getCoreData')
                    .then(() => {
                        this.coreDataLoaded = true
                        this.$emit('dataLoaded')
                    })
                    .catch((error) => {
                        if (axios.isCancel(error)) {
                            // Request canceled
                        } else {
                            this.pluginStoreDataError = true
                            this.statusMessage = this.$options.filters.t('The Plugin Store is not available, please try again later.', 'app')
                        }
                    })

                // plugin license info
                this.$store.dispatch('craft/getPluginLicenseInfo')
                    .then(() => {
                        this.pluginLicenseInfoLoaded = true
                        this.$emit('dataLoaded')
                    })
                    .catch((error) => {
                        if (axios.isCancel(error)) {
                            // Request canceled
                        } else {
                            throw error
                        }
                    })
            },

            onDataLoaded() {
                if (!this.pluginStoreDataLoaded) {
                    return null
                }

                if (!this.craftDataLoaded) {
                    return null
                }

                this.allDataLoaded = true
                this.$emit('allDataLoaded')
            },
        },

        created() {
            // Page Title
            this.pageTitle = this.$options.filters.t("Plugin Store", 'app')

            // Status message
            this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app')

            // Initialize outer components
            this.initializeOuterComponents()

            // On data loaded
            this.$on('dataLoaded', this.onDataLoaded)

            // Load data
            this.loadData()
        },
    }).$mount('#app')
})
