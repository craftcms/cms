import Vue from 'vue';
import { currency } from './filters/currency';
import { escapeHtml, formatNumber, t } from './filters/craft';
import router from './router';
import store from './store';
import { mapGetters } from 'vuex';
import GlobalModal from './components/GlobalModal';

Vue.filter('currency', currency);
Vue.filter('escapeHtml', escapeHtml);
Vue.filter('formatNumber', formatNumber);
Vue.filter('t', t);

Garnish.$doc.ready(function() {
    Craft.initUiElements();

    window.pluginStoreApp = new Vue({
        el: '#content',
        router,
        store,

        components: {
            GlobalModal
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
              pluginStoreDataLoading: true,
              pluginStoreDataLoaded: false,
              pluginStoreDataError: false,
              craftIdDataLoading: true,
              craftIdDataLoaded: false,
              showModal: false,
              lastOrder: null,
              statusMessage: null,
          }
        },

        computed: {

            ...mapGetters({
                cartPlugins: 'cartPlugins',
                craftIdAccount: 'craftIdAccount',
            }),

        },

        watch: {

            cartPlugins() {
                if(window.enableCraftId) {
                    $('.badge', this.$cartButton).html(this.cartPlugins.length);
                }
            },

            crumbs(crumbs) {
                // Remove existing crumbs
                $('nav', this.$crumbs).remove();

                if(crumbs && crumbs.length > 0) {
                    this.$crumbs.removeClass('empty');

                    // Create new crumbs
                    let crumbsNav = $('<nav></nav>');
                    let crumbsUl = $('<ul></ul>').appendTo(crumbsNav);
                    let crumbsLi = $('<li></li>').appendTo(crumbsUl);

                    // Add crumb items
                    let $this = this;

                    for (let i = 0; i < crumbs.length; i++) {
                        let item = crumbs[i];
                        let link = $('<a href="#" data-path="'+item.path+'">'+item.label+'</a>').appendTo(crumbsLi);

                        link.on('click', (e) => {
                            e.preventDefault();
                            $this.$router.push({ path: item.path })
                        });
                    }

                    crumbsNav.appendTo(this.$crumbs);
                } else {
                    this.$crumbs.removeClass('empty');
                }
            },

            pageTitle(pageTitle) {
                this.$pageTitle.html(pageTitle);
            },

            craftIdAccount() {
                if(this.craftIdAccount) {
                    $('.label', this.$craftIdAccount).html(this.craftIdAccount.username);

                    this.$craftIdAccount.removeClass('hidden');
                    this.$craftIdConnectForm.addClass('hidden');
                    this.$craftIdDisconnectForm.removeClass('hidden');
                } else {
                    this.$craftIdAccount.addClass('hidden');
                    this.$craftIdConnectForm.removeClass('hidden');
                    this.$craftIdDisconnectForm.addClass('hidden');
                }
            }

        },

        methods: {

            displayNotice(message) {
                Craft.cp.displayNotice(message);
            },

            displayError(message) {
                Craft.cp.displayError(message);
            },

            showPlugin(plugin) {
                this.plugin = plugin;
                this.pluginId = plugin.id;
                this.openGlobalModal('plugin-details');
            },

            openGlobalModal(modalStep) {
                this.modalStep = modalStep;

                this.showModal = true;
            },

            closeGlobalModal() {
                this.showModal = false;
            },

            updateCraftId(craftId) {
                this.$store.dispatch('updateCraftId', { craftId });
            },

        },

        created() {
            // Crumbs
            this.$crumbs = $('#crumbs');

            // Page title
            this.$pageTitle = $('#header').find('h1');

            if(this.$pageTitle) {
                this.$pageTitle.html(this.pageTitle)
            }

            // Plugin Store actions
            this.$pluginStoreActions = $('#pluginstore-actions');
            this.$pluginStoreActionsSpinner = $('#pluginstore-actions-spinner');

            // Craft ID account
            this.$craftIdAccount = $('#craftid-account');

            // Connect form
            this.$craftIdConnectForm = $('#craftid-connect-form');

            // Disconnect form
            this.$craftIdDisconnectForm = $('#craftid-disconnect-form');

            // On all data loaded
            this.$on('allDataLoaded', function() {
                if(window.enableCraftId) {
                    this.$pluginStoreActions.removeClass('hidden');
                    this.$pluginStoreActionsSpinner.addClass('hidden');
                }
            }.bind(this));

            // Dispatch actions
            this.$store.dispatch('getCraftData')
                .then(data => {
                    this.craftIdDataLoading = false;
                    this.craftIdDataLoaded = true;
                    this.$emit('craftIdDataLoaded');

                    if(this.pluginStoreDataLoaded) {
                        this.$emit('allDataLoaded');
                    }
                })
                .catch(response => {
                    this.craftIdDataLoading = false;
                    this.craftIdDataLoaded = true;
                    this.$emit('craftIdDataLoaded');
                });

            this.$store.dispatch('getPluginStoreData')
                .then(data => {
                    this.pluginStoreDataLoading = false;
                    this.pluginStoreDataLoaded = true;
                    this.$emit('pluginStoreDataLoaded');

                    if(this.craftIdDataLoaded) {
                        this.$emit('allDataLoaded');
                    } else {
                        if(window.enableCraftId) {
                            this.$pluginStoreActionsSpinner.removeClass('hidden');
                        }
                    }
                })
                .catch(response => {
                    this.pluginStoreDataLoading = false;
                    this.pluginStoreDataError = true;
                    this.statusMessage = this.$options.filters.t('The Plugin Store is not available, please try again later.', 'app');
                });

            this.$store.dispatch('getCartState')
        },

        mounted() {

            this.pageTitle = this.$options.filters.t("Plugin Store", 'app');
            this.statusMessage = this.$options.filters.t("Loading Plugin Store…", 'app');

            let $this = this;

            if(window.enableCraftId) {
                // Cart button
                this.$cartButton = $('#cart-button');

                this.$cartButton.on('click', (e) => {
                    e.preventDefault();
                    $this.openGlobalModal('cart');
                });

                this.$cartButton.keydown(e => {
                    switch(e.which) {
                        case 13: // Enter
                        case 32: // Space
                            e.preventDefault();
                            $this.openGlobalModal('cart');
                            break;

                    }
                });

                // Payment button
                let $paymentButton = $('#payment-button');

                $paymentButton.on('click', (e) => {
                    e.preventDefault();
                    $this.openGlobalModal('payment');
                });

                // Reset cart button
                let $resetCartButton = $('#reset-cart-button');

                $resetCartButton.on('click', (e) => {
                    e.preventDefault();
                    this.$store.dispatch('resetCart');
                });
            }
        },

    });
});