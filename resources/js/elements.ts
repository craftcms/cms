import {defineAsyncComponent} from 'vue';

/**
 * The CP's element index and editor, and the pieces their pages build
 * toolbars from, published to plugin bundles through the import map as
 * `@craftcms/cms/elements` (see `Cp::sharedModules()`), so a plugin's own
 * element pages can wrap the same `ElementIndexPage` and `ElementEditor` the
 * CP's pages do.
 *
 * The components load through dynamic imports so Vite's preload helper brings
 * their CSS along with them, the way the CP's own pages get theirs — an entry
 * reached through the import map has no stylesheet links of its own.
 */
export const ElementIndexPage = defineAsyncComponent(
  () => import('./modules/elements/index/components/ElementIndexPage.vue')
);

export const ElementEditor = defineAsyncComponent(
  () => import('./modules/elements/components/ElementEditor.vue')
);

export const CpButtonLink = defineAsyncComponent(
  () => import('./common/components/CpButtonLink.vue')
);

export const ActionMenu = defineAsyncComponent(
  () => import('./common/components/ActionMenu.vue')
);

export type {ActionItem, ActionItemLink} from './common/types';

/**
 * Lets a plugin's page configure the control panel shell around it — its
 * secondary nav, most usefully, so an index can put its own sources there.
 */
export {useAppLayout} from './common/composables/useAppLayout';

export {
  useCustomizeSources,
  type CustomizeSourcesTarget,
} from './modules/elements/index/composables/useCustomizeSources';

export {
  appendIndexQuery,
  type ElementIndexRoute,
  type IndexQueryParams,
} from './modules/elements/index/composables/useElementIndexVisits';
