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
import {
  hasSlotted,
  LightDomController,
} from '@src/controllers/LightDomController';

/**
 * @summary A boxed message: an optional icon, an optional title, body content,
 * and an optional trailing action. Use a callout to explain the state of the
 * page or the consequences of an action, in place rather than in a toast.
 *
 * `variant` carries the meaning and supplies a default icon. `appearance`
 * controls how loudly that meaning is stated. Each region collapses when it
 * has nothing in it, so a callout with only body content is a plain box.
 *
 * @slot - The callout's body content.
 * @slot title - Title content, shown above the body. Takes precedence over the
 *   `label` attribute, which is the shorthand for the same region.
 * @slot icon - Leading artwork, replacing the icon the variant would supply.
 *   Slotted content is honored even when no icon name resolves, and is
 *   suppressed entirely by `hide-icon`.
 * @slot action - Trailing content, shown after the body. Typically a button or
 *   a link.
 *
 * @attr size - `small` steps the box down to `--c-text-sm` and tightens the gap
 *   between the icon and the text. Defaults to `auto`, which leaves the callout
 *   at the surrounding text size. Note this is type only — the padding below is
 *   `--c-spacing-*`, i.e. rem-based, so it does not scale with the size; set
 *   `padding` alongside it if a small callout wants a tighter box too.
 *
 * @attr {'sm'|'md'|'lg'|'xl'|'none'|'0'} padding - Spacing applied to the callout box. Accepts `sm`, `md`,
 *   `lg`, and `xl` (mapped to `--c-spacing-*`), or `0`/`none`. Values off that
 *   scale are ignored; set `--c-callout-padding-block` /
 *   `--c-callout-padding-inline` for anything else.
 *
 *   The callout's own default is asymmetric — `--c-spacing-sm` on the block
 *   axis, `--c-spacing-md` on the inline one — so leaving `padding` off keeps
 *   exactly that pair. A value that *is* given applies to both axes, the way a
 *   one-value CSS `padding` shorthand does; scaling the two axes apart from a
 *   single value would make `padding="md"` mean something other than `md`.
 *   Reach for the custom properties to keep an asymmetric pair of your own.
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
 * @cssproperty --c-callout-icon-padding-inline - Inline-axis padding around
 *   the icon. Defaults to `--c-spacing-sm`.
 * @cssproperty --c-callout-text-padding-inline - Inline-axis padding around
 *   the title and description. Defaults to `--c-spacing-sm`.
 * @csspart base - The callout's box, which carries its fill and border.
 */
