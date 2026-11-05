import {property, state} from 'lit/decorators.js';
import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {styleMap} from 'lit/directives/style-map.js';
import {Paddable} from '@src/mixins/Paddable.js';
import styles from './card.styles.js';
import {classMap} from 'lit/directives/class-map.js';
import {
  hasSlotted,
  LightDomController,
} from '@src/controllers/LightDomController';
import {
  coverAppearance,
  COVER_BACKING_HOVER,
  COVER_INK_OPACITY,
  type CoverAppearance,
  type CoverTheme,
  type Luminance,
} from './cover.js';

/** Parses luminance set as a JSON string; anything unreadable is unknown. */
const parseLuminance = (value: Luminance | string | null): Luminance | null => {
  if (typeof value !== 'string') {
    return value;
  }

  try {
    const parsed: unknown = JSON.parse(value);
    return Array.isArray(parsed) ? (parsed as Luminance) : null;
  } catch {
    return null;
  }
};

const ink = (theme: CoverTheme) =>
  theme === 'dark'
    ? `rgb(255 255 255 / ${COVER_INK_OPACITY.dark})`
    : `rgb(0 0 0 / ${COVER_INK_OPACITY.light})`;

const solid = (theme: CoverTheme) => (theme === 'dark' ? '#000' : '#fff');

const inkSolid = (theme: CoverTheme) => (theme === 'dark' ? '#fff' : '#000');

const surface = (theme: CoverTheme, opacity: number) =>
  theme === 'dark'
    ? `rgb(0 0 0 / ${opacity})`
    : `rgb(255 255 255 / ${opacity})`;

const rule = (theme: CoverTheme) =>
  theme === 'dark' ? 'rgb(255 255 255 / 20%)' : 'rgb(0 0 0 / 15%)';

/**
 * @summary A surface that groups related content into a bordered, rounded
 * container with optional header and footer regions. Used for element cards in
 * index views and other standalone content blocks.
 *
 * The header region is only rendered when the `label` attribute is set or the
 * `header`, `label`, `badge`, or `actions` slot has something to show, rather
 * than only empty `div`s and `span`s, so the body is flush with the card's top
 * otherwise. When no `header`
 * slot is provided, the header falls back to showing the `label`, `badge`, and
 * `actions` slots (with the `label` attribute as the default label content).
 * A header without a label (see `_hasLabel()`) has no fill or rule of its own,
 * so its controls float over the card, as a cover card's do. Its controls stay
 * where they'd be with a label.
 * The footer region is only rendered when the `footer` slot is filled.
 *
 * The card renders its own chrome, so attributes set on the host are left
 * alone — an `id`, `style` custom properties, and `data-*` metadata can all be
 * spread onto it without the component interfering.
 *
 * @slot - The card's body content.
 * @slot header - The full header region. Replaces the default
 *   `label`/`badge`/`actions` content when provided.
 * @slot label - Label content shown in the header; defaults to the `label`
 *   attribute.
 * @slot badge - Content that can be displayed before the actions at the end of
 *   the header, e.g. a status badge.
 * @slot actions - Action content shown at the end of the header, e.g. buttons.
 * @slot footer - Footer content. The footer is only rendered when this slot is
 *   filled.
 * @slot thumbnail - Artwork shown in a fixed column beside the body. Rendered
 *   only while `show-thumb` is set; `thumb-alignment` puts the column at the
 *   start or the end of the body. Not shown on a cover card.
 *
 * @csspart base - The card's outermost element, which carries the surface and
 *   the border.
 * @csspart header - The default header region.
 * @csspart label - The label slot within the header.
 * @csspart badge - The wrapper around the `badge` slot.
 * @csspart actions - The wrapper around the `actions` slot.
 * @csspart body - The region holding the thumbnail column and the content.
 * @csspart thumbnail - The fixed column the `thumbnail` slot renders into.
 * @csspart footer - The default footer region.
 * @csspart cover - A cover card's image.
 *
 * @attr collapsed - Collapses the card to its header.
 * @attr cover-src - Makes this a cover card: the image fills the card, and the
 *   header and body are laid over it. See `luminance`.
 *
 * @cssproperty --c-card-border-width - Border width. Defaults to `1px`.
 * @cssproperty --c-card-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-card-shadow - Box shadow. Defaults to the theme's
 *   `--c-shadow-sm`, or `--c-shadow-xs` for a white card.
 * @cssproperty --c-card-fill-selected - Header and footer fill of a selected
 *   card without a color. Defaults to the neutral loud fill.
 * @cssproperty --c-card-border-selected - Border color of a selected card
 *   without a color. Defaults to the neutral loud border.
 * @cssproperty --c-card-text-selected - Header and footer text color of a
 *   selected card without a color. Defaults to the neutral loud text color.
 * @cssproperty --c-card-padding-inline - Inline (horizontal) padding of the
 *   header, body, and footer. Defaults to `--c-spacing-md` for the
 *   header/footer and `--cp-container-padding` for the body, or
 *   `--c-spacing-lg` where that isn't defined.
 * @cssproperty --c-card-padding-block - Block (vertical) padding of the header,
 *   body, and footer. Defaults to `--c-spacing-sm` for the header/footer and
 *   `--cp-container-padding` for the body, or `--c-spacing-lg` where that
 *   isn't defined.
 *
 * @property padding - Spacing for every padded region, from the shared spacing
 *   scale. Supplied by the `Paddable` mixin, the same as `craft-pane` and
 *   `craft-callout`. Leave it unset to keep the per-region defaults above; set
 *   the custom properties for anything the scale cannot express.
 */
