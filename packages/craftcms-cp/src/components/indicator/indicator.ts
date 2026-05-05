import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {Variant, type VariantKey} from '@src/types';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@src/styles/variants.styles';

export default class CraftIndicator extends LitElement {
  static override styles = [
    variantsStyles,
    css`
      .indicator {
        display: inline-flex;
        aspect-ratio: 1;
        width: var(--c-indicator-size, 0.5em);
        border-radius: var(--c-radius-full);
        color: var(--c-color-on-loud);
        background-color: var(--indicator-background-color);
        border: 1px solid var(--indicator-border-color);
      }

      .indicator--empty {
        --indicator-background-color: none;
        --indicator-border-color: var(--c-text-default);
      }

      .indicator--success {
        --indicator-background-color: var(--c-success-background-color-default);
        --indicator-border-color: var(--c-success-border-color);
      }

      .indicator--danger {
        --indicator-background-color: var(--c-danger-background-color-default);
        --indicator-border-color: var(--c-danger-border-color);
      }

      .indicator--warning {
        --indicator-background-color: var(--c-warning-background-color-default);
        --indicator-border-color: var(--c-warning-border-color);
      }

      .indicator--info {
        --indicator-background-color: var(--c-info-background-color-default);
        --indicator-border-color: var(--c-info-border-color);
      }
    `,
  ];

  @property()
  variant: VariantKey | 'empty' = Variant.Default;

  @property()
  label: string | null = null;

  protected override render(): unknown {
    return html`<span
      class="${classMap({
        indicator: true,
        'indicator--success': this.variant === Variant.Success,
        'indicator--danger': this.variant === Variant.Danger,
        'indicator--warning': this.variant === Variant.Warning,
        'indicator--info': this.variant === Variant.Info,
        'indicator--empty': this.variant === 'empty',
      })}"
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
