import {LionSelectRich} from '@lion/ui/select-rich.js';
import {html, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './select-rich.styles.js';
import CraftSelectInvoker from './select-invoker.js';
import '../option/option.js';
import '../icon/icon.js';
import {HasLabel} from '@src/mixins/HasLabel';
import {FormAssociated} from '@src/mixins/FormAssociated';

/**
 * @summary A rich `<select>` replacement built on Lion's select-rich. Unlike a
 * native select, options are `craft-option` elements that may contain arbitrary
 * markup (icons, indicators, hints), and the selected option is cloned into the
 * invoker.
 *
 * Uses {@link CraftSelectInvoker} as its invoker (adding the chevron indicator)
 * and sizes the invoker to its content via {@link _alignInvokerWidth}.
 *
 * Options rendered as markup take their value from a `choice-value` attribute;
 * in Lit templates, bind `.choiceValue`.
 *
 * It is form-associated: it belongs to its `<form>`, follows an ancestor
 * `<fieldset disabled>`, and goes back to its initial option on `form.reset()`.
 * The value posts through a hidden light-DOM input rather than
 * `ElementInternals`, because jQuery's `serialize()` and
 * `serializeFormInputs()` only read native controls.
 *
 * @attr {string} name - Field name. The control posts under this, like any
 * other input. Supplied by the `FormAssociated` mixin.
 * @attr {boolean} disabled - Whether the control is disabled. A disabled
 * control is left out of form submission. Supplied by the `FormAssociated`
 * mixin.
 *
 * @fires change - When the user picks an option.
 * @fires input - When the user picks an option.
 *
 * @slot invoker - The element that opens the listbox (provided by default).
 * @slot input - The listbox node.
 * @slot label - Field label.
 * @slot help-text - Supplementary help text.
 * @slot feedback - Validation feedback.
 *
 * @since 1.0
 */
export default class CraftSelectRich extends FormAssociated(
  HasLabel(LionSelectRich)
) {
  static override get styles() {
    return [...super.styles, styles];
  }

  /**
   * Registers the elements the select renders into its own shadow root.
   */
  static override get scopedElements() {
    return {
      ...super.scopedElements,
      'lion-select-invoker': CraftSelectInvoker,
    };
  }

  /** Renders the invoker at a smaller size. */
  @property({reflect: true, type: Boolean}) small = false;

  #input?: HTMLInputElement;

  /** The value a form reset goes back to, read once the options register. */
  #defaultValue = '';

  constructor() {
    super();

    this.addEventListener('model-value-changed', (ev) => {
      if (ev.target !== this) {
        return;
      }

      this.#syncInput();

      if ((ev as CustomEvent).detail?.isTriggeredByUser) {
        this.dispatchEvent(new Event('input', {bubbles: true, composed: true}));
        this.dispatchEvent(new Event('change', {bubbles: true}));
      }
    });
  }

  /** The checked option's value as a string, or empty when none is. */
  get #value(): string {
    const value = this.modelValue;

    return value === undefined || value === null ? '' : String(value);
  }

  // The hidden input posts the value, so `ElementInternals` posts nothing.
  // oxlint-disable-next-line class-methods-use-this
  override _formValue(): string | null {
    return null;
  }

  /**
   * Called when the owning form resets. Checks the option that was checked
   * on load, replacing the mixin's reset of `_formValue()`.
   */
  formResetCallback() {
    const option = this.formElements.find(
      (element) => String(element.choiceValue) === this.#defaultValue
    );

    if (option) {
      option.checked = true;
    }
  }

  override connectedCallback() {
    super.connectedCallback();

    this.registrationComplete.then(() => {
      this.#defaultValue = this.#value;
      this.#syncInput();
    });
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);
    this.#syncInput();
  }

  #syncInput() {
    if (!this.name) {
      this.#input?.remove();

      return;
    }

    if (!this.#input) {
      this.#input = document.createElement('input');
      this.#input.type = 'hidden';
      // A slot the shadow root doesn't render, so Lion leaves it out of the
      // options it moves into the listbox.
      this.#input.slot = 'form-value';
    }

    if (this.#input.parentNode !== this) {
      this.append(this.#input);
    }

    this.#input.name = this.name;
    this.#input.value = this.#value;
    this.#input.disabled = this.disabled;
  }

  /**
   * Override Lion's default which sets a fixed pixel width on the invoker.
   * Use min-width instead so the invoker can grow with its content, up to
   * the width of its container.
   */
  override async _alignInvokerWidth() {
    await this.updateComplete;

    if (!this._overlayCtrl?.content) {
      return;
    }

    const initContentDisplay = this._overlayCtrl.content.style.display;
    const initContentMinWidth =
      this._overlayCtrl.contentWrapperNode.style.minWidth;
    const initContentWidth = this._overlayCtrl.contentWrapperNode.style.width;
    this._overlayCtrl.content.style.display = '';
    this._overlayCtrl.contentWrapperNode.style.minWidth = 'auto';
    this._overlayCtrl.contentWrapperNode.style.width = 'auto';
    const contentWidth =
      this._overlayCtrl.contentWrapperNode.getBoundingClientRect().width;

    if (contentWidth > 0) {
      this._invokerNode.style.minWidth = `min(${contentWidth}px, 100%)`;
      this._invokerNode.style.width = '';
    }

    this._overlayCtrl.content.style.display = initContentDisplay;
    this._overlayCtrl.contentWrapperNode.style.minWidth = initContentMinWidth;
    this._overlayCtrl.contentWrapperNode.style.width = initContentWidth;
  }

  /**
   * Lion treats any printable key as type-ahead, modifiers included, so a
   * shortcut pressed while the select has focus — ⌘S to save — would jump to
   * the option starting with that letter.
   */
  override _handleTypeAhead(
    ev: KeyboardEvent,
    options: {setAsChecked: boolean}
  ) {
    if (ev.metaKey || ev.ctrlKey || ev.altKey) {
      return;
    }

    super._handleTypeAhead(ev, options);
  }

  // oxlint-disable-next-line class-methods-use-this
  override _inputGroupInputTemplate() {
    return html`
      <div class="input-group__input">
        <slot name="invoker"></slot>
        <div id="overlay-content-node-wrapper">
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-select-rich')) {
  customElements.define('craft-select-rich', CraftSelectRich);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select-rich': CraftSelectRich;
  }
}
