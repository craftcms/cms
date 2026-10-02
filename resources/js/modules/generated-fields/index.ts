import {
  GeneratedFieldsTable,
  GeneratedFieldsTableRow,
} from './generated-fields-table';
import CraftGeneratedFieldsTable from '@/modules/generated-fields/generated-fields-table.ce';
import {defineElement} from '@/common/web-components';
import {registerCraftGlobals} from '@/common/craft-global';

// Re-expose the Row sub-class on the constructor, as the legacy bundle did
// (`Craft.GeneratedFieldsTable.Row`).
Object.assign(GeneratedFieldsTable, {Row: GeneratedFieldsTableRow});

// Assign onto the legacy `Craft` global so the PHP-emitted
// `new Craft.GeneratedFieldsTable(...)` keeps working. Nothing subclasses it via
// legacy `.extend()`, so — unlike `EditableTable` — it needs no compat shim and is
// assigned as the modern ES class directly.
registerCraftGlobals({GeneratedFieldsTable});

defineElement('craft-generated-fields-table', CraftGeneratedFieldsTable);

export {
  GeneratedFieldsTable,
  GeneratedFieldsTableRow,
  CraftGeneratedFieldsTable,
};
