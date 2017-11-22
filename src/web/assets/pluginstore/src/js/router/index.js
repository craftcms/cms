import Vue from 'vue';
import Router from 'vue-router';
import Index from '../Index';
import Category from '../Category';
import UpgradeCraft from '../UpgradeCraft';
import Developer from '../Developer';
import FeaturedPlugins from '../FeaturedPlugins';
import Tests from '../Tests';

Vue.use(Router);

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
    ]
});
