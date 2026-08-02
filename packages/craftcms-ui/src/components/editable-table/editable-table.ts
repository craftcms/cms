import {css, html, LitElement, nothing, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import {repeat} from 'lit/directives/repeat.js';
import {styleMap} from 'lit/directives/style-map.js';
import {toHandle} from '@src/utilities/string';
import {t} from '@src/utilities/translate';
import type CraftCheckbox from '../checkbox/checkbox.js';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../input-color/input-color.js';
import '../input/input.js';
import '../option-rows/option-rows.js';
import '../reorder-button/reorder-button.js';
import '../select/select.js';
import '../switch/switch.js';
import '../visually-hidden/visually-hidden.js';
import styles from '../table/table.styles.js';
import type CraftOptionRows from '../option-rows/option-rows.js';

export type EditableTableOption = {
  label: string;
  value: string | number | boolean | null;
  default?: boolean;
};

export type EditableTableColumn = {
  key: string;
  label: string;
  type:
    | 'checkbox'
    | 'color'
    | 'date'
    | 'email'
    | 'lightswitch'
    | 'multiline'
    | 'number'
    | 'select'
    | 'text'
    | 'time'
    | 'url';
  width?: string | number;
  class?: string;
  code?: boolean;
  autoPopulate?: string;
  nestedOptions?: boolean;
  options?: EditableTableOption[];
};

export type EditableTableRow = Record<string, unknown>;
export type EditableTableValue =
  | EditableTableRow[]
  | Record<string, EditableTableRow>;

type RenderedRow = {key: string; row: EditableTableRow};

const columnTypes = [
  'checkbox',
  'color',
  'date',
  'email',
  'lightswitch',
  'multiline',
  'number',
  'select',
  'text',
  'time',
  'url',
] as const;

const arrayConverter = {
  fromAttribute(value: string | null): unknown[] {
    return value ? JSON.parse(value) : [];
  },
};

const objectConverter = {
  fromAttribute(value: string | null): Record<string, unknown> {
    return value ? JSON.parse(value) : {};
  },
};

const valueConverter = {
  fromAttribute(value: string | null): EditableTableValue {
    return value ? JSON.parse(value) : [];
  },
};

const editableTableStyles = css`
  table {
    margin-block-end: var(--c-spacing-sm);
  }

  th,
  td {
    vertical-align: top;
  }
`;

function assertColumns(value: unknown): asserts value is EditableTableColumn[] {
  if (!Array.isArray(value)) {
    throw new TypeError('Editable table columns must be a JSON array.');
  }

  value.forEach((column, index) => {
    if (!column || typeof column !== 'object' || Array.isArray(column)) {
      throw new TypeError(`Editable table column ${index} must be an object.`);
    }

    const supported = [
      'key',
      'label',
      'type',
      'width',
      'class',
      'code',
      'autoPopulate',
      'nestedOptions',
      'options',
    ];
    const unsupported = Object.keys(column).find(
      (property) => !supported.includes(property)
    );

    if (unsupported) {
      throw new TypeError(
        `Editable table column ${index} has an unsupported ${unsupported} property.`
      );
    }

    if (
      typeof column.key !== 'string' ||
      typeof column.label !== 'string' ||
      typeof column.type !== 'string'
    ) {
      throw new TypeError(
        `Editable table column ${index} must define string key, label, and type values.`
      );
    }

    if (!(columnTypes as readonly string[]).includes(column.type)) {
      throw new TypeError(
        `Editable table column ${index} does not support the ${column.type} type.`
      );
    }

    if (
      Object.hasOwn(column, 'width') &&
      typeof column.width !== 'string' &&
      typeof column.width !== 'number'
    ) {
      throw new TypeError(
        `Editable table column ${index} has an invalid width.`
      );
    }

    for (const property of ['class', 'autoPopulate'] as const) {
      if (
        Object.hasOwn(column, property) &&
        typeof column[property] !== 'string'
      ) {
        throw new TypeError(
          `Editable table column ${index} has an invalid ${property}.`
        );
      }
    }

    for (const property of ['code', 'nestedOptions'] as const) {
      if (
        Object.hasOwn(column, property) &&
        typeof column[property] !== 'boolean'
      ) {
        throw new TypeError(
          `Editable table column ${index} has an invalid ${property}.`
        );
      }
    }

    if (Object.hasOwn(column, 'options')) {
      assertOptions(column.options, index);
    }
  });
}

function assertOptions(
  value: unknown,
  columnIndex: number
): asserts value is EditableTableOption[] {
  if (!Array.isArray(value)) {
    throw new TypeError(
      `Editable table column ${columnIndex} options must be a JSON array.`
    );
  }

  value.forEach((option, optionIndex) => {
    const scalar =
      (option &&
        typeof option === 'object' &&
        !Array.isArray(option) &&
        ['string', 'number', 'boolean'].includes(typeof option.value)) ||
      (option as {value?: unknown} | null)?.value === null;

    if (
      !option ||
      typeof option !== 'object' ||
      Array.isArray(option) ||
      Object.keys(option).some(
        (property) => !['label', 'value', 'default'].includes(property)
      ) ||
      typeof option.label !== 'string' ||
      !scalar ||
      (Object.hasOwn(option, 'default') && typeof option.default !== 'boolean')
    ) {
      throw new TypeError(
        `Editable table column ${columnIndex} option ${optionIndex} is invalid.`
      );
    }
  });
}

function assertValue(
  value: unknown,
  keyed: boolean
): asserts value is EditableTableValue {
  if (keyed && Array.isArray(value) && value.length === 0) {
    return;
  }

  if (keyed && value && typeof value === 'object' && !Array.isArray(value)) {
    for (const [key, row] of Object.entries(value)) {
      assertRow(row, `Editable table value ${key}`);
    }

    return;
  }

  if (!keyed && Array.isArray(value)) {
    value.forEach((row, index) =>
      assertRow(row, `Editable table row ${index}`)
    );

    return;
  }

  throw new TypeError(
    `Editable table value must be a JSON ${keyed ? 'object' : 'array'}.`
  );
}

function assertRow(
  value: unknown,
  label: string
): asserts value is EditableTableRow {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    throw new TypeError(`${label} must be an object.`);
  }
}

