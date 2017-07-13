import Vue from 'vue'
import Router from 'vue-router'
import Index from '../Index'
import CartModal from '../CartModal'
import Category from '../Category'
import Developer from '../Developer'
import PluginModal from '../PluginModal'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Index',
            component: Index,
            children: [
                {
                    path: 'cart',
                    name: 'CartModal',
                    component: CartModal,
                },
                {
                    path: 'plugins/:pluginId',
                    name: 'PluginModal',
                    component: PluginModal,
                },
            ],
        },
        {
            path: '/categories/:id',
            name: 'Category',
            component: Category,
            children: [
                {
                    path: ':pluginId',
                    name: 'PluginModal',
                    component: PluginModal,
                },
            ]
        },
        {
            path: '/developer/:id',
            name: 'Developer',
            component: Developer,
            children: [
                {
                    path: ':pluginId',
                    name: 'PluginModal',
                    component: PluginModal,
                },
            ]
        },
    ]
})
