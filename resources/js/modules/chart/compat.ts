import {DataTable, Tip, BaseChart, Area, chartsUtils} from './chart';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft.charts` namespace: `new Craft.charts.Area(...)` /
// `new Craft.charts.DataTable(...)` are created by legacy chart widgets (e.g.
// the Craft 5 NewUsersWidget). Core's own widget imports the classes. Plain
// modern ES classes — nothing subclasses them via the legacy `.extend()` API.
const charts = {DataTable, Tip, BaseChart, Area, utils: chartsUtils};
registerCraftGlobals({charts});
