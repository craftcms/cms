import {Base} from '@craftcms/garnish';
import {FieldToggle} from '@/modules/field-toggle/field-toggle';
import {fieldToggleData} from '@/modules/field-toggle/support';
import {resolveElement, type ElementArg} from '@/common/utils/dom';
import {jq} from '@/common/utils/jquery';

/**
 * LinkField — a port of `Craft.LinkField` onto `@craftcms/garnish` `Base`. The
 * outer orchestrator for the Link field type: it watches the link-type select
 * (a {@link FieldToggle}) and, whenever the active link type changes, syncs the
 * label and filename inputs' placeholders to that type's defaults.
 *
 * Pairs with {@link LinkInput}, which reaches this instance through the legacy
 * `.data('linkField')` jQuery seam (kept because the pair hands off across the
 * still-jQuery field markup). Booted from `Link.php`.
 */
export class LinkField extends Base {
  container: HTMLElement | null = null;

  #labelInput: HTMLInputElement | null = null;
  #filenameInput: HTMLInputElement | null = null;

  // Per active-link-type-container placeholder storage — was jQuery
  // `.data('linkLabel')` / `.data('filename')`, now a jQuery-free WeakMap.
  #placeholders = new WeakMap<
    Element,
    {linkLabel?: string; filename?: string}
  >();

  constructor(container?: ElementArg) {
    super();
    if (new.target === LinkField) {
      this.init(container ?? null);
    }
  }

  init(container: ElementArg): void {
    this.container = resolveElement(container);
    if (!this.container) {
      return;
    }

    // Legacy back-reference, read by LinkInput via jQuery `.data('linkField')`.
    jq()?.(this.container).data('linkField', this);

    const typeSelect =
      this.container.querySelector<HTMLSelectElement>('select');
    const labelField = this.container.querySelector(
      ':scope > [data-label-field]'
    );
    this.#labelInput =
      labelField?.querySelector<HTMLInputElement>('.text') ?? null;
    this.#filenameInput = this.container.querySelector<HTMLInputElement>(
      '[data-filename-field] .text'
    );

    if (typeSelect) {
      // FieldToggle is a peer module; reuse the existing instance if the page
      // already booted one on this select, else create it.
      const toggle =
        fieldToggleData.get(typeSelect) ?? new FieldToggle(typeSelect);
      toggle.on('toggleChange', () => {
        this.updateLabel();
        this.updateFilename();
      });
    }
  }

  getActiveLinkTypeContainer(): HTMLElement | null {
    return (
      this.container?.querySelector<HTMLElement>(
        '[data-link-type]:not(.hidden)'
      ) ?? null
    );
  }

  updateLabel(label: string | null = null): void {
    const container = this.getActiveLinkTypeContainer();
    if (!container) {
      return;
    }
    const store = this.#store(container);
    if (label === null) {
      label = store.linkLabel ?? '';
    } else {
      store.linkLabel = label;
    }
    if (this.#labelInput) {
      this.#labelInput.placeholder = label;
    }
  }

  updateFilename(filename: string | null = null): void {
    const container = this.getActiveLinkTypeContainer();
    if (!container) {
      return;
    }
    const store = this.#store(container);
    if (filename === null) {
      filename = store.filename ?? '';
    } else {
      store.filename = filename;
    }
    if (this.#filenameInput) {
      this.#filenameInput.placeholder = filename;
    }
  }

  #store(el: Element): {linkLabel?: string; filename?: string} {
    let store = this.#placeholders.get(el);
    if (!store) {
      store = {};
      this.#placeholders.set(el, store);
    }
    return store;
  }
}
