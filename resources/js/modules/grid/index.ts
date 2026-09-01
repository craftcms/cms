import {Grid} from './grid';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.Grid(...)` and the `.grid`
// jQuery plugin both boot it (Dashboard, field-layout-designer, generic CP
// `.grid` elements). Plain modern ES class — nothing subclasses it via the
// legacy `.extend()` API.
registerCraftGlobals({Grid});

export {Grid};
