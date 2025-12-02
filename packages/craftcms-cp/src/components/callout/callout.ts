import {type CSSResultGroup, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './callout.styles.js';
import '../icon/icon.js';
import {
  type VariantKey,
  type AppearanceKey,
  Appearance,
  Variant,
} from '@/types/index.js';
import variantsStyles from '@/styles/variants.styles';

export default class CraftCallout extends LitElement {
  static override styles: CSSResultGroup = [variantsStyles, styles];

  /** Variant style of the callout */
  @property({reflect: true}) variant: VariantKey = Variant.Default;

  /**
   * Appearance style of the callout
   * @TODO maybe drop "outline"?
   */
  @property({reflect: true}) appearance: AppearanceKey = Appearance.OutlineFill;

  /** Title of the callout */
  @property() override title: string = '';

  /** Icon to display in the callout */
  @property() icon: string | null = null;

  getIcon() {
    switch (this.variant) {
      case Variant.Info:
        return 'lightbulb';
      case Variant.Success:
        return 'check-circle';
      case Variant.Warning:
        return 'exclamation-circle';
      case Variant.Danger:
        return 'exclamation-triangle';
      default:
        return null;
    }
  }

  protected override render(): unknown {
    const hasIcon = !!this.icon || !!this.querySelector('[slot="icon"]');

    return html`
      ${hasIcon
        ? html`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getIcon()}"
              style="font-size: 0.9em"
            ></craft-icon>
          </slot>`
        : nothing}
      <div class="callout__body">
        <slot name="title" class="callout__title">${this.title}</slot>
        <div class="callout__description">
          <slot></slot>
        </div>
      </div>
    `;
  }
}

if (!customElements.get('craft-callout')) {
  customElements.define('craft-callout', CraftCallout);
}
