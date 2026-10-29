import {openCustomizeSourcesModal} from './open-customize-sources-modal';
import {registerCraftGlobals} from '@/common/craft-global';

// The legacy element index opens Customize Sources through this global.
registerCraftGlobals({openCustomizeSourcesModal});
