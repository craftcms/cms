import {EditableTable, Row} from './EditableTable';
import {compatify} from '@craftcms/garnish/compat';

// Wrap the modern classes with the Garnish compat shim so the still-legacy
// `TableFieldSettings` bundle keeps working unchanged. It does:
//
//   const ColumnTable = Craft.EditableTable.extend({ init() { … this.base(…) } });
//   ColumnTable.Row   = Craft.EditableTable.Row.extend({ init() { … this.base(…) } });
//   Craft.EditableTable.createRow(…);
//   new Craft.EditableTable(id, baseName, columns, settings);
//
// `compatify` restores `.extend()` / the `init` trampoline / `this.base(...)` on
// top of the modern `class extends Base`. Static members (`createRow`,
// `textualColTypes`, `defaults`) are inherited automatically via ES static
// inheritance, so only the nested `Row` needs to be re-attached — mirroring how
// the FLD index re-attaches `.Tab`/`.Element` onto its constructor.
const EditableTableCompat = compatify(EditableTable) as any;
EditableTableCompat.Row = compatify(Row);

// Assign onto the legacy `Craft` global so the PHP/Twig-emitted
// `new Craft.EditableTable(...)` (and the `TableFieldSettings` subclasses) keep
// working unchanged. The `Craft` global is created by the legacy cp bundle; we
// only assign onto it.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.EditableTable = EditableTableCompat;

export {EditableTable, Row};
