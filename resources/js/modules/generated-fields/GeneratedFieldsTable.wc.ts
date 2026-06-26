import {getPostData} from '@craftcms/garnish';
import {GeneratedFieldsTable} from '@/modules/generated-fields/GeneratedFieldsTable';

/**
 * `<craft-generated-fields-table>` — boots a {@link GeneratedFieldsTable} around
 * the server-rendered `<table>` it wraps, so PHP/Twig can emit the element
 * instead of a manual `new Craft.GeneratedFieldsTable(...)` `{% js %}` block.
 *
 * The element renders the table (and its rows) as **light-DOM children** — the
 * jQuery-based table code queries the document for the table by id, so a
 * shadow-DOM (Lit) component would hide it. The constructor args mirror the
 * legacy `new Craft.GeneratedFieldsTable(id, name, cols, settings)` emit:
 *
 *   - **id** — read from the child `<table>`'s live `id`, not an attribute. This
 *     is exactly the element `EditableTable#init` resolves via `$('#'+id)`, so
 *     it's correct regardless of input namespacing and can never collide with
 *     this wrapper element's own id.
 *   - **name** — the namespaced base input name (`name` attribute).
 *   - **cols** / **settings** — JSON (`cols` / `settings` attributes).
 */
export default class CraftGeneratedFieldsTable extends HTMLElement {
  #instance: GeneratedFieldsTable | null = null;

  connectedCallback(): void {
    this.#boot();
  }

  /**
   * Construct the table once its child `<table>` is present. Depending on how
   * the markup lands (initial HTML parse vs. an AJAX-injected fragment) the
   * children may not exist yet when the element upgrades, so retry on the next
   * frame until they do (bailing if the element is disconnected meanwhile).
   */
  #boot(): void {
    if (this.#instance || !this.isConnected) {
      return;
    }

    const table = this.querySelector<HTMLTableElement>('table');
    if (!table) {
      requestAnimationFrame(() => this.#boot());
      return;
    }

    this.#instance = new GeneratedFieldsTable(
      table.id,
      this.getAttribute('name') ?? '',
      this.#jsonAttr('cols'),
      this.#jsonAttr('settings')
    );
  }

  /**
   * The table's value as an **ordered list** of row payloads in DOM (drag-sort)
   * order — `[{ name, handle, template, uid }, …]` — which is what the server
   * stores as the generated-fields sort order: `Fields::assembleLayoutFromPost`
   * reads it raw via `Request::input('generatedFields')`, and
   * `FieldLayout::setGeneratedFields` runs `array_values()` over it (preserving
   * order; each item carries its `uid`, so identity is kept).
   *
   * Why a list and not the expanded `{ rowId: {…} }` object: each row's input
   * names bake in its original numeric index (`generatedFields[0][…]`,
   * `generatedFields[1][…]`), which does NOT change when the row is dragged.
   * Returning the keyed object lets JS re-sort those integer-like keys
   * ascending, discarding the dragged order — so the saved sort order never
   * actually changed. Reading the order off the DOM (`<tr data-id>`) and
   * emitting a list fixes that.
   *
   * Inertia forms don't post the table's distributed inputs (they collect only
   * the single hidden `fieldLayout` input), so the submit transform merges this
   * in. Native/Twig forms still post the inputs directly — left intact — so
   * this is additive.
   */
  serialize(): any[] {
    const table = this.querySelector<HTMLTableElement>('table');
    if (!table) {
      return [];
    }

    // Flat `name → value` map (e.g. `generatedFields[0][handle]`) expanded into
    // a nested `{ rowId: { name, handle, template, uid } }` object via the
    // legacy Craft helper (the `getPostData` + `expandPostArray` pairing the
    // Table field uses) — the per-row payloads, keyed by their (stable) row id.
    // Scoped to the inner `<table>` so the sibling base hidden
    // (`<input name="generatedFields" value="">`) is excluded.
    const flat = getPostData(table);
    const expand = (window as any).Craft?.expandPostArray;
    const expandedAll: Record<string, any> = expand ? expand(flat) : {};
    const baseName = this.getAttribute('name') ?? 'generatedFields';
    // The table only holds `generatedFields` inputs, so there's a single
    // top-level key; prefer the base name, falling back to that lone key.
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

  #jsonAttr(name: string): Record<string, any> {
    const raw = this.getAttribute(name);
    if (!raw) {
      return {};
    }
    try {
      return JSON.parse(raw);
    } catch {
      return {};
    }
  }

  disconnectedCallback(): void {
    if (this.#instance) {
      this.#instance.destroy();
      this.#instance = null;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-generated-fields-table': CraftGeneratedFieldsTable;
  }
}
