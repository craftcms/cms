import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@src/styles/variants.styles';
import {colors} from '@src/constants/colors';
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
        border: 1px solid var(--_fill);
      }

      /* Appearances */
      :host([appearance~='outline-fill']) .indicator {
        background: var(--_fill);
        border: 1px solid rgba(0, 0, 0, 0.5);
      }

      :host([appearance~='solid']) .indicator {
        background: var(--_fill);
        border-color: transparent;
      }

      :host([appearance~='outline']) .indicator {
        background: transparent;
        border: 2px solid var(--_fill);
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
   * How the dot is drawn: `outline-fill` is filled with a subtle outline,
   * `solid` is filled with none, and `outline` is a hollow ring over a
   * transparent centre.
   */
  @property({reflect: true})
  appearance: 'solid' | 'outline-fill' | 'outline' = Appearance.OutlineFill;

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
        'indicator--outline': this.appearance === Appearance.Outline,
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