export default class CraftCallout extends Paddable(LitElement, {
  customProperty: ['--_callout-padding-block', '--_callout-padding-inline'],
}) {
  static override styles: CSSResultGroup = [variantsStyles, styles];

  /**
   * The semantic color group the callout draws its tokens from, and what the
   * message means. Every variant except `neutral` also supplies a default
   * icon.
   */
  @property({reflect: true}) variant: VariantValue = Variant.Neutral;

  /**
   * How prominently the variant color is applied. `solid` is the loudest and
   * `plain` the quietest.
   */
  // @TODO maybe drop "outline"?
  @property({reflect: true}) appearance: AppearanceValue =
    Appearance.OutlineFill;

  /**
   * Title text, shown above the body. A shorthand for the `title` slot; slot
   * it instead when the title needs markup.
   *
   * Named `label` rather than `title` on purpose, as on `craft-pane`: `title`
   * is a global HTML attribute, so it would paint a native browser tooltip
   * over the whole callout and be announced in addition to the visible title.
   */
  @property() label: string = '';

  /**
   * Name of the icon to show, replacing the one the variant would supply.
   * Leave it unset to take the variant's default.
   */
  @property() icon: string | null = null;

  /**
   * Suppresses the icon region entirely, including a variant's default icon
   * and anything slotted into `icon`.
   */
  @property({type: Boolean, attribute: 'hide-icon'}) hideIcon: boolean = false;

  /**
   * Which corners are rounded. Use `start` or `end` when the callout is
   * flush against the top or bottom of another surface, and `none` when it
   * spans a container edge to edge.
   */
  @property({reflect: true})
  rounded: 'all' | 'start' | 'end' | 'none' = 'all';

  /**
   * Renders the callout as an inline pill that flows with surrounding text,
   * rather than as a block-level box.
   */
  @property({reflect: true, type: Boolean})
  inline: boolean = false;

  /** See the `size` attribute above. */
  @property()
  size: 'small' | 'auto' = 'auto';

  /**
   * The icon actually rendered: the one that was set, or the variant's
   * default. Held in state so the icon and the layout around it are decided
   * together — a callout with no icon to show collapses the icon column
   * rather than leaving a gap where one would have gone.
   */
  @state() private resolvedIcon: string | null = null;

  /**
   * The icon and title regions only render when they have something in them,
   * so their slots can't report their own changes. The controller re-renders
   * the callout whenever its light DOM moves, so content slotted in later
   * shows up.
   */
  private _lightDom = new LightDomController(this);

  /** Re-reads the first line's font when the text reflows, as when a web font loads. */
  private _textObserver?: ResizeObserver;

  override connectedCallback(): void {
    super.connectedCallback();
    this._textObserver = new ResizeObserver(() => this.syncFirstLineFont());
    this.updateComplete.then(() => {
      const text = this.renderRoot.querySelector('.callout__text');
      if (text) {
        this._textObserver?.observe(text);
      }
    });
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this._textObserver?.disconnect();
    this._textObserver = undefined;
  }

  protected override updated(): void {
    this.syncFirstLineFont();
  }

  /**
   * Gives the icon column's strut the font of the first line of text, so the
   * icon centers on that line even when it's a slotted heading set larger
   * than the callout's own text.
   */
  private syncFirstLineFont(): void {
    const strut = this.renderRoot.querySelector<HTMLElement>('.callout__strut');
    if (!strut) {
      return;
    }

    const slot =
      this.renderRoot.querySelector<HTMLSlotElement>('slot[name="title"]') ??
      this.renderRoot.querySelector<HTMLSlotElement>(
        '.callout__description slot'
      );
    const text = firstText(slot?.assignedNodes({flatten: true}) ?? []);
    // Text slotted straight into the callout takes its font from the slot.
    const parent =
      text?.parentElement === this ? text.assignedSlot : text?.parentElement;
    const style = parent ? getComputedStyle(parent) : null;

    strut.style.fontFamily = style?.fontFamily ?? '';
    strut.style.fontSize = style?.fontSize ?? '';
    strut.style.lineHeight = style?.lineHeight ?? '';
  }

  protected override willUpdate(): void {
    this.resolvedIcon = this.icon ?? this.getDefaultIcon();
  }

  protected getDefaultIcon() {
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
    const hasTitle = this.label || hasSlotted(this, 'title');
    // A slotted icon counts even when there's no name to resolve — it's the
    // consumer bringing their own artwork.
    const hasIcon =
      !this.hideIcon && (!!this.resolvedIcon || hasSlotted(this, 'icon'));

    return html`
      <div
        part="base"
        data-variant="${this.variant}"
        style="${styleMap(this.paddingStyles)}"
        class="${classMap({
          callout: true,
          'callout--hide-icon': !hasIcon,
          'callout--small': this.size === 'small',
        })}"
      >
        <div class="callout__body">
          ${hasIcon
            ? html`<span class="callout__icon">
                <span class="callout__strut" aria-hidden="true">&#8203;</span>
                <slot name="icon">
                  ${this.resolvedIcon
                    ? html`<craft-icon
                        name="${this.resolvedIcon}"
                        style="font-size: 0.9em"
                      ></craft-icon>`
                    : nothing}
                </slot>
              </span>`
            : nothing}
          <div class="callout__text">
            ${hasTitle
              ? html`<div class="callout__title">
                  <slot name="title">${this.label}</slot>
                </div>`
              : nothing}

            <div class="callout__description">
              <slot></slot>
            </div>
          </div>
        </div>

        <div class="callout__action"><slot name="action"></slot></div>
      </div>
    `;
  }
}

/** The first text node with something in it, in document order. */
function firstText(nodes: Iterable<Node>): Text | null {
  for (const node of nodes) {
    if (node instanceof Text) {
      if (node.data.trim()) {
        return node;
      }
    } else if (node instanceof Element) {
      const text = firstText(node.childNodes);
      if (text) {
        return text;
      }
    }
  }

  return null;
}

if (!customElements.get('craft-callout')) {
  customElements.define('craft-callout', CraftCallout);
}
