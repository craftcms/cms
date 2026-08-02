import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {repeat} from 'lit/directives/repeat.js';
import {property} from 'lit/decorators.js';
import {toHandle} from '@src/utilities/string';
import {t} from '@src/utilities/translate';
import type CraftCheckbox from '../checkbox/checkbox.js';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../input-color/input-color.js';
import '../input/input.js';
import '../reorder-button/reorder-button.js';
import '../visually-hidden/visually-hidden.js';
import styles from './option-rows.styles.js';

type ErrorValue = {value: OptionText; hasErrors?: boolean};
type OptionText = string | number | null | ErrorValue;
type OptionBoolean = boolean | 0 | 1 | '' | '0' | '1';

export type OptionRow = {
  optgroup?: OptionText;
  label?: OptionText;
  value?: OptionText;
  icon?: OptionText;
  color?: OptionText;
  default?: OptionBoolean;
  disabled?: OptionBoolean;
};

type RenderedRow = {key: number; row: OptionRow};

function assertOptionRows(value: unknown): asserts value is OptionRow[] {
  if (!Array.isArray(value)) {
    throw new TypeError('Option rows value must be a JSON array.');
  }

  value.forEach((row, index) => {
    if (!row || typeof row !== 'object' || Array.isArray(row)) {
      throw new TypeError(`Option row ${index} must be an object.`);
    }

    if (Object.hasOwn(row, 'optgroup')) {
      assertRowProperties(row, index, ['optgroup', 'disabled']);

      if (!isOptionText(row.optgroup)) {
        throw new TypeError(`Option row ${index} has an invalid optgroup.`);
      }

      if (Object.hasOwn(row, 'disabled') && !isOptionBoolean(row.disabled)) {
        throw new TypeError(
          `Option row ${index} has an invalid disabled state.`
        );
      }

      return;
    }

    assertRowProperties(row, index, [
      'label',
      'value',
      'icon',
      'color',
      'default',
      'disabled',
    ]);

    if (
      !Object.hasOwn(row, 'label') ||
      !Object.hasOwn(row, 'value') ||
      !isOptionText(row.label) ||
      !isOptionText(row.value)
    ) {
      throw new TypeError(`Option row ${index} must define a label and value.`);
    }

    for (const property of ['icon', 'color'] as const) {
      if (Object.hasOwn(row, property) && !isOptionText(row[property])) {
        throw new TypeError(`Option row ${index} has an invalid ${property}.`);
      }
    }

    for (const property of ['default', 'disabled'] as const) {
      if (Object.hasOwn(row, property) && !isOptionBoolean(row[property])) {
        throw new TypeError(
          `Option row ${index} has an invalid ${property} state.`
        );
      }
    }
  });
}

function assertRowProperties(
  row: object,
  index: number,
  supported: string[]
): void {
  const unsupported = Object.keys(row).find(
    (property) => !supported.includes(property)
  );

  if (unsupported) {
    throw new TypeError(
      `Option row ${index} has an unsupported ${unsupported} property.`
    );
  }
}

function isOptionText(value: unknown): value is OptionText {
  if (
    value === null ||
    typeof value === 'string' ||
    typeof value === 'number'
  ) {
    return true;
  }

  return (
    Boolean(value) &&
    typeof value === 'object' &&
    Object.hasOwn(value, 'value') &&
    Object.keys(value).every((property) =>
      ['value', 'hasErrors'].includes(property)
    ) &&
    (!Object.hasOwn(value, 'hasErrors') ||
      typeof (value as ErrorValue).hasErrors === 'boolean') &&
    isOptionText((value as ErrorValue).value)
  );
}

function isOptionBoolean(value: unknown): value is OptionBoolean {
  return (
    typeof value === 'boolean' ||
    value === 0 ||
    value === 1 ||
    value === '' ||
    value === '0' ||
    value === '1'
  );
}

