import Vue from 'vue';
import VueResource from 'vue-resource';
import lodash from 'lodash'
import VueLodash from 'vue-lodash/dist/vue-lodash.min'

import App from './App';
import Cart from './components/Cart';
import PluginDetails from './components/PluginDetails';
import { currency } from './filters/currency';
import { t } from './filters/t';
import router from './router';
import store from './store'
import { mapGetters } from 'vuex'

Vue.use(VueResource);
Vue.use(VueLodash, lodash);
Vue.filter('currency', currency)
Vue.filter('t', t)

const app = new Vue({
    el: '#main',
    router,
    store,
    components: { App, Cart, PluginDetails },
    data() {
      return {
          $crumbs: null,
          $pageTitle: null,
          showCrumbs: false,
          pageTitle: 'Plugin Store',
          modal: null,
          plugin: null,
          modalStep: null,
          loading: true,
      }
    },

    computed: {
        ...mapGetters({
            cartPlugins: 'cartPlugins',
        }),
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
            this.openGlobalModal('plugin-details');
        },
        openGlobalModal(modalStep) {
            this.modalStep = modalStep;

            if(!this.modal.visible) {
                this.modal.show();
            }
        },
        closeGlobalModal() {
            this.modal.hide();
        },
    },

    watch: {
        cartPlugins() {
            this.$cartButton.html('Cart ('+this.cartPlugins.length+')');
        },
        showCrumbs(showCrumbs) {
            if(showCrumbs) {
                this.$crumbs.removeClass('hidden');
            } else {
                this.$crumbs.addClass('hidden');
            }
        },
        pageTitle(pageTitle) {
            this.$pageTitle.html(pageTitle);
        }
    },

    created() {
        // Crumbs

        this.$crumbs = $('#crumbs');

        if(!this.showCrumbs) {
            this.$crumbs.addClass('hidden')
        }

        let $a = $('a', this.$crumbs);
        let $this = this;

        $a.on('click', (e) => {
            e.preventDefault();
            $this.$router.push({ path: '/'})
        });


        // Page title

        this.$pageTitle = $('#page-title h1')
        this.$pageTitle.html(this.pageTitle)


        // Dispatch

        this.$store.dispatch('getPluginStoreData')
        this.$store.dispatch('getAllPlugins').then(() => {
            this.loading = false
        })
        this.$store.dispatch('getCartState')
    },

    mounted() {
        this.modal = new Garnish.Modal(this.$refs.globalmodal, {
            autoShow: false,
            resizable: true,
            onHide() {
                // $this.$emit('update:showModal', false);
            }
        });


        // Cart Button

        let $this = this;

        this.$cartButton = $('#cart-button')

        this.$cartButton.on('click', (e) => {
            e.preventDefault();
            $this.openGlobalModal('cart');
        });
    },
});
