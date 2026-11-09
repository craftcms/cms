import {LionSelectRich} from '@lion/ui/select-rich.js';
import {html, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './select-rich.styles.js';
import CraftSelectInvoker from './select-invoker.js';
import '../option/option.js';
import '../icon/icon.js';
import '../input-search/input-search.js';
import type CraftInputSearch from '../input-search/input-search.js';
import type {LionOption} from '@lion/ui/listbox.js';
import {HasLabel} from '@src/mixins/HasLabel';
import {FormAssociated} from '@src/mixins/FormAssociated';
import {emitChange, emitInput} from '@src/utilities/form-events';
import {t} from '@src/utilities/translate';

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
 * With `searchable`, the open list leads with a search field that filters
 * the options by their label and hint, plus any `data-keywords`.
 * Focus stays in the field while the arrow keys move through the matches and
 * Enter picks one; Escape clears the field, then closes the list.
 *
 * @fires change - When the user picks an option.
 * @fires input - When the user picks an option.
 *
 * @slot invoker - The element that opens the listbox (provided by default).
 * @slot input - The listbox node.
 * @slot search - Supplied by the component when `searchable`.
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

  /** Leads the open list with a search field that filters the options. */
  @property({reflect: true, type: Boolean}) searchable = false;

  #search?: HTMLElement;

  #searchInput?: HTMLInputElement;

  #searchField?: CraftInputSearch;

  #noResults?: HTMLElement;

  /** The options the filter hid, so it only ever un-hides its own. */
  #filtered = new Set<LionOption>();

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
        emitInput(this);
        emitChange(this);
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

  #defaultValueRead = false;

  override connectedCallback() {
    super.connectedCallback();

    this.registrationComplete.then(() => {
      if (!this.#defaultValueRead) {
        this.#defaultValue = this.#value;
        this.#defaultValueRead = true;
      }
      this.#syncInput();
    });
  }

  override updated(changed: PropertyValues) {
    super.updated(changed);
    this.#syncInput();

    if (changed.has('searchable')) {
      this.#syncSearch();
    }
  }

  /** While searching, focus stays in the field, so it owns the highlighted option. */
  override get _activeDescendantOwnerNode(): HTMLElement {
    return this.searchable && this.#searchInput
      ? this.#searchInput
      : super._activeDescendantOwnerNode;
  }

  override _setupOverlayCtrl() {
    super._setupOverlayCtrl();
    // Added after Lion's own listeners, so this focus wins over the listbox's.
    this._overlayCtrl.addEventListener('show', this.#onOverlayShow);
    this._overlayCtrl.addEventListener('hide', this.#onOverlayHide);
  }

  override _teardownOverlayCtrl() {
    this._overlayCtrl?.removeEventListener('show', this.#onOverlayShow);
    this._overlayCtrl?.removeEventListener('hide', this.#onOverlayHide);
    super._teardownOverlayCtrl();
  }

  #onOverlayShow = () => {
    if (this.searchable) {
      this.#searchInput?.focus();
    }
  };

  #onOverlayHide = () => {
    if (this.#searchField) {
      this.#searchField.modelValue = '';
    }
    this.#filter('');
  };

  #syncSearch() {
    if (!this.searchable) {
      this.#search?.remove();
      this.#filter('');

      return;
    }

    if (!this.#search) {
      this.#search = this.#buildSearch();
    }

    // After the listbox, which Lion finds as the first `[slot="input"]` inside.
    if (this.#search.parentNode !== this) {
      this.append(this.#search);
    }
  }

  // Light DOM rather than shadow, so the field's `aria-activedescendant` and
  // `aria-controls` can point at the options.
  #buildSearch(): HTMLElement {
    const container = document.createElement('div');
    container.slot = 'search';
    container.className = 'select-rich__search';
    // The search field is a form control too: kept from registering as an option.
    container.addEventListener('form-element-register', (ev) =>
      ev.stopPropagation()
    );

    const input = document.createElement('input');
    input.slot = 'input';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'true');
    if (this._listboxNode?.id) {
      input.setAttribute('aria-controls', this._listboxNode.id);
    }
    input.addEventListener('input', () => this.#filter(input.value));
    input.addEventListener('keydown', this.#onSearchKeydown);

    const field = document.createElement('craft-input-search');
    field.setAttribute('size', 'small');
    field.append(input);

    const noResults = document.createElement('p');
    noResults.className = 'select-rich__no-results';
    noResults.textContent = t('No results');
    noResults.hidden = true;
    // Light DOM inside a slotted node, which the shadow styles can't reach.
    noResults.style.cssText =
      'margin: 0; padding: var(--c-spacing-sm) var(--c-spacing-md) 0; color: var(--c-text-quiet);';

    container.append(field, noResults);

    this.#searchInput = input;
    this.#searchField = field;
    this.#noResults = noResults;

    return container;
  }

  #onSearchKeydown = (ev: KeyboardEvent) => {
    switch (ev.key) {
      case 'ArrowDown':
      case 'ArrowUp':
      case 'Enter':
        // The listbox's own handling, minus type-ahead, which would fight the typing.
        this._listboxOnKeyDown(ev);
        if (ev.key === 'Enter') {
          ev.preventDefault();
          this.#swallowInvokerEnterKeyup();
        }
        break;
      case 'Tab':
        this.opened = false;
        break;
      case 'Escape':
        // With text in it, the search field clears itself and stops the key first.
        this.opened = false;
        break;
      /* no default */
    }
  };

  /**
   * Picking an option closes the list and moves focus to the invoker, which opens it
   * again on Enter's keyup. Lion only guards against that for a moment, so a slow
   * press would get past it.
   */
  #swallowInvokerEnterKeyup() {
    const invoker = this._invokerNode;
    const swallow = (event: KeyboardEvent) => {
      if (event.key !== 'Enter') {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      invoker.removeEventListener('keyup', swallow, {capture: true});
    };

    invoker.addEventListener('keyup', swallow, {capture: true});
  }

  #filter(rawValue: string) {
    const value = rawValue.trim().toLowerCase();
    let firstMatch = -1;

    this.formElements.forEach((option: LionOption, index: number) => {
      const haystack = searchText(option).toLowerCase();

      if (!value || haystack.includes(value)) {
        if (this.#filtered.delete(option)) {
          option.hidden = false;
          option.removeAttribute('aria-hidden');
        }
        if (firstMatch === -1 && !option.hidden) {
          firstMatch = index;
        }
      } else if (!option.hidden) {
        option.hidden = true;
        // How Lion's arrow keys know to pass an option by.
        option.setAttribute('aria-hidden', 'true');
        this.#filtered.add(option);
      }
    });

    if (this.#noResults) {
      this.#noResults.hidden = firstMatch !== -1;
    }

    if (value) {
      this.activeIndex = firstMatch;
    }
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
          <slot name="search"></slot>
          <slot name="input"></slot>
          <slot id="options-outlet"></slot>
        </div>
      </div>
    `;
  }
}

/**
 * What the filter matches an option on: its label and hint, plus its
 * `data-keywords`, leaving out slotted extras such as a badge.
 */
function searchText(option: LionOption): string {
  const label = Array.from(option.childNodes)
    .filter((node) => !(node instanceof Element && node.slot))
    .map((node) => node.textContent ?? '')
    .join(' ');

  return [
    label,
    (option as LionOption & {hint?: string | null}).hint ?? '',
    option.getAttribute('data-keywords') ?? '',
  ].join(' ');
}

if (!customElements.get('craft-select-rich')) {
  customElements.define('craft-select-rich', CraftSelectRich);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select-rich': CraftSelectRich;
  }
}