function optionBoolean(value: OptionBoolean | undefined): boolean {
  return value === true || value === 1 || value === '1';
}

const valueConverter = {
  fromAttribute(value: string | null): OptionRow[] {
    if (!value) {
      return [];
    }

    const rows: unknown = JSON.parse(value);

    assertOptionRows(rows);

    return rows;
  },
};

let nextHeadingId = 0;

/**
 * @summary An ordered editor for labels, values, defaults, optgroups, icons, and colors.
 *
 * @event input - Emitted when the ordered option rows change.
 *
 * @since 1.0
 */
export default class CraftOptionRows extends LitElement {
  static override styles = [styles];

  /** Base input name used for nested option row values. */
  @property({reflect: true}) name: string | null = null;

  /** Ordered option rows. */
  @property({converter: valueConverter}) value: OptionRow[] = [];

  /** Allows more than one row to be selected by default. */
  @property({attribute: 'multiple-defaults', reflect: true, type: Boolean})
  multipleDefaults = false;

  /** Allows group-heading rows. */
  @property({reflect: true, type: Boolean}) optgroups = false;

  /** Shows an icon picker for each option. */
  @property({reflect: true, type: Boolean}) icons = false;

  /** Shows a color picker for each option. */
  @property({reflect: true, type: Boolean}) colors = false;

