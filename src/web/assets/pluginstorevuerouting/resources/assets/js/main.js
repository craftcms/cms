import Vue from 'vue';
import App from './App';
import Plugins from './Plugins';
import router from './router';
import VueResource from 'vue-resource';

Vue.use(VueResource);

// Vue.component('plugins', require('./Plugins.vue'));

const app = new Vue({
  el: '#app',
  router,
  // template: '<App/>',
  components: { App, Plugins }
});
