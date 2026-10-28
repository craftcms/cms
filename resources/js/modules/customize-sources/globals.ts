import {openCustomizeSourcesModal} from './open-customize-sources-modal';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * `Craft.openCustomizeSourcesModal` is how the legacy element index offers
 * Customize Sources: it has no modal of its own anymore, so it opens this one.
 */
registerCraftGlobals({openCustomizeSourcesModal});
