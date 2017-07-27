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
    el: '#container',
    router,
    store,
    components: { App, CartButton },
    data() {
      return {
          showCrumbs: false,
          pageTitle: null,
      }
    },

    methods: {
        displayNotification(type, message) {
            var $notificationContainer = $('#notifications');
            var notificationDuration = Craft.CP.notificationDuration;

            if (type == 'error') {
                notificationDuration *= 2;
            }

            var $notification = $('<div class="notification ' + type + '">' + message + '</div>')
                .appendTo($notificationContainer);

            var fadedMargin = -($notification.outerWidth() / 2) + 'px';

            $notification
                .hide()
                .css({opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin})
                .velocity({opacity: 1, 'margin-left': '2px', 'margin-right': '2px'}, {display: 'inline-block', duration: 'fast'})
                .delay(notificationDuration)
                .velocity({opacity: 0, 'margin-left': fadedMargin, 'margin-right': fadedMargin}, {
                    complete: function() {
                        $notification.remove();
                    }
                });
        },
        displayNotice(message) {
            this.displayNotification('notice', message);
        },
        displayError(message) {
            this.displayNotification('error', message);
        }
    },

    created() {
        this.$store.dispatch('getAllPlugins')
        this.$store.dispatch('getAllCategories')
        this.$store.dispatch('getStaffPicks')
        this.$store.dispatch('getCartState')
    }
});
