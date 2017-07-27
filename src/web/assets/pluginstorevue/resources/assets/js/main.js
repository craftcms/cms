import Vue from 'vue';
import VueResource from 'vue-resource';
import lodash from 'lodash'
import VueLodash from 'vue-lodash/dist/vue-lodash.min'

import App from './App';
import CartButton from './components/CartButton';
import { currency } from './filters/currency';
import { t } from './filters/t';
import router from './router';
import store from './store'

Vue.use(VueResource);
Vue.use(VueLodash, lodash);
Vue.filter('currency', currency)
Vue.filter('t', t)

const app = new Vue({
    el: '#main',
    router,
    store,
    components: { App, CartButton },
    data() {
      return {
          $crumbs: null,
          $pageTitle: null,
          showCrumbs: false,
          pageTitle: 'Plugin Store',
      }
    },

    methods: {
        displayNotice(message) {
            this.displayNotice(message);
        },
        displayError(message) {
            this.displayError(message);
        }
    },

    watch: {
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
    }
});
