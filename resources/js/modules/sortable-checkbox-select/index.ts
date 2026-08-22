import {Item, SortableCheckboxSelect} from './sortable-checkbox-select';
import CraftSortableCheckboxSelect from '@/modules/sortable-checkbox-select/sortable-checkbox-select.ce';
import {defineElement} from '@/common/web-components';
import {registerCraftGlobals} from '@/common/craft-global';

// Re-expose the Item sub-class on the constructor, as the legacy bundle did
// (`Craft.SortableCheckboxSelect.Item`).
Object.assign(SortableCheckboxSelect, {Item});

// Assign onto the legacy `Craft` global so the Twig-emitted
// `new Craft.SortableCheckboxSelect($container)` and the
// `Craft.ui.createSortableCheckboxSelect` factory keep working. Nothing subclasses
// it via legacy `.extend()`, so — like `GeneratedFieldsTable` — it needs no compat
// shim and is assigned as the modern ES class directly.
registerCraftGlobals({SortableCheckboxSelect});

defineElement('craft-sortable-checkbox-select', CraftSortableCheckboxSelect);

export {SortableCheckboxSelect, Item, CraftSortableCheckboxSelect};
