import Vue from 'vue'
import Router from 'vue-router'
import Plugins from '../Plugins'
import PluginDetails from '../PluginDetails'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Plugins',
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
            component: PluginDetails
        }
    ]
})