  /** Prevents editing while preserving form submission. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  private _blankRow: OptionRow = {label: '', value: '', default: false};
  private _iconHeadingId = `option-icons-${nextHeadingId++}`;
  private _nextRowKey = 0;
  private _renderedRows: RenderedRow[] = [];
  private _rowKeys = new WeakMap<OptionRow, number>();

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }
  }

  protected override willUpdate(changedProperties: PropertyValues) {
    super.willUpdate(changedProperties);

    if (changedProperties.has('value')) {
      assertOptionRows(this.value);
      this._reconcileRows();
    }
  }

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('readOnly')) {
      if (this.readOnly) {
        this.setAttribute('aria-readonly', 'true');
      } else {
        this.removeAttribute('aria-readonly');
      }
    }

    if (
      changedProperties.has('name') ||
      changedProperties.has('value') ||
      changedProperties.has('optgroups') ||
      changedProperties.has('icons') ||
      changedProperties.has('colors')
    ) {
      this._syncFormInputs();
    }
  }

  protected override render() {
    return html`
      <table>
        <thead>
          <tr>
            ${this.optgroups
              ? html`<th scope="col">${t('Optgroup?')}</th>`
              : nothing}
            <th scope="col">${t('Option Label')}</th>
            <th scope="col">${t('Value')}</th>
            ${this.icons
              ? html`<th id=${this._iconHeadingId} scope="col">
                  ${t('Icon')}
                </th>`
              : nothing}
            ${this.colors ? html`<th scope="col">${t('Color')}</th>` : nothing}
            <th scope="col">${t('Default?')}</th>
            <th scope="col">
              <craft-visually-hidden>${t('Actions')}</craft-visually-hidden>
            </th>
          </tr>
        </thead>
        <tbody>
          ${repeat(
            this._rows(),
            ({key}) => key,
            ({row}, index) => this._rowTemplate(row, index)
          )}
        </tbody>
      </table>
      <craft-button
        type="button"
        size="small"
        ?disabled=${this.readOnly}
        data-add-option
        @click=${this._addRow}
      >
        ${t('Add an option')}
      </craft-button>
    `;
  }

  private _rowTemplate(row: OptionRow, index: number) {
    const optgroup = this._isOptgroup(row);
    const disabled = this._rowDisabled(row);
    const label = this._rowLabel(row);

    return html`
      <tr data-option-row=${index}>
        ${this.optgroups
          ? html`<td>
              <craft-checkbox
                label-sr-only
                label=${t('Optgroup for {label}', {
                  label: this._accessibleLabel(row, index),
                })}
                .checked=${optgroup}
                ?disabled=${disabled}
                data-option-optgroup
                @change=${(event: Event) => this._toggleOptgroup(index, event)}
              ></craft-checkbox>
            </td>`
          : nothing}
        <td>
          <craft-input
            type="text"
            .modelValue=${label}
            ?readonly=${disabled}
            aria-label=${t('Option Label for {label}', {
              label: this._accessibleLabel(row, index),
            })}
            aria-invalid=${this._hasErrors(optgroup ? row.optgroup : row.label)
              ? 'true'
              : nothing}
            data-option-label
            @input=${(event: Event) => this._updateLabel(index, event)}
          ></craft-input>
        </td>
        <td>
          <craft-input
            type="text"
            .modelValue=${this._textValue(row.value)}
            ?readonly=${disabled}
            ?disabled=${optgroup}
            aria-label=${t('Value for {label}', {
              label: this._accessibleLabel(row, index),
            })}
            aria-invalid=${this._hasErrors(row.value) ? 'true' : nothing}
            data-option-value
            @input=${(event: Event) => this._updateTextValue(index, event)}
          ></craft-input>
        </td>
        ${this.icons
          ? html`<td>
              <craft-icon-picker
                value=${this._textValue(row.icon)}
                ?disabled=${disabled || optgroup}
                labelled-by=${this._iconHeadingId}
                data-option-icon
                @change=${(event: CustomEvent<{value: string}>) =>
                  this._updateIcon(index, event)}
              ></craft-icon-picker>
            </td>`
          : nothing}
        ${this.colors
          ? html`<td>
              <craft-input-color
                .modelValue=${this._textValue(row.color)}
                ?readonly=${disabled}
                ?disabled=${optgroup}
                label=${t('Color for {label}', {
                  label: this._accessibleLabel(row, index),
                })}
                label-sr-only
                aria-invalid=${this._hasErrors(row.color) ? 'true' : nothing}
                data-option-color
                @input=${(event: Event) => this._updateColor(index, event)}
              ></craft-input-color>
            </td>`
          : nothing}
        <td>
          <craft-checkbox
            label-sr-only
            label=${t('Default for {label}', {
              label: this._accessibleLabel(row, index),
            })}
            .checked=${optionBoolean(row.default)}
            ?disabled=${disabled || optgroup}
            data-option-default
            @change=${(event: Event) => this._updateDefault(index, event)}
          ></craft-checkbox>
        </td>
        <td>
          <div class="actions">
            <craft-reorder-button
              position=${this._position(index)}
              label=${t('Reorder {label}', {
                label: this._accessibleLabel(row, index),
              })}
              ?disabled=${disabled}
              @reorder=${(event: CustomEvent<{direction: 'up' | 'down'}>) =>
                this._reorderRow(index, event)}
            ></craft-reorder-button>
            <craft-button
              type="button"
              size="small"
              variant="plain"
              aria-label=${t('Delete {label}', {
                label: this._accessibleLabel(row, index),
              })}
              ?disabled=${disabled}
              data-delete-option
              @click=${() => this._deleteRow(index)}
            >
              ${t('Delete')}
            </craft-button>
          </div>
        </td>
      </tr>
    `;
  }

  private _reconcileRows() {
    const previousKeys = new Map<string, number[]>();

    this._renderedRows.forEach(({key, row}) => {
      const signature = JSON.stringify(row);
      previousKeys.set(signature, [
        ...(previousKeys.get(signature) ?? []),
        key,
      ]);
    });

    this._renderedRows = this.value.map((row) => {
      let key = this._rowKeys.get(row);

      if (key === undefined) {
        key = previousKeys.get(JSON.stringify(row))?.shift();
      }

      if (key === undefined) {
        key = this._nextRowKey++;
      }

      this._rowKeys.set(row, key);

      return {key, row};
    });
  }

  private _rows(): RenderedRow[] {
    if (this._renderedRows.length > 0) {
      return this._renderedRows;
    }

    let key = this._rowKeys.get(this._blankRow);

    if (key === undefined) {
      key = this._nextRowKey++;
      this._rowKeys.set(this._blankRow, key);
    }

    return [{key, row: this._blankRow}];
  }

  private _isOptgroup(row: OptionRow): boolean {
    return Object.hasOwn(row, 'optgroup');
  }

  private _rowDisabled(row: OptionRow): boolean {
    return this.readOnly || optionBoolean(row.disabled);
  }

  private _rowLabel(row: OptionRow): string {
    return this._textValue(this._isOptgroup(row) ? row.optgroup : row.label);
  }

  private _accessibleLabel(row: OptionRow, index: number): string {
    return this._rowLabel(row) || t('option {number}', {number: index + 1});
  }

  private _textValue(value: unknown): string {
    if (typeof value === 'string' || typeof value === 'number') {
      return String(value);
    }

    if (value && typeof value === 'object' && 'value' in value) {
      return this._textValue(value.value);
    }

    return '';
  }

  private _hasErrors(value: unknown): boolean {
    return Boolean(
      value &&
      typeof value === 'object' &&
      'hasErrors' in value &&
      value.hasErrors
    );
  }

  private _inputValue(event: Event): string {
    return String(
      (event.currentTarget as HTMLElementTagNameMap['craft-input']).value ?? ''
    );
  }

  private _checkedValue(event: Event): boolean {
    return (event.currentTarget as CraftCheckbox).checked;
  }

  private _changedRow(row: OptionRow, changes: Partial<OptionRow>): OptionRow {
    const nextRow = {...row, ...changes};
    const key = this._rowKeys.get(row);

    if (key !== undefined) {
      this._rowKeys.set(nextRow, key);
    }

    return nextRow;
  }

  private _updateRow(index: number, changes: Partial<OptionRow>) {
    const current = this._rows()[index]?.row;

    if (!current || this._rowDisabled(current)) {
      return;
    }

    this._updateValue(
      this._rows().map(({row}, rowIndex) =>
        rowIndex === index ? this._changedRow(row, changes) : row
      )
    );
  }

  private _updateLabel(index: number, event: Event) {
    event.stopPropagation();
    const row = this._rows()[index]!.row;
    const label = this._inputValue(event);

    if (this._isOptgroup(row)) {
      this._updateRow(index, {optgroup: label});

      return;
    }

    const currentLabel = this._textValue(row.label);
    const currentValue = this._textValue(row.value);
    const generated =
      currentValue === '' ||
      currentValue === this._generatedValue(currentLabel);

    this._updateRow(index, {
      label,
      ...(generated ? {value: this._generatedValue(label)} : {}),
    });
  }

  private _updateTextValue(index: number, event: Event) {
    event.stopPropagation();
    this._updateRow(index, {value: this._inputValue(event)});
  }

  private _toggleOptgroup(index: number, event: Event) {
    event.stopPropagation();
    const row = this._rows()[index]!.row;

    if (this._rowDisabled(row)) {
      return;
    }

    const label = this._rowLabel(row);
    const nextRow = this._checkedValue(event)
      ? {optgroup: label}
      : {label, value: this._generatedValue(label), default: false};
    const key = this._rowKeys.get(row);

    if (key !== undefined) {
      this._rowKeys.set(nextRow, key);
    }

    this._updateValue(
      this._rows().map(({row: current}, rowIndex) =>
        rowIndex === index ? nextRow : current
      )
    );
  }

  private _updateDefault(index: number, event: Event) {
    event.stopPropagation();
    const current = this._rows()[index]!.row;

    if (this._rowDisabled(current) || this._isOptgroup(current)) {
      return;
    }

    const checked = this._checkedValue(event);
    this._updateValue(
      this._rows().map(({row}, rowIndex) => {
        if (this._isOptgroup(row)) {
          return row;
        }

        if (this.multipleDefaults) {
          return rowIndex === index
            ? this._changedRow(row, {default: checked})
            : row;
        }

        return this._changedRow(row, {
          default: rowIndex === index && checked,
        });
      })
    );
  }

  private _addRow() {
    if (this.readOnly) {
      return;
    }

    this._updateValue([...this.value, {label: '', value: '', default: false}]);
  }

  private _deleteRow(index: number) {
    const row = this._rows()[index]!.row;

    if (this._rowDisabled(row)) {
      return;
    }

    this._updateValue(
      this._rows()
        .filter((_, rowIndex) => rowIndex !== index)
        .map(({row}) => row)
    );
  }

  private _reorderRow(
    index: number,
    event: CustomEvent<{direction: 'up' | 'down'}>
  ) {
    event.stopPropagation();
    const row = this._rows()[index]!.row;

    if (this._rowDisabled(row)) {
      return;
    }

    const destination = event.detail.direction === 'up' ? index - 1 : index + 1;

    if (destination < 0 || destination >= this._rows().length) {
      return;
    }

    const rows = this._rows().map(({row}) => row);
    [rows[index], rows[destination]] = [rows[destination]!, rows[index]!];
    this._updateValue(rows);
  }

  private _updateIcon(index: number, event: CustomEvent<{value: string}>) {
    event.stopPropagation();

    if (typeof event.detail?.value === 'string') {
      this._updateRow(index, {icon: event.detail.value});
    }
  }

  private _updateColor(index: number, event: Event) {
    event.stopPropagation();
    this._updateRow(index, {color: this._inputValue(event)});
  }

  private _updateValue(rows: OptionRow[]) {
    this.value = rows;
    this.dispatchEvent(
      new InputEvent('input', {bubbles: true, composed: true})
    );
  }

  private _syncFormInputs() {
    this.querySelectorAll('[data-option-rows-value]').forEach((input) =>
      input.remove()
    );

    if (!this.name) {
      return;
    }

    const fragment = document.createDocumentFragment();

    this.value.forEach((row, index) => {
      if (this._isOptgroup(row)) {
        this._appendInput(fragment, index, 'isOptgroup', '1');
        this._appendInput(fragment, index, 'label', this._rowLabel(row));

        return;
      }

      this._appendInput(fragment, index, 'label', this._textValue(row.label));
      this._appendInput(fragment, index, 'value', this._textValue(row.value));
      this._appendInput(
        fragment,
        index,
        'default',
        optionBoolean(row.default) ? '1' : ''
      );

      if (this.icons) {
        this._appendInput(fragment, index, 'icon', this._textValue(row.icon));
      }

      if (this.colors) {
        this._appendInput(fragment, index, 'color', this._textValue(row.color));
      }

      if (Object.hasOwn(row, 'disabled')) {
        this._appendInput(
          fragment,
          index,
          'disabled',
          optionBoolean(row.disabled) ? '1' : ''
        );
      }
    });

    this.append(fragment);
  }

  private _appendInput(
    fragment: DocumentFragment,
    index: number,
    property: string,
    value: string
  ) {
    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = `${this.name}[${index}][${property}]`;
    input.value = value;
    input.dataset.optionRowsValue = '';
    fragment.append(input);
  }

  private _position(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) {
      return 'first';
    }

    return index === this._rows().length - 1 ? 'last' : 'middle';
  }

  private _generatedValue(label: string): string {
    const cp = (
      window as unknown as {
        Cp?: {$config?: {get(key: string, defaultValue: string): string}};
      }
    ).Cp;

    return toHandle(label, {
      allowNonAlphaStart: true,
      handleCasing: cp?.$config?.get('handleCasing', 'camel') ?? 'camel',
    });
  }
}

if (!customElements.get('craft-option-rows')) {
  customElements.define('craft-option-rows', CraftOptionRows);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-option-rows': CraftOptionRows;
  }
}
