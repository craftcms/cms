import Vue from 'vue'
import Router from 'vue-router'
import Index from '../Index'
import Category from '../Category'
import Developer from '../Developer'
import StaffPicks from '../StaffPicks'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Index',
            component: Index,
        },
        {
            path: '/staff-picks',
            name: 'StaffPicks',
            component: StaffPicks,
        },
        {
            path: '/categories/:id',
            name: 'Category',
            component: Category,
        },
        {
            path: '/developer/:id',
            name: 'Developer',
            component: Developer,
        },
    ]
})
