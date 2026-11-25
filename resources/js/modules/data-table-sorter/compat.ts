import {DataTableSorter} from './data-table-sorter';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.DataTableSorter(...)` is created by the legacy `Craft.AdminTable`.
registerCraftGlobals({DataTableSorter});
