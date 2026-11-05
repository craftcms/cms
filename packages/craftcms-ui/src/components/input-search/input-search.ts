import {css, html, type PropertyValues} from 'lit';
import CraftInput from '../input/input.js';
import {t} from '@src/utilities/translate';
import '../icon/icon.js';
import type CraftButton from '../button/button.js';
import '../button/button.js';

/**
 * @summary A search field: an input led by a search icon, which stands in for
 * the visible label, with a button to clear it while it has a value.
 *
 * Unless it's given its own, the field is labeled and placeholdered "Search",
 * and the label is visually hidden. Pressing the clear button, or Escape,
 * empties the field and leaves focus in it.
 *
 * It extends `craft-input`, so everything on that control applies here too.
 *
 * @slot label - The control's label, as an alternative to the `label`
 *   attribute. Defaults to "Search".
 * @slot help-text - Guidance shown below the label.
 * @slot feedback - Validation messages.
 * @slot prefix - Supplied by the component: the search icon. Slotting your
 *   own replaces it.
 * @slot suffix - Content after the input, such as a filter button, in its
 *   own segment. The clear button sits inside the input, before it.
 *
 * @fires clear - The field was emptied with the clear button or Escape.
 */
export default class CraftInputSearch extends CraftInput {
  static override get styles() {
    return [
      ...super.styles,
      css`
        .input-group__container {
          position: relative;
        }

        .input-group__prefix {
          position: absolute;
          inset-block: 0;
          inset-inline-start: 0;
          z-index: 1;
          border: none;
          background: none;
          pointer-events: none;
        }

        .input-group__prefix + .input-group__input ::slotted([slot='input']) {
          --_input-start-start-radius: var(
            --c-input-radius,
            var(--c-radius-sm)
          );
          --_input-end-start-radius: var(--c-input-radius, var(--c-radius-sm));
          padding-inline-start: calc(var(--c-input-spacing-inline) * 2 + 1em);
        }

        .input-group__prefix
          + .input-group__input:not(:has(+ .input-group__suffix))
          ::slotted([slot='input']) {
          --c-focus-outline-style: inherit;
          --c-focus-outline-offset: inherit;
        }

        .input-group__input {
          position: relative;
        }

        .input-search__clear {
          position: absolute;
          z-index: 1;
          inset-block: 0;
          inset-inline-end: var(--_clear-inset);
          display: flex;
          align-items: center;
        }

        :host {
          --_clear-inset: calc(
            (var(--_control-height) - var(--c-size-control-sm)) / 2
          );
        }

        :host([clearable]) .input-group__input ::slotted([slot='input']) {
          padding-inline-end: calc(
            var(--c-size-control-sm) + var(--_clear-inset) * 2
          );
        }

        .input-group__suffix {
          grid-auto-flow: column;
          padding-inline: 0;
        }

        ::slotted([slot='suffix']:not(craft-button)) {
          padding-inline: var(--c-input-spacing-inline);
        }

        :host(:not([inline-suffix])) ::slotted(craft-button[slot='suffix']) {
          margin-inline: 0;
        }
      `,
    ];
  }

  #clearButton: HTMLElement | null = null;

  #swallowEscapeKeyup = false;

  constructor() {
    super();
    this.inputMode = 'search';

    // Capture on the host so Escape is handled before any listener on the
    // input itself empties the field first.
    this.addEventListener('keydown', this.#onKeydown, {capture: true});
    this.addEventListener('keyup', this.#onKeyup, {capture: true});
  }

  override connectedCallback() {
    if (!this.label && !this.querySelector(':scope > [slot="label"]')) {
      this.label = t('Search');
    }

    if (!this.hasAttribute('label-sr-only')) {
      this.labelSrOnly = true;
    }

    if (!this.placeholder) {
      this.placeholder = t('Search');
    }

    super.connectedCallback();

    this._inputNode?.setAttribute('enterkeyhint', 'search');
    if (!this._inputNode?.hasAttribute('autocomplete')) {
      this._inputNode?.setAttribute('autocomplete', 'off');
    }
    this.#syncClearButton();
  }

  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (
      changedProperties.has('modelValue') ||
      changedProperties.has('disabled') ||
      changedProperties.has('readOnly')
    ) {
      this.#syncClearButton();
    }
  }

  /**
   * Adds a slot for the clear button inside the input, at its end, so the
   * input's border and focus ring go around it.
   */
  protected override _inputGroupInputTemplate() {
    return html`
      <div class="input-group__input">
        <slot name="input"></slot>
        <div class="input-search__clear">
          <slot name="search-clear"></slot>
        </div>
      </div>
    `;
  }

  /**
   * Adds the search icon to the field, in the prefix slot.
   */
  override get slots() {
    return {
      ...super.slots,
      prefix: () => ({
        template: html`<craft-icon name="search"></craft-icon>`,
        renderAsDirectHostChild: true,
      }),
    };
  }

  /**
   * Empties the field, moves focus to it, and fires `clear`.
   */
  override clear() {
    if (this.disabled || this.readOnly) {
      return;
    }

    this.modelValue = '';
    this._inputNode?.dispatchEvent(
      new Event('input', {bubbles: true, composed: true})
    );
    this._inputNode?.focus();
    this.dispatchEvent(
      new CustomEvent('clear', {bubbles: true, composed: true})
    );
  }

  #hasValue(): boolean {
    return String(this.modelValue ?? '') !== '';
  }

  #onKeydown = (event: KeyboardEvent) => {
    if (
      event.key !== 'Escape' ||
      event.target !== this._inputNode ||
      !this.#hasValue()
    ) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    this.#swallowEscapeKeyup = true;
    this.clear();
  };

  #onKeyup = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && this.#swallowEscapeKeyup) {
      event.stopPropagation();
      this.#swallowEscapeKeyup = false;
    }
  };

  #syncClearButton() {
    const show = this.#hasValue() && !this.disabled && !this.readOnly;

    if (show && !this.#clearButton) {
      this.#clearButton = this.#createClearButton();
      this.append(this.#clearButton);
      this.toggleAttribute('clearable', true);
      this.requestUpdate();
    } else if (!show && this.#clearButton) {
      if (this.#clearButton.contains(document.activeElement)) {
        this._inputNode?.focus();
      }
      this.#clearButton.remove();
      this.#clearButton = null;
      this.toggleAttribute('clearable', false);
      this.requestUpdate();
    }
  }

  #createClearButton(): CraftButton {
    const button = document.createElement('craft-button') as CraftButton;
    button.slot = 'search-clear';
    button.setAttribute('data-search-clear', '');
    button.setAttribute('type', 'button');
    button.setAttribute('icon', '');
    button.setAttribute('size', 'small');
    button.setAttribute('variant', 'plain');
    button.addEventListener('click', () => this.clear());

    const icon = document.createElement('craft-icon');
    icon.setAttribute('name', 'xmark-large');
    icon.setAttribute('label', t('Clear search'));
    button.append(icon);

    // Out of the Tab order: a keyboard user empties the field with Escape,
    // or by selecting the text and deleting it. Set after the button's first
    // update, which otherwise puts it back to 0.
    button.updateComplete.then(() => {
      button.tabIndex = -1;
    });

    return button;
  }
}

if (!customElements.get('craft-input-search')) {
  customElements.define('craft-input-search', CraftInputSearch);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-input-search': CraftInputSearch;
  }
}
