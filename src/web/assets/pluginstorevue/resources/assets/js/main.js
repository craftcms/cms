import Vue from 'vue';
import VueResource from 'vue-resource';
import lodash from 'lodash'
import VueLodash from 'vue-lodash/dist/vue-lodash.min'

import App from './App';
import Index from './Index';
import Category from './Category';
import Developer from './Developer';
import AllPlugins from './components/AllPlugins';
import CartButton from './components/CartButton';
import router from './router';
import store from './store'

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

    created() {
        this.$store.dispatch('getAllProducts')
        this.$store.dispatch('getAllCategories')
    }
});
