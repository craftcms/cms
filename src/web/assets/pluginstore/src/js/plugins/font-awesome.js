import Vue from 'vue'

// Font Awesome
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faCheck, faInfoCircle, faLink, faBook } from '@fortawesome/free-solid-svg-icons'
library.add([ faCheck, faInfoCircle, faLink, faBook ])

Vue.component('font-awesome-icon', FontAwesomeIcon)
Vue.config.productionTip = false