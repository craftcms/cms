import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {styleMap} from 'lit/directives/style-map.js';
import {Variant, type VariantKey} from '@src/types';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@src/styles/variants.styles';

export type Volume = 'quiet' | 'normal' | 'loud';

export type ColorGroup =
  | 'red'
  | 'orange'
  | 'amber'
  | 'yellow'
  | 'lime'
  | 'green'
  | 'emerald'
  | 'teal'
  | 'cyan'
  | 'sky'
  | 'blue'
  | 'indigo'
  | 'violet'
  | 'purple'
  | 'fuchsia'
  | 'pink'
  | 'rose'
  | 'white'
  | 'gray'
  | 'black'
  | 'neutral'
  | 'brand'
  | 'accent'
  | 'info'
  | 'success'
  | 'warning'
  | 'danger';

/**
 * @summary A small circular indicator used to convey status, category, or color.
 *
 * @prop {VariantKey | 'empty' | 'custom' | 'swatch'} variant - Controls the color source.
 *   - Semantic variants (`default`, `success`, `warning`, `danger`, `info`) draw from
 *     the component's token system and respect the `volume` property.
 *   - `swatch` renders a color from the design system's colorable palette. Requires
 *     the `swatch` attribute to name the color group and respects the `volume` property.
 *   - `custom` renders an arbitrary CSS color or gradient supplied via the `color`
 *     property. Volume is ignored.
 *   - `empty` renders a neutral unfilled dot.
 *
 * @prop {'quiet' | 'normal' | 'loud'} volume - Intensity of the color. Applies to
 *   semantic and swatch variants. Defaults to `normal`.
 *
 * @prop {string} swatch - Color group name from the colorable palette (e.g. `"red"`,
 *   `"blue"`, `"violet"`). Required when `variant="swatch"`.
 *
 * @prop {string} color - Any valid CSS `background` value (hex, rgb, hsl, gradient,
 *   etc.). Required when `variant="custom"`.
 *
 * @prop {string | null} label - Accessible label for the indicator. When provided,
 *   the element receives a title attribute.
 */
export default class CraftIndicator extends LitElement {
  static override styles = [
    variantsStyles,
    css`
      .indicator {
        --_background: var(--background, var(--c-color-fill-loud));
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--c-indicator-size, 0.5em);
        border-radius: var(--c-radius-full);
        color: var(--c-color-on-loud);
        background: var(--_background);
        border: 1px solid var(--c-color-border-loud);
      }

      .indicator--empty {
        background-color: var(--c-color-neutral-fill-quiet);
        border: 1px solid var(--c-color-neutral-border-normal);
      }

      .indicator--volume-quiet {
        background-color: var(--c-color-fill-quiet);
        border-color: var(--c-color-border-quiet);
        color: var(--c-color-on-quiet);
      }

      .indicator--volume-normal {
        background-color: var(--c-color-fill-normal);
        border-color: var(--c-color-border-normal);
        color: var(--c-color-on-normal);
      }

      .indicator--volume-loud {
        background-color: var(--c-color-fill-loud);
        border-color: var(--c-color-border-loud);
        color: var(--c-color-on-loud);
      }

      .indicator--custom {
        background: var(--c-indicator-color);
        border-color: transparent;
      }
    `,
  ];

  /** Controls the color source. Use `swatch` or `custom` for arbitrary colors. */
  @property({reflect: true})
  variant: VariantKey | 'empty' | 'custom' | 'swatch' = Variant.Default;

  /** Accessible label rendered as a title attribute. */
  @property()
  label: string | null = null;

  /** Color intensity for semantic and swatch variants. Defaults to `normal`. */
  @property({reflect: true})
  volume: Volume = 'normal';

  /** Color group name from the colorable palette. Used with `variant="swatch"`. */
  @property({reflect: true})
  swatch: ColorGroup | null = null;

  /** Any valid CSS background value. Used with `variant="custom"`. */
  @property()
  color: string | null = null;

  protected override render(): unknown {
    const isCustom = this.variant === 'custom';
    const isSwatch = this.variant === 'swatch';
    const isSemanticOrDefault =
      !isCustom && !isSwatch && this.variant !== 'empty';

    const inlineStyles = isCustom && this.color
      ? {'--c-indicator-color': this.color}
      : isSwatch && this.swatch
      ? {
          'background-color': `var(--c-color-${this.swatch}-fill-${this.volume})`,
          'border-color': `var(--c-color-${this.swatch}-border-${this.volume})`,
        }
      : {};

    return html`<span
      class="${classMap({
        indicator: true,
        'indicator--success': this.variant === Variant.Success,
        'indicator--danger': this.variant === Variant.Danger,
        'indicator--warning': this.variant === Variant.Warning,
        'indicator--info': this.variant === Variant.Info,
        'indicator--empty': this.variant === 'empty',
        'indicator--custom': isCustom,
        'indicator--volume-quiet': isSemanticOrDefault && this.volume === 'quiet',
        'indicator--volume-normal': isSemanticOrDefault && this.volume === 'normal',
        'indicator--volume-loud': isSemanticOrDefault && this.volume === 'loud',
      })}"
      style="${styleMap(inlineStyles)}"
      title="${this.label ?? ''}"
    >
      <slot></slot>
    </span>`;
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