export default class CraftCard extends Paddable(LitElement, {
  // The public properties, so an unset `padding` leaves the stylesheet's
  // per-region defaults alone and only an explicit value overrides them.
  customProperty: ['--c-card-padding-block', '--c-card-padding-inline'],
}) {
  // In the CP, an element's server-rendered card attributes are spread onto
  // the host with `attrs(element.cardAttributes)`, excluding `class` — the
  // component draws its own chrome rather than the server's `.card` classes.
  static override styles: CSSResultGroup = [styles];

  /** Label shown in the header when the `label` slot is not filled. */
  @property() label = '';

  /**
   * Whether the card is in an active/selected state. Reflected to the host as
   * an `active` attribute (e.g. ElementCards binds this to row selection).
   */
  @property({type: Boolean, reflect: true})
  active = false;

  /**
   * Whether the card is collapsed to just its header. The body and footer are
   * hidden rather than unrendered, so slotted content — hidden inputs among it —
   * stays in the document and keeps posting.
   */
  @property({type: Boolean, reflect: true})
  collapsed = false;

  /** Whether the thumbnail region renders at all, even with slotted content. */
  @property({attribute: 'show-thumb', type: Boolean}) showThumb: boolean = true;

  /**
   * Which side of the body the thumbnail column sits on. Has no effect unless
   * the `thumbnail` slot is filled and `show-thumb` is set.
   */
  @property({attribute: 'thumb-alignment'}) thumbAlignment: 'start' | 'end' =
    'start';

  /**
   * URL of an image that covers the whole card, with the header and body laid
   * over it, in place of a thumbnail. The image is decorative: the card's
   * content should say what it shows. If it fails to load, the card falls back
   * to a normal one, with its thumbnail.
   */
  @property({attribute: 'cover-src'}) coverSrc = '';

  /** Whether the cover image failed to load. */
  @state() private _coverFailed = false;

  /**
   * A cover image's luminance, as rows of regions from top to bottom, each a
   * `[P10, P90]` pair of relative luminances, or `null` for a transparent
   * region (an asset's `analysis.colors.luminance`). The card picks white or
   * black text and controls for its header and body from it. The black or
   * white behind them is fixed, and meets contrast over any image, so the
   * luminance only decides which looks better. Without it, both are white.
   *
   * The card sets `data-theme` on the children it lays over the image, so
   * their controls take the matching theme, and `data-cover`: `body` on the
   * body's and footer's, and the header's treatment on the header's: `bar`
   * when it has text, or `backed` when it has only controls.
   */
  @property({type: Array}) luminance: Luminance | string | null = null;

  /**
   * `luminance`, parsed if it was set as a JSON string, as a framework that
   * sets a custom element's attributes as its properties (Vue) would do.
   */
  private _luminance: Luminance | null = null;

  /** Children whose `data-theme` the card has set for its cover. */
  private _themed = new Set<Element>();

  /**
   * The header, footer, and thumbnail regions only render when they have
   * something in them, so their slots can't report their own changes — an
   * unrendered slot never fires `slotchange`. The controller re-renders the
   * card whenever its light DOM moves, which is how a thumbnail swapped in
   * by the element index shows up.
   */
  private _lightDom = new LightDomController(this, {characterData: true});

  /**
   * Whether the header has a label: the `label` attribute or slot, or a
   * `[data-card-label]` element in the `header` slot.
   */
  private _hasLabel(): boolean {
    return (
      !!this.label ||
      hasSlotted(this, 'label') ||
      Array.from(this.children).some(
        (child) =>
          child.slot === 'header' && !!child.querySelector('[data-card-label]')
      )
    );
  }

  /** Whether the header needs a cover bar behind its label or badge. */
  private _hasHeaderText(): boolean {
    return this._hasLabel() || hasSlotted(this, 'badge');
  }

  /**
   * Whether anything shows in the header: the `label` attribute, or slotted
   * content with text or anything other than empty wrappers, such as a
   * checkbox, button, or icon.
   */
  private _hasHeaderContent(): boolean {
    const isWrapper = (el: Element) =>
      el.tagName === 'DIV' || el.tagName === 'SPAN';
    return (
      !!this.label ||
      Array.from(this.children).some(
        (child) =>
          ['header', 'label', 'badge', 'actions'].includes(child.slot) &&
          (!!child.textContent?.trim() ||
            !isWrapper(child) ||
            Array.from(child.querySelectorAll('*')).some(
              (el) => !isWrapper(el)
            ))
      )
    );
  }

  private _cover(): CoverAppearance | null {
    return this.coverSrc && !this._coverFailed
      ? coverAppearance(this._luminance, this._hasHeaderText())
      : null;
  }

  private _onCoverError = () => {
    this._coverFailed = true;
  };

  override willUpdate(changed: PropertyValues<this>) {
    super.willUpdate(changed);

    if (changed.has('luminance')) {
      this._luminance = parseLuminance(this.luminance);
    }

    if (changed.has('coverSrc')) {
      this._coverFailed = false;
    }
  }

  private _coverStyles(cover: CoverAppearance | null) {
    if (!cover) {
      return {};
    }

    const {header, body} = cover;

    return {
      '--_header-ink': ink(header.theme),
      '--_header-ink-solid': inkSolid(header.theme),
      '--_header-solid': solid(header.theme),
      '--_header-fill': surface(header.theme, header.opacity),
      '--_header-backing':
        header.treatment === 'backed'
          ? surface(header.theme, header.opacity)
          : undefined,
      '--_header-backing-hover':
        header.treatment === 'backed'
          ? surface(header.theme, COVER_BACKING_HOVER)
          : undefined,
      '--_header-rule': rule(header.theme),
      '--_body-ink': ink(body.theme),
      '--_body-ink-solid': inkSolid(body.theme),
      '--_body-solid': solid(body.theme),
      '--_body-scrim': String(body.opacity),
      '--_body-rule': rule(body.theme),
    };
  }

  override updated() {
    const cover = this._cover();
    const themed = new Set<Element>();

    if (cover) {
      for (const child of Array.from(this.children)) {
        if (child.slot === 'thumbnail') {
          continue;
        }

        const inBody = child.slot === '' || child.slot === 'footer';
        child.setAttribute(
          'data-theme',
          inBody ? cover.body.theme : cover.header.theme
        );
        child.setAttribute(
          'data-cover',
          inBody ? 'body' : cover.header.treatment
        );

        themed.add(child);
      }
    }

    for (const child of this._themed) {
      if (!themed.has(child)) {
        child.removeAttribute('data-theme');
        child.removeAttribute('data-cover');
      }
    }

    this._themed = themed;
  }

  override render() {
    const hasSlottedHeader = this._hasHeaderContent();
    const hasSlottedFooter = hasSlotted(this, 'footer');
    const cover = this._cover();
    const showThumbnail =
      !cover && this.showThumb && hasSlotted(this, 'thumbnail');
    const bareHeader = !cover && hasSlottedHeader && !this._hasLabel();

    return html`
      <div
        part="base"
        class="${classMap({
          card: true,
          'card--has-thumbnail': showThumbnail,
          'card--cover': !!cover,
          'card--cover-bar': cover?.header.treatment === 'bar',
          'card--bare-header': bareHeader,
        })}"
        style="${styleMap({
          ...this.paddingStyles,
          ...this._coverStyles(cover),
        })}"
      >
        ${cover
          ? html`<img
              class="card__cover"
              part="cover"
              src="${this.coverSrc}"
              alt=""
              loading="lazy"
              decoding="async"
              @error="${this._onCoverError}"
            />`
          : nothing}
        ${hasSlottedHeader
          ? html`<div class="card__header" part="header">
              <slot name="header">
                <slot name="label" class="card__label" part="label"
                  >${this.label
                    ? html`<span class="card__label-text">${this.label}</span>`
                    : nothing}</slot
                >
                <div class="card__header__suffix">
                  <slot name="badge" class="card__badge" part="badge"></slot>
                  <slot
                    name="actions"
                    class="card__actions"
                    part="actions"
                  ></slot>
                </div>
              </slot>
            </div>`
          : nothing}

        <div
          part="body"
          class="${classMap({
            'card-body': true,
            'card-body--thumb-start':
              showThumbnail && this.thumbAlignment === 'start',
            'card-body--thumb-end':
              showThumbnail && this.thumbAlignment === 'end',
          })}"
        >
          <div
            class="card-body__thumb"
            part="thumbnail"
            ?hidden="${!showThumbnail}"
          >
            <slot name="thumbnail"></slot>
          </div>

          <div class="card-body__main">
            <slot></slot>
          </div>
        </div>

        ${hasSlottedFooter
          ? html`<div class="card__footer" part="footer">
              <slot name="footer"></slot>
            </div>`
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-card')) {
  customElements.define('craft-card', CraftCard);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-card': CraftCard;
  }
}
