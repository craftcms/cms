import {defineAsyncComponent} from 'vue';

/**
 * The CP's element index, published to plugin bundles through the import map
 * as `@craftcms/cms/elements` (see `Cp::sharedModules()`), so a plugin's own
 * element index page can wrap the same `ElementIndexPage` the CP's pages do.
 *
 * The components load through dynamic imports so Vite's preload helper brings
 * their CSS along with them, the way the CP's own pages get theirs — an entry
 * reached through the import map has no stylesheet links of its own.
 */
export const ElementIndexPage = defineAsyncComponent(
  () => import('./modules/elements/components/ElementIndexPage.vue')
);

export const CpButtonLink = defineAsyncComponent(
  () => import('./common/components/CpButtonLink.vue')
);

export {
  appendIndexQuery,
  type ElementIndexRoute,
  type IndexQueryParams,
} from './modules/elements/composables/useElementIndexVisits';
