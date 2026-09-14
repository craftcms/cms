import type {ReorderDirection} from '@craftcms/ui';
import {ControllerElement} from '@/common/web-components';
import {
  ComponentSelect,
  type ComponentSelectSettings,
} from './component-select';

/**
 * `<craft-component-select>` — a light-DOM progressive-enhancement port of the
 * legacy `Craft.ComponentSelectInput` (jQuery/Garnish). It wraps the
 * server-rendered markup from `_includes/forms/componentSelect.twig` and boots
 * a {@link ComponentSelect} controller around it (see that file for the
 * behavior). This element is a `ControllerElement` facade: it owns attribute
 * parsing and is the public API surface consumers use — `selectedIds`,
 * `addComponent`, `removeComponent`, plus the seams
 * `<craft-entry-type-manager>` drives (`showOption`/`hideOption`, `adoptChip`,
 * `moveComponent`, `openChooseMenu`, the `getInputValue` hook, and the
 * `define-chip-actions` event) — there is no jQuery `.data('componentSelect')`
 * back-reference like the legacy class kept.
 *
 * Every forwarding member below guards for the pre-boot window (the
 * `ControllerElement` base retries on `requestAnimationFrame` until
 * {@link rootSelector} resolves): getters fall back to `false`/`[]`, and the
 * action methods no-op until `this.instance` exists. `getInputValue` is the
 * one exception — it's stored on the element itself (not the controller), so
 * it survives being set before boot completes; see its accessor below.
 */
export default class CraftComponentSelect extends ControllerElement<ComponentSelect> {
  protected readonly rootSelector = ':scope > ul';

  /**
   * Backing field for the {@link getInputValue} hook. Owned by the element —
   * not the controller — so a consumer that sets it before boot (or across a
   * disconnect/reconnect) never loses it. The controller reads the current
   * value lazily via `settings.getInputValue`, a closure passed in
   * {@link create} that always dereferences this field.
   */
  #getInputValue: ((id: string | number) => string | null) | null = null;

  /**
   * Optional per-instance hook: the hidden-input value a newly-rendered chip
   * should carry (legacy `renderSettings().inputValue`). Set by wrapping
   * elements that need structured values — `<craft-entry-type-manager>` uses
   * it to render `{id, group}` JSON. When absent (or it returns `null`), the
   * server renders its default input for the chip.
   */
  get getInputValue(): ((id: string | number) => string | null) | null {
    return this.#getInputValue;
  }

  set getInputValue(value: ((id: string | number) => string | null) | null) {
    this.#getInputValue = value;
  }

  // `root` (the `:scope > ul`) is unused here — the controller re-resolves it
  // off `this` (the container) since it needs to re-query on every boot, and
  // there's no benefit to threading the already-resolved node through.
  // oxlint-disable-next-line @typescript-eslint/no-unused-vars
  protected create(root: HTMLElement): ComponentSelect {
    return new ComponentSelect(this, {
      ...this.#parseSettings(),
      getInputValue: () => this.#getInputValue,
    });
  }

  // --- Public API -------------------------------------------------------------

  /** Whether boot has completed (markup resolved and behavior wired). */
  get initialized(): boolean {
    return this.instance?.initialized ?? false;
  }

  /** The ids of the currently selected components, in DOM order. */
  get selectedIds(): Array<string | number> {
    return this.instance?.selectedIds ?? [];
  }

  /** The selected component chips, in DOM order. */
  get chips(): HTMLElement[] {
    return this.instance?.chips ?? [];
  }

  /** Show/hide the Choose-menu option for a component id. No-op before boot. */
  showOption(id: string | number): void {
    this.instance?.showOption(id);
  }

  hideOption(id: string | number): void {
    this.instance?.hideOption(id);
  }

  /** Open the Choose menu. No-op before boot. */
  openChooseMenu(): void {
    this.instance?.openChooseMenu();
  }

  /** Adopt a chip `li` rendered by another `craft-component-select`. No-op before boot. */
  adoptChip(li: HTMLElement): void {
    this.instance?.adoptChip(li);
  }

  /** Hand chip drag-sorting to an outer coordinator. No-op before boot. */
  releaseSort(): void {
    this.instance?.releaseSort();
  }

  /** Render + append a chip for the given component. Resolves immediately, before boot. */
  async addComponent(
    type: string,
    id: string | number,
    addToMenu = false
  ): Promise<void> {
    await this.instance?.addComponent(type, id, addToMenu);
  }

  /** Remove a chip. No-op before boot. */
  removeComponent(el: HTMLElement, animate = true): void {
    this.instance?.removeComponent(el, animate);
  }

  /** Move a chip's `li` one slot toward the start or end. No-op before boot. */
  moveComponent(li: HTMLElement, direction: ReorderDirection): void {
    this.instance?.moveComponent(li, direction);
  }

  // --- Attribute parsing --------------------------------------------------------

  /**
   * Boolean attribute with a legacy default: absent → the default; present with
   * `"false"`/`"0"` → false (so default-true settings like `sortable` can be
   * switched off); any other presence → true.
   */
  #boolAttr(name: string, fallback: boolean): boolean {
    const value = this.getAttribute(name);
    if (value === null) {
      return fallback;
    }
    return value !== 'false' && value !== '0';
  }

  /**
   * Attribute-driven configuration, replacing the legacy `{% js %}` settings
   * boot. `getInputValue` is intentionally left unset here — {@link create}
   * fills it with a closure over {@link #getInputValue} (optional properties
   * don't need to be present; `Omit<ComponentSelectSettings, 'getInputValue'>`
   * would collapse to an index-signature type here, since
   * `ComponentSelectSettings` extends `GarnishBaseSettings`, so the full type
   * is used instead).
   */
  #parseSettings(): ComponentSelectSettings {
    const limitAttr = this.getAttribute('limit');
    const limit =
      limitAttr !== null && limitAttr !== '' ? Number(limitAttr) || null : null;

    const settings: ComponentSelectSettings = {
      name: this.getAttribute('name'),
      limit,
      sortable: this.#boolAttr('sortable', true),
      selectable: this.#boolAttr('selectable', true),
      showHandles: this.#boolAttr('show-handles', false),
      showDescription: this.#boolAttr('show-description', false),
      showActionMenus: this.#boolAttr('show-action-menus', true),
      hyperlinks: this.#boolAttr('hyperlinks', false),
      createAction: this.getAttribute('create-action'),
      disabled: this.#boolAttr('disabled', false),
    };

    // No reason to be sortable when only one selection is allowed (legacy parity).
    if (settings.limit === 1) {
      settings.sortable = false;
    }

    return settings;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-component-select': CraftComponentSelect;
  }
}
