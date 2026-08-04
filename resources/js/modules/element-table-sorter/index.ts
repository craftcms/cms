import {ElementTableSorter} from './element-table-sorter';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.ElementTableSorter(...)` is created by Craft.TableElementIndexView.
// Plain modern ES class.
registerCraftGlobals({ElementTableSorter});

export {ElementTableSorter};
