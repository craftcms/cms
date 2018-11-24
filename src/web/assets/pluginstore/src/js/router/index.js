import Vue from 'vue'
import VueRouter from 'vue-router'
import Index from '../pages/index'
import Category from '../pages/categories/_id'
import UpgradeCraft from '../pages/upgrade-craft'
import Developer from '../pages/developer/_id'
import FeaturedPlugins from '../pages/featured/_id'
import BuyPluginHandle from '../pages/buy/_handle'
import Tests from '../pages/tests'
import NotFound from '../pages/_not-found'
import Search from '../pages/search/index'
import PluginsHandle from '../pages/plugins/_handle'

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
            path: '/buy/:handle',
            name: 'BuyPluginHandle',
            component: BuyPluginHandle,
        },
        {
            path: '/plugins/:handle',
            name: 'PluginsHandle',
            component: PluginsHandle,
        },
        {
            path: '/search',
            name: 'Search',
            component: Search,
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
