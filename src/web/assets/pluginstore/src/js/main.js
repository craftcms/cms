import Vue from 'vue';
import { currency } from './filters/currency';
import { t } from './filters/t';
import { escapeHtml } from './filters/escapeHtml';
import router from './router';
import store from './store';
import { mapGetters } from 'vuex';
import GlobalModal from './components/GlobalModal';

Vue.filter('currency', currency);
Vue.filter('t', t);
Vue.filter('escapeHtml', escapeHtml);

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
          statusMessage: 'Loading Plugin Store…'
      }
    },

    computed: {

        ...mapGetters({
            cartPlugins: 'cartPlugins',
        }),

    },

    watch: {

        cartPlugins() {
            if(window.enableCraftId) {
                this.$cartButton.html('Cart (' + this.cartPlugins.length + ')');
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
            let $accountInfoMenu = $('#account-info').data('menubtn').menu.$container;

            if(craftId) {
                $('.craftid-connected').removeClass('hidden');
                $('.craftid-disconnected').addClass('hidden');
                $('.craftid-connected', $accountInfoMenu).removeClass('hidden');
                $('.craftid-disconnected', $accountInfoMenu).addClass('hidden');
            } else {
                $('.craftid-connected').addClass('hidden');
                $('.craftid-disconnected').removeClass('hidden');
                $('.craftid-connected', $accountInfoMenu).addClass('hidden');
                $('.craftid-disconnected', $accountInfoMenu).removeClass('hidden');
            }

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

        // Dispatch actions
        this.$store.dispatch('getCraftData')
            .then(data => {
                this.craftIdDataLoading = false;
                this.craftIdDataLoaded = true;
                this.$emit('craftIdDataLoaded');
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
            })
            .catch(response => {
                this.pluginStoreDataLoading = false;
                this.pluginStoreDataError = true;
                this.statusMessage = 'The Plugin Store is not available, please try again later.';
            });

        this.$store.dispatch('getCartState')
    },

    mounted() {
        let $this = this;

        if(window.enableCraftId) {
            // Cart Button
            this.$cartButton = $('#cart-button');

            this.$cartButton.on('click', (e) => {
                e.preventDefault();
                $this.openGlobalModal('cart');
            });

            // Payment button
            let $paymentButton = $('#payment-button');

            $paymentButton.on('click', (e) => {
                e.preventDefault();
                $this.openGlobalModal('payment');
            });

            // reset-cart-button
            let $resetCartButton = $('#reset-cart-button');

            $resetCartButton.on('click', (e) => {
                e.preventDefault();
                this.$store.dispatch('resetCart');
            });
        }
    },

});
