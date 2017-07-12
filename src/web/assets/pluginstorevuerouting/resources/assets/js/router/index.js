import Vue from 'vue'
import Router from 'vue-router'
import Plugins from '../Plugins'
import PluginDetails from '../PluginDetails'
import Modal from '../Modal'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Home',
            component: Plugins
        },
        {
            path: '/plugins',
            name: 'Plugins',
            component: Plugins
        },
        {
            path: '/plugins-details',
            name: 'PluginDetails',
            component: PluginDetails,
            children: [
                { path: 'mod', component: Modal, name: 'mod' }
            ]
        }
    ]
})
