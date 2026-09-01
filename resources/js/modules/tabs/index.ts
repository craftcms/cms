import {Tabs} from './tabs';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.Tabs(...)` is created by CP,
// Preview, cp-screen-slideout, and matrix-entry. Plain modern ES class —
// nothing subclasses it via the legacy `.extend()` API.
registerCraftGlobals({Tabs});

export {Tabs};
