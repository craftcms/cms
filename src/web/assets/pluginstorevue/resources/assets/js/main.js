import Vue from 'vue';
import Index from './Index';
import AllPlugins from './AllPlugins';
import CartButton from './CartButton';
import Category from './Category';
import Developer from './Developer';
import VueResource from 'vue-resource';
import lodash from 'lodash'
import VueLodash from 'vue-lodash/dist/vue-lodash.min'
import store from './store'

Vue.use(VueResource);
Vue.use(VueLodash, lodash);

// Vue.component('plugins', require('./Plugins.vue'));

const app = new Vue({
  el: '#container',
  store,
  components: { Index, AllPlugins, CartButton, Category, Developer },
    data() {
      return {
          pageTitle: null,
      }
    },

    methods: {
        updateTitle(newTitle) {
            this.pageTitle = newTitle;
        }
    },

    created() {
      console.log('App created', this.$store);
    }
});
