import {property} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement} from 'lit';
import styles from './button-group.styles.js';

const START_ATTRIBUTE = 'data-button-group-start';
const END_ATTRIBUTE = 'data-button-group-end';
const DIVIDER_ATTRIBUTE = 'data-button-group-divider';
const DIVIDER_END_ATTRIBUTE = 'data-button-group-divider-end';

/**
 * @summary Wrapper component used to group a set of buttons together.
 * When `name` is set, the selected value is submitted with the form. Set
 * `multiple` to allow more than one selected button.
 *
 * A group that holds a selection is a group of toggle buttons: each button
 * keeps its role and its own tab stop, and reports its state with
 * `aria-pressed`. For a form setting that picks one value from a set of
 * options, use `craft-radio-group` with `buttons` instead, which looks the
 * same but is announced and operated as radios.
 *
 * @slot - The default slot.
 *
 * @fires change - Fired when the selected value changes.
 */
export default class CraftButtonGroup extends LitElement {
  static override styles: CSSResultGroup = [styles];
  /**
   * Makes the group a form-associated custom element, so its selected value
   * posts with the form in selection mode.
   */
  static formAssociated = true;

  private _internals: ElementInternals;

  constructor() {
    super();
    this._internals = this.attachInternals();
  }

  /** Form field name. When set, enables selection mode. */
  @property({reflect: true}) name: string;

  /**
   * The currently selected value in single-selection mode.
   *
   * The group owns the children's `active` state and rewrites it from this, so
   * drive the selection here rather than on the buttons. Left unset, it is
   * seeded from whichever child is marked `active`, so markup that states its
   * own selection keeps it.
   */
  @property({reflect: true}) value: string;

  /** Whether multiple buttons can be selected. */
  @property({reflect: true, type: Boolean}) multiple = false;

  /**
   * Sets the buttons on a quiet track instead of joining their borders, for a
   * selection that sits alongside a page's main actions rather than among them.
   */
  @property({reflect: true, type: Boolean}) subtle = false;

  /**
   * Re-marks the ends and dividers when a child is shown or hidden, which
   * changes which one is visibly first or last without firing `slotchange`,
   * or when one is selected or deselected.
   */
  private _childObserver = new MutationObserver(() => this._markEnds());

  /**
   * Buttons use `active` for their pressed state too, and clear it when a
   * mouse press ends, including on the selected button. In single-selection
   * mode that would leave the selected button looking deselected, so it gets
   * its `active` back.
   */
  private _selectionObserver = new MutationObserver((records) => {
    if (!this.name || this.multiple) {
      return;
    }
    for (const {target} of records) {
      if (
        target instanceof Element &&
        target.localName === 'craft-button' &&
        !target.hasAttribute('active') &&
        target.getAttribute('value') === this.value
      ) {
        target.setAttribute('active', '');
      }
    }
  });

  override connectedCallback() {
    super.connectedCallback();
    this._childObserver.observe(this, {
      childList: true,
      attributes: true,
      attributeFilter: ['hidden', 'style', 'class', 'active', 'aria-pressed'],
      subtree: true,
    });
    this._selectionObserver.observe(this, {
      attributes: true,
      attributeFilter: ['active'],
      subtree: true,
    });
  }

  override firstUpdated(changed: PropertyValues) {
    super.firstUpdated(changed);
    if (this.name) {
      this._setupSelectionMode();
    }
    this._markEnds();
  }

