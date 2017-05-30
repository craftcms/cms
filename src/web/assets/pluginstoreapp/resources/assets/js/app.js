import Vue from 'vue';
import Home from './Home';
import router from './router'
import VueResource from 'vue-resource';

Vue.use(VueResource);

// Vue.component('plugins', require('./Plugins.vue'));

const app = new Vue({
  el: '#app',
  router,
  template: '<Home/>',
  components: { Home }
});
