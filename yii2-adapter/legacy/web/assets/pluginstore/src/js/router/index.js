import Vue from 'vue';
import VueRouter from 'vue-router';
import Index from '../pages/index';
import CategoriesId from '../pages/categories/_id';
import UpgradeCraft from '../pages/upgrade-craft';
import DeveloperId from '../pages/developer/_id';
import FeaturedHandle from '../pages/featured/_handle';
import BuyPlugin from '../pages/buy/_plugin';
import BuyAllTrials from '../pages/buy-all-trials';
import Tests from '../pages/tests';
import NotFound from '../pages/_not-found';
import Search from '../pages/search';
import PluginsHandle from '../pages/_handle';
import PluginsHandleEditions from '../pages/_handle/editions';
import PluginsHandleReviews from '../pages/_handle/reviews';
import PluginsHandleChangelog from '../pages/_handle/changelog';

Vue.use(VueRouter);

export default new VueRouter({
  base: window.pluginStoreAppBaseUrl,

  mode: 'history',

  scrollBehavior() {
    return {x: 0, y: 0};
  },

  routes: [
    {
      path: '/',
      name: 'Index',
      component: Index,
    },
    {
      path: '/categories/:id',
      name: 'CategoriesId',
      component: CategoriesId,
    },
    {
      path: '/upgrade-craft',
      name: 'UpgradeCraft',
      component: UpgradeCraft,
    },
    {
      path: '/developer/:id',
      name: 'DeveloperId',
      component: DeveloperId,
    },
    {
      path: '/featured/:handle',
      name: 'FeaturedHandle',
      component: FeaturedHandle,
    },
    {
      path: '/buy/:plugin',
      name: 'BuyPlugin',
      component: BuyPlugin,
    },
    {
      path: '/buy/:plugin/:edition',
      name: 'BuyPlugin',
      component: BuyPlugin,
    },
    {
      path: '/buy-all-trials',
      name: 'BuyAllTrials',
      component: BuyAllTrials,
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
      path: '/:handle',
      name: 'PluginsHandle',
      component: PluginsHandle,
    },
    {
      path: '/:handle/reviews',
      name: 'PluginsHandleReviews',
      component: PluginsHandleReviews,
    },
    {
      path: '/:handle/editions',
      name: 'PluginsHandleEditions',
      component: PluginsHandleEditions,
    },
    {
      path: '/:handle/changelog',
      name: 'PluginsHandleChangelog',
      component: PluginsHandleChangelog,
    },
    {
      path: '*',
      name: 'NotFound',
      component: NotFound,
    },
  ],
});
