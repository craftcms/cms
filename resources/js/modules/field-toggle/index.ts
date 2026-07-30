import {FieldToggle} from './field-toggle';
import {fieldToggleData} from './support';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global so the still-legacy `$.fn.fieldtoggle` jQuery
// plugin and the `Craft.initUiElements` `.fieldtoggle` sweep (both in the cp
// bundle), plus `new Craft.FieldToggle(...)` in UI.js / LinkField /
// ElementDeletionManager, keep working. Plain modern ES class — nothing
// subclasses it via legacy `.extend()`.
registerCraftGlobals({FieldToggle});

export {FieldToggle, fieldToggleData};
