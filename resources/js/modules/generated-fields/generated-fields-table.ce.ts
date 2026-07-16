import {getPostData} from '@craftcms/garnish';
import {GeneratedFieldsTable} from '@/modules/generated-fields/generated-fields-table';
import {ControllerElement} from '@/common/web-components';

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
    return new GeneratedFieldsTable(
      table.id,
      this.getAttribute('name') ?? '',
      this.jsonAttr('cols'),
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
  serialize(): any[] {
    const table = this.querySelector<HTMLTableElement>('table');
    if (!table) {
      return [];
    }

    // Flat `name → value` map expanded into nested `{ rowId: {…} }` payloads via
    // the legacy `getPostData` + `expandPostArray` pairing. Scoped to the inner
    // `<table>` so the sibling base hidden input is excluded.
    const flat = getPostData(table);
    const expand = (window as any).Craft?.expandPostArray;
    const expandedAll: Record<string, any> = expand ? expand(flat) : {};
    const baseName = this.getAttribute('name') ?? 'generatedFields';
    // Single top-level key; prefer the base name, fall back to that lone key.
    const rowsById: Record<string, any> =
      expandedAll[baseName] ?? Object.values(expandedAll)[0] ?? {};

    // Read the row order from the DOM — drag-sort reorders the `<tr>`s but not
    // their baked-in input-name indices — and return the payloads in that order.
    const domOrderIds = Array.from(table.querySelectorAll('tbody tr')).map(
      (tr) => (tr as HTMLElement).dataset.id
    );

    return domOrderIds
      .map((id) => (id != null ? rowsById[id] : undefined))
      .filter(Boolean);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-generated-fields-table': CraftGeneratedFieldsTable;
  }
}
