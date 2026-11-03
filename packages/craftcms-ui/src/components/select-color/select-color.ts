import {html, nothing, render, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {colors as paletteColors} from '@src/constants/colors';
import {HostAriaReferences} from '@src/mixins/HostAriaReferences';
import {emitChange, emitInput} from '@src/utilities/form-events';
import {t} from '@src/utilities/translate';
import CraftSelectRich from '../select-rich/select-rich.js';
import styles from './select-color.styles.js';
import '../option/option.js';

/**
 * Title-case a color value for display (e.g. `red` -> `Red`).
 */
function titleCase(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * @summary A color picker: a rich select whose options are the colors from
 * `constants/colors` (or {@link CraftSelectColor.colors}, when narrowed), with
 * an optional "transparent" option.
 *
 * Everything else, including the label, help text, validation and the way a
 * surrounding `craft-field` names it, comes from {@link CraftSelectRich}.
 * Outside a field, `aria-labelledby` and `aria-describedby` on the host name
 * and describe the button after elements elsewhere, such as a table column
 * heading.
 *
 * @since 1.0
 *
 * @attr model-value - The selected color.
 *
 * @fires input - Emitted when a color is picked.
 * @fires change - Emitted when a color is picked. Picking is a commit, so it
 *   follows each `input`.
 * @fires model-value-changed - The selected color changed.
 *
 * `model-value-changed` is Lion's own protocol name, not one this package
 * chose, and it is kept because Lion's form system dispatches and listens for
 * it. Prefer the native events above: they are the contract this package
 * supports, and they carry the component as `event.target`.
 *
 * @cssproperty [--c-select-color-swatch-edge=rgb(0 0 0 / 15%)] - Edge of the
 *   no-color swatch, which would otherwise blend into a dark surface. The dark
 *   theme lightens it.
 * @cssproperty [--c-select-color-swatch-ring=transparent] - Ring around every
 *   swatch. The dark theme sets a faint one.
 */
export default class CraftSelectColor extends HostAriaReferences(
  CraftSelectRich
) {
  static override get styles() {
    return [...super.styles, styles];
  }

  /**
   * When enabled, a blank option (labeled {@link blankLabel}) is prepended
   * to the list of colors.
   */
  @property({type: Boolean, reflect: true, attribute: 'allow-transparent'})
  allowTransparent = false;

  /**
   * Label for the blank option (default "Transparent").
   */
  @property({attribute: 'blank-label'})
  blankLabel: string | null = null;

  /**
   * Offered colors, in order. Defaults to the shared palette.
   */
  @property({type: Array})
  colors: string[] = [...paletteColors];

  /** Marks the control as required. */
  @property({type: Boolean, reflect: true})
  required = false;

  /**
   * Renders a color swatch for the given color value. The special `__blank__`
   * value (the "Transparent" option) reuses the checkerboard treatment so it
   * reads as "no color". Other swatches match the header of a card in that
   * color, fill and border alike.
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
    // Every swatch casts a slight shadow and has an edge in its card header's
    // border color (see --c-select-color-swatch-edge for no color's). The dark
    // theme rings them too (see --c-select-color-swatch-ring).
    const shadows = [
      '0 0 0 1px var(--c-select-color-swatch-ring, transparent)',
      '0 1px 1px rgb(0 0 0 / 20%)',
      `inset 0 0 0 1px ${this._swatchBorder(color)}`,
    ];

    const base =
      'flex:0 0 auto;inline-size:1rem;block-size:1rem;' +
      'border-radius:var(--c-radius-full);' +
      `box-shadow:${shadows.join(', ')};`;

    const transparent =
      'background:' +
      'linear-gradient(45deg, var(--c-color-neutral-fill-quiet) 25%, transparent 25%),' +
      'linear-gradient(-45deg, var(--c-color-neutral-fill-quiet) 25%, transparent 25%),' +
      'linear-gradient(45deg, transparent 75%, var(--c-color-neutral-fill-quiet) 75%),' +
      'linear-gradient(-45deg, transparent 75%, var(--c-color-neutral-fill-quiet) 75%);' +
      'background-position:0 0, 0 0.25rem, 0.25rem -0.25rem, -0.25rem 0;' +
      'background-size:0.5rem 0.5rem;';

    // White cards take the white surface rather than white's own fill.
    const fill =
      color === 'white'
        ? 'var(--c-surface-white)'
        : `var(--c-color-${color}-fill-quiet)`;

    const style = isTransparent
      ? base + transparent
      : base + `background-color:${fill};`;

    return html`<span
      class="select-color__swatch"
      style="${style}"
      aria-hidden="true"
    ></span>`;
  }

  /**
   * The border color of a card header in the given color, mirroring the
   * card's own exceptions for black and white.
   */
  protected _swatchBorder(color: string): string {
    switch (color) {
      case '__blank__':
        return 'var(--c-select-color-swatch-edge, rgb(0 0 0 / 15%))';
      case 'black':
        return 'color-mix(in srgb, var(--c-color-black-on-quiet), transparent 80%)';
      case 'white':
        return 'var(--c-table-border-color)';
      default:
        return `var(--c-color-${color}-border-quiet)`;
    }
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
   * The rich select only adopts a `model-value` once its options exist, so the
   * attribute is applied as a property, which waits for them.
   */
  static override get observedAttributes() {
    return [...super.observedAttributes, 'model-value'];
  }

  override attributeChangedCallback(
    name: string,
    old: string | null,
    value: string | null
  ) {
    if (name === 'model-value') {
      this.modelValue = value ?? '';
      return;
    }
    super.attributeChangedCallback(name, old, value);
  }

  constructor() {
    super();
    /** No color is picked until one is asked for. */
    this.hasNoDefaultSelected = true;
    // Picking a color is a commit, so the pair fires together, the way a radio
    // group's does.
    this.addEventListener('model-value-changed', (event) => {
      if (
        event.target === this &&
        (event as CustomEvent).detail?.isTriggeredByUser
      ) {
        emitInput(this);
        emitChange(this);
      }
    });
  }

  override connectedCallback() {
    super.connectedCallback();
    this.#renderOptions();
  }

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    if (
      changed.has('colors') ||
      changed.has('allowTransparent') ||
      changed.has('blankLabel')
    ) {
      this.#renderOptions();
    }
  }

  /** Renders the options into the listbox, as `craft-combobox` does. */
  #renderOptions() {
    if (!this._listboxNode) {
      return;
    }

    render(
      html`${this.allowTransparent
        ? this._optionTemplate('__blank__', this.blankLabel ?? t('Transparent'))
        : nothing}${this.colors.map((color) =>
        this._optionTemplate(color, t(titleCase(color)))
      )}`,
      this._listboxNode
    );
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
