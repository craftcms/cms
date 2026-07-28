import {type CSSResultGroup, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import styles from './callout.styles.js';
import '../icon/icon.js';
import {Appearance, type AppearanceValue} from '@src/constants/appearances';
import {Variant, type VariantValue} from '@src/constants/variants';
import variantsStyles from '@src/styles/variants.styles.js';

export default class CraftCallout extends LitElement {
  static override styles: CSSResultGroup = [variantsStyles, styles];

  /** Variant style of the callout */
  @property({reflect: true}) variant: VariantValue = Variant.Neutral;

  /**
   * Appearance style of the callout
   * @TODO maybe drop "outline"?
   */
  @property({reflect: true}) appearance: AppearanceValue =
    Appearance.OutlineFill;

  /** Title of the callout */
  @property() override title: string = '';

  /** Icon to display in the callout */
  @property() icon: string | null = null;

  @property({type: Boolean, attribute: 'hide-icon'}) hideIcon: boolean = false;

  @property({reflect: true})
  rounded: 'all' | 'start' | 'end' | 'none' = 'all';

  @property({reflect: true, type: Boolean})
  inline: boolean = false;

  getDefaultIcon() {
    switch (this.variant) {
      case Variant.Info:
        return 'lightbulb';
      case Variant.Success:
        return 'circle-check';
      case Variant.Warning:
        return 'circle-exclamation';
      case Variant.Danger:
        return 'triangle-exclamation';
      default:
        return null;
    }
  }

  protected override render(): unknown {
    return html`
      ${!this.hideIcon
        ? html`<slot name="icon" class="callout__icon">
            <craft-icon
              name="${this.getDefaultIcon()}"
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
