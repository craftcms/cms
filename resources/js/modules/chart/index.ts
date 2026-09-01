import {DataTable, Tip, BaseChart, Area, chartsUtils} from './chart';

// Assign the legacy `Craft.charts` namespace: `new Craft.charts.Area(...)` /
// `new Craft.charts.DataTable(...)` are created by the chart widgets (e.g.
// NewUsersWidget). Plain modern ES classes — nothing subclasses them via the
// legacy `.extend()` API.
Object.assign(window.Craft, {
  charts: {
    DataTable,
    Tip,
    BaseChart,
    Area,
    utils: chartsUtils,
  },
});

export {DataTable, Tip, BaseChart, Area, chartsUtils};