/**
 * @summary An ordered, editable table supporting typed cells and stable row identity.
 *
 * @event input - Emitted when rows or cells change.
 *
 * @since 1.0
 */
export default class CraftEditableTable extends LitElement {
  static override styles = [styles, editableTableStyles];
  private static _instances = new Set<CraftEditableTable>();

  /** Base input name used for nested row values. */
  @property({reflect: true}) name: string | null = null;

  /** Local Form Definition name used to coordinate dependent tables. */
  @property({attribute: false}) sourceName: string | null = null;

  /** Host-owned identity that isolates related table coordination. */
  @property({attribute: false}) coordinationScope: object | null = null;

  /** Current ordered or keyed rows. */
  @property({converter: valueConverter}) value: EditableTableValue = [];

  /** Ordered typed column definitions. */
  @property({converter: arrayConverter}) columns: EditableTableColumn[] = [];

  /** Label shown by the add-row button. */
  @property({attribute: 'add-row-label', reflect: true})
  addRowLabel: string | null = null;

  /** Values merged into newly added rows. */
  @property({attribute: 'default-row', converter: objectConverter})
  defaultRow: EditableTableRow = {};

  /** Stores rows as an object keyed by their stable row keys. */
  @property({reflect: true, type: Boolean}) keyed = false;

  /** Adds and preserves a rowId value for ordered rows. */
  @property({attribute: 'include-row-id', reflect: true, type: Boolean})
  includeRowId = false;

  /** Publishes the edited rows as column definitions for dependent tables. */
  @property({attribute: 'defines-columns', reflect: true, type: Boolean})
  definesColumns = false;

  /** Local name of the editable table that supplies this table's columns. */
  @property({attribute: 'columns-from', reflect: true})
  columnsFrom: string | null = null;

