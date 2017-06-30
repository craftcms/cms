import Vue from 'vue';
import Index from './Index';
import VueResource from 'vue-resource';
import lodash from 'lodash'
import VueLodash from 'vue-lodash/dist/vue-lodash.min'

Vue.use(VueResource);
Vue.use(VueLodash, lodash);

// Vue.component('plugins', require('./Plugins.vue'));

const app = new Vue({
  el: '#content',
  components: { Index }
});
