import {IntervalManager} from './interval-manager';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.IntervalManager(...)` is still
// called from the cp bundle by `Craft.ProgressBar`. Plain modern ES class —
// nothing subclasses it via legacy `.extend()`.
registerCraftGlobals({IntervalManager});

export {IntervalManager};
