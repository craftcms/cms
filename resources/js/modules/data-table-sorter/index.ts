import {DataTableSorter} from './data-table-sorter';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.DataTableSorter(...)` is created by Craft.AdminTable (legacy) and
// the modern editable-table module. Plain modern ES class.
registerCraftGlobals({DataTableSorter});

export {DataTableSorter};
