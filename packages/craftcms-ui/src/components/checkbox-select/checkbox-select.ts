import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {repeat} from 'lit/directives/repeat.js';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import {t} from '@src/utilities/translate';
import {ReorderableController} from '@src/controllers/ReorderableController';
import type {ActionMenuItem} from '../action-menu/action-menu.types.js';
import type {ReorderDirection} from '../reorder-button/reorder-button.js';
import styles from './checkbox-select.styles.js';
import '../action-menu/action-menu.js';
import '../button/button.js';
import type CraftCheckbox from '../checkbox/checkbox.js';
import '../checkbox/checkbox.js';
import '../icon/icon.js';
import '../reorder-button/reorder-button.js';

export interface CheckboxSelectOption {
  value: string;
  label: string;
  /** Trusted HTML shown in place of `label`. */
  labelHtml?: string;
  /** Trusted HTML shown below the label. */
  info?: string;
  checked?: boolean;
  /** A disabled option can't be toggled, removed, or moved. */
  disabled?: boolean;
}

export type CheckboxSelectPresentation = 'checkboxes' | 'add-menu';

export type CheckboxSelectAllMode = 'single-value' | 'each-value';

export interface CheckboxSelectOrderChangeDetail {
  value: string;
  index: number;
}

const ITEM_SELECTOR = ':scope > .cp-checkbox-select__item';
const ALL_SELECTOR = ':scope > .cp-checkbox-select__item.all';
const EMPTY_INPUT_SELECTOR = ':scope > input[type="hidden"][data-empty]';
const GOVERNED_NAME = 'governedName';
const DISABLED_BY_GROUP = 'disabledByGroup';

let nextKey = 0;

/**
 * @summary A list of options to check any number of, which can also be put in
 * order, offer an “All” option, or list only what's chosen with the rest in an
 * “Add item” menu.
 *
 * Each option is a `craft-checkbox` in a `div.cp-checkbox-select__item`, kept
 * in the light DOM so it posts with the form and can be read or changed like
 * any other markup. Items post `name[]` in their display order, so the order
 * is part of the value. The component lays the items out and adds the reorder
 * buttons, Remove buttons, and “Add item” menu in its own shadow DOM.
 *
 * Server-rendered items are adopted as they are. Options can also be set or
 * changed at runtime, with the `options` property or {@link addOption},
 * {@link updateOption}, and {@link removeOption}.
 *
 * @slot - The items: `div.cp-checkbox-select__item`s, each holding a
 *   `craft-checkbox` with a slotted checkbox input. The “All” item has the
 *   `all` class as well. Rendered for you when you set `options`.
 *
 * @fires {Event} change - When an option is checked or unchecked, from its
 *   input.
 * @fires {CustomEvent<CheckboxSelectOrderChangeDetail>} craft-order-change -
 *   When an option is moved.
 * @fires {Event} model-value-changed - When the value changes, including its
 *   order.
 *
 * @dependency craft-action-menu
 * @dependency craft-button
 * @dependency craft-checkbox
 * @dependency craft-reorder-button
 */
export default class CraftCheckboxSelect extends LitElement {
  static override styles = [styles];

  /**
   * Items post `{name}[]`, as does an “All” option rendered here. A
   * server-rendered “All” option keeps the name it was rendered with.
   */
  @property() name: string | null = null;

  /** A visible label, which also names the group. */
  @property() label: string | null = null;

  /** Disables every option. */
  @property({type: Boolean, reflect: true}) disabled = false;

  /** Lets options be put in order, by dragging or with their reorder button. */
  @property({type: Boolean, reflect: true}) sortable = false;

  /**
   * `checkboxes` lists every option with its checkbox. `add-menu` lists only
   * the checked ones, each with a Remove button, and offers the rest in an
   * “Add item” menu.
   */
  @property({reflect: true})
  presentation: CheckboxSelectPresentation = 'checkboxes';

  /** Adds an “All” option with this label above the others. */
  @property({attribute: 'all-label'}) allLabel: string | null = null;

  /** The value the “All” option posts in `single-value` mode. */
  @property({attribute: 'all-value'}) allValue = '*';

  /**
   * `single-value`: checking “All” posts its value alone, so the stored value
   * stays “all of them” as options come and go. `each-value`: a plain
   * select-all, where every option posts its own value.
   */
  @property({attribute: 'all-mode', reflect: true})
  allMode: CheckboxSelectAllMode = 'single-value';

