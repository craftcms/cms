import {html, LitElement, render, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './select-rich.styles.js';
import CraftOption from '../option/option.js';
import './select-invoker.js';
import type CraftSelectInvoker from './select-invoker.js';
import '../icon/icon.js';
import {FormAssociated} from '@src/mixins/FormAssociated';
import {
  FormControl,
  formControlStyles,
  type SlotFactory,
} from '@src/mixins/FormControl';
import {emitChange, emitInput} from '@src/utilities/form-events';
import {activateOption, stepOption, Typeahead} from '@src/utilities/listbox';
import {claimEscape} from '@src/utilities/overlay-escape';
import {layerStyles} from '@src/utilities/overlay-layer';
import {Dropdown} from '@src/utilities/overlay-dropdown';
import {uniqueId} from '@src/utilities/unique-id';

/**
 * @summary A rich `<select>` replacement. Unlike a native select, options are
 * `craft-option` elements that may contain arbitrary markup (icons,
 * indicators, hints), and the selected option's content is copied into the
 * field.
 *
 * Options rendered as markup take their value from a `choice-value`
 * attribute; in Lit templates, bind `.choiceValue`. With no option checked,
 * the first enabled one is.
 *
 * The field is a select-only combobox: it keeps focus while the listbox is
 * open and points at the active option with `aria-activedescendant`.
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
 * @slot - The `craft-option`s.
 * @slot input - The field (provided by default).
 * @slot label - Field label.
 * @slot help-text - Supplementary help text.
 * @slot feedback - Validation feedback.
 *
 * @since 1.0
 */
export default class CraftSelectRich extends FormAssociated(
  FormControl(LitElement)
) {
  static override styles = [formControlStyles, layerStyles, styles];

  /** Renders the field at a smaller size. */
  @property({reflect: true, type: Boolean}) small = false;

  /** Marks the value as required: sets `aria-required` on the field. */
  @property({type: Boolean}) required = false;

  /** Marks the value as invalid: sets `aria-invalid` on the field. */
  @property({type: Boolean, reflect: true}) invalid = false;

  /** Whether the listbox is showing. */
  @property({type: Boolean, reflect: true}) opened = false;

  #value = '';

  /** The checked option's value, or empty when none is. */
  @property()
  get value(): string {
    return this.#value;
  }

  set value(value: string) {
    const old = this.#value;
    this.#value = value === null || value === undefined ? '' : String(value);
    this.#applyValue();
    this.requestUpdate('value', old);
  }

  #input?: HTMLInputElement;

  /** The value a form reset goes back to, read once the options are in. */
  #defaultValue = '';

  #defaultValueRead = false;

  /** Whether option `checked` changes are this component's own. */
  #applying = false;

  #typeahead = new Typeahead();

  #observer = new MutationObserver((records) => {
    // The field's copy of the selection and the hidden input change with
    // every sync, so only changes to the options themselves count.
    const own = (node: Node) =>
      node === this._inputNode ||
      node === this.#input ||
      Boolean(this._inputNode?.contains(node));

    if (records.some((record) => !own(record.target))) {
      this.#onOptionsChanged();
    }
  });

  #dropdown = new Dropdown(() => {
    const layer = this.renderRoot.querySelector<HTMLElement>('.overlay-layer');
    const pane = this.#pane;
    const reference = this._inputNode;

    return layer && pane && reference ? {layer, pane, reference} : undefined;
  });

  constructor() {
    super();
    this.addEventListener('craft-checked-change', this.#onOptionChecked);
  }

  /** Creates the field when none is slotted. */
  override get slots(): Record<string, SlotFactory> {
    return {
      ...super.slots,
      input: () => document.createElement('craft-select-invoker'),
    };
  }

  override get _inputNode(): CraftSelectInvoker | undefined {
    return super._inputNode as CraftSelectInvoker | undefined;
  }

  get #pane(): HTMLElement | null {
    return this.renderRoot.querySelector<HTMLElement>(
      '#overlay-content-node-wrapper'
    );
  }

  get #options(): CraftOption[] {
    return [...this.querySelectorAll(':scope > craft-option')].filter(
      (option): option is CraftOption => option instanceof CraftOption
    );
  }

  /** The options the keyboard moves through: the ones that are showing. */
  get #keyboardOptions(): CraftOption[] {
    return this.#options.filter((option) => !option.hidden);
  }

  get #checkedOption(): CraftOption | undefined {
    return this.#options.find((option) => option.checked);
  }

  get #disabled(): boolean {
    return this.disabled || this._formDisabled;
  }

  get #inactive(): boolean {
    return this.#disabled || this.readOnly || this.#singleOption;
  }

  /**
   * Whether the first enabled option is checked when the value matches none.
   * A subclass that starts with nothing selected returns false.
   */
  protected get _selectsFirstOption(): boolean {
    return true;
  }

  get #singleOption(): boolean {
    return this.#options.length === 1;
  }

  override connectedCallback() {
    super.connectedCallback();

    const invoker = this._inputNode;
    if (invoker && !invoker.hasAttribute('role')) {
      invoker.addEventListener('keydown', this.#onInvokerKeydown);
      invoker.addEventListener('click', this.#onInvokerClick);
      invoker.addEventListener('focusout', this.#onFocusOut);
    }

    this.#observer.observe(this, {
      childList: true,
      subtree: true,
      characterData: true,
    });
    this.#onOptionsChanged();
  }

  override disconnectedCallback() {
    this.#observer.disconnect();
    this.#dropdown.hide();
    document.removeEventListener('pointerdown', this.#onDocumentPointerDown);
    this.#typeahead.reset();
    super.disconnectedCallback();
  }

  // The hidden input posts the value, so `ElementInternals` posts nothing.
  // oxlint-disable-next-line class-methods-use-this
  override _formValue(): string | null {
    return null;
  }

  /** Checks the option that was checked on load when the owning form resets. */
  formResetCallback() {
    this.value = this.#defaultValue;
    this.#syncInput();
  }

  // ─── Options and value ────────────────────────────────────────────────

  #onOptionsChanged({waited = false} = {}): void {
    // Markup's options connect after the select does, and upgrade just after,
    // so their state isn't readable yet. Wait for that, once.
    if (
      !waited &&
      [...this.querySelectorAll(':scope > craft-option')].some(
        (option) => !(option instanceof CraftOption)
      )
    ) {
      void customElements
        .whenDefined('craft-option')
        .then(() =>
          queueMicrotask(() => this.#onOptionsChanged({waited: true}))
        );
      return;
    }

    if (this.#value === '') {
      const checked = this.#checkedOption;
      if (checked) {
        this.#value = checked.choiceValue;
      }
    }

    this.#applyValue({fallBackToFirst: true});

    if (!this.#defaultValueRead && this.#options.length) {
      this.#defaultValue = this.#value;
      this.#defaultValueRead = true;
    }

    // A replaced active option hands over to its replacement, if there is one.
    const active = this.#active;
    if (active && !this.#options.includes(active)) {
      this.#setActive(
        this.#options.find(
          (option) =>
            option.choiceValue === active.choiceValue && !option.disabled
        )
      );
    }

    this.#syncInvoker();
    this.#syncInput();
    this.requestUpdate();
    void this.updateComplete.then(() => this.#alignInvokerWidth());
  }

  /**
   * Checks the option the value names. When options arrive and none of them
   * matches, the first enabled one is checked instead.
   */
  #applyValue({fallBackToFirst = false} = {}): void {
    const options = this.#options;
    let match = options.find((option) => option.choiceValue === this.#value);

    if (!match && fallBackToFirst && this._selectsFirstOption) {
      match = options.find((option) => !option.disabled);
      if (match) {
        this.#value = match.choiceValue;
      }
    }

    this.#applying = true;
    try {
      for (const option of options) {
        option.checked = option === match;
      }
    } finally {
      this.#applying = false;
    }

    this.#syncInvoker();
    this.#syncInput();
  }

  /** An option checked from outside, by setting its `checked`. */
  #onOptionChecked = (event: Event): void => {
    const option = event.target as CraftOption;

    if (
      this.#applying ||
      option.parentElement !== this ||
      option.localName !== 'craft-option' ||
      !option.checked
    ) {
      return;
    }

    const old = this.#value;
    this.#value = option.choiceValue;
    this.#applyValue();
    this.requestUpdate('value', old);
  };

  /** Selects an option on the user's behalf. */
  #pick(option: CraftOption): void {
    if (option.disabled || option.choiceValue === this.#value) {
      return;
    }

    this.value = option.choiceValue;
    emitInput(this);
    emitChange(this);
  }

  #syncInvoker(): void {
    const invoker = this._inputNode;
    if (!invoker) {
      return;
    }

    const checked = this.#checkedOption;
    const single = this.#singleOption;

    invoker.id ||= this._inputId;
    invoker.singleOption = single;
    invoker.disabled = this.#disabled;
    render(
      checked
        ? [...checked.childNodes].map((node) => node.cloneNode(true))
        : '',
      invoker
    );

    // With one option there's nothing to open, but the field still says
    // what it is and what it holds.
    invoker.setAttribute('role', 'combobox');
    if (single) {
      invoker.removeAttribute('aria-haspopup');
      invoker.removeAttribute('aria-expanded');
      invoker.removeAttribute('aria-activedescendant');
    } else {
      invoker.setAttribute('aria-haspopup', 'listbox');
      invoker.setAttribute('aria-expanded', String(this.opened));
    }

    invoker.tabIndex = this.#disabled ? -1 : 0;
    if (this.#disabled) {
      invoker.setAttribute('aria-disabled', 'true');
    } else {
      invoker.removeAttribute('aria-disabled');
    }
    if (this.readOnly || single) {
      invoker.setAttribute('aria-readonly', 'true');
    } else {
      invoker.removeAttribute('aria-readonly');
    }
    if (this.invalid) {
      invoker.setAttribute('aria-invalid', 'true');
    } else {
      invoker.removeAttribute('aria-invalid');
    }
    if (this.required && !single) {
      invoker.setAttribute('aria-required', 'true');
    } else {
      invoker.removeAttribute('aria-required');
    }
  }

  #syncInput(): void {
    if (!this.name) {
      this.#input?.remove();

      return;
    }

    if (!this.#input) {
      this.#input = document.createElement('input');
      this.#input.type = 'hidden';
      // A slot the shadow root doesn't render.
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
   * Gives the field at least the width of the widest option, up to its
   * container's, so it doesn't change size with the selection.
   */
  #alignInvokerWidth(): void {
    const layer = this.renderRoot.querySelector<HTMLElement>('.overlay-layer');
    const pane = this.#pane;
    const invoker = this._inputNode;

    if (!layer || !pane || !invoker || this.opened) {
      return;
    }

    layer.style.visibility = 'hidden';
    layer.setAttribute('data-open', '');
    pane.style.minWidth = 'auto';
    const width = pane.getBoundingClientRect().width;
    layer.removeAttribute('data-open');
    layer.style.removeProperty('visibility');

    if (width > 0) {
      invoker.style.minWidth = `min(${width}px, 100%)`;
    }
  }

  // ─── Opening and closing ──────────────────────────────────────────────

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    if (changed.has('opened')) {
      this.#onOpenedChanged();
    }

    if (
      changed.has('disabled') ||
      changed.has('_formDisabled') ||
      changed.has('readOnly') ||
      changed.has('invalid') ||
      changed.has('required') ||
      changed.has('opened')
    ) {
      this.#syncInvoker();
    }

    if (changed.has('name') || changed.has('disabled')) {
      this.#syncInput();
    }

    if (this.opened) {
      this.#dropdown.update();
    }
  }

  #onOpenedChanged(): void {
    const invoker = this._inputNode;

    if (this.opened && !this.#inactive) {
      this.#typeahead.reset();
      this.#dropdown.show();
      document.addEventListener('pointerdown', this.#onDocumentPointerDown);
      const checked = this.#checkedOption;
      this.#setActive(checked?.hidden ? undefined : checked);
      return;
    }

    this.#dropdown.hide();
    document.removeEventListener('pointerdown', this.#onDocumentPointerDown);
    this.#setActive(undefined);
    this.#typeahead.reset();
    invoker?.removeAttribute('aria-activedescendant');
  }

  #open(): void {
    if (!this.#inactive) {
      this.opened = true;
    }
  }

  #onDocumentPointerDown = (event: PointerEvent): void => {
    if (!event.composedPath().includes(this)) {
      this.opened = false;
    }
  };

  #onFocusOut = (event: FocusEvent): void => {
    if (
      !(event.relatedTarget instanceof Node) ||
      !this.contains(event.relatedTarget)
    ) {
      this.opened = false;
    }
  };

  #onInvokerClick = (): void => {
    if (this.opened) {
      this.opened = false;
    } else {
      this.#open();
    }
  };

  // ─── The active option ────────────────────────────────────────────────

  #active?: CraftOption;

  #setActive(option: CraftOption | undefined): void {
    this.#active = option;
    activateOption(this.#options, option, this._inputNode, {
      scroll: this.opened,
    });
  }

  // ─── Keyboard ─────────────────────────────────────────────────────────

  #onInvokerKeydown = (event: KeyboardEvent): void => {
    if (this.#inactive || event.isComposing) {
      return;
    }

    if (this.opened) {
      this.#onOpenKeydown(event);
      return;
    }

    switch (event.key) {
      case 'Enter':
      case ' ':
      case 'ArrowDown':
      case 'ArrowUp':
        event.preventDefault();
        if (!event.repeat) {
          this.#open();
        }
        return;
    }

    // Typing while closed selects, as it does on a native select.
    const match = this.#typeahead.match(
      event,
      this.#keyboardOptions,
      this.#checkedOption
    );
    if (match) {
      event.preventDefault();
      this.#pick(match);
    }
  };

  #onOpenKeydown(event: KeyboardEvent): void {
    const options = this.#keyboardOptions;

    switch (event.key) {
      case 'ArrowDown':
      case 'ArrowUp':
        event.preventDefault();
        this.#setActive(
          stepOption(
            options,
            this.#active,
            event.key === 'ArrowDown' ? 1 : -1,
            {
              wrap: false,
              skipDisabled: true,
            }
          )
        );
        return;
      case 'Home':
      case 'End': {
        event.preventDefault();
        const enabled = options.filter((option) => !option.disabled);
        this.#setActive(event.key === 'Home' ? enabled[0] : enabled.at(-1));
        return;
      }
      case 'Enter':
        event.preventDefault();
        this.#commitActive();
        return;
      case ' ':
        if (!this.#typeahead.typing) {
          event.preventDefault();
          this.#commitActive();
          return;
        }
        break;
      case 'Escape':
        event.preventDefault();
        claimEscape(event);
        this.opened = false;
        return;
      case 'Tab':
        this.#commitActive();
        return;
    }

    const match = this.#typeahead.match(event, options, this.#active);
    if (match) {
      event.preventDefault();
      this.#setActive(match);
    }
  }

  #commitActive(): void {
    if (this.#active) {
      this.#pick(this.#active);
    }
    this.opened = false;
  }

  #onListboxClick = (event: MouseEvent): void => {
    const option = (event.target as Element | null)?.closest<CraftOption>(
      'craft-option'
    );

    if (!option || option.disabled || this.#inactive) {
      return;
    }

    this.#pick(option);
    this.opened = false;
    this._inputNode?.focus();
  };

  /** Focuses the field, as a `craft-field` does when its label is clicked. */
  override focus(options?: FocusOptions): void {
    this._inputNode?.focus(options);
  }

  protected override _onLabelClick(): void {
    this._inputNode?.focus();
  }

  /** The text that names the listbox, which can't reference the label. */
  #listboxLabel(): string {
    return this._labelNode?.textContent?.trim() || this.label;
  }

  protected override _inputGroupInputTemplate() {
    return html`
      <div class="input-group__input">
        <slot name="input"></slot>
        <div class="overlay-layer" role="none" popover="manual">
          <div id="overlay-content-node-wrapper">
            <div
              id=${this.#listboxId}
              role="listbox"
              aria-label=${this.#listboxLabel()}
              @mousedown=${(event: Event) => event.preventDefault()}
              @click=${this.#onListboxClick}
            >
              <slot></slot>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  #listboxId = uniqueId('listbox');
}

if (!customElements.get('craft-select-rich')) {
  customElements.define('craft-select-rich', CraftSelectRich);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select-rich': CraftSelectRich;
  }
}
