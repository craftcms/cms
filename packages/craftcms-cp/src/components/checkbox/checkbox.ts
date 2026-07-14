import {LionCheckbox} from '@lion/ui/checkbox-group.js';
import {css, type PropertyValues} from 'lit';
import {SsrChoiceInputMixin} from '../../mixins/SsrChoiceInputMixin.js';

export default class CraftCheckbox extends SsrChoiceInputMixin(LionCheckbox) {
  private __inputPatched = false;

  override connectedCallback() {
    super.connectedCallback();
    this.__patchSlottedInputProps();
  }

  // Fallback for streaming parsing, where the input can be absent on connect.
  override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);
    this.__patchSlottedInputProps();
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
        :host {
          --_gap-x: var(--gap-x, var(--c-spacing-md));
          display: grid;
          align-items: center;
          gap: 0 var(--_gap-x);
          grid-template-areas: 'input label' '. help-text';
          grid-template-columns: auto 1fr;
          grid-template-rows: repeat(2, auto);
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
          width: var(--c-size-control-2xs);
          height: var(--c-size-control-2xs);
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
