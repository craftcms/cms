import {LitElement, html, css} from 'lit';
import {property} from 'lit/decorators.js';
import {Variant, type VariantKey} from '@/types';
import {classMap} from 'lit/directives/class-map.js';
import variantsStyles from '@/styles/variants.styles';
import CraftCombobox from '@/components/combobox/combobox';

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
        border: 1px solid var(--c-color-bg-emphasis);
      }
    `,
  ];

  @property({reflect: true})
  variant: VariantKey = Variant.Default;

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
