import Vue from 'vue'
import VueRouter from 'vue-router'
import Index from '../pages/Index'
import Category from '../pages/Category'
import UpgradeCraft from '../pages/UpgradeCraft'
import Developer from '../pages/Developer'
import FeaturedPlugins from '../pages/FeaturedPlugins'
import Tests from '../pages/Tests'
import NotFound from '../pages/NotFound'

Vue.use(VueRouter)

export default new VueRouter({
    base: window.pluginStoreAppBaseUrl,
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'Index',
            component: Index,
        },
        {
            path: '/categories/:id',
            name: 'Category',
            component: Category,
        },
        {
            path: '/upgrade-craft',
            name: 'UpgradeCraft',
            component: UpgradeCraft,
        },
        {
            path: '/developer/:id',
            name: 'Developer',
            component: Developer,
        },
        {
            path: '/featured/:id',
            name: 'FeaturedPlugins',
            component: FeaturedPlugins,
        },
        {
            path: '/tests',
            name: 'Tests',
            component: Tests,
        },
        {
            path: '*',
            name: 'NotFound',
            component: NotFound,
        },
    ]
})
