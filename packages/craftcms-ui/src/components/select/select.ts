import styles from './select.styles.js';
import {LionSelect} from '@lion/ui/select.js';
import {html, type PropertyValues} from 'lit';
import '../option/option.js';
import '../icon/icon.js';
import {property} from 'lit/decorators.js';

export interface SelectOption {
  label: string;
  value: string | number | boolean | null;
  disabled?: boolean;
  hidden?: boolean;
  data?: Record<string, unknown>;
}

export interface SelectOptgroup {
  type: 'optgroup';
  label: string;
  options: SelectOption[];
  disabled?: boolean;
}

export type SelectItem = SelectOption | SelectOptgroup;

export default class CraftSelect extends LionSelect {
  static override get styles() {
    return [...super.styles, styles];
  }

  @property({reflect: true, type: Boolean}) small = false;

  /** Options used when no server-rendered native options are available. */
  @property({attribute: false}) options?: SelectItem[];

  private _optionsSignature?: string;

  override get _inputNode(): HTMLSelectElement {
    const existing = Array.from(this.children).find(
      (child): child is HTMLSelectElement =>
        child instanceof HTMLSelectElement && child.slot === 'input'
    );

    if (existing) {
      return existing;
    }

    const select = document.createElement('select');
    select.slot = 'input';
    this.append(select);

    return select;
  }

  override connectedCallback() {
    this._renderOptions();
    super.connectedCallback();
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('options')) {
      this._renderOptions();
    }
  }

  override _syncValueUpwards(): void {
    if (this.options && !this._isHandlingUserInput) {
      return;
    }

    super._syncValueUpwards();
  }

  override _reflectBackFormattedValueToUser(): void {
    if (this._reflectBackOn()) {
      this.value =
        this.modelValue === undefined ? '' : this._htmlValue(this.modelValue);
    }
  }

  // oxlint-disable-next-line class-methods-use-this
  override _inputGroupInputTemplate() {
    return html`
      <div class="input-group__input">
        <slot name="input"></slot>
        <craft-icon
          class="indicator"
          name="chevron-down"
          style="font-size: 0.8em"
        ></craft-icon>
      </div>
    `;
  }

  private _renderOptions(): void {
    if (!this.options) {
      return;
    }

    const signature = JSON.stringify(this.options);

    if (signature === this._optionsSignature) {
      return;
    }

    this._optionsSignature = signature;

    this._inputNode.replaceChildren(
      ...this.options.map((item) => this._option(item))
    );

    if (this.modelValue !== undefined) {
      const value = this._htmlValue(this.modelValue);
      this._inputNode.value = value;
      const selected = Array.from(this._inputNode.options).find(
        (option) => option.value === value
      );

      if (selected) {
        selected.selected = true;
      }
    }
  }

  private _option(item: SelectItem): HTMLOptionElement | HTMLOptGroupElement {
    if (this._isOptgroup(item)) {
      const group = document.createElement('optgroup');
      group.label = item.label;
      group.disabled = item.disabled ?? false;
      group.append(...item.options.map((option) => this._option(option)));

      return group;
    }

    const option = document.createElement('option');
    option.textContent = item.label;
    option.value = this._htmlValue(item.value);
    option.disabled = item.disabled ?? false;
    option.hidden = item.hidden ?? false;

    for (const [name, value] of Object.entries(item.data ?? {})) {
      if (value !== null && value !== undefined) {
        option.setAttribute(
          `data-${name}`,
          typeof value === 'object' ? JSON.stringify(value) : String(value)
        );
      }
    }

    return option;
  }

  private _isOptgroup(item: SelectItem): item is SelectOptgroup {
    return 'type' in item && item.type === 'optgroup';
  }

  private _htmlValue(value: unknown): string {
    if (value === true) {
      return '1';
    }

    return value === false || value === null ? '' : String(value);
  }
}

if (!customElements.get('craft-select')) {
  customElements.define('craft-select', CraftSelect);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select': CraftSelect;
    // 'craft-select-invoker': CraftSelectInvoker;
  }
}
