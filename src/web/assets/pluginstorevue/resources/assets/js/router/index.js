import Vue from 'vue'
import Router from 'vue-router'
import Index from '../Index'
import Category from '../Category'
import Craft from '../Craft'
import Developer from '../Developer'
import Tests from '../Tests'

Vue.use(Router)

export default new Router({
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
            path: '/craft',
            name: 'Craft',
            component: Craft,
        },
        {
            path: '/developer/:id',
            name: 'Developer',
            component: Developer,
        },
        {
            path: '/tests',
            name: 'Tests',
            component: Tests,
        },
    ]
})
