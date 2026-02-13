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
        color: var(--c-color-on-emphasis);
        background-color: var(--c-color-bg-emphasis);
        border: 1px solid var(--c-color-border-emphasis);
      }

      .indicator--empty {
        background-color: var(--c-color-neutral-bg-faint);
        border: 1px solid var(--c-color-neutral-border-normal);
      }
    `,
  ];

  @property({reflect: true})
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
