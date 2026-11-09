import {html, nothing, render, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {colors as paletteColors} from '@src/constants/colors';
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

/** Whether a color is a hex value rather than a palette color's name. */
function isHex(color: string): boolean {
  return color.startsWith('#');
}

/**
 * @summary A color picker: a rich select whose options are the colors from
 * `constants/colors` (or {@link CraftSelectColor.colors}, when narrowed, which
 * can also list hex colors), with optional "transparent" and "Custom…" options.
 *
 * Everything else, including the label, help text, validation and the way a
 * surrounding `craft-field` names it, comes from {@link CraftSelectRich}.
 * Outside a field, `labelled-by` and `described-by` on the host name and
 * describe the field after elements elsewhere, such as a table column
 * heading. No color is selected until one is given.
 *
 * @since 1.0
 *
 * @attr value - The selected color.
 * @attr labelled-by - Ids of elements elsewhere that label the control, such
 *   as a table column heading.
 * @attr described-by - Ids of elements elsewhere that describe the control.
 *
 * @fires input - Emitted when a color is picked.
 * @fires change - Emitted when a color is picked. Picking is a commit, so it
 *   follows each `input`.
 *
 * @cssproperty [--c-select-color-swatch-edge=rgb(0 0 0 / 15%)] - Edge of the
 *   no-color swatch, which would otherwise blend into a dark surface. The dark
 *   theme lightens it.
 * @cssproperty [--c-select-color-swatch-ring=transparent] - Ring around every
 *   swatch. The dark theme sets a faint one.
 */
export default class CraftSelectColor extends CraftSelectRich {
  static override styles = [...CraftSelectRich.styles, styles];

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
   * When enabled, a "Custom…" option is appended to the list of colors, for
   * a color that isn't one of them. Its value is `__custom__`, and whoever
   * offers it takes the color itself, such as with a `craft-input-color`.
   */
  @property({type: Boolean, reflect: true, attribute: 'allow-custom'})
  allowCustom = false;

  /**
   * Offered colors, in order: palette color names, or hex colors such as
   * `#e5422b`. Defaults to the shared palette.
   */
  @property({type: Array})
  colors: string[] = [...paletteColors];

  /**
   * Names for the offered colors, by color. A palette color without one is
   * named after itself (`red` is "Red"), and a hex color by its value.
   */
  @property({type: Object})
  labels: Record<string, string> = {};

  /** The options this component rendered. */
  #renderedOptions: Element[] = [];

  /**
   * Renders a color swatch for the given color value. The special `__blank__`
   * value (the "Transparent" option) and a `transparent` color reuse the
   * checkerboard treatment so they read as "no color". Hex colors fill their
   * swatch. Other swatches match the header of a card in that color, fill and
   * border alike.
   *
   * Structural styling is applied inline (rather than via the component's
   * scoped stylesheet) so the rendered node stays self-contained when the
   * rich select copies the selected option's content into its field, where
   * this component's class rules don't apply.
   */
  protected _swatchTemplate(color: string) {
    // The "Custom…" option has no color of its own, so it keeps the swatch's
    // space only, lining its name up with the others.
    if (color === '__custom__') {
      return html`<span
        class="select-color__swatch"
        style="flex:0 0 auto;inline-size:1rem;block-size:1rem;"
        aria-hidden="true"
      ></span>`;
    }

    const isTransparent = color === '__blank__' || color === 'transparent';
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
    const fill = isHex(color)
      ? color
      : color === 'white'
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
   * card's own exceptions for black and white. A hex color or
   * `transparent`, which has no card, takes the no-color swatch's edge.
   */
  protected _swatchBorder(color: string): string {
    if (isHex(color) || color === 'transparent') {
      return this._swatchBorder('__blank__');
    }

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
   * layout is applied inline so it survives being copied into the field
   * alongside the swatch (see `_swatchTemplate`).
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

  protected override get _selectsFirstOption(): boolean {
    return false;
  }

  override connectedCallback() {
    // Rendered first, so the rich select finds them as it connects.
    this.#renderOptions();
    super.connectedCallback();
  }

  override updated(changed: PropertyValues<this>) {
    super.updated(changed);

    if (
      changed.has('colors') ||
      changed.has('labels') ||
      changed.has('allowTransparent') ||
      changed.has('blankLabel') ||
      changed.has('allowCustom')
    ) {
      this.#renderOptions();
    }
  }

  /**
   * Renders the options as the host's children. Each render goes through a
   * fresh container, since the host's own render part belongs to its slots.
   */
  #renderOptions() {
    const container = document.createElement('div');
    render(
      html`${this.allowTransparent
        ? this._optionTemplate('__blank__', this.blankLabel ?? t('Transparent'))
        : nothing}${this.colors.map((color) =>
        this._optionTemplate(
          color,
          this.labels[color] ?? (isHex(color) ? color : t(titleCase(color)))
        )
      )}${this.allowCustom
        ? this._optionTemplate('__custom__', t('Custom…'))
        : nothing}`,
      container
    );

    for (const option of this.#renderedOptions) {
      option.remove();
    }
    this.#renderedOptions = [...container.children];
    this.append(...this.#renderedOptions);
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
