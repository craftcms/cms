import {DataTable, Tip, BaseChart, Area, chartsUtils} from './chart';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft.charts` namespace: `new Craft.charts.Area(...)` /
// `new Craft.charts.DataTable(...)` are created by the chart widgets (e.g.
// NewUsersWidget). Plain modern ES classes — nothing subclasses them via the
// legacy `.extend()` API.
registerCraftGlobals({
    charts: {
        DataTable,
        Tip,
        BaseChart,
        Area,
        utils: chartsUtils,
    },
});

export {DataTable, Tip, BaseChart, Area, chartsUtils};
