import {QueueService} from './queue';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * Legacy and plugin code reaches the queue through the `Craft` global rather
 * than an ES module import, so expose the service class there
 * (`Craft.QueueService.getInstance()`).
 *
 * Note that the legacy webpack bundle's `CP.js` cannot use this global — it
 * needs the service while its own bundle is still evaluating, before Vite's
 * module scripts have run — so it imports `./queue` directly and bundles its
 * own copy.
 */
registerCraftGlobals({QueueService});

export {QueueService};
export * from './types';
