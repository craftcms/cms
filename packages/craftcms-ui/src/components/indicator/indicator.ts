import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@src/styles/variants.styles';
import {Color, colors} from '@src/constants/colors';
import {variants} from '@src/constants/variants';
import {Appearance} from '@src/constants/appearances';

/**
 * @summary A small dot representing the status of an object.
 *
 * Most of the time you want `craft-status`, which covers the fixed vocabulary
 * of object states. Reach for this when the dot means something that
 * vocabulary does not cover, since it takes any palette colour or CSS colour.
 *
 * @since 1.0
 */
export default class CraftIndicator extends LitElement {
  static override styles = [
    variantsStyles,
    css`
      :host {
        display: contents;
      }

      .indicator {
        --_fill: var(--fill, var(--c-color-fill-loud));
        --_size: var(--size, 0.5em);
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--_size);
        border-radius: var(--c-radius-full);
        background: var(--_fill);
        /* Longhands, so a fill that isn't a colour (a gradient, say) only
           drops the border's colour rather than the whole border, and the dot
           keeps its size. */
        border-width: 1px;
        border-style: solid;
        border-color: var(--_fill);
      }

      /*
       * Appearances. Classes rather than :host([appearance]) selectors, because
       * the default depends on the fill (see effectiveAppearance), and an
       * unset appearance isn't reflected to the attribute.
       */
      .indicator--outline-fill {
        border-color: rgba(0, 0, 0, 0.5);
      }

      /* A dark outline would vanish against a black dot. */
      .indicator--outline-fill.indicator--black {
        border-color: var(--color-white);
      }

      .indicator--solid {
        border-color: transparent;
      }

      .indicator--outline {
        background: transparent;
        border-color: var(--_fill);
      }
    `,
  ];

  /**
   * Dot size. Both are defined in `em`, so either scales with the surrounding
   * font size — set `font-size` on the host to fine-tune.
   */
  @property()
  size: 'md' | 'lg' = 'md';

  /**
   * The dot's colour. A status variant (`success`, `warning`, `danger`,
   * `info`) or a palette swatch resolves to the matching `--c-color-*` token;
   * any other value — a hex code, `rgb()`, a custom property — is used
   * verbatim.
   *
   * @phpType {Color|string}
   */
  @property({reflect: true})
  fill: string = 'var(--c-color-fill-loud)';

  /**
   * Accessible name, exposed as `aria-label`. Set it whenever the dot is not
   * purely decorative — a status conveyed by colour alone is conveyed to
   * nobody who cannot see it.
   */
  @property()
  label: string | null = null;

  /**
   * How the dot is drawn: `solid` is filled with no outline, `outline-fill` is
   * filled with a subtle outline (white on a black dot), and `outline` is a
   * hollow ring over a transparent centre.
   *
   * Defaults to `outline-fill` for white and black fills, which would
   * otherwise disappear against a light or dark surface, and `solid` for
   * everything else.
   */
  @property({reflect: true})
  appearance?: 'solid' | 'outline-fill' | 'outline';

  /** The appearance to draw: the one set, or the default for the fill. */
  private get effectiveAppearance(): 'solid' | 'outline-fill' | 'outline' {
    if (this.appearance) {
      return this.appearance;
    }

    return this.fill === Color.White || this.fill === Color.Black
      ? Appearance.OutlineFill
      : Appearance.Solid;
  }

  protected getFill() {
    // If the fill is known swatch
    if ((colors as string[]).includes(this.fill)) {
      return `var(--c-color-${this.fill}-fill-loud)`;
    }

    // If it's a known variant
    if ((variants as string[]).includes(this.fill)) {
      return `var(--c-color-${this.fill}-fill-loud)`;
    }

    return this.fill;
  }

  protected getSize() {
    switch (this.size) {
      case 'md':
        return '0.6em';
      case 'lg':
        return '1em';
      default:
        return this.size;
    }
  }

  protected override render(): unknown {
    // Without a label the indicator is purely decorative, so omit the image role
    // and name rather than exposing an unnamed `role="img"`.
    return html`<span
      style="--fill: ${this.getFill()}; --size: ${this.getSize()}"
      aria-label="${this.label ?? nothing}"
      role="${this.label ? 'img' : nothing}"
      class="${classMap({
        indicator: true,
        [`indicator--${this.effectiveAppearance}`]: true,
        'indicator--black': this.fill === Color.Black,
      })}"
    ></span>`;
  }
}

if (!customElements.get('craft-indicator')) {
  customElements.define('craft-indicator', CraftIndicator);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-indicator': CraftIndicator;
  }
}
