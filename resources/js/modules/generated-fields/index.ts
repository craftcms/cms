import {
  GeneratedFieldsTable,
  GeneratedFieldsTableRow,
} from './GeneratedFieldsTable';
import CraftGeneratedFieldsTable from '@/modules/generated-fields/GeneratedFieldsTable.wc';

// Re-expose the Row sub-class on the constructor, as the legacy bundle did
// (`Craft.GeneratedFieldsTable.Row`).
(GeneratedFieldsTable as any).Row = GeneratedFieldsTableRow;

// Assign onto the legacy `Craft` global so the PHP-emitted
// `new Craft.GeneratedFieldsTable(id, name, cols, settings)`
// (`src/Cp/FieldLayoutDesigner/FieldLayoutDesigner.php`) keeps working
// unchanged. Nothing subclasses `GeneratedFieldsTable` via the legacy
// `.extend()`, so — unlike `EditableTable` — it does not need the compat shim
// and is assigned as the modern ES class directly.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.GeneratedFieldsTable = GeneratedFieldsTable;

if (!customElements.get('craft-generated-fields-table')) {
  customElements.define(
    'craft-generated-fields-table',
    CraftGeneratedFieldsTable
  );
}

export {
  GeneratedFieldsTable,
  GeneratedFieldsTableRow,
  CraftGeneratedFieldsTable,
};
