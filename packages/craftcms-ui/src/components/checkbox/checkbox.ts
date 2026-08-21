import {LionCheckbox} from '@lion/ui/checkbox-group.js';
import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {SsrChoiceInputMixin} from '../../mixins/SsrChoiceInputMixin.js';

export default class CraftCheckbox extends SsrChoiceInputMixin(LionCheckbox) {
  private __inputPatched = false;

  /**
   * LionCheckbox has no native indeterminate state, so mirror this property onto
   * the underlying input. Used for "select all" affordances where some — but not
   * all — items are selected.
   */
  @property({type: Boolean, reflect: true}) indeterminate = false;

  override connectedCallback() {
    super.connectedCallback();
    this.__patchSlottedInputProps();
  }

  // Patch fallback for streaming parsing, where the input can be absent on
  // connect; also mirrors `indeterminate` onto the underlying input.
  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);
    this.__patchSlottedInputProps();

    const input = this._inputNode as HTMLInputElement | undefined;
    if (input) {
      input.indeterminate = this.indeterminate;
    }
  }

  /**
   * Keep the host's `checked`/`disabled` in step with the slotted input when
   * the input's properties are written directly, bypassing Lion. Lion only
   * learns of checkedness through `change`/`user-input-changed`, but the
   * legacy checkbox select "All" handler flips every option with jQuery
   * `.prop({checked, disabled})`, which sets the properties and fires no
   * event — leaving Lion's model stale. A stale `checked` breaks consumers
   * that read it off the host (`SortableCheckboxSelect`'s `isItemChecked`); a
   * stale `disabled` is worse: Lion's `_toggleChecked` refuses user input
   * while the host is disabled, so after unchecking "All" (which re-enables
   * the inputs) no option could be selected.
   *
   * We wrap the input instance's own accessors to mirror external writes into
   * the host, rather than overriding the host's properties — those are
   * load-bearing for Lion (model sync, checkbox groups, its own input
   * reflection), and reading them off the input there breaks that sync.
   * Lion's own writes already match the host value, so the guard makes them
   * no-ops and avoids recursion.
   */
  private __patchSlottedInputProps() {
    if (this.__inputPatched) {
      return;
    }

    const input = this.__slottedInputElement();
    if (!input) {
      return;
    }

    this.__inputPatched = true;

    const host = this;

    for (const prop of ['checked', 'disabled'] as const) {
      const descriptor = Object.getOwnPropertyDescriptor(
        HTMLInputElement.prototype,
        prop
      );
      if (!descriptor?.get || !descriptor.set) {
        continue;
      }

      const {get, set} = descriptor;

      Object.defineProperty(input, prop, {
        configurable: true,
        enumerable: descriptor.enumerable,
        get() {
          return get.call(this);
        },
        set(value: boolean) {
          set.call(this, value);
          if (host[prop] !== value) {
            host[prop] = value;
          }
        },
      });
    }
  }

  private __slottedInputElement(): HTMLInputElement | undefined {
    return Array.from(this.children).find(
      (child): child is HTMLInputElement =>
        child instanceof HTMLInputElement && child.slot === 'input'
    );
  }
  static override get styles() {
    return [
      ...LionCheckbox.styles,
      css`
        /* same as radio, potentially consolidate */
        :host(:not([label-sr-only])) {
          --_gap-x: var(--gap-x, var(--c-spacing-md));
          display: grid;
          align-items: center;
          gap: 0 var(--_gap-x);
          grid-template-areas: 'input label' '. help-text';
          grid-template-columns: auto 1fr;
          grid-template-rows: repeat(2, auto);
        }

        /*
         * Choice inputs render their label/help-text in \`.choice-field__*\`, so
         * Lion's built-in \`label-sr-only\` rule (which targets
         * \`.form-field__label\`) never matches. When the label is hidden we also
         * hide the help text, but both stay available to screen readers: Lion
         * associates the help text with the input via \`aria-describedby\`, and we
         * clip rather than use \`display:none\`/\`visibility:hidden\` so the content
         * remains in the accessibility tree (WCAG 1.3.1 / 4.1.2). \`white-space:
         * nowrap\` avoids clipped multi-line text being announced oddly, and the
         * \`:not(:focus-within)\` guard reveals any focusable help-text content
         * (e.g. links) when focused (WCAG 2.4.7).
         */
        :host([label-sr-only]) .choice-field__label,
        :host([label-sr-only]) .choice-field__help-text:not(:focus-within) {
          position: absolute;
          width: 1px;
          height: 1px;
          overflow: hidden;
          clip-path: inset(100%);
          clip: rect(1px, 1px, 1px, 1px);
          white-space: nowrap;
          border: 0;
          margin: 0;
          padding: 0;
        }

        ::slotted(label) {
          font: inherit;
          grid-area: label;
        }

        ::slotted([slot='input']) {
          background-color: var(--c-input-fill, var(--c-form-control-fill));
          border-width: var(
            --c-input-border-width,
            var(--c-form-control-border-width)
          );
          border-style: var(
            --c-input-border-style,
            var(--c-form-control-border-style)
          );
          border-color: var(
            --c-input-border-color,
            var(--c-form-control-border-color)
          );
          border-radius: var(--c-input-radius, var(--c-radius-sm));
          width: var(--c-checkbox-size);
          height: var(--c-checkbox-size);
        }

        .choice-field__help-text {
          font-size: 1em;
          color: var(--c-text-quiet);
          grid-area: help-text;
        }
      `,
    ];
  }
}

if (!customElements.get('craft-checkbox')) {
  customElements.define('craft-checkbox', CraftCheckbox);
}
