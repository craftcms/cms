import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import {repeat} from 'lit/directives/repeat.js';
import {t} from '@src/utilities/translate';
import type {
  ReorderDirection,
  ReorderPosition,
} from '../reorder-button/reorder-button.js';
import '../button/button.js';
import '../reorder-button/reorder-button.js';
import styles from './object-select.styles.js';

export type ObjectSelectOption = {
  key: string;
  label: string;
  value: unknown;
};

const arrayConverter = {
  fromAttribute(value: string | null): unknown[] {
    return value ? JSON.parse(value) : [];
  },
};

/**
 * @summary An ordered selector for objects supplied by its host.
 *
 * @event model-value-changed - Emitted when the selected objects change.
 *
 * @since 1.0
 */
export default class CraftObjectSelect extends LitElement {
  static override styles = [styles];

  /** Local input name supplied by the form host. */
  @property({reflect: true}) name: string | null = null;

  /** Selected objects. */
  @property({attribute: 'value', converter: arrayConverter})
  modelValue: unknown[] = [];

  /** Objects available for selection. */
  @property({converter: arrayConverter}) options: ObjectSelectOption[] = [];

  /** Property used to identify object values. An empty string identifies scalar values directly. */
  @property({attribute: 'identity-key', reflect: true}) identityKey = '';

  /** Prevents selection changes. */
  @property({attribute: 'readonly', reflect: true, type: Boolean})
  readOnly = false;

  @state() private selectedKey = '';

  override connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
    }
  }

  protected override willUpdate(changed: PropertyValues<this>) {
    super.willUpdate(changed);

    if (
      (changed.has('options') || changed.has('modelValue')) &&
      !this.availableOptions.some(({key}) => key === this.selectedKey)
    ) {
      this.selectedKey = this.availableOptions[0]?.key ?? '';
    }
  }

  protected override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    if (changed.has('readOnly')) {
      if (this.readOnly) {
        this.setAttribute('aria-readonly', 'true');
      } else {
        this.removeAttribute('aria-readonly');
      }
    }

    if (changed.has('name') || changed.has('modelValue')) {
      this.syncFormInputs();
    }
  }

  private get availableOptions(): ObjectSelectOption[] {
    return this.options.filter(
      (option) =>
        !this.modelValue.some((value) => this.identity(value) === option.key)
    );
  }

  private identity(value: unknown): string {
    if (this.identityKey === '') {
      return String(value ?? '');
    }

    if (value && typeof value === 'object') {
      return String((value as Record<string, unknown>)[this.identityKey] ?? '');
    }

    return '';
  }

  private label(value: unknown): string {
    if (value && typeof value === 'object') {
      const name = (value as Record<string, unknown>).name;

      if (typeof name === 'string') {
        return name;
      }
    }

    const identity = this.identity(value);

    return this.options.find(({key}) => key === identity)?.label ?? identity;
  }

  private add() {
    if (this.readOnly) {
      return;
    }

    const option = this.availableOptions.find(
      ({key}) => key === this.selectedKey
    );

    if (!option) {
      return;
    }

    this.updateValue([...this.modelValue, option.value]);
  }

  private removeSelection(index: number) {
    if (this.readOnly) {
      return;
    }

    this.updateValue(
      this.modelValue.filter((_, valueIndex) => valueIndex !== index)
    );
  }

  private reorder(index: number, direction: ReorderDirection) {
    if (this.readOnly) {
      return;
    }

    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= this.modelValue.length) {
      return;
    }

    const value = [...this.modelValue];
    [value[index], value[targetIndex]] = [value[targetIndex], value[index]];
    this.updateValue(value);
  }

  private updateValue(value: unknown[]) {
    this.modelValue = value;
    this.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
  }

  private syncFormInputs() {
    this.querySelectorAll('[data-object-select-value]').forEach((input) =>
      input.remove()
    );

    if (!this.name) {
      return;
    }

    const fragment = document.createDocumentFragment();

    this.modelValue.forEach((value, index) => {
      this.appendFormValue(fragment, `${this.name}[${index}]`, value);
    });
    this.append(fragment);
  }

  private appendFormValue(
    fragment: DocumentFragment,
    name: string,
    value: unknown
  ) {
    if (Array.isArray(value)) {
      value.forEach((item, index) => {
        this.appendFormValue(fragment, `${name}[${index}]`, item);
      });

      return;
    }

    if (value && typeof value === 'object') {
      Object.entries(value).forEach(([key, item]) => {
        this.appendFormValue(fragment, `${name}[${key}]`, item);
      });

      return;
    }

    const input = document.createElement('input');

    input.type = 'hidden';
    input.name = name;
    input.value =
      typeof value === 'boolean' ? (value ? '1' : '0') : String(value ?? '');
    input.dataset.objectSelectValue = '';
    fragment.append(input);
  }

  private reorderPosition(index: number): ReorderPosition {
    if (index === 0) {
      return 'first';
    }

    if (index === this.modelValue.length - 1) {
      return 'last';
    }

    return 'middle';
  }

  protected override render() {
    const availableOptions = this.availableOptions;

    return html`
      ${repeat(
        this.modelValue,
        (value) => this.identity(value),
        (value, index) => html`
          <div data-object-select-row="${this.identity(value)}">
            <craft-reorder-button
              ?disabled="${this.readOnly || this.modelValue.length < 2}"
              position="${this.reorderPosition(index)}"
              @reorder="${(event: CustomEvent<{direction: ReorderDirection}>) =>
                this.reorder(index, event.detail.direction)}"
            ></craft-reorder-button>
            <span>${this.label(value)}</span>
            <craft-button
              type="button"
              size="small"
              variant="plain"
              ?disabled="${this.readOnly}"
              aria-label="${t('Remove {label}', {label: this.label(value)})}"
              data-object-select-remove
              @activate="${() => this.removeSelection(index)}"
            >
              ${t('Remove')}
            </craft-button>
          </div>
        `
      )}
      ${availableOptions.length
        ? html`
            <div data-object-select-controls>
              <select
                .value="${this.selectedKey}"
                aria-label="${t('Available options')}"
                ?disabled="${this.readOnly}"
                data-object-select-available
                @change="${(event: Event) => {
                  this.selectedKey = (
                    event.currentTarget as HTMLSelectElement
                  ).value;
                }}"
              >
                ${availableOptions.map(
                  (option) => html`
                    <option value="${option.key}">${option.label}</option>
                  `
                )}
              </select>
              <craft-button
                type="button"
                variant="dashed"
                ?disabled="${this.readOnly}"
                data-object-select-add
                @activate="${this.add}"
              >
                ${t('Add')}
              </craft-button>
            </div>
          `
        : nothing}
    `;
  }
}

if (!customElements.get('craft-object-select')) {
  customElements.define('craft-object-select', CraftObjectSelect);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-object-select': CraftObjectSelect;
  }
}
