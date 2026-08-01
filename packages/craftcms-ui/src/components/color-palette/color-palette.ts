import {html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {t} from '@src/utilities/translate';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../input-color/input-color.js';
import '../input/input.js';
import '../reorder-button/reorder-button.js';
import '../visually-hidden/visually-hidden.js';
import styles from './color-palette.styles.js';

export type ColorPaletteRow = {
  color: string | null;
  label: string | null;
  default: boolean;
};

const valueConverter = {
  fromAttribute(value: string | null): ColorPaletteRow[] {
    if (!value) {
      return [];
    }

    const rows: unknown = JSON.parse(value);

    if (!Array.isArray(rows)) {
      throw new TypeError('Color palette value must be a JSON array.');
    }

    return rows;
  },
};

/**
 * @summary An ordered editor for labeled color palette rows.
 *
 * @since 1.0
 */
export default class CraftColorPalette extends LitElement {
  static override styles = [styles];

  /** Base input name used for nested palette row values. */
  @property({reflect: true}) name: string | null = null;

  /** Ordered palette rows. */
  @property({converter: valueConverter}) value: ColorPaletteRow[] = [];

  /** Prevents editing while preserving form submission. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('name') || changedProperties.has('value')) {
      this._syncFormInputs();
    }
  }

  protected override render() {
    return html`
      <table>
        <thead>
          <tr>
            <th>${t('Color')}</th>
            <th>${t('Label')}</th>
            <th>${t('Default')}</th>
            <th>
              <craft-visually-hidden>${t('Actions')}</craft-visually-hidden>
            </th>
          </tr>
        </thead>
        <tbody>
          ${this.value.map(
            (row, index) => html`
              <tr data-palette-row=${index}>
                <td>
                  <craft-input-color
                    .modelValue=${(row.color ?? '').replace(/^#/, '')}
                    ?disabled=${this.readOnly}
                    aria-label=${t('Color for {label}', {
                      label: this._rowLabel(row, index),
                    })}
                    data-palette-color=${index}
                    @input=${(event: Event) =>
                      this._updateRow(index, 'color', event)}
                  ></craft-input-color>
                </td>
                <td>
                  <craft-input
                    .modelValue=${row.label ?? ''}
                    ?disabled=${this.readOnly}
                    aria-label=${t('Label for {label}', {
                      label: this._rowLabel(row, index),
                    })}
                    data-palette-label=${index}
                    @input=${(event: Event) =>
                      this._updateRow(index, 'label', event)}
                  ></craft-input>
                </td>
                <td>
                  <craft-checkbox
                    label=${t('Default for {label}', {
                      label: this._rowLabel(row, index),
                    })}
                    label-sr-only
                    .checked=${row.default}
                    ?disabled=${this.readOnly}
                    data-palette-default=${index}
                    @change=${(event: Event) => this._setDefault(index, event)}
                  ></craft-checkbox>
                </td>
                <td>
                  <craft-reorder-button
                    position=${this._position(index)}
                    label=${t('Reorder {label}', {
                      label: this._rowLabel(row, index),
                    })}
                    ?disabled=${this.readOnly}
                    @reorder=${(event: CustomEvent) =>
                      this._reorder(index, event)}
                  ></craft-reorder-button>
                  <craft-button
                    type="button"
                    variant="plain"
                    aria-label=${t('Delete {label}', {
                      label: this._rowLabel(row, index),
                    })}
                    ?disabled=${this.readOnly}
                    @click=${() => this._deleteRow(index)}
                  >
                    ${t('Delete')}
                  </craft-button>
                </td>
              </tr>
            `
          )}
        </tbody>
      </table>
      <craft-button
        type="button"
        ?disabled=${this.readOnly}
        @click=${this._addRow}
      >
        ${t('Add a color')}
      </craft-button>
    `;
  }

  private _updateRow(index: number, property: 'color' | 'label', event: Event) {
    event.stopPropagation();
    const value = (event.target as unknown as {value: string}).value;

    this._updateValue((rows) => {
      rows[index]![property] =
        property === 'color' && value ? `#${value}` : value;
    });
  }

  private _setDefault(index: number, event: Event) {
    event.stopPropagation();
    this._updateValue((rows) => {
      rows.forEach((row, rowIndex) => {
        row.default = rowIndex === index;
      });
    });
  }

  private _reorder(index: number, event: CustomEvent) {
    event.stopPropagation();
    const offset = event.detail.direction === 'up' ? -1 : 1;
    const targetIndex = index + offset;

    if (targetIndex < 0 || targetIndex >= this.value.length) {
      return;
    }

    this._updateValue((rows) => {
      const [row] = rows.splice(index, 1);
      rows.splice(targetIndex, 0, row!);
    });
  }

  private _addRow() {
    this._updateValue((rows) => {
      rows.push({color: null, label: null, default: false});
    });
  }

  private _deleteRow(index: number) {
    this._updateValue((rows) => {
      rows.splice(index, 1);
    });
  }

  private _updateValue(update: (rows: ColorPaletteRow[]) => void) {
    const rows = this.value.map((row) => ({...row}));

    update(rows);
    this.value = rows;
    this.dispatchEvent(
      new InputEvent('input', {bubbles: true, composed: true})
    );
  }

  private _syncFormInputs() {
    this.querySelectorAll('[data-color-palette-value]').forEach((input) =>
      input.remove()
    );

    if (!this.name) {
      return;
    }

    const fragment = document.createDocumentFragment();

    this.value.forEach((row, index) => {
      for (const [property, value] of Object.entries(row)) {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = `${this.name}[${index}][${property}]`;
        input.value =
          property === 'default' ? (value ? '1' : '') : String(value ?? '');
        input.dataset.colorPaletteValue = '';
        fragment.append(input);
      }
    });

    this.append(fragment);
  }

  private _position(index: number): 'first' | 'middle' | 'last' {
    if (index === 0) {
      return 'first';
    }

    return index === this.value.length - 1 ? 'last' : 'middle';
  }

  private _rowLabel(row: ColorPaletteRow, index: number): string {
    return row.label || row.color || t('color {number}', {number: index + 1});
  }
}

if (!customElements.get('craft-color-palette')) {
  customElements.define('craft-color-palette', CraftColorPalette);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-color-palette': CraftColorPalette;
  }
}
