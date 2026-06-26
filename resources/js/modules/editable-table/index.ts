import {EditableTable, Row} from './EditableTable';
import {compatify} from '@craftcms/garnish/compat';

// Wrap the modern classes with the Garnish compat shim so the still-legacy
// `TableFieldSettings` bundle (which uses `.extend()` / `this.base(...)`) keeps
// working unchanged. Statics are inherited automatically, so only the nested `Row`
// needs re-attaching — as the FLD index does with `.Tab`/`.Element`.
const EditableTableCompat = compatify(EditableTable) as any;
EditableTableCompat.Row = compatify(Row);

// Assign onto the legacy `Craft` global (created by the cp bundle) so Twig-emitted
// `new Craft.EditableTable(...)` and `TableFieldSettings` subclasses keep working.
const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.EditableTable = EditableTableCompat;

export {EditableTable, Row};
