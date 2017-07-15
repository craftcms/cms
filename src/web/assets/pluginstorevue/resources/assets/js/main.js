import Vue from 'vue';
import App from './App';
import Index from './Index';
import AllPlugins from './AllPlugins';
import CartButton from './CartButton';
import Category from './Category';
import Developer from './Developer';
import VueResource from 'vue-resource';
import lodash from 'lodash'
import VueLodash from 'vue-lodash/dist/vue-lodash.min'
import store from './store'

import router from './router';

Vue.use(VueResource);
Vue.use(VueLodash, lodash);

const app = new Vue({
    el: '#container',
    router,
    store,
    components: { App, Index, AllPlugins, CartButton, Category, Developer },
    data() {
      return {
          showCrumbs: false,
          pageTitle: null,
      }
    },

    methods: {
        updateTitle(newTitle) {
            console.log('updateTitle');
            this.pageTitle = newTitle;
        }
    },

    created() {
        this.$store.dispatch('getAllProducts')
        this.$store.dispatch('getAllCategories')
    }
});
