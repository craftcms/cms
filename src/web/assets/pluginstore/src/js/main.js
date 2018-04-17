import Vue from 'vue'
import {currency} from './filters/currency'
import {escapeHtml, formatNumber, t} from './filters/craft'
import router from './router'
import store from './store'
import {mapState} from 'vuex'

Vue.filter('currency', currency)
Vue.filter('escapeHtml', escapeHtml)
Vue.filter('formatNumber', formatNumber)
Vue.filter('t', t)

Garnish.$doc.ready(function() {
    Craft.initUiElements()

    window.pluginStoreApp = new Vue({
        el: '#content',
        router,
        store,

        components: {
            GlobalModal: require('./components/GlobalModal')
        },

        data() {
            return {
                $crumbs: null,
                $pageTitle: null,
                crumbs: null,
                pageTitle: 'Plugin Store',
                plugin: null,
                pluginId: null,
                modalStep: null,
                pluginStoreDataLoaded: false,
                pluginStoreDataError: false,
                craftIdDataLoaded: false,
                cartDataLoaded: false,
                showModal: false,
                statusMessage: null,
            }
        },

        computed: {

            ...mapState({
                cart: state => state.cart.cart,
                craftId: state => state.craft.craftId,
            }),

        },

        watch: {

            cart() {
                let totalQty = 0

                if (this.cart) {
                    totalQty = this.cart.totalQty
                }

                $('.badge', this.$cartButton).html(totalQty)
            },

            crumbs(crumbs) {
                // Remove existing crumbs
                $('nav', this.$crumbs).remove()

                if (crumbs && crumbs.length > 0) {
                    this.$crumbs.removeClass('empty')

                    // Create new crumbs
                    let crumbsNav = $('<nav></nav>')
                    let crumbsUl = $('<ul></ul>').appendTo(crumbsNav)
                    let crumbsLi = $('<li></li>').appendTo(crumbsUl)

                    // Add crumb items
                    let $this = this

                    for (let i = 0; i < crumbs.length; i++) {
                        let item = crumbs[i]
                        let link = $('<a href="#" data-path="' + item.path + '">' + item.label + '</a>').appendTo(crumbsLi)

                        link.on('click', (e) => {
                            e.preventDefault()
                            $this.$router.push({path: item.path})
                        })
                    }

                    crumbsNav.appendTo(this.$crumbs)
                } else {
                    this.$crumbs.addClass('empty')
                }
            },

            pageTitle(pageTitle) {
                this.$pageTitle.html(pageTitle)
            },

            craftId() {
                if (this.craftId) {
                    $('.label', this.$craftId).html(this.craftId.username)

                    this.$craftId.removeClass('hidden')
                    this.$craftIdConnectForm.addClass('hidden')
                    this.$craftIdDisconnectForm.removeClass('hidden')
                } else {
                    this.$craftId.addClass('hidden')
                    this.$craftIdConnectForm.removeClass('hidden')
                    this.$craftIdDisconnectForm.addClass('hidden')
                }
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
                this.openGlobalModal('plugin-details')
            },

            openGlobalModal(modalStep) {
                this.modalStep = modalStep

                this.showModal = true
            },

            closeGlobalModal() {
                this.showModal = false
            },

            updateCraftId(craftId) {
                this.$store.dispatch('updateCraftId', {craftId})
                this.$emit('craftIdUpdated')
            },

        },

        created() {
            // Crumbs
            this.$crumbs = $('#crumbs')

            // Page title
            this.$pageTitle = $('#header').find('h1')

            if (this.$pageTitle) {
                this.$pageTitle.html(this.pageTitle)
            }

            // Plugin Store actions
            this.$pluginStoreActions = $('#pluginstore-actions')
            this.$pluginStoreActionsSpinner = $('#pluginstore-actions-spinner')

            // Craft ID account
            this.$craftId = $('#craftid-account')

            // Connect form
            this.$craftIdConnectForm = $('#craftid-connect-form')

            // Disconnect form
            this.$craftIdDisconnectForm = $('#craftid-disconnect-form')

            // On data loaded
            this.$on('dataLoaded', function() {
                if (this.pluginStoreDataLoaded && (!this.craftIdDataLoaded || !this.cartDataLoaded)) {
                    this.$pluginStoreActionsSpinner.removeClass('hidden')
                }

                if (this.pluginStoreDataLoaded && this.craftIdDataLoaded && this.cartDataLoaded) {
                    // All data loaded
                    this.$pluginStoreActions.removeClass('hidden')
                    this.$pluginStoreActionsSpinner.addClass('hidden')
                    this.$emit('allDataLoaded')
                }
            }.bind(this))

            // Load Plugin Store data
            this.$store.dispatch('getPluginStoreData')
                .then(response => {
                    this.pluginStoreDataLoaded = true
                    this.$emit('dataLoaded')
                })
                .catch(response => {
                    this.pluginStoreDataError = true
                    this.statusMessage = this.$options.filters.t('The Plugin Store is not available, please try again later.', 'app')
                })

            // Load Craft data
            this.$store.dispatch('getCraftData')
                .then(data => {
                    this.craftIdDataLoaded = true
                    this.$emit('dataLoaded')

                    // Load cart
                    this.$store.dispatch('getCart')
                        .then(() => {
                            this.cartDataLoaded = true
                            this.$emit('dataLoaded')
                        })
                })
                .catch(response => {
                    this.craftIdDataLoaded = true
                })
        },

        mounted() {
            this.pageTitle = this.$options.filters.t("Plugin Store", 'app')
            this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app')

            let $this = this

            // Cart button
            this.$cartButton = $('#cart-button')

            this.$cartButton.on('click', (e) => {
                e.preventDefault()
                $this.openGlobalModal('cart')
            })

            this.$cartButton.keydown(e => {
                switch (e.which) {
                    case 13: // Enter
                    case 32: // Space
                        e.preventDefault()
                        $this.openGlobalModal('cart')
                        break

                }
            })
        },

    })
})