  /** Bumped when the light DOM changes, to re-render the rows. */
  @state() private _revision = 0;

  @state() private _status = '';

  readonly #keys = new WeakMap<Element, string>();
  readonly #reorderable = new ReorderableController(this, {
    onReorder: (start, finish) => this.#moveRow(start, finish),
  });
  #observer: MutationObserver | null = null;
  #pendingFocus: (() => HTMLElement | null | undefined) | null = null;

  /** The options, in display order, with their current state. */
  get options(): CheckboxSelectOption[] {
    return this.#items().map((item) => this.#optionFor(item));
  }

  set options(options: CheckboxSelectOption[]) {
    const items = this.#items();
    const wanted = new Set(options.map((option) => String(option.value)));

    for (const item of items) {
      if (!wanted.has(this.#valueOf(item))) {
        item.remove();
      }
    }

    let previous: Element | null = this.#allItem() ?? this.#emptyInput();
    for (const option of options) {
      const item =
        this.#itemFor(String(option.value)) ?? this.#createItem(option);
      this.#applyOption(item, option);

      if (previous) {
        previous.after(item);
      } else {
        this.prepend(item);
      }
      previous = item;
    }

    this.requestUpdate('options');
  }

  /**
   * The checked values in display order — or, while “All” is checked in
   * `single-value` mode, just the “All” value.
   */
  get modelValue(): string[] {
    if (this.#allIsSingleValue() && this.#allInput()?.checked) {
      return [this.allValue];
    }

    return this.#items()
      .filter((item) => this.#inputOf(item)?.checked)
      .map((item) => this.#valueOf(item));
  }

  set modelValue(value: string[]) {
    const values = new Set((value ?? []).map(String));
    const all = this.#allIsSingleValue() && values.has(this.allValue);

    for (const item of this.#items()) {
      const input = this.#inputOf(item);
      if (input && !input.disabled) {
        input.checked = all || values.has(this.#valueOf(item));
      }
    }

    this.#syncAll();
    this.requestUpdate('modelValue');
  }

  /** Adds an option, at `index` among the options or at the end. */
  addOption(option: CheckboxSelectOption, index?: number): void {
    const existing = this.#itemFor(String(option.value));
    const item = existing ?? this.#createItem(option);
    this.#applyOption(item, option);

    const items = this.#items().filter((other) => other !== item);
    const before = index === undefined ? null : (items[index] ?? null);
    const after = items.at(-1) ?? this.#allItem() ?? this.#emptyInput();
    if (before) {
      before.before(item);
    } else if (after) {
      after.after(item);
    } else {
      this.prepend(item);
    }

    // “All” in single-value mode stands for every option, new ones included.
    if (this.#allIsSingleValue() && this.#allInput()?.checked) {
      this.#check(item, true);
      this.#syncAll();
    }

    this.requestUpdate();
  }

  /** Removes the option with the given value. */
  removeOption(value: string): void {
    this.#itemFor(String(value))?.remove();
    this.requestUpdate();
  }

  /** Changes the label, info, checked, or disabled state of an option. */
  updateOption(value: string, changes: Partial<CheckboxSelectOption>): void {
    const item = this.#itemFor(String(value));
    if (!item) {
      return;
    }

    this.#applyOption(item, {...this.#optionFor(item), ...changes});
    this.#syncDisabled();
    this.#syncAll();
    this.requestUpdate();
  }

  constructor() {
    super();
    // Re-apply values set on the element before it was upgraded, which
    // would otherwise shadow the accessors.
    for (const property of ['options', 'modelValue'] as const) {
      if (Object.prototype.hasOwnProperty.call(this, property)) {
        // SAFETY: Only the two accessors above are read back.
        const value = (this as Record<string, unknown>)[property];
        delete (this as Record<string, unknown>)[property];
        queueMicrotask(() => {
          (this as Record<string, unknown>)[property] = value;
        });
      }
    }
  }

  override connectedCallback(): void {
    super.connectedCallback();
    this.addEventListener('change', this.#onChange);
    this.addEventListener('model-value-changed', this.#onModelValueChanged);
    this.#observer = new MutationObserver(() => this.#revisionChanged());
    this.#observer.observe(this, {
      childList: true,
      subtree: true,
      characterData: true,
    });
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.removeEventListener('change', this.#onChange);
    this.removeEventListener('model-value-changed', this.#onModelValueChanged);
    this.#observer?.disconnect();
    this.#observer = null;
  }

  protected override willUpdate(changed: PropertyValues<this>): void {
    if (
      changed.has('name') ||
      changed.has('allLabel') ||
      changed.has('allMode')
    ) {
      this.#ensureEmptyInput();
      this.#ensureAllItem();
    }

    if (changed.has('disabled')) {
      this.#syncDisabled();
    }

    if (changed.has('allMode') || changed.has('allLabel')) {
      this.#syncAll();
    }

    for (const item of this.#items()) {
      if (!this.#keys.has(item)) {
        this.#keys.set(item, `item-${nextKey++}`);
      }
      item.slot = this.#keys.get(item)!;
    }

    this.#allItem()?.setAttribute('slot', 'all');
  }

  protected override updated(): void {
    this.#reorderable.setItems(
      this.#movableItems().flatMap((item) => {
        const row = this.#rowFor(item);
        return row
          ? [{element: row, handle: row.querySelector('craft-reorder-button')}]
          : [];
      })
    );

    const focus = this.#pendingFocus;
    this.#pendingFocus = null;
    if (focus) {
      void this.#focus(focus);
    }
  }

  /**
   * Focuses an element once it has rendered. A reorder button takes focus on
   * the button inside it, which is what opens its menu.
   */
  async #focus(target: () => HTMLElement | null | undefined): Promise<void> {
    let element = target();
    await (element as Partial<LitElement> | null | undefined)?.updateComplete;
    if (element?.localName === 'craft-reorder-button') {
      element =
        element.shadowRoot?.querySelector<HTMLElement>('craft-button') ??
        element;
      await (element as Partial<LitElement>).updateComplete;
    }
    element?.focus();
  }

  protected override render() {
    void this._revision;

    const rows = this.#rowItems();
    const addable = this.#addableItems();
    const movable = this.#movableItems();

    return html`
      ${this.label
        ? html`<div id="label" class="label" part="label">${this.label}</div>`
        : nothing}
      <div
        class="group"
        part="group"
        role=${this.label ? 'group' : nothing}
        aria-labelledby=${this.label ? 'label' : nothing}
      >
        <slot name="all"></slot>
        ${rows.length
          ? html`<div class="items" part="items">
              ${repeat(
                rows,
                (item) => this.#keys.get(item)!,
                (item) => this.#renderRow(item, movable)
              )}
            </div>`
          : nothing}
        ${this.presentation === 'add-menu' && addable.length && !this.disabled
          ? html`<craft-action-menu
              class="add"
              part="add-menu"
              ?searchable=${addable.length > 10}
              .actions=${() => this.#addMenuActions()}
            >
              <craft-button
                slot="invoker"
                type="button"
                size="small"
                variant="dashed"
              >
                <craft-icon name="plus" slot="prefix"></craft-icon>
                ${t('Add item')}
              </craft-button>
            </craft-action-menu>`
          : nothing}
      </div>
      <span class="cp-visually-hidden" role="status">${this._status}</span>
      <slot hidden></slot>
    `;
  }

  #renderRow(item: HTMLElement, movable: HTMLElement[]) {
    const key = this.#keys.get(item)!;
    const index = movable.indexOf(item);
    const isMovable = index !== -1;
    const dragging = isMovable && this.#reorderable.draggingIndex === index;
    const dropIndex = this.#reorderable.dropIndex;
    const option = this.#optionFor(item);
    const addMenu = this.presentation === 'add-menu';

    return html`<div
      class="row ${dragging ? 'row--dragging' : ''}"
      part="row"
      data-key=${key}
      data-drop=${dropIndex === index
        ? 'before'
        : dropIndex === index + 1 && index === movable.length - 1
          ? 'after'
          : nothing}
    >
      ${this.sortable
        ? html`<craft-reorder-button
            class="handle"
            position=${this.#positionOf(index, movable.length)}
            ?disabled=${!isMovable || movable.length < 2}
            @craft-reorder=${(
              event: CustomEvent<{direction: ReorderDirection}>
            ) =>
              this.#moveRow(
                index,
                index + (event.detail.direction === 'up' ? -1 : 1),
                true
              )}
          ></craft-reorder-button>`
        : nothing}
      ${addMenu
        ? html`<span class="row__label">
              ${option.labelHtml !== undefined
                ? unsafeHTML(option.labelHtml)
                : option.label}
              ${option.info
                ? html`<span class="row__info"
                    >${unsafeHTML(option.info)}</span
                  >`
                : nothing}
            </span>
            ${!option.disabled && !this.disabled
              ? html`<craft-button
                  class="remove"
                  type="button"
                  icon="xmark"
                  size="small"
                  variant="plain"
                  aria-label=${t('Remove {name}', {name: option.label})}
                  @click=${() => this.#remove(item)}
                ></craft-button>`
              : nothing}`
        : html`<slot name=${key}></slot>`}
    </div>`;
  }

  #positionOf(index: number, count: number) {
    if (count < 2) {
      return 'only';
    }

    return index <= 0 ? 'first' : index === count - 1 ? 'last' : 'middle';
  }

  /** Items with a row: all of them, or only the checked ones in add-menu. */
  #rowItems(): HTMLElement[] {
    const items = this.#items();
    return this.presentation === 'add-menu'
      ? items.filter((item) => this.#inputOf(item)?.checked)
      : items;
  }

  /** Rows that can be moved: enabled ones with a row. */
  #movableItems(): HTMLElement[] {
    if (!this.sortable || this.disabled) {
      return [];
    }

    return this.#rowItems().filter((item) => !this.#inputOf(item)?.disabled);
  }

  #addableItems(): HTMLElement[] {
    return this.#items().filter((item) => {
      const input = this.#inputOf(item);
      return input && !input.checked && !input.disabled;
    });
  }

  #addMenuActions(): ActionMenuItem[] {
    return this.#addableItems().map((item) => ({
      label: this.#labelOf(item),
      onClick: () => this.#add(item),
    }));
  }

  /** Checks an item from the “Add item” menu, after the checked ones. */
  #add(item: HTMLElement): void {
    const lastChecked = this.#rowItems().at(-1);
    if (lastChecked && lastChecked !== item) {
      lastChecked.after(item);
    }

    this.#check(item, true, true);
    this._status = t('Added {name}', {name: this.#labelOf(item)});

    // Once nothing is left to add, the menu and its button are gone, so
    // land on the new item's Remove button instead.
    if (!this.#addableItems().length) {
      this.#pendingFocus = () =>
        this.#rowFor(item)?.querySelector<HTMLElement>('.remove');
    }
  }

  /** Unchecks an item, moving focus to a neighbor or the “Add item” button. */
  #remove(item: HTMLElement): void {
    const rows = this.#rowItems();
    const index = rows.indexOf(item);
    const neighbor = [
      ...rows.slice(index + 1),
      ...rows.slice(0, index).reverse(),
    ].find((other) => !this.#inputOf(other)?.disabled);

    this.#check(item, false, true);
    this._status = t('Removed {name}', {name: this.#labelOf(item)});
    this.#pendingFocus = () =>
      (neighbor &&
        this.#rowFor(neighbor)?.querySelector<HTMLElement>('.remove')) ??
      this.renderRoot.querySelector<HTMLElement>(
        '.add craft-button[slot="invoker"]'
      );
  }

  /** Moves a row among the movable ones. */
  #moveRow(start: number, finish: number, keepFocus = false): void {
    const movable = this.#movableItems();
    const item = movable[start];
    const target = movable[finish];
    if (!item || !target || item === target) {
      return;
    }

    if (finish > start) {
      target.after(item);
    } else {
      target.before(item);
    }

    this._status = t('Moved {name} to position {position}', {
      name: this.#labelOf(item),
      position: finish + 1,
    });

    if (keepFocus) {
      this.#pendingFocus = () =>
        this.#rowFor(item)?.querySelector<HTMLElement>('craft-reorder-button');
    }

    this.requestUpdate();
    this.dispatchEvent(
      new CustomEvent<CheckboxSelectOrderChangeDetail>('craft-order-change', {
        bubbles: true,
        composed: true,
        detail: {value: this.#valueOf(item), index: finish},
      })
    );
    this.#emitModelValue();
  }

  /** Reacts to a checkbox changing, by the user or by {@link #check}. */
  #onChange = (event: Event): void => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox') {
      return;
    }

    if (input === this.#allInput()) {
      this.#applyAll(input.checked);
    } else {
      this.#syncAll();
    }

    this.requestUpdate();
    this.#emitModelValue();
  };

  /** The checkboxes' own value events would pass for the group's. */
  #onModelValueChanged = (event: Event): void => {
    if (event.target !== this) {
      event.stopPropagation();
    }
  };

  #check(item: HTMLElement, checked: boolean, notify = false): void {
    const input = this.#inputOf(item);
    if (!input || input.checked === checked) {
      return;
    }

    input.checked = checked;
    if (notify) {
      input.dispatchEvent(new Event('change', {bubbles: true}));
    }
  }

  /** Checks or clears every enabled option from the “All” option. */
  #applyAll(checked: boolean): void {
    for (const item of this.#items()) {
      if (!this.#inputOf(item)?.disabled) {
        this.#check(item, checked);
      }
    }

    this.#syncAll();
  }

  /**
   * Matches the “All” option to the others: checked when every enabled option
   * is, indeterminate when only some are. In single-value mode, a checked
   * “All” speaks for the options, so their names are parked and only its value
   * posts. They stay enabled, so “All” can clear them again.
   */
  #syncAll(): void {
    const allItem = this.#allItem();
    const allInput = this.#allInput();
    const enabled = this.#items().filter(
      (item) => !this.#inputOf(item)?.disabled
    );
    const checkedCount = enabled.filter(
      (item) => this.#inputOf(item)?.checked
    ).length;

    if (allItem && allInput) {
      const everything = enabled.length > 0 && checkedCount === enabled.length;
      allInput.checked = everything;
      const checkbox = allItem.querySelector<CraftCheckbox>('craft-checkbox');
      if (checkbox) {
        checkbox.indeterminate = checkedCount > 0 && !everything;
      }
    }

    const parked = this.#allIsSingleValue() && !!allInput?.checked;
    for (const item of this.#items()) {
      const input = this.#inputOf(item);
      if (!input) {
        continue;
      }

      if (parked) {
        if (input.name) {
          input.dataset[GOVERNED_NAME] = input.name;
          input.removeAttribute('name');
        }
      } else if (input.dataset[GOVERNED_NAME] !== undefined) {
        input.name = input.dataset[GOVERNED_NAME];
        delete input.dataset[GOVERNED_NAME];
      }
    }
  }

  #syncDisabled(): void {
    for (const input of this.querySelectorAll<HTMLInputElement>(
      ':scope > .cp-checkbox-select__item input[type="checkbox"]'
    )) {
      if (this.disabled && !input.disabled) {
        input.disabled = true;
        input.dataset[DISABLED_BY_GROUP] = '';
      } else if (
        !this.disabled &&
        input.dataset[DISABLED_BY_GROUP] !== undefined
      ) {
        input.disabled = false;
        delete input.dataset[DISABLED_BY_GROUP];
      }
    }
  }

  #emitModelValue(): void {
    this.dispatchEvent(
      new Event('model-value-changed', {bubbles: true, composed: true})
    );
  }

  #revisionChanged(): void {
    this._revision++;
  }

  /** Posts an empty value while nothing is checked. */
  #ensureEmptyInput(): void {
    const input = this.#emptyInput();
    if (!this.name) {
      input?.remove();
      return;
    }

    if (input) {
      input.name = this.name;
      return;
    }

    const empty = document.createElement('input');
    empty.type = 'hidden';
    empty.name = this.name;
    empty.value = '';
    empty.dataset.empty = '';
    this.prepend(empty);
  }

  #ensureAllItem(): void {
    const existing = this.#allItem();
    if (!this.allLabel) {
      existing?.remove();
      return;
    }

    const item =
      existing ??
      this.#createItem({value: this.allValue, label: this.allLabel});
    item.classList.add('all');
    const input = this.#inputOf(item)!;
    input.value = this.allValue;
    if (!existing) {
      item.dataset.generated = '';
    }
    if (item.dataset.generated !== undefined) {
      if (this.allMode === 'single-value' && this.name) {
        input.name = `${this.name}[]`;
      } else {
        input.removeAttribute('name');
      }
    }
    const label = item.querySelector('label');
    if (label && label.textContent !== this.allLabel) {
      label.textContent = this.allLabel;
    }

    if (!existing) {
      const empty = this.#emptyInput();
      if (empty) {
        empty.after(item);
      } else {
        this.prepend(item);
      }
    }
  }

  #createItem(option: CheckboxSelectOption): HTMLElement {
    const id = `${this.id || 'checkbox-select'}-${nextKey++}`;
    const item = document.createElement('div');
    item.className = 'cp-checkbox-select__item';

    const checkbox = document.createElement('craft-checkbox');
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.className = 'checkbox';
    input.slot = 'input';
    input.id = id;
    input.value = String(option.value);
    if (this.name) {
      input.name = `${this.name}[]`;
    }

    const label = document.createElement('label');
    label.slot = 'label';
    label.htmlFor = id;
    label.id = `${id}-label`;

    checkbox.append(input, label);
    item.append(checkbox);

    return item;
  }

  #applyOption(item: HTMLElement, option: CheckboxSelectOption): void {
    const input = this.#inputOf(item);
    const label = item.querySelector<HTMLLabelElement>(
      'craft-checkbox > label'
    );

    if (input) {
      if (option.checked !== undefined) {
        input.checked = option.checked;
      }
      if (option.disabled !== undefined) {
        input.disabled = option.disabled;
        delete input.dataset[DISABLED_BY_GROUP];
      }
    }

    if (label) {
      if (option.labelHtml !== undefined) {
        if (label.innerHTML !== option.labelHtml) {
          label.innerHTML = option.labelHtml;
        }
      } else if (label.textContent !== option.label) {
        label.textContent = option.label;
      }
      label.dataset.label = option.label;
    }

    const checkbox = item.querySelector('craft-checkbox');
    let info = checkbox?.querySelector<HTMLElement>(
      ':scope > [slot="help-text"][data-info]'
    );
    if (option.info) {
      if (!info) {
        info = document.createElement('div');
        info.slot = 'help-text';
        info.dataset.info = '';
        checkbox?.append(info);
      }
      if (info.innerHTML !== option.info) {
        info.innerHTML = option.info;
      }
    } else {
      info?.remove();
    }
  }

  #optionFor(item: HTMLElement): CheckboxSelectOption {
    const input = this.#inputOf(item);
    const label = item.querySelector<HTMLLabelElement>(
      'craft-checkbox > label, :scope > label'
    );
    const info = item.querySelector<HTMLElement>(
      '[slot="help-text"][data-info]'
    );

    return {
      value: this.#valueOf(item),
      label: this.#labelOf(item),
      ...(label?.children.length ? {labelHtml: label.innerHTML} : {}),
      ...(info ? {info: info.innerHTML} : {}),
      checked: !!input?.checked,
      disabled:
        !!input?.disabled && input.dataset[DISABLED_BY_GROUP] === undefined,
    };
  }

  #items(): HTMLElement[] {
    return Array.from(this.querySelectorAll<HTMLElement>(ITEM_SELECTOR)).filter(
      (item) => !item.classList.contains('all')
    );
  }

  #itemFor(value: string): HTMLElement | undefined {
    return this.#items().find((item) => this.#valueOf(item) === value);
  }

  #allItem(): HTMLElement | null {
    return this.querySelector<HTMLElement>(ALL_SELECTOR);
  }

  #allInput(): HTMLInputElement | null {
    const item = this.#allItem();
    return item ? this.#inputOf(item) : null;
  }

  #allIsSingleValue(): boolean {
    return !!this.#allItem() && this.allMode === 'single-value';
  }

  #emptyInput(): HTMLInputElement | null {
    return this.querySelector<HTMLInputElement>(EMPTY_INPUT_SELECTOR);
  }

  #rowFor(item: HTMLElement): HTMLElement | null {
    const key = this.#keys.get(item);
    return key
      ? this.renderRoot.querySelector<HTMLElement>(`.row[data-key="${key}"]`)
      : null;
  }

  /**
   * Reads the native input, never the `craft-checkbox` host: the host's
   * `checked` is Lion state that can lag a `change` by a tick, and isn't
   * adopted yet while the component first renders.
   */
  #inputOf(item: Element): HTMLInputElement | null {
    return item.querySelector<HTMLInputElement>('input[type="checkbox"]');
  }

  #valueOf(item: Element): string {
    const input = this.#inputOf(item);
    return input?.getAttribute('value') ?? input?.value ?? '';
  }

  #labelOf(item: Element): string {
    const label = item.querySelector<HTMLLabelElement>(
      'craft-checkbox > label, :scope > label'
    );
    return label?.dataset.label ?? label?.textContent?.trim() ?? '';
  }
}

if (!customElements.get('craft-checkbox-select')) {
  customElements.define('craft-checkbox-select', CraftCheckboxSelect);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-checkbox-select': CraftCheckboxSelect;
  }
}
