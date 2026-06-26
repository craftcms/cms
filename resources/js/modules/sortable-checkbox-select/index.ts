import {Item, SortableCheckboxSelect} from './SortableCheckboxSelect';

// Re-expose the Item sub-class on the constructor, as the legacy bundle did
// (`Craft.SortableCheckboxSelect.Item`).
(SortableCheckboxSelect as any).Item = Item;

// Assign onto the legacy `Craft` global so the Twig-emitted
// `new Craft.SortableCheckboxSelect($container)` and the
// `Craft.ui.createSortableCheckboxSelect` factory keep working. Nothing subclasses
// it via legacy `.extend()`, so — like `GeneratedFieldsTable` — it needs no compat
// shim and is assigned as the modern ES class directly.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.SortableCheckboxSelect = SortableCheckboxSelect;

export {SortableCheckboxSelect, Item};
