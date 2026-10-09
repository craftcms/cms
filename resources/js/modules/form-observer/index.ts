import {FormObserver} from './form-observer';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.FormObserver(...)` is still
// called from the CP bundle by `Craft.ElementEditor` for draft autosave.
// Plain modern ES class —
// nothing subclasses it via legacy `.extend()`.
registerCraftGlobals({FormObserver});

export {FormObserver};
