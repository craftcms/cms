import {getPostData} from '@craftcms/garnish';
import {GeneratedFieldsTable} from '@/modules/generated-fields/generated-fields-table';
import {ControllerElement} from '@/common/web-components';
import {
  expandPostArray,
  type PostValue,
  type PostValues,
} from '@/common/utils/forms';
import type {EditableTableColumns} from '@/modules/editable-table/types';

interface GeneratedFieldRow extends PostValues {
  name?: string;
  handle?: string;
  template?: string;
  uid?: string;
}

function isGeneratedFieldRow(value: PostValue): value is GeneratedFieldRow {
  return (
    value instanceof Object && !Array.isArray(value) && !(value instanceof File)
  );
}

/**
 * `<craft-generated-fields-table>` — boots a {@link GeneratedFieldsTable} around
 * the server-rendered `<table>` it wraps, so PHP/Twig can emit the element instead
 * of a manual `new Craft.GeneratedFieldsTable(...)` `{% js %}` block.
 *
 * The table renders as **light-DOM children** — the jQuery table code queries the
 * document for the table by id, so shadow DOM would hide it. Constructor args
 * mirror the legacy emit; `id` is read from the child `<table>`'s live `id` (what
 * `EditableTable#init` resolves via `$('#'+id)`), not an attribute, so it can't
 * collide with this wrapper's own id. `name` is the namespaced base input name;
 * `cols`/`settings` are JSON attributes.
 */
export default class CraftGeneratedFieldsTable extends ControllerElement<GeneratedFieldsTable> {
  protected readonly rootSelector = 'table';

  protected create(table: HTMLElement): GeneratedFieldsTable {
    // SAFETY: PHP serializes the generated-fields column definitions into the cols attribute.
    const columns = this.jsonAttr('cols') as EditableTableColumns;
    return new GeneratedFieldsTable(
      table.id,
      this.getAttribute('name') ?? '',
      columns,
      this.jsonAttr('settings')
    );
  }

  /**
   * The table's value as an **ordered list** of row payloads in DOM (drag-sort)
   * order — `[{ name, handle, template, uid }, …]`. The server stores this as the
   * generated-fields sort order (`Fields::assembleLayoutFromPost` reads it raw,
   * `FieldLayout::setGeneratedFields` runs `array_values()`; each item keeps its
   * `uid`).
   *
   * A list, not the keyed `{ rowId: {…} }` object: row input names bake in the
   * original numeric index, which doesn't change on drag, so a keyed object lets JS
   * re-sort those integer-like keys ascending and discard the dragged order.
   * Reading order off the DOM (`<tr data-id>`) and emitting a list fixes that.
   *
   * Inertia forms post only the single hidden `fieldLayout` input, so the submit
   * transform merges this in; native/Twig forms still post the inputs directly, so
   * this is additive.
   */
  serialize(): GeneratedFieldRow[] {
    const table = this.querySelector<HTMLTableElement>('table');
    if (!table) {
      return [];
    }

    // Flat `name → value` map expanded into nested `{ rowId: {…} }` payloads via
    // the legacy `getPostData` + `expandPostArray` pairing. Scoped to the inner
    // `<table>` so the sibling base hidden input is excluded.
    const flat = getPostData(table);
    const expandedAll = expandPostArray(flat);
    const baseName = this.getAttribute('name') ?? 'generatedFields';
    // Single top-level key; prefer the base name, fall back to that lone key.
    const rowsValue = expandedAll[baseName] ?? Object.values(expandedAll)[0];
    const rowsById = isGeneratedFieldRow(rowsValue) ? rowsValue : {};

    // Read the row order from the DOM — drag-sort reorders the `<tr>`s but not
    // their baked-in input-name indices — and return the payloads in that order.
    const domOrderIds = Array.from(
      table.querySelectorAll<HTMLTableRowElement>('tbody tr')
    ).map((tr) => tr.dataset.id);

    return domOrderIds
      .map((id) => (id != null ? rowsById[id] : undefined))
      .filter(isGeneratedFieldRow);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-generated-fields-table': CraftGeneratedFieldsTable;
  }
}
