import {property} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement} from 'lit';
import styles from './button-group.styles.js';

/**
 * @summary Wrapper component used to group a set of buttons together.
 * When `name` is set, the group behaves as a radio input: one button is
 * selected at a time and the selected value is submitted with the form.
 *
 * @slot - The default slot.
 *
 * @fires change - Fired when the selected value changes (radio mode only).
 *   `event.detail.value` contains the new value.
 */
export default class CraftButtonGroup extends LitElement {
  static override styles: CSSResultGroup = [styles];
  static formAssociated = true;

  private _internals: ElementInternals;

  constructor() {
    super();
    this._internals = this.attachInternals();
  }

  /** Form field name. When set, enables radio mode. */
  @property({reflect: true}) name: string;

  /** The currently selected value (radio mode only). */
  @property({reflect: true}) value: string;

  override firstUpdated(changed: PropertyValues) {
    super.firstUpdated(changed);
    if (this.name) {
      this._setupRadioMode();
    }
  }

  override updated(changed: PropertyValues) {
    if (changed.has('name')) {
      if (this.name) {
        this._setupRadioMode();
      } else {
        this._teardownRadioMode();
      }
    }
    if (changed.has('value') && this.name) {
      this._syncChildren();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('click', this._handleClick);
  }

  private _setupRadioMode() {
    this.setAttribute('role', 'radiogroup');
    this.removeEventListener('click', this._handleClick);
    this.addEventListener('click', this._handleClick);
    this._syncChildren();
  }

  private _teardownRadioMode() {
    this.removeAttribute('role');
    this.removeEventListener('click', this._handleClick);
  }

  private _handleClick = (e: Event) => {
    const button = (e.composedPath() as Element[]).find(
      (el) => el instanceof Element && el.hasAttribute('value') && el !== this
    ) as Element | undefined;

    if (!button) return;

    const newValue = button.getAttribute('value') ?? '';
    if (newValue === this.value) return;

    this.value = newValue;
    this._syncChildren();
    this.dispatchEvent(
      new CustomEvent('change', {
        bubbles: true,
        composed: true,
        detail: {value: newValue},
      })
    );
  };

  private _syncChildren() {
    const buttons = this.querySelectorAll<Element>('craft-button');
    buttons.forEach((btn) => {
      if (btn.getAttribute('type') !== 'button') {
        btn.setAttribute('type', 'button');
      }
      const isSelected = btn.getAttribute('value') === this.value;
      btn.toggleAttribute('active', isSelected);
      btn.setAttribute('aria-pressed', String(isSelected));
    });
    this._internals.setFormValue(this.value ?? null);
  }

  override render() {
    return html`<slot @slotchange=${this._onSlotChange}></slot>`;
  }

  private _onSlotChange() {
    if (this.name) {
      this._syncChildren();
    }
  }
}

if (!customElements.get('craft-button-group')) {
  customElements.define('craft-button-group', CraftButtonGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-button-group': CraftButtonGroup;
  }
}
