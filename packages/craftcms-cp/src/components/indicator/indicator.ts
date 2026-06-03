import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {Variant, type VariantKey} from '@src/types';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@src/styles/variants.styles';

/**
 * @summary Indicators are used to visually represent the status of an object.
 * Most of the time, you won't want to use the component directly but instead
 * should use one of the status components.
 *
 * @since 1.0
 */
export default class CraftIndicator extends LitElement {
  static override styles = [
    variantsStyles,
    css`
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
      :host([appearance~='filled-outlined']) .indicator {
        background: var(--_fill);
        border: 1px solid rgba(0, 0, 0, 0.5);
      }

      :host([appearance~='filled']) .indicator {
        background: var(--_fill);
        border-color: transparent;
      }

      :host([appearance~='outlined']) .indicator {
        background: transparent;
        border: 2px solid var(--_fill);
      }
    `,
  ];

  @property()
  size: 'md' | 'lg' = 'md';

  /** @phpType {Color|string} */
  @property()
  fill: string = 'var(--c-color-fill-loud)';

  @property()
  label: string | null = null;

  @property({reflect: true})
  appearance: 'filled' | 'outlined' | 'filled-outlined' = 'filled-outlined';

  getFill() {
    switch (this.fill) {
      case 'live':
      case Variant.Success:
        return 'var(--c-color-success-fill-loud)';
      case Variant.Warning:
        return 'var(--c-color-warning-fill-loud)';
      case Variant.Danger:
        return 'var(--c-color-danger-fill-loud)';
      case Variant.Info:
        return 'var(--c-color-info-fill-loud)';
      case 'draft':
      case 'default':
        return 'var(--c-color-neutral-fill-loud)';

      default:
        return this.fill;
    }
  }

  getSize() {
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
    return html`<span
      style="--fill: ${this.getFill()}; --size: ${this.getSize()}"
      aria-label="${this.label}"
      role="img"
      class="${classMap({
        indicator: true,
        'indicator--outlined': this.appearance === 'outlined',
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