  /** Prevents editing while preserving form submission. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  private _nextRowKey = 0;
  private _hasPublishedColumns = false;
  private _receivedColumns?: EditableTableColumn[];
  private _renderedRows: RenderedRow[] = [];
  private _rowKeys = new WeakMap<EditableTableRow, string>();

  override connectedCallback() {
    super.connectedCallback();
    CraftEditableTable._instances.add(this);

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }

    queueMicrotask(() => this._connectColumns());
  }

  override disconnectedCallback() {
    CraftEditableTable._instances.delete(this);
    super.disconnectedCallback();
  }

  protected override willUpdate(changedProperties: PropertyValues) {
    assertColumns(this.columns);
    assertRow(this.defaultRow, 'Editable table default row');
    assertValue(this.value, this.keyed);

    if (changedProperties.has('value') || changedProperties.has('keyed')) {
      this._reconcileRows();
    }
  }

  protected override updated(changedProperties: PropertyValues) {
    if (this.readOnly) {
      this.setAttribute('aria-readonly', 'true');
    } else {
      this.removeAttribute('aria-readonly');
    }
    this._syncFormInputs();

    if (
      changedProperties.has('columnsFrom') ||
      changedProperties.has('sourceName') ||
      changedProperties.has('coordinationScope') ||
      changedProperties.has('name')
    ) {
      this._connectColumns();
    }

    if (
      this.definesColumns &&
      (changedProperties.has('value') ||
        changedProperties.has('definesColumns') ||
        changedProperties.has('sourceName') ||
        changedProperties.has('name'))
    ) {
      this._publishColumns();
    }
  }

  protected override render() {
    const columns = this._effectiveColumns();

    return html`
      <table>
        <thead>
          <tr>
            ${repeat(
              columns,
              ({key}) => key,
              (column) => html`
                <th scope="col" style=${styleMap(this._cellStyle(column))}>
                  ${column.label}
                </th>
              `
            )}
            <th scope="col">
              <craft-visually-hidden>${t('Actions')}</craft-visually-hidden>
            </th>
          </tr>
        </thead>
        <tbody>
          ${repeat(
            this._renderedRows,
            ({key}) => key,
            (renderedRow, index) => this._rowTemplate(renderedRow, index)
          )}
        </tbody>
      </table>
      <craft-button
        type="button"
        size="small"
        ?disabled=${this.readOnly || columns.length === 0}
        data-add-row
        @click=${this._addRow}
      >
        ${this.addRowLabel ?? t('Add a row')}
      </craft-button>
    `;
  }

  private _rowTemplate(renderedRow: RenderedRow, index: number) {
    const columns = this._effectiveColumns();

    return html`
      <tr data-editable-table-row data-row-key=${renderedRow.key}>
        ${repeat(
          columns,
          ({key}) => key,
          (column) => html`
            <td style=${styleMap(this._cellStyle(column))}>
              ${this._cellTemplate(renderedRow, index, column)}
            </td>
          `
        )}
        <td>
          <div class="actions">
            <craft-reorder-button
              position=${this._position(index)}
              label=${t('Reorder row {number}', {number: index + 1})}
              ?disabled=${this.readOnly}
              @reorder=${(event: CustomEvent<{direction: 'up' | 'down'}>) =>
                this._reorderRow(index, event)}
            ></craft-reorder-button>
            <craft-button
              type="button"
              size="small"
              variant="plain"
              aria-label=${t('Delete row {number}', {number: index + 1})}
              ?disabled=${this.readOnly}
              data-delete-row
              @click=${() => this._deleteRow(index)}
            >
              ${t('Delete')}
            </craft-button>
          </div>
        </td>
      </tr>
      ${this._hasNestedOptions(renderedRow.row)
        ? html`<tr data-table-nested-options>
            <td colspan=${columns.length + 1}>
              <craft-option-rows
                name=${ifDefined(
                  this._inputName(index, renderedRow, 'options')
                )}
                .value=${this._nestedOptions(renderedRow.row)}
                ?readonly=${this.readOnly}
                @input=${(event: Event) =>
                  this._updateNestedOptions(index, event)}
              ></craft-option-rows>
            </td>
          </tr>`
        : nothing}
    `;
  }

  private _cellTemplate(
    renderedRow: RenderedRow,
    index: number,
    column: EditableTableColumn
  ) {
    const name = this._inputName(index, renderedRow, column.key);
    const cell = `${renderedRow.key}:${column.key}`;
    const value = renderedRow.row[column.key];

    if (column.type === 'select') {
      return html`<craft-select
        ?disabled=${this.readOnly}
        data-table-cell=${cell}
      >
        <select
          slot="input"
          name=${ifDefined(name)}
          .value=${this._textValue(value)}
          ?disabled=${this.readOnly}
          aria-label=${column.label}
          @change=${(event: Event) =>
            this._updateCell(index, column, this._selectedValue(event, column))}
        >
          ${(column.options ?? []).map(
            (option) => html`
              <option value=${String(option.value ?? '')}>
                ${option.label}
              </option>
            `
          )}
        </select>
      </craft-select>`;
    }

    if (column.type === 'checkbox') {
      return html`<craft-checkbox
        name=${ifDefined(name)}
        label=${column.label}
        label-sr-only
        .checked=${Boolean(value)}
        ?disabled=${this.readOnly}
        data-table-cell=${cell}
        @change=${(event: Event) =>
          this._updateCell(
            index,
            column,
            (event.currentTarget as CraftCheckbox).checked
          )}
      ></craft-checkbox>`;
    }

    if (column.type === 'lightswitch') {
      return html`<craft-switch
        name=${ifDefined(name)}
        label=${column.label}
        label-sr-only
        .checked=${Boolean(value)}
        ?disabled=${this.readOnly}
        data-table-cell=${cell}
        @change=${(event: Event) =>
          this._updateCell(
            index,
            column,
            (event.currentTarget as HTMLElementTagNameMap['craft-switch'])
              .checked
          )}
      ></craft-switch>`;
    }

    if (column.type === 'multiline') {
      return html`<textarea
        name=${ifDefined(name)}
        .value=${this._textValue(value)}
        ?readonly=${this.readOnly}
        aria-label=${column.label}
        data-table-cell=${cell}
        @input=${(event: Event) =>
          this._updateInputCell(
            index,
            column,
            event,
            (event.currentTarget as HTMLTextAreaElement).value
          )}
      ></textarea>`;
    }

    if (column.type === 'color') {
      return html`<craft-input-color
        name=${ifDefined(name)}
        .modelValue=${this._textValue(value)}
        ?readonly=${this.readOnly}
        label=${column.label}
        label-sr-only
        data-table-cell=${cell}
        @input=${(event: Event) =>
          this._updateInputCell(
            index,
            column,
            event,
            (event.currentTarget as HTMLElementTagNameMap['craft-input-color'])
              .value
          )}
      ></craft-input-color>`;
    }

    return html`<craft-input
      name=${ifDefined(name)}
      type=${this._inputType(column.type)}
      .modelValue=${this._textValue(value)}
      ?readonly=${this.readOnly}
      class=${[column.class, column.code ? 'code' : '']
        .filter(Boolean)
        .join(' ')}
      label=${column.label}
      label-sr-only
      data-table-cell=${cell}
      @input=${(event: Event) =>
        this._updateInputCell(
          index,
          column,
          event,
          (event.currentTarget as HTMLElementTagNameMap['craft-input']).value
        )}
    ></craft-input>`;
  }

  private _reconcileRows() {
    if (this.keyed && !Array.isArray(this.value)) {
      this._renderedRows = Object.entries(this.value).map(([key, row]) => ({
        key,
        row,
      }));

      return;
    }

    const rows = Array.isArray(this.value) ? this.value : [];
    const previousKeys = new Map<string, string[]>();

    this._renderedRows.forEach(({key, row}) => {
      const signature = JSON.stringify(row);
      previousKeys.set(signature, [
        ...(previousKeys.get(signature) ?? []),
        key,
      ]);
    });

    this._renderedRows = rows.map((row) => {
      let key =
        typeof row.rowId === 'string' && row.rowId !== ''
          ? row.rowId
          : this._rowKeys.get(row);

      key ??= previousKeys.get(JSON.stringify(row))?.shift();
      key ??= `row-${this._nextRowKey++}`;
      this._rowKeys.set(row, key);

      return {key, row};
    });
  }

  private _effectiveColumns(): EditableTableColumn[] {
    return this._receivedColumns ?? this.columns;
  }

  private _cellStyle(column: EditableTableColumn): Record<string, string> {
    if (column.width === undefined) {
      return {};
    }

    return {
      width:
        typeof column.width === 'number' ? `${column.width}px` : column.width,
    };
  }

  private _inputName(
    index: number,
    renderedRow: RenderedRow,
    property: string
  ): string | undefined {
    if (!this.name) {
      return undefined;
    }

    const rowKey = this.keyed ? renderedRow.key : index;

    return `${this.name}[${rowKey}][${property}]`;
  }

  private _inputType(type: string): string {
    return ['date', 'email', 'number', 'time', 'url'].includes(type)
      ? type
      : 'text';
  }

  private _selectedValue(
    event: Event,
    column: EditableTableColumn
  ): EditableTableOption['value'] {
    event.stopPropagation();
    const selected = (event.currentTarget as HTMLSelectElement).value;
    const option = column.options?.find(
      ({value}) => String(value ?? '') === selected
    );

    return option?.value ?? null;
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

  private _changedRow(
    renderedRow: RenderedRow,
    changes: EditableTableRow
  ): EditableTableRow {
    const row = {...renderedRow.row, ...changes};

    if (this.includeRowId && !row.rowId) {
      row.rowId = renderedRow.key;
    }

    this._rowKeys.set(row, renderedRow.key);

    return row;
  }

  private _updateRow(index: number, changes: EditableTableRow) {
    if (this.readOnly) {
      return;
    }

    this._updateValue(
      this._renderedRows.map((renderedRow, rowIndex) =>
        rowIndex === index
          ? {renderedRow, row: this._changedRow(renderedRow, changes)}
          : {renderedRow, row: renderedRow.row}
      )
    );
  }

  private _updateCell(
    index: number,
    column: EditableTableColumn,
    value: unknown
  ) {
    const renderedRow = this._renderedRows[index];

    if (!renderedRow || this.readOnly) {
      return;
    }

    const changes: EditableTableRow = {[column.key]: value};

    if (column.autoPopulate) {
      const currentSource = this._textValue(renderedRow.row[column.key]);
      const currentTarget = this._textValue(
        renderedRow.row[column.autoPopulate]
      );

      if (
        currentTarget === '' ||
        currentTarget === this._generatedValue(currentSource)
      ) {
        changes[column.autoPopulate] = this._generatedValue(
          this._textValue(value)
        );
      }
    }

    this._updateRow(index, changes);
  }

  private _updateInputCell(
    index: number,
    column: EditableTableColumn,
    event: Event,
    value: unknown
  ) {
    event.stopPropagation();
    this._updateCell(index, column, value);
  }

  private _nestedOptions(row: EditableTableRow): EditableTableRow[] {
    return Array.isArray(row.options)
      ? (row.options as EditableTableRow[])
      : [];
  }

  private _hasNestedOptions(row: EditableTableRow): boolean {
    return this._effectiveColumns().some(
      (column) =>
        column.nestedOptions === true &&
        this._textValue(row[column.key]) === 'select'
    );
  }

  private _updateNestedOptions(index: number, event: Event) {
    event.stopPropagation();
    this._updateRow(index, {
      options: (event.currentTarget as CraftOptionRows).value,
    });
  }

  private _newRow(): EditableTableRow {
    const row = Object.fromEntries(
      this._effectiveColumns().map((column) => [
        column.key,
        this._defaultValue(column),
      ])
    );

    Object.assign(row, this.defaultRow);

    if (this.includeRowId) {
      row.rowId = crypto.randomUUID();
    }

    return row;
  }

  private _defaultValue(column: EditableTableColumn) {
    if (column.type === 'checkbox' || column.type === 'lightswitch') {
      return false;
    }

    if (column.type === 'select') {
      return column.options?.find((option) => option.default)?.value ?? '';
    }

    return '';
  }

  private _addRow() {
    if (this.readOnly || this._effectiveColumns().length === 0) {
      return;
    }

    const row = this._newRow();
    const key = this.keyed ? this._nextKey() : this._rowIdentity(row);

    this._updateValue([
      ...this._renderedRows.map((renderedRow) => ({
        renderedRow,
        row: renderedRow.row,
      })),
      {renderedRow: {key, row}, row},
    ]);
  }

  private _deleteRow(index: number) {
    if (this.readOnly) {
      return;
    }

    this._updateValue(
      this._renderedRows
        .filter((_, rowIndex) => rowIndex !== index)
        .map((renderedRow) => ({renderedRow, row: renderedRow.row}))
    );
  }

  private _reorderRow(
    index: number,
    event: CustomEvent<{direction: 'up' | 'down'}>
  ) {
    event.stopPropagation();

    if (this.readOnly) {
      return;
    }

    const destination = event.detail.direction === 'up' ? index - 1 : index + 1;

    if (destination < 0 || destination >= this._renderedRows.length) {
      return;
    }

    const rows = this._renderedRows.map((renderedRow) => ({
      renderedRow,
      row: renderedRow.row,
    }));
    [rows[index], rows[destination]] = [rows[destination]!, rows[index]!];
    this._updateValue(rows);
  }

  private _position(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) {
      return 'first';
    }

    return index === this._renderedRows.length - 1 ? 'last' : 'middle';
  }

  private _rowIdentity(row: EditableTableRow): string {
    if (typeof row.rowId === 'string' && row.rowId !== '') {
      return row.rowId;
    }

    let key = this._rowKeys.get(row);

    if (!key) {
      key = `row-${this._nextRowKey++}`;
      this._rowKeys.set(row, key);
    }

    return key;
  }

  private _nextKey(): string {
    let index = 1;

    while (this._renderedRows.some(({key}) => key === `new${index}`)) {
      index++;
    }

    return `new${index}`;
  }

  private _updateValue(
    rows: Array<{renderedRow: RenderedRow; row: EditableTableRow}>
  ) {
    rows.forEach(({renderedRow, row}) =>
      this._rowKeys.set(row, renderedRow.key)
    );
    this.value = this.keyed
      ? Object.fromEntries(
          rows.map(({renderedRow, row}) => [renderedRow.key, row])
        )
      : rows.map(({row}) => row);
    this.dispatchEvent(
      new InputEvent('input', {bubbles: true, composed: true})
    );
  }

  private _generatedValue(value: string): string {
    const cp = (
      window as unknown as {
        Cp?: {$config?: {get(key: string, defaultValue: string): string}};
      }
    ).Cp;

    return toHandle(value, {
      allowNonAlphaStart: true,
      handleCasing: cp?.$config?.get('handleCasing', 'camel') ?? 'camel',
    });
  }

  private _publishedColumns(): EditableTableColumn[] {
    return this._renderedRows.map(({key, row}) => {
      const configuredType = this._textValue(row.type);
      const type =
        configuredType === 'heading' || configuredType === 'singleline'
          ? 'text'
          : configuredType;
      const width = this._textValue(row.width);
      const options = Array.isArray(row.options)
        ? (row.options as EditableTableOption[])
        : undefined;

      return {
        key,
        label:
          this._textValue(row.heading) || this._textValue(row.handle) || key,
        type: type as EditableTableColumn['type'],
        ...(width ? {width} : {}),
        ...(options ? {options} : {}),
        ...(configuredType === 'heading' ? {class: 'heading'} : {}),
      };
    });
  }

  private _scope(): object {
    return this.coordinationScope ?? this.closest('form') ?? this.getRootNode();
  }

  private _connectColumns() {
    if (!this.columnsFrom) {
      this._receivedColumns = undefined;

      return;
    }

    const source = Array.from(CraftEditableTable._instances).find(
      (table) =>
        table !== this &&
        table.definesColumns &&
        table._scope() === this._scope() &&
        (table.sourceName ?? table.name) === this.columnsFrom
    );

    if (source) {
      const columns = source._publishedColumns();

      if (columns.length > 0) {
        this._receiveColumns(columns);

        return;
      }
    }

    if (this._receivedColumns) {
      this._receivedColumns = undefined;
      this.requestUpdate();
    }
  }

  private _publishColumns() {
    const sourceName = this.sourceName ?? this.name;

    if (!sourceName) {
      return;
    }

    const columns = this._publishedColumns();

    if (columns.length === 0 && !this._hasPublishedColumns) {
      return;
    }

    this._hasPublishedColumns = true;

    CraftEditableTable._instances.forEach((table) => {
      if (
        table !== this &&
        table.columnsFrom === sourceName &&
        table._scope() === this._scope()
      ) {
        table._receiveColumns(columns);
      }
    });
  }

  private _receiveColumns(columns: EditableTableColumn[]) {
    assertColumns(columns);

    if (JSON.stringify(this._receivedColumns) === JSON.stringify(columns)) {
      return;
    }

    this._receivedColumns = columns;
    this.requestUpdate();
    this._syncRowsToColumns(columns);
  }

  private _syncRowsToColumns(columns: EditableTableColumn[]) {
    if (this.readOnly || this.keyed || this._renderedRows.length === 0) {
      return;
    }

    if (columns.length === 0) {
      this._updateValue([]);

      return;
    }

    const rows = this._renderedRows.map((renderedRow) => ({
      renderedRow,
      row: {
        ...(this.includeRowId
          ? {
              rowId: this._textValue(renderedRow.row.rowId) || renderedRow.key,
            }
          : {}),
        ...Object.fromEntries(
          columns.map((column) => [
            column.key,
            Object.hasOwn(renderedRow.row, column.key)
              ? renderedRow.row[column.key]
              : this._defaultValue(column),
          ])
        ),
      },
    }));
    const value = rows.map(({row}) => row);

    if (JSON.stringify(value) !== JSON.stringify(this.value)) {
      this._updateValue(rows);
    }
  }

  private _syncFormInputs() {
    this.querySelectorAll('[data-editable-table-value]').forEach((input) =>
      input.remove()
    );

    if (!this.name) {
      return;
    }

    const fragment = document.createDocumentFragment();

    this._renderedRows.forEach((renderedRow, index) => {
      const rowKey = this.keyed ? renderedRow.key : index;
      const row = this.includeRowId
        ? {
            ...renderedRow.row,
            rowId: this._textValue(renderedRow.row.rowId) || renderedRow.key,
          }
        : renderedRow.row;

      for (const [property, value] of Object.entries(row)) {
        this._appendFormValue(
          fragment,
          `${this.name}[${rowKey}][${property}]`,
          value
        );
      }
    });

    this.append(fragment);
  }

  private _appendFormValue(
    fragment: DocumentFragment,
    name: string,
    value: unknown
  ) {
    if (Array.isArray(value)) {
      value.forEach((item, index) =>
        this._appendFormValue(fragment, `${name}[${index}]`, item)
      );

      return;
    }

    if (value && typeof value === 'object') {
      Object.entries(value).forEach(([property, item]) =>
        this._appendFormValue(fragment, `${name}[${property}]`, item)
      );

      return;
    }

    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = name;
    input.value =
      typeof value === 'boolean' ? (value ? '1' : '') : String(value ?? '');
    input.dataset.editableTableValue = '';
    fragment.append(input);
  }
}

if (!customElements.get('craft-editable-table')) {
  customElements.define('craft-editable-table', CraftEditableTable);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-editable-table': CraftEditableTable;
  }
}
