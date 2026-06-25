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
   * The table's value as the nested object the server expects under the
   * `generatedFields` request param — i.e. `{ <rowId>: { name, handle,
   * template, uid } }`, exactly what a native `generatedFields[rowId][col]`
   * bracket POST produces (see `Fields::assembleLayoutFromPost`, which reads it
   * raw via `Request::input('generatedFields')` — not JSON-decoded).
   *
   * Inertia forms don't post the table's distributed inputs (they collect only
   * the single hidden `fieldLayout` input), so this lets the submit transform
   * merge the value in. Native/Twig forms still post the inputs directly — they
   * are left intact — so this is additive.
   *
   * Scoped to the inner `<table>` so the sibling base hidden
   * (`<input name="generatedFields" value="">`) is excluded, which would
   * otherwise collide with the bracketed row keys when expanded.
   */
  serialize(): Record<string, any> {
    const table = this.querySelector<HTMLTableElement>('table');
    if (!table) {
      return {};
    }

    // Flat `name → value` map (e.g. `generatedFields[row0][handle]`), then
    // expand the bracket names into a nested object via the legacy Craft helper
    // (the same `getPostData` + `expandPostArray` pairing the Table field uses).
    const flat = getPostData(table);
    const expand = (window as any).Craft?.expandPostArray;
    const expanded: Record<string, any> = expand ? expand(flat) : {};

    const baseName = this.getAttribute('name') ?? 'generatedFields';
    // The table only holds `generatedFields` inputs, so there's a single
    // top-level key; prefer the base name, falling back to that lone key.
    return expanded[baseName] ?? Object.values(expanded)[0] ?? {};
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
