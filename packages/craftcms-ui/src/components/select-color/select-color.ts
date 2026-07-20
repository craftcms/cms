import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {colors} from '@src/constants/colors';
import {t} from '@src/utilities/translate';
import styles from './select-color.styles.js';
import '../select-rich/select-rich.js';
import '../option/option.js';

/**
 * Title-case a color value for display (e.g. `red` -> `Red`).
 */
function titleCase(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * @summary A color picker built on top of the rich select. Renders one option
 * per color from `constants/colors`, with an optional "transparent" option.
 *
 * @since 1.0
 */
export default class CraftSelectColor extends LitElement {
  static override styles = [styles];

  /**
   * Field label, forwarded to the underlying rich select.
   */
  @property()
  label = '';

  /**
   * Field name, forwarded to the underlying rich select.
   */
  @property()
  name = '';

  /**
   * The currently selected color value.
   */
  @property({attribute: 'model-value'})
  modelValue: string | null = null;

  /**
   * When enabled, a "Transparent" option is prepended to the list of colors.
   */
  @property({type: Boolean, reflect: true, attribute: 'allow-transparent'})
  allowTransparent = false;

  /**
   * Renders a color swatch for the given color value. The special `__blank__`
   * value (the "Transparent" option) reuses the checkerboard treatment so it
   * reads as "no color".
   *
   * Structural styling is applied inline (rather than via the component's
   * scoped stylesheet) so the rendered node stays self-contained when Lion's
   * `LionSelectInvoker` clones the selected option's child nodes into the
   * invoker's shadow root, where this component's class rules don't apply.
   * The CSS custom properties referenced here are inherited through the shadow
   * boundary, so they still resolve in the invoker.
   */
  protected _swatchTemplate(color: string) {
    const isTransparent = color === '__blank__';

    const base =
      'flex:0 0 auto;inline-size:1rem;block-size:1rem;' +
      'border-radius:var(--c-radius-full);' +
      'box-shadow:inset 0 0 0 1px rgb(0 0 0 / 15%);';

    // Reuses the checkerboard treatment from input-color so the transparent
    // option reads as "no color".
    const transparent =
      'background:' +
      'linear-gradient(45deg, var(--c-color-neutral-fill-quiet) 25%, transparent 25%),' +
      'linear-gradient(-45deg, var(--c-color-neutral-fill-quiet) 25%, transparent 25%),' +
      'linear-gradient(45deg, transparent 75%, var(--c-color-neutral-fill-quiet) 75%),' +
      'linear-gradient(-45deg, transparent 75%, var(--c-color-neutral-fill-quiet) 75%);' +
      'background-position:0 0, 0 0.25rem, 0.25rem -0.25rem, -0.25rem 0;' +
      'background-size:0.5rem 0.5rem;';

    const style = isTransparent
      ? base + transparent
      : base + `background-color:var(--c-color-${color}-fill-loud);`;

    return html`<span
      class="select-color__swatch"
      style="${style}"
      aria-hidden="true"
    ></span>`;
  }

  /**
   * Builds a single `<craft-option>` for a color value. The wrapper's flex
   * layout is applied inline so it survives being cloned into the invoker's
   * shadow root alongside the swatch (see `_swatchTemplate`).
   */
  protected _optionTemplate(color: string, label: string) {
    return html`<craft-option .choiceValue=${color}>
      <span
        class="select-color__option"
        style="display:flex;align-items:center;gap:var(--c-spacing-sm);"
      >
        ${this._swatchTemplate(color)}
        <span class="select-color__label" style="white-space:nowrap;"
          >${label}</span
        >
      </span>
    </craft-option>`;
  }

  /**
   * Bridges the inner rich select's selection back out to consumers.
   *
   * The inner `craft-select-rich` emits Lion's `model-value-changed` event with
   * `bubbles: true` but NOT `composed`, so it stays inside this component's
   * shadow root and never reaches Vue (or other host listeners). We catch it
   * here, sync our own host `modelValue` from the inner select, then re-dispatch
   * a `composed` `model-value-changed` from the host so v-model wrappers can
   * read the up-to-date `this.modelValue` off `event.target`.
   */
  protected _handleModelValueChanged(event: Event) {
    // Don't let the inner (non-composed) event escape; we re-dispatch our own.
    event.stopPropagation();

    const inner = event.target as {modelValue?: string | null} | null;
    this.modelValue = inner?.modelValue ?? null;

    // Re-dispatch from the host so it crosses the shadow boundary (composed)
    // and Vue's `@model-value-changed` listener fires with the host as target.
    this.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, composed: true})
    );
  }

  protected override render() {
    return html`
      <craft-select-rich
        label=${this.label}
        name=${this.name}
        .modelValue=${this.modelValue}
        @model-value-changed=${this._handleModelValueChanged}
      >
        ${this.allowTransparent
          ? this._optionTemplate('__blank__', t('Transparent'))
          : ''}
        ${colors.map((color) =>
          this._optionTemplate(color, t(titleCase(color)))
        )}
      </craft-select-rich>
    `;
  }
}

if (!customElements.get('craft-select-color')) {
  customElements.define('craft-select-color', CraftSelectColor);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-select-color': CraftSelectColor;
  }
}