  override updated(changed: PropertyValues) {
    if (changed.has('name') || changed.has('multiple')) {
      if (this.name) {
        this._setupSelectionMode();
      } else {
        this._teardownSelectionMode();
      }
    }
    if ((changed.has('value') || changed.has('multiple')) && this.name) {
      this._syncChildren();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._childObserver.disconnect();
    this._selectionObserver.disconnect();
  }

  /**
   * Marks the first and last visible children, so only those get rounded
   * outer corners. `:first-child` and `:last-child` can't be used: a group
   * can open with hidden inputs, or with an empty placeholder a framework
   * renders and later fills.
   *
   * Also marks the dividers between neighbors: between every pair, except
   * on either side of a selected child, whose own look sets it apart. Both
   * neighbors draw it, at the edge they share, so it still shows when either
   * one is raised above the other, as a hovered or focused one is.
   */
  private _markEnds() {
    const visible = Array.from(this.children).filter((child) =>
      this._isVisible(child)
    );
    const first = visible[0];
    const last = visible[visible.length - 1];

    for (const child of Array.from(this.children)) {
      const index = visible.indexOf(child);
      const previous = index > 0 ? visible[index - 1] : undefined;
      const next = index >= 0 ? visible[index + 1] : undefined;

      child.toggleAttribute(START_ATTRIBUTE, child === first);
      child.toggleAttribute(END_ATTRIBUTE, child === last);
      child.toggleAttribute(
        DIVIDER_ATTRIBUTE,
        previous !== undefined &&
          !this._isSelected(child) &&
          !this._isSelected(previous)
      );
      child.toggleAttribute(
        DIVIDER_END_ATTRIBUTE,
        next !== undefined &&
          !this._isSelected(child) &&
          !this._isSelected(next)
      );
    }
  }

  private _isSelected(element: Element): boolean {
    return (
      element.hasAttribute('active') ||
      element.getAttribute('aria-pressed') === 'true'
    );
  }

  private _isVisible(element: Element): boolean {
    if (
      element instanceof HTMLTemplateElement ||
      element instanceof HTMLScriptElement ||
      element instanceof HTMLStyleElement ||
      (element instanceof HTMLInputElement && element.type === 'hidden') ||
      (element instanceof HTMLElement && element.hidden)
    ) {
      return false;
    }

    const display = getComputedStyle(element).display;
    if (display === 'none') {
      return false;
    }

    // A `display: contents` wrapper has no box of its own; it only shows
    // what's inside it.
    if (display === 'contents') {
      return Array.from(element.children).some((child) =>
        this._isVisible(child)
      );
    }

    return true;
  }

  /** Whether the group set its own `role`, so it can tell it from a consumer's. */
  private _ownsRole = false;

  private _setupSelectionMode() {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'group');
      this._ownsRole = true;
    }
    this.removeEventListener('click', this._handleClick);
    this.addEventListener('click', this._handleClick);
    this._syncChildren();
    this._defaultSelection ??= {
      value: this.value,
      active: this._selectedValues(),
    };
  }

  /**
   * The selection the group started with, which a form reset puts back. Taken
   * when selection mode first starts, after a selection given in markup has
   * been adopted.
   */
  private _defaultSelection?: {value: string | undefined; active: string[]};

  /** The buttons a disabled fieldset disabled, so only they are re-enabled. */
  private _formDisabledButtons = new Set<Element>();

  private _formDisabled = false;

  /** Restores the initial selection when the group's form is reset. */
  formResetCallback() {
    if (!this.name || !this._defaultSelection) {
      return;
    }

    const {value, active} = this._defaultSelection;

    this.querySelectorAll('craft-button').forEach((button) => {
      button.toggleAttribute(
        'active',
        this.multiple && active.includes(button.getAttribute('value') ?? '')
      );
    });

    if (!this.multiple) {
      // `value` is declared without a default, so `undefined` is its unset state.
      this.value = value as string;
    }

    this._syncChildren();
  }

  /**
   * The buttons aren't form-associated themselves, so a disabled fieldset
   * reaches them through the group.
   */
  formDisabledCallback(disabled: boolean) {
    this._formDisabled = disabled;
    this._applyFormDisabled();
  }

  private _applyFormDisabled() {
    if (!this._formDisabled) {
      this._formDisabledButtons.forEach((button) =>
        button.removeAttribute('disabled')
      );
      this._formDisabledButtons.clear();
      return;
    }

    this.querySelectorAll('craft-button:not([disabled])').forEach((button) => {
      button.setAttribute('disabled', '');
      this._formDisabledButtons.add(button);
    });
  }

  private _teardownSelectionMode() {
    if (this._ownsRole) {
      this.removeAttribute('role');
      this._ownsRole = false;
    }
    this.removeEventListener('click', this._handleClick);
  }

  private _handleClick = (e: Event) => {
    const button = (e.composedPath() as Element[]).find(
      (el) => el instanceof Element && el.hasAttribute('value') && el !== this
    ) as Element | undefined;

    if (!button) return;

    if (this.multiple) {
      button.toggleAttribute('active');
      this._syncChildren();
      this.dispatchEvent(
        new CustomEvent('change', {
          bubbles: true,
          composed: true,
          detail: {values: this._selectedValues()},
        })
      );
      return;
    }

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

  /**
   * Seeds `value` from a child marked `active`, so a selection given in markup
   * survives the first sync. Multi-select reads `active` directly.
   */
  private _adoptSlottedValue() {
    if (this.multiple || this.value !== undefined) {
      return;
    }

    const selected = this.querySelector('craft-button[active]');
    const value = selected?.getAttribute('value');

    // No active child yet — leave `value` unset so a later sync can still
    // adopt once the children have been parsed. There is nothing to clobber in
    // the meantime.
    if (value != null) {
      this.value = value;

      if (this._defaultSelection?.value === undefined) {
        this._defaultSelection = {value, active: [value]};
      }
    }
  }

  private _syncChildren() {
    this._adoptSlottedValue();

    const buttons = this.querySelectorAll<Element>('craft-button');
    buttons.forEach((btn) => {
      if (btn.getAttribute('type') !== 'button') {
        btn.setAttribute('type', 'button');
      }
      const isSelected = this.multiple
        ? btn.hasAttribute('active')
        : btn.getAttribute('value') === this.value;
      if (!this.multiple) {
        btn.toggleAttribute('active', isSelected);
      }
      btn.setAttribute('aria-pressed', String(isSelected));
    });

    if (!this.multiple) {
      this._internals.setFormValue(this.value ?? null);
      return;
    }

    const formData = new FormData();
    formData.append(this.name, '');
    this._selectedValues().forEach((value) => {
      formData.append(`${this.name}[]`, value);
    });
    this._internals.setFormValue(formData);
  }

  private _selectedValues(): string[] {
    return Array.from(
      this.querySelectorAll<Element>('craft-button[active]'),
      (button) => button.getAttribute('value') ?? ''
    );
  }

  override render() {
    return html`<slot @slotchange=${this._onSlotChange}></slot>`;
  }

  private _onSlotChange() {
    if (this.name) {
      this._syncChildren();
    }
    this._applyFormDisabled();
    this._markEnds();
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
