import {Group, GroupedEntryTypeManager} from './grouped-entry-type-manager';
import {registerCraftGlobals} from '@/common/craft-global';

// Re-expose the Group sub-class on the constructor, as the legacy bundle did
// (`Craft.GroupedEntryTypeManager.Group`).
Object.assign(GroupedEntryTypeManager, {Group});

// Core renders `<craft-entry-type-manager>`; the global is only for plugins
// that still boot `new Craft.GroupedEntryTypeManager(...)`. Nothing subclasses
// it via legacy `.extend()`, so the modern ES class is assigned directly.
// (`Craft.GroupedEntryTypeSelectInput` is gone without replacement: the
// cross-group select behavior now lives in the manager + `craft-component-select`.)
registerCraftGlobals({GroupedEntryTypeManager});
