import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {repeat} from 'lit/directives/repeat.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import {t} from '@src/utilities/translate';
import '../input/input.js';
import '../visually-hidden/visually-hidden.js';
import styles from '../table/table.styles.js';

export type KeyedTableColumn = {
  key: string;
  label: string;
  placeholder?: string;
  code?: boolean;
};

export type KeyedTableRow = {
  key: string;
  label: string;
};

export type KeyedTableValue = Record<string, Record<string, unknown>>;

const arrayConverter = {
  fromAttribute(value: string | null): unknown[] {
    return value ? JSON.parse(value) : [];
  },
};

const valueConverter = {
  fromAttribute(value: string | null): KeyedTableValue {
    return value ? JSON.parse(value) : {};
  },
};

function assertColumns(value: unknown): asserts value is KeyedTableColumn[] {
  if (!Array.isArray(value)) {
    throw new TypeError('Keyed table columns must be a JSON array.');
  }

  value.forEach((column, index) => {
    if (!column || typeof column !== 'object' || Array.isArray(column)) {
      throw new TypeError(`Keyed table column ${index} must be an object.`);
    }

    const properties = Object.keys(column);
    const unsupported = properties.find(
      (property) => !['key', 'label', 'placeholder', 'code'].includes(property)
    );

    if (unsupported) {
      throw new TypeError(
        `Keyed table column ${index} has an unsupported ${unsupported} property.`
      );
    }

    if (typeof column.key !== 'string' || typeof column.label !== 'string') {
      throw new TypeError(
        `Keyed table column ${index} must define string key and label values.`
      );
    }

    if (
      Object.hasOwn(column, 'placeholder') &&
      typeof column.placeholder !== 'string'
    ) {
      throw new TypeError(
        `Keyed table column ${index} has an invalid placeholder.`
      );
    }

    if (Object.hasOwn(column, 'code') && typeof column.code !== 'boolean') {
      throw new TypeError(`Keyed table column ${index} has an invalid code.`);
    }
  });
}

function assertRows(value: unknown): asserts value is KeyedTableRow[] {
  if (!Array.isArray(value)) {
    throw new TypeError('Keyed table rows must be a JSON array.');
  }

  value.forEach((row, index) => {
    if (
      !row ||
      typeof row !== 'object' ||
      Array.isArray(row) ||
      Object.keys(row).some(
        (property) => !['key', 'label'].includes(property)
      ) ||
      typeof row.key !== 'string' ||
      typeof row.label !== 'string'
    ) {
      throw new TypeError(
        `Keyed table row ${index} must define only string key and label values.`
      );
    }
  });
}

function assertValue(value: unknown): asserts value is KeyedTableValue {
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    throw new TypeError('Keyed table value must be a JSON object.');
  }

  for (const [key, row] of Object.entries(value)) {
    if (!row || typeof row !== 'object' || Array.isArray(row)) {
      throw new TypeError(`Keyed table value ${key} must be an object.`);
    }
  }
}

/**
 * @summary A fixed-row, fixed-column table editor keyed by stable row and column identifiers.
 *
 * @event model-value-changed - Emitted when a cell value changes.
 *
 * @since 1.0
 */
export default class CraftKeyedTable extends LitElement {
  static override styles = [styles];

  /** Base input name used for nested cell values. */
  @property({reflect: true}) name: string | null = null;

  /** Current values keyed by row and then column. */
  @property({attribute: 'value', converter: valueConverter})
  modelValue: KeyedTableValue = {};

  /** Ordered column definitions. */
  @property({converter: arrayConverter}) columns: KeyedTableColumn[] = [];

  /** Ordered row definitions. */
  @property({converter: arrayConverter}) rows: KeyedTableRow[] = [];

  /** Prevents editing while preserving form submission. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }
  }

  protected override willUpdate() {
    assertColumns(this.columns);
    assertRows(this.rows);
    assertValue(this.modelValue);
  }

  protected override updated() {
    if (this.readOnly) {
      this.setAttribute('aria-readonly', 'true');
    } else {
      this.removeAttribute('aria-readonly');
    }
    this._syncFormInputs();
  }

  protected override render() {
    return html`
      <table>
        <thead>
          <tr>
            <th scope="col">
              <craft-visually-hidden>${t('Rows')}</craft-visually-hidden>
            </th>
            ${repeat(
              this.columns,
              ({key}) => key,
              (column) => html`<th scope="col">${column.label}</th>`
            )}
          </tr>
        </thead>
        <tbody>
          ${repeat(
            this.rows,
            ({key}) => key,
            (row) => html`
              <tr data-keyed-table-row data-row-key=${row.key}>
                <th scope="row">${row.label}</th>
                ${repeat(
                  this.columns,
                  ({key}) => key,
                  (column) => html`
                    <td>
                      <craft-input
                        data-keyed-table-cell=${`${row.key}:${column.key}`}
                        name=${ifDefined(this._inputName(row, column))}
                        .modelValue=${this._cellValue(row, column)}
                        placeholder=${ifDefined(column.placeholder)}
                        label=${column.label}
                        label-sr-only
                        class=${column.code ? 'code' : ''}
                        ?readonly=${this.readOnly}
                        @input=${(event: Event) =>
                          this._update(row, column, event)}
                      ></craft-input>
                    </td>
                  `
                )}
              </tr>
            `
          )}
        </tbody>
      </table>
    `;
  }

  private _cellValue(row: KeyedTableRow, column: KeyedTableColumn): string {
    return String(this.modelValue[row.key]?.[column.key] ?? '');
  }

  private _inputName(
    row: KeyedTableRow,
    column: KeyedTableColumn
  ): string | undefined {
    return this.name ? `${this.name}[${row.key}][${column.key}]` : undefined;
  }

  private _update(row: KeyedTableRow, column: KeyedTableColumn, event: Event) {
    event.stopPropagation();

    if (this.readOnly) {
      return;
    }

    this.modelValue = {
      ...this.modelValue,
      [row.key]: {
        ...this.modelValue[row.key],
        [column.key]: (
          event.currentTarget as HTMLElementTagNameMap['craft-input']
        ).value,
      },
    };
    this.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
  }

  private _syncFormInputs() {
    this.querySelectorAll('[data-keyed-table-value]').forEach((input) =>
      input.remove()
    );

    if (!this.name) {
      return;
    }

    const fragment = document.createDocumentFragment();

    this.rows.forEach((row) => {
      this.columns.forEach((column) => {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = `${this.name}[${row.key}][${column.key}]`;
        input.value = this._cellValue(row, column);
        input.dataset.keyedTableValue = '';
        fragment.append(input);
      });
    });

    this.append(fragment);
  }
}

if (!customElements.get('craft-keyed-table')) {
  customElements.define('craft-keyed-table', CraftKeyedTable);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-keyed-table': CraftKeyedTable;
  }
}
