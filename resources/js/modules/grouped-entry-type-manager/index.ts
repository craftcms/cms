import {Group, GroupedEntryTypeManager} from './grouped-entry-type-manager';
import CraftEntryTypeManager from '@/modules/grouped-entry-type-manager/grouped-entry-type-manager.ce';
import {defineElement} from '@/common/web-components';
import {registerCraftGlobals} from '@/common/craft-global';

// Re-expose the Group sub-class on the constructor, as the legacy bundle did
// (`Craft.GroupedEntryTypeManager.Group`).
Object.assign(GroupedEntryTypeManager, {Group});

// Assign onto the legacy `Craft` global so any remaining
// `new Craft.GroupedEntryTypeManager(...)` boots (plugins) keep working — the
// legacy GroupedEntryTypeManager.js/GroupedEntryTypeSelectInput.js are removed
// from the legacy bundle. Nothing subclasses it via legacy `.extend()`, so —
// like `SortableCheckboxSelect` — it needs no compat shim and is assigned as
// the modern ES class directly. (`Craft.GroupedEntryTypeSelectInput` is gone
// without replacement: the cross-group select behavior now lives in the
// manager + `craft-component-select`, not a select subclass.)
registerCraftGlobals({GroupedEntryTypeManager});

defineElement('craft-entry-type-manager', CraftEntryTypeManager);

export {GroupedEntryTypeManager, Group, CraftEntryTypeManager};
export {attachChipMoveActions} from './grouped-entry-type-manager';
export type {GroupedEntryTypeManagerSettings} from './grouped-entry-type-manager';
export {groupedEntryTypeManagerData} from './support';

// The Vue components in ./components (EntryTypeChip.vue, EntryTypeSelect.vue)
// are a separate, Inertia-page concern and are imported directly by path.
