import {IntervalManager} from './interval-manager';

// Assign the legacy `Craft` global: `new Craft.IntervalManager(...)` is still
// called from the cp bundle by `Craft.ProgressBar`. Plain modern ES class —
// nothing subclasses it via legacy `.extend()`.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.IntervalManager = IntervalManager;

export {IntervalManager};
