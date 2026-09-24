import {registerCraftGlobals} from '@/common/craft-global';
import {openCustomizeSourcesModal} from './open-customize-sources-modal';

/**
 * `Craft.openCustomizeSourcesModal` is how the legacy element index offers
 * Customize Sources: it has no modal of its own anymore, so it opens this one.
 */
registerCraftGlobals({openCustomizeSourcesModal});

export {openCustomizeSourcesModal} from './open-customize-sources-modal';
export type {CustomizeSourcesModalOptions} from './open-customize-sources-modal';
