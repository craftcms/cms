import {SortableCheckboxSelect, Item} from './SortableCheckboxSelect';

// Re-expose the Item sub-class on the constructor, as the legacy bundle did
// (`Craft.SortableCheckboxSelect.Item`).
(SortableCheckboxSelect as any).Item = Item;

// Assign onto the legacy `Craft` global so the PHP/Twig-emitted
// `new Craft.SortableCheckboxSelect($container)` and the
// `Craft.ui.createSortableCheckboxSelect` factory keep working unchanged.
// Nothing subclasses it via the legacy `.extend()`, so — unlike `EditableTable`
// — it doesn't need the compat shim and is assigned as the modern ES class
// directly (like `GeneratedFieldsTable`).
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.SortableCheckboxSelect = SortableCheckboxSelect;

export {SortableCheckboxSelect, Item};
