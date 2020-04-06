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

        components: {
            Modal,
            StatusMessage,
            App,
        },

        data() {
            return {
                allDataLoaded: false,
                cartDataLoaded: false,
                coreDataLoaded: false,
                craftDataLoaded: false,
                craftIdDataLoaded: false,
                modalStep: null,
                pageTitle: 'Plugin Store',
                plugin: null,
                pluginId: null,
                pluginLicenseInfoLoaded: false,
                pluginStoreDataError: false,
                showModal: false,
                statusMessage: null,
            }
        },

        computed: {
            ...mapState({
                cart: state => state.cart.cart,
                craftId: state => state.craft.craftId,
            }),

            /**
             * Returns `true``if the core data and the plugin license info have been loaded.
             *
             * @returns {boolean}
             */
            pluginStoreDataLoaded() {
                return this.coreDataLoaded && this.pluginLicenseInfoLoaded
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
            /**
             * Displays a notice.
             *
             * @param message
             */
            displayNotice(message) {
                Craft.cp.displayNotice(message)
            },

            /**
             * Displays an error.
             *
             * @param message
             */
            displayError(message) {
                Craft.cp.displayError(message)
            },

            /**
             * Opens up the modal.
             *
             * @param modalStep
             */
            openModal(modalStep) {
                this.modalStep = modalStep

                this.showModal = true
            },

            /**
             * Closes the modal.
             */
            closeModal() {
                this.showModal = false
            },

            /**
             * Updates Craft ID.
             *
             * @param craftIdJson
             */
            updateCraftId(craftId, callback) {
                this.$store.commit('craft/updateCraftId', craftId)

                if (this.craftId && this.craftId.email !== this.cart.email) {
                    // Update the cart’s email with the one from the Craft ID account
                    let data = {
                        email: this.craftId.email,
                    }

                    this.$store.dispatch('cart/saveCart', data)
                        .then(() => {
                            this.$emit('craftIdUpdated')

                            if (callback) {
                                callback()
                            }
                        })
                        .catch((error) => {
                            this.$root.displayError("Couldn’t update cart’s email.")

                            if (callback) {
                                callback()
                            }

                            throw error
                        })
                } else {
                    this.$emit('craftIdUpdated')

                    if (callback) {
                        callback()
                    }
                }
            },

            /**
             * Initializes components that live outside of the Vue app.
             */
            initializeOuterComponents() {
                // Header Title
                const $headerTitle = $('#header h1')

                $headerTitle.on('click', function() {
                    this.$router.push({path: '/'})
                }.bind(this))

                // Cart button
                const $cartButton = $('#cart-button')

                $cartButton.on('click', function(e) {
                    e.preventDefault()
                    this.openModal('cart')
                }.bind(this))

                $cartButton.keydown(function(e) {
                    switch (e.which) {
                        case 13: // Enter
                        case 32: // Space
                            e.preventDefault()
                            this.openModal('cart')
                            break
                    }
                }.bind(this))

                this.$on('cartChange', function (cart) {
                    let totalQty = 0

                    if (cart) {
                        totalQty = cart.totalQty
                    }

                    $('.badge', $cartButton).html(totalQty)
                })

                // Plugin Store actions
                const $pluginStoreActions = $('#pluginstore-actions')
                const $pluginStoreActionsSpinner = $('#pluginstore-actions-spinner')

                // Show actions spinner when Plugin Store data has finished loading but Craft data has not.
                this.$on('dataLoaded', function() {
                    if (this.pluginStoreDataLoaded && !(this.craftDataLoaded && this.cartDataLoaded && this.craftIdDataLoaded)) {
                        $pluginStoreActionsSpinner.removeClass('hidden')
                    }
                }.bind(this))

                // Hide actions spinner when Plugin Store data and Craft data have finished loading.
                this.$on('allDataLoaded', function() {
                    $pluginStoreActions.removeClass('hidden')
                    $pluginStoreActionsSpinner.addClass('hidden')
                })

                // Craft ID
                const $craftId = $('#craftid-account')
                const $craftIdConnectForm = $('#craftid-connect-form')
                const $craftIdDisconnectForm = $('#craftid-disconnect-form')

                this.$on('craftIdChange', function() {
                    if (this.craftId) {
                        $('.label', $craftId).html(this.craftId.username)

                        $craftId.removeClass('hidden')
                        $craftIdConnectForm.addClass('hidden')
                        $craftIdDisconnectForm.removeClass('hidden')
                    } else {
                        $craftId.addClass('hidden')
                        $craftIdConnectForm.removeClass('hidden')
                        $craftIdDisconnectForm.addClass('hidden')
                    }
                })

                // Cancel ajax requests when an outbound link gets clicked
                $('a[href]').on('click', function() {
                    this.$store.dispatch('craft/cancelRequests')
                    this.$store.dispatch('pluginStore/cancelRequests')
                }.bind(this))
            },

            /**
             * Loads the cart data.
             */
            loadCartData() {
                this.$store.dispatch('cart/getCart')
                    .then(() => {
                        this.cartDataLoaded = true
                        this.$emit('dataLoaded')
                    })
            },

            /**
             * Loads Craft data.
             */
            loadCraftData(afterSuccess) {
                this.$store.dispatch('craft/getCraftData')
                    .then(() => {
                        this.craftDataLoaded = true
                        this.$emit('dataLoaded')

                        if (typeof afterSuccess === 'function') {
                            afterSuccess()
                        }
                    })
                    .catch(() => {
                        this.craftDataLoaded = true
                    })
            },

            loadCraftIdData() {
                if (window.craftIdAccessToken) {
                    const accessToken = window.craftIdAccessToken

                    this.$store.dispatch('craft/getCraftIdData', {accessToken})
                        .then(() => {
                            this.craftIdDataLoaded = true
                            this.$emit('dataLoaded')
                        })
                } else {
                    this.craftIdDataLoaded = true
                    this.$emit('dataLoaded')
                }
            },

            /**
             * Loads all the data required for the Plugin Store and cart to work.
             */
            loadData() {
                this.loadPluginStoreData()

                this.loadCraftData(function() {
                    this.loadCraftIdData()
                    this.loadCartData()
                }.bind(this))
            },

            /**
             * Loads the Plugin Store’s plugin data.
             */
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
                            throw error
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

            /**
             * Checks that all the data has been loaded.
             *
             * @returns {null}
             */
            onDataLoaded() {
                if (!this.pluginStoreDataLoaded) {
                    return null
                }

                if (!this.craftDataLoaded) {
                    return null
                }

                if (!this.cartDataLoaded) {
                    return null
                }

                if (!this.craftIdDataLoaded) {
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
