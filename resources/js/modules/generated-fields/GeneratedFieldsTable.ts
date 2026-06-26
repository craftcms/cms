import {EditableTable, Row} from '@/modules/editable-table/EditableTable';
import {cvdData} from '@/modules/field-layout-designer/support';
import type {EditableTableColumns} from '@/modules/editable-table/types';

// `Craft` and jQuery (`$`) are still globals on the page; used only at the
// Craft-widget seams (`Craft.HandleGenerator`, `Craft.uuid`, `Craft.trim`) and
// for the jQuery `<tr>` returned by `EditableTable`'s row assembly.
declare const Craft: any;
declare const $: any;

/**
 * A row within a {@link GeneratedFieldsTable}. Port of the legacy
 * `Craft.GeneratedFieldsTable.Row`. Wires each generated field's name input to
 * the Card View Designer's checkbox library (add / rename / remove) so the
 * card-attribute picker stays in sync.
 */
export class GeneratedFieldsTableRow extends Row {
  uid: string | null = null;

  constructor(table?: EditableTable, tr?: any) {
    super(table, tr);
    if (new.target === GeneratedFieldsTableRow) {
      this.init(table!, tr);
    }
  }

  override init(table: EditableTable, tr: any): void {
    super.init(table, tr);

    this.uid = this.$tr.find('> input[name$="[uid]"]').val();

    const $nameInput = this.$tr.find('> td:first-child > textarea');
    const $handleInput = this.$tr.find('> td:nth-child(2) > textarea');

    if (!$nameInput.val()) {
      new Craft.HandleGenerator($nameInput, $handleInput);
    }

    this.addListener($nameInput, 'input', () => {
      const name = Craft.trim($nameInput.val());
      const cvd = (this.table as GeneratedFieldsTable).cvd;

      if (!cvd) {
        return;
      }

      const value = `generatedField:${this.uid}`;

      if (name !== '') {
        if (cvd.findCheckboxByValue(value)) {
          cvd.updateCheckboxLabel(value, name);
        } else {
          cvd.addCheckbox({
            value,
            labelHtml: name,
            data: {
              // `fieldId` was never set on the legacy Row either; resolves to undefined.
              'field-id': (this as any).fieldId,
              'field-label': name,
            },
          });
        }
      } else {
        cvd.removeCheckbox(value);
      }
    });
  }

  override destroy(): void {
    const cvd = (this.table as GeneratedFieldsTable).cvd;
    cvd?.removeCheckbox(`generatedField:${this.uid}`);
    super.destroy();
  }
}

/**
 * Generated-fields editable table — port of the legacy
 * `Craft.GeneratedFieldsTable` (`class extends EditableTable`). Adds a hidden
 * `uid` input to every row and uses {@link GeneratedFieldsTableRow} so rows stay
 * synced with the Field Layout Designer's Card View Designer.
 *
 * Rendered by `src/Cp/FieldLayoutDesigner/FieldLayoutDesigner.php`, which emits
 * `new Craft.GeneratedFieldsTable(id, name, cols, settings)`.
 */
export class GeneratedFieldsTable extends EditableTable {
  constructor(
    id?: string,
    baseName?: string,
    columns?: EditableTableColumns,
    settings?: any
  ) {
    super(id, baseName, columns, settings);
    if (new.target === GeneratedFieldsTable) {
      this.init(id!, baseName!, columns!, settings);
    }
  }

  /**
   * The Card View Designer associated with this table, or `undefined`. The modern
   * FLD/CVD stores itself in the `cvdData` WeakMap (not jQuery `.data`), so we
   * resolve the `.card-view-designer` sibling element and look it up there.
   */
  get cvd(): any {
    const tableEl: HTMLElement | undefined = this.$table?.[0];
    const field = tableEl?.closest('.field');
    const next = field?.nextElementSibling;
    const cvdEl = next && next.matches('.card-view-designer') ? next : null;
    return cvdEl ? cvdData.get(cvdEl) : undefined;
  }

  override createRow(
    rowId: string,
    columns: EditableTableColumns,
    baseName: string,
    values: Record<string, any>
  ): any {
    const $tr = super.createRow(rowId, columns, baseName, values);
    $(
      `<input type="hidden" name="${baseName}[${rowId}][uid]" value="${Craft.uuid()}">`
    ).appendTo($tr);
    return $tr;
  }

  override createRowObj($tr: any): Row {
    return new GeneratedFieldsTableRow(this, $tr);
  }
}
