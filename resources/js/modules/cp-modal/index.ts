import {CpModal} from './cp-modal';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.CpModal(...)` is emitted by the
// PHP deletion blockers. Plain modern ES class — nothing subclasses it via the
// legacy `.extend()` API.
registerCraftGlobals({CpModal});

export {CpModal};
