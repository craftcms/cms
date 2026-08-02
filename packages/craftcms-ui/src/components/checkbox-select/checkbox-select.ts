import {css, html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import type {ReorderDirection} from '../reorder-button/reorder-button.js';
import '../checkbox/checkbox.js';
import '../icon/icon.js';
import '../reorder-button/reorder-button.js';

export type CheckboxSelectOptionValue = string | number | boolean | null;

export interface CheckboxSelectOption {
  label: string;
  value: CheckboxSelectOptionValue;
  icon?: string;
  color?: string;
  disabled?: boolean;
}

export type CheckboxSelectValue =
  | CheckboxSelectOptionValue
  | CheckboxSelectOptionValue[];

export default class CraftCheckboxSelect extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }

    fieldset {
      display: grid;
      gap: var(--c-spacing-sm);
      min-width: 0;
      margin: 0;
      padding: 0;
      border: 0;
    }

    :host([sortable]) ::slotted(.cp-checkbox-select__item:not(.all)) {
      display: grid;
      grid-template-columns: auto 1fr;
      align-items: center;
      gap: var(--c-spacing-sm);
    }
  `;

  @property({attribute: false}) options?: CheckboxSelectOption[];

  @property({attribute: 'all-option'})
  allOption?: CheckboxSelectOptionValue;

  @property({reflect: true, type: Boolean}) sortable = false;

  @property({reflect: true, type: Boolean}) disabled = false;

  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  @property({reflect: true}) name = '';

  private _modelValue: CheckboxSelectValue | undefined;

  private _committedValue: CheckboxSelectValue | undefined;

  private _hasCommittedValue = false;

  private _optionsSignature?: string;

  @property({attribute: false})
  get modelValue(): CheckboxSelectValue | undefined {
    return this._modelValue;
  }

  set modelValue(value: CheckboxSelectValue | undefined) {
    const previous = this._modelValue;
    this._modelValue = value;
    this.requestUpdate('modelValue', previous);
  }

  constructor() {
    super();
    this.addEventListener('model-value-changed', (event) => {
      if (event.target !== this) {
        event.stopImmediatePropagation();
      }
    });
  }

  override connectedCallback(): void {
    super.connectedCallback();
    this.setAttribute('role', 'group');
    this.addEventListener('change', this._onChange);
    this._renderOptions();
    this._adoptSsrValue();
    this._syncDom();
  }

  override disconnectedCallback(): void {
    this.removeEventListener('change', this._onChange);
    super.disconnectedCallback();
  }

  protected override updated(changedProperties: PropertyValues): void {
    super.updated(changedProperties);

    if (
      changedProperties.has('options') ||
      changedProperties.has('name') ||
      changedProperties.has('allOption') ||
      changedProperties.has('sortable')
    ) {
      this._renderOptions();
    }

    const preserveOrder =
      changedProperties.has('modelValue') &&
      this._hasCommittedValue &&
      this._sameValue(this.modelValue, this._committedValue);
    this._syncDom(!preserveOrder);

    if (changedProperties.has('modelValue') && !preserveOrder) {
      this._committedValue = undefined;
      this._hasCommittedValue = false;
    }
  }

  override render() {
    return html`<fieldset ?disabled=${this.disabled || this.readOnly}>
      <slot></slot>
    </fieldset>`;
  }

  private _onChange = (event: Event): void => {
    if (this.readOnly || this.disabled) {
      return;
    }

    const input = event.target;

    if (!(input instanceof HTMLInputElement) || input.type !== 'checkbox') {
      return;
    }

    const allValue = this._allValue();

    if (allValue !== undefined && input.value === allValue) {
      this._commitValue(input.checked ? allValue : []);

      return;
    }

    this._commitValue(this._checkedValues());
  };

  private _onReorder = (
    event: CustomEvent<{direction: ReorderDirection}>
  ): void => {
    if (this.closest('craft-sortable-checkbox-select')) {
      return;
    }

    const button = event.target;

    if (!(button instanceof HTMLElement) || this.readOnly || this.disabled) {
      return;
    }

    const row = button.closest<HTMLElement>('.cp-checkbox-select__item');
    const selectedRows = this._selectedRows();
    const index = row ? selectedRows.indexOf(row) : -1;
    const target =
      event.detail.direction === 'down'
        ? selectedRows[index + 1]
        : selectedRows[index - 1];

    if (!row || !target) {
      return;
    }

    if (event.detail.direction === 'down') {
      target.after(row);
    } else {
      target.before(row);
    }

    this._commitValue(this._checkedValues());
  };

  private _commitValue(value: CheckboxSelectValue): void {
    this._committedValue = value;
    this._hasCommittedValue = true;
    this.modelValue = value;
    this._syncDom(false);
    this.dispatchEvent(
      new CustomEvent('model-value-changed', {
        bubbles: true,
        composed: true,
      })
    );
  }

  private _renderOptions(): void {
    if (!this.options) {
      return;
    }

    const signature = JSON.stringify([
      this.options,
      this.name,
      this.allOption,
      this.sortable,
    ]);

    if (signature === this._optionsSignature) {
      return;
    }

    this._optionsSignature = signature;

    const options = this._orderedOptions();
    const nodes: HTMLElement[] = [];

    if (
      this._allValue() === undefined &&
      this.name &&
      !this.name.endsWith('[]')
    ) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = this.name;
      hidden.value = '';
      nodes.push(hidden);
    }

    options.forEach((option, index) => nodes.push(this._option(option, index)));
    this.replaceChildren(...nodes);
  }

  private _option(option: CheckboxSelectOption, index: number): HTMLElement {
    const value = this._htmlValue(option.value);
    const isAll = value === this._allValue();
    const row = document.createElement('div');
    row.className = `cp-checkbox-select__item${isAll ? ' all' : ''}`;

    if (this.sortable && !isAll) {
      const reorder = document.createElement('craft-reorder-button');
      reorder.addEventListener('reorder', this._onReorder as EventListener);
      row.append(reorder);
    }

    const checkbox = document.createElement('craft-checkbox');
    const input = document.createElement('input');
    const inputId = `${this.id || 'checkbox-select'}-${isAll ? 'all' : index}`;
    input.slot = 'input';
    input.type = 'checkbox';
    input.id = inputId;
    input.name = isAll ? this.name : this._arrayName();
    input.value = value;
    input.dataset.optionDisabled = option.disabled ? 'true' : 'false';

    const label = document.createElement('label');
    label.slot = 'label';
    label.htmlFor = inputId;
    this._appendOptionLabel(label, option);

    checkbox.append(input, label);
    row.append(checkbox);

    return row;
  }

  private _appendOptionLabel(
    label: HTMLLabelElement,
    option: CheckboxSelectOption
  ): void {
    if (option.icon) {
      const icon = document.createElement('craft-icon');
      icon.name = option.icon;
      if (option.color) {
        icon.style.color = option.color;
      }
      label.append(icon);
    } else if (option.color) {
      const color = document.createElement('span');
      const preview = document.createElement('span');
      color.className = 'color small';
      preview.className = 'color-preview';
      preview.style.backgroundColor = option.color;
      color.append(preview);
      label.append(color);
    }

    label.append(document.createTextNode(option.label));
  }

  private _syncDom(orderRows = true): void {
    if (orderRows) {
      this._orderRows();
    }

    const selected = new Set(this._selectedValues());
    const allValue = this._allValue();
    const allSelected = allValue !== undefined && selected.has(allValue);

    for (const input of this._inputs()) {
      const isAll = allValue !== undefined && input.value === allValue;
      input.checked = allSelected || selected.has(input.value);
      input.disabled = Boolean(
        this.disabled ||
        this.readOnly ||
        input.dataset.optionDisabled === 'true' ||
        (allSelected && !isAll)
      );
    }

    this._updateReorderButtons();
  }

  private _orderRows(): void {
    if (!this.sortable || !Array.isArray(this.modelValue)) {
      return;
    }

    const rows = this._rows();
    const byValue = new Map(
      rows.map((row) => [this._rowInput(row)?.value, row])
    );
    const ordered = this.modelValue
      .map((value) => byValue.get(this._htmlValue(value)))
      .filter((row): row is HTMLElement => row !== undefined);
    const allRow = rows.find(
      (row) => this._rowInput(row)?.value === this._allValue()
    );
    const remainder = rows.filter(
      (row) => row !== allRow && !ordered.includes(row)
    );

    for (const row of [allRow, ...ordered, ...remainder]) {
      if (row) {
        this.append(row);
      }
    }
  }

  private _updateReorderButtons(): void {
    const selectedRows = this._selectedRows();

    for (const row of this._rows()) {
      const button = row.querySelector<
        HTMLElementTagNameMap['craft-reorder-button']
      >('craft-reorder-button');

      if (!button) {
        continue;
      }

      const index = selectedRows.indexOf(row);
      button.disabled =
        this.disabled ||
        this.readOnly ||
        index === -1 ||
        selectedRows.length < 2;
      button.position =
        index === 0
          ? 'first'
          : index === selectedRows.length - 1
            ? 'last'
            : 'middle';
    }
  }

  private _adoptSsrValue(): void {
    if (this.modelValue !== undefined) {
      return;
    }

    const allValue = this._allValue();
    const all = this._inputs().find((input) => input.value === allValue);
    this._modelValue = all?.checked ? all.value : this._checkedValues();

    for (const input of this._inputs()) {
      input.dataset.optionDisabled ??=
        input.disabled && !this.disabled && !this.readOnly && !all?.checked
          ? 'true'
          : 'false';
    }
  }

  private _selectedValues(): string[] {
    const value = this.modelValue;
    const allValue = this._allValue();

    if ((value === null || value === undefined) && allValue !== undefined) {
      return [allValue];
    }

    return (Array.isArray(value) ? value : [value])
      .filter((item): item is CheckboxSelectOptionValue => item !== undefined)
      .map((item) => this._htmlValue(item));
  }

  private _checkedValues(): string[] {
    const allValue = this._allValue();

    return this._inputs()
      .filter((input) => input.checked && input.value !== allValue)
      .map((input) => input.value);
  }

  private _selectedRows(): HTMLElement[] {
    const allValue = this._allValue();

    return this._rows().filter((row) => {
      const input = this._rowInput(row);

      return input?.checked && input.value !== allValue;
    });
  }

  private _rows(): HTMLElement[] {
    return Array.from(
      this.querySelectorAll<HTMLElement>(':scope > .cp-checkbox-select__item')
    );
  }

  private _inputs(): HTMLInputElement[] {
    return Array.from(
      this.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
    );
  }

  private _rowInput(row: HTMLElement): HTMLInputElement | null {
    return row.querySelector<HTMLInputElement>('input[type="checkbox"]');
  }

  private _allValue(): string | undefined {
    if (this.allOption !== undefined) {
      return this._htmlValue(this.allOption);
    }

    return this.querySelector<HTMLInputElement>('input.all')?.value;
  }

  private _arrayName(): string {
    return this.name.endsWith('[]') ? this.name : `${this.name}[]`;
  }

  private _orderedOptions(): CheckboxSelectOption[] {
    if (!this.options || !this.sortable || !Array.isArray(this.modelValue)) {
      return this.options ?? [];
    }

    const selectedOrder = new Map(
      this.modelValue.map((value, index) => [this._htmlValue(value), index])
    );
    const allValue = this._allValue();

    return this.options.slice().sort((first, second) => {
      const firstValue = this._htmlValue(first.value);
      const secondValue = this._htmlValue(second.value);

      if (firstValue === allValue) {
        return -1;
      }

      if (secondValue === allValue) {
        return 1;
      }

      const firstIndex = selectedOrder.get(firstValue);
      const secondIndex = selectedOrder.get(secondValue);

      if (firstIndex === undefined) {
        return secondIndex === undefined ? 0 : 1;
      }

      return secondIndex === undefined ? -1 : firstIndex - secondIndex;
    });
  }

  private _htmlValue(value: CheckboxSelectOptionValue): string {
    if (value === true) {
      return '1';
    }

    return value === false || value === null ? '' : String(value);
  }

  private _sameValue(
    first: CheckboxSelectValue | undefined,
    second: CheckboxSelectValue | undefined
  ): boolean {
    if (!Array.isArray(first) || !Array.isArray(second)) {
      return first === second;
    }

    return (
      first.length === second.length &&
      first.every((value, index) => value === second[index])
    );
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
