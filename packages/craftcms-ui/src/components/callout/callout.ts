import {type CSSResultGroup, html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import styles from './callout.styles.js';
import '../icon/icon.js';
import {Appearance, type AppearanceValue} from '@src/constants/appearances';
import {Variant, type VariantValue} from '@src/constants/variants';
import {Paddable} from '@src/mixins/Paddable.js';
import variantsStyles from '@src/styles/variants.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {styleMap} from 'lit/directives/style-map.js';

/**
 * @summary A boxed message: an optional icon, an optional title, body content,
 * and an optional trailing action.
 *
 * @attr size - `small` steps the box down to `--c-text-sm` and tightens the gap
 *   between the icon and the text. Defaults to `auto`, which leaves the callout
 *   at the surrounding text size. Note this is type only — the padding below is
 *   `--c-spacing-*`, i.e. rem-based, so it does not scale with the size; set
 *   `padding` alongside it if a small callout wants a tighter box too.
 *
 * @attr padding - Spacing applied to the callout box. Accepts
 *   `sm`/`md`/`lg`/`xl` (mapped to `--c-spacing-*`), `0` or `none`, a unitless
 *   number (treated as pixels), or any CSS length.
 *
 *   The callout's own default is asymmetric — `--c-spacing-sm` on the block
 *   axis, `--c-spacing-md` on the inline one — so leaving `padding` off keeps
 *   exactly that pair. A value that *is* given applies to both axes, the way a
 *   one-value CSS `padding` shorthand does; scaling the two axes apart from a
 *   single value would make `padding="md"` mean something other than `md`.
 *   Reach for `--c-callout-padding-block` / `--c-callout-padding-inline` to
 *   keep an asymmetric pair of your own.
 *
 *   Note that an `inline` callout also carries a little padding on the host
 *   itself, which is part of that pill treatment rather than of the box, and
 *   isn't governed by this attribute.
 *
 * @cssproperty --c-callout-radius - Corner radius, applied to whichever
 *   corners `rounded` selects. Defaults to `--c-radius-md`.
 * @cssproperty --c-callout-padding-block - Block-axis padding of the callout
 *   box when `padding` is absent. Defaults to `--c-spacing-sm`.
 * @cssproperty --c-callout-padding-inline - Inline-axis padding of the callout
 *   box when `padding` is absent. Defaults to `--c-spacing-md`.
 */
export default class CraftCallout extends Paddable(LitElement, {
  customProperty: ['--_callout-padding-block', '--_callout-padding-inline'],
}) {
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

  @property()
  size: 'small' | 'auto' = 'auto';

  /**
   * The icon actually rendered: the one that was set, or the variant's
   * default. Held in state so the icon and the layout around it are decided
   * together — a callout with no icon to show collapses the icon column
   * rather than leaving a gap where one would have gone.
   */
  @state() private resolvedIcon: string | null = null;

  protected override willUpdate(): void {
    this.resolvedIcon = this.icon ?? this.getDefaultIcon();
  }

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
    const hasTitle = this.title || !!this.querySelector('[slot="title"]');
    // A slotted icon counts even when there's no name to resolve — it's the
    // consumer bringing their own artwork.
    const hasIcon =
      !this.hideIcon &&
      (!!this.resolvedIcon || !!this.querySelector('[slot="icon"]'));

    return html`
      <div
        data-variant="${this.variant}"
        style="${styleMap(this.paddingStyles)}"
        class="${classMap({
          callout: true,
          'callout--hide-icon': !hasIcon,
          'callout--title': hasTitle,
          'callout--small': this.size === 'small',
        })}"
      >
        ${hasIcon
          ? html`<slot name="icon" class="callout__icon">
              ${this.resolvedIcon
                ? html`<craft-icon
                    name="${this.resolvedIcon}"
                    style="font-size: 0.9em"
                  ></craft-icon>`
                : nothing}
            </slot>`
          : nothing}
        ${hasTitle
          ? html`<div class="callout__title">
              <slot name="title">${this.title}</slot>
            </div>`
          : nothing}

        <div class="callout__description">
          <slot></slot>
        </div>

        <div class="callout__action"><slot name="action"></slot></div>
      </div>
    `;
  }
}

if (!customElements.get('craft-callout')) {
  customElements.define('craft-callout', CraftCallout);
}
