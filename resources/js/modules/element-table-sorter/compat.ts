import {ElementTableSorter} from './element-table-sorter';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.ElementTableSorter(...)` is created by the legacy
// `Craft.TableElementIndexView`.
registerCraftGlobals({ElementTableSorter});
