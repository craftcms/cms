import {property} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement} from 'lit';
import styles from './button-group.styles.js';

const START_ATTRIBUTE = 'data-button-group-start';
const END_ATTRIBUTE = 'data-button-group-end';

/**
 * @summary Wrapper component used to group a set of buttons together.
 * When `name` is set, the selected value is submitted with the form. Set
 * `multiple` to allow more than one selected button.
 *
 * A group that holds a selection — `name` is set, it's a `radiogroup`, or its
 * buttons carry `aria-pressed` — is a single tab stop. The arrow keys, Home
 * and End move between its buttons, and select the one they land on unless
 * more than one can be selected (`multiple`, or `role="group"`). They select
 * by clicking it, so a consumer that manages the selection itself follows
 * along.
 *
 * @slot - The default slot.
 *
 * @fires change - Fired when the selected value changes.
 */
export default class CraftButtonGroup extends LitElement {
  static override styles: CSSResultGroup = [styles];
  /**
   * Makes the group a form-associated custom element, so its selected value
   * posts with the form in radio mode.
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
   * Re-marks the ends when a child is shown or hidden, which changes which
   * one is visibly first or last without firing `slotchange`.
   */
  private _childObserver = new MutationObserver(() => this._markEnds());

  /**
   * Keeps the selection and the tab stop in step with the buttons' state.
   * Buttons use `active` for their pressed state too, and clear it when a
   * mouse press ends, including on the selected button. In single-selection
   * mode that would leave the selected button looking deselected, so it gets
   * its `active` back.
   */
  private _stateObserver = new MutationObserver((records) => {
    if (this.name && !this.multiple) {
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
    }
    this._syncTabStop();
  });

  /** The button that last had focus, which keeps the tab stop in multi-select. */
  private _focusedItem: HTMLElement | null = null;

  override connectedCallback() {
    super.connectedCallback();
    this._childObserver.observe(this, {
      childList: true,
      attributes: true,
      attributeFilter: ['hidden', 'style', 'class'],
      subtree: true,
    });
    this._stateObserver.observe(this, {
      childList: true,
      attributes: true,
      attributeFilter: [
        'active',
        'aria-pressed',
        'disabled',
        'hidden',
        'role',
        'tabindex',
      ],
      subtree: true,
    });
    this.addEventListener('keydown', this._handleKeydown);
    this.addEventListener('focusin', this._handleFocusin);
  }

  override firstUpdated(changed: PropertyValues) {
    super.firstUpdated(changed);
    if (this.name) {
      this._setupRadioMode();
    }
    this._markEnds();
    this._syncTabStop();
  }

  override updated(changed: PropertyValues) {
    if (changed.has('name') || changed.has('multiple')) {
      if (this.name) {
        this._setupRadioMode();
      } else {
        this._teardownRadioMode();
      }
    }
    if ((changed.has('value') || changed.has('multiple')) && this.name) {
      this._syncChildren();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();
    this.removeEventListener('click', this._handleClick);
    this.removeEventListener('keydown', this._handleKeydown);
    this.removeEventListener('focusin', this._handleFocusin);
    this._childObserver.disconnect();
    this._stateObserver.disconnect();
  }

  /**
   * Marks the first and last visible children, so only those get rounded
   * outer corners. `:first-child` and `:last-child` can't be used: a group
   * can open with hidden inputs, or with an empty placeholder a framework
   * renders and later fills.
   */
  private _markEnds() {
    const visible = Array.from(this.children).filter((child) =>
      this._isVisible(child)
    );
    const first = visible[0];
    const last = visible[visible.length - 1];

    for (const child of Array.from(this.children)) {
      child.toggleAttribute(START_ATTRIBUTE, child === first);
      child.toggleAttribute(END_ATTRIBUTE, child === last);
    }
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

  private _setupRadioMode() {
    this.setAttribute('role', this.multiple ? 'group' : 'radiogroup');
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

  private _items(): HTMLElement[] {
    return Array.from(this.children).filter(
      (child): child is HTMLElement =>
        (child.localName === 'craft-button' || child.localName === 'button') &&
        this._isVisible(child)
    );
  }

  private _isEnabled(item: HTMLElement): boolean {
    return (
      !item.hasAttribute('disabled') && !(item as HTMLButtonElement).disabled
    );
  }

  private _isSelected(item: HTMLElement): boolean {
    return item.hasAttribute('aria-pressed')
      ? item.getAttribute('aria-pressed') === 'true'
      : item.hasAttribute('active');
  }

  private _holdsSelection(items: HTMLElement[]): boolean {
    return (
      Boolean(this.name) ||
      this.getAttribute('role') === 'radiogroup' ||
      items.some((item) => item.hasAttribute('aria-pressed'))
    );
  }

  private _isMultiple(): boolean {
    return this.name
      ? this.multiple
      : this.multiple || this.getAttribute('role') === 'group';
  }

  /**
   * Gives one button the tab stop: the focused one in multi-select, otherwise
   * the selected one, otherwise the first.
   */
  private _syncTabStop() {
    const items = this._items();
    if (!this._holdsSelection(items)) {
      return;
    }

    const enabled = items.filter((item) => this._isEnabled(item));
    const focused =
      this._isMultiple() &&
      this._focusedItem &&
      enabled.includes(this._focusedItem)
        ? this._focusedItem
        : null;
    const stop =
      focused ?? enabled.find((item) => this._isSelected(item)) ?? enabled[0];

    for (const item of enabled) {
      const tabIndex = item === stop ? '0' : '-1';
      if (item.getAttribute('tabindex') !== tabIndex) {
        item.setAttribute('tabindex', tabIndex);
      }
    }
  }

  private _handleFocusin = (event: FocusEvent) => {
    const item = this._items().find((item) =>
      event.composedPath().includes(item)
    );
    if (item) {
      this._focusedItem = item;
      this._syncTabStop();
    }
  };

  private _handleKeydown = (event: KeyboardEvent) => {
    if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
      return;
    }

    const items = this._items();
    if (!this._holdsSelection(items)) {
      return;
    }

    const enabled = items.filter((item) => this._isEnabled(item));
    const current = enabled.find((item) => event.composedPath().includes(item));
    if (!current) {
      return;
    }

    const rtl = getComputedStyle(this).direction === 'rtl';
    const index = enabled.indexOf(current);
    let next: HTMLElement | undefined;
    switch (event.key) {
      case 'ArrowDown':
        next = enabled[(index + 1) % enabled.length];
        break;
      case 'ArrowUp':
        next = enabled[(index - 1 + enabled.length) % enabled.length];
        break;
      case 'ArrowRight':
      case 'ArrowLeft': {
        const step = (event.key === 'ArrowRight') !== rtl ? 1 : -1;
        next = enabled[(index + step + enabled.length) % enabled.length];
        break;
      }
      case 'Home':
        next = enabled[0];
        break;
      case 'End':
        next = enabled[enabled.length - 1];
        break;
      default:
        return;
    }

    event.preventDefault();
    if (!next) {
      return;
    }

    this._focusedItem = next;
    next.focus();
    if (!this._isMultiple() && !this._isSelected(next)) {
      next.click();
    }
    this._syncTabStop();
  };

  override render() {
    return html`<slot @slotchange=${this._onSlotChange}></slot>`;
  }

  private _onSlotChange() {
    if (this.name) {
      this._syncChildren();
    }
    this._markEnds();
    this._syncTabStop();
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
