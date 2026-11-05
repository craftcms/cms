import {IntervalManager} from './interval-manager';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.IntervalManager(...)` is created by the legacy `Craft.ProgressBar`.
registerCraftGlobals({IntervalManager});
