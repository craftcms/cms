import type {CSSResultGroup, PropertyValues} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import {styleMap} from 'lit/directives/style-map.js';
import {Paddable} from '@src/mixins/Paddable.js';
import hostStyles from '@src/styles/host.styles.js';
import {t} from '@src/utilities/translate.js';
import {
  hasSlotted,
  LightDomController,
} from '@src/controllers/LightDomController';
import styles from './pane.styles.js';

export const PaneAppearance = {
  Raised: 'raised',
  Outline: 'outline',
  Plain: 'plain',
  Sunken: 'sunken',
} as const;

export const paneAppearances = Object.values(PaneAppearance);

export type PaneAppearanceValue =
  (typeof PaneAppearance)[keyof typeof PaneAppearance];

export const PaneVariant = {
  Plain: 'plain',
  Error: 'error',
  Code: 'code',
} as const;

export const paneVariants = Object.values(PaneVariant);

export type PaneVariantValue = (typeof PaneVariant)[keyof typeof PaneVariant];

/**
 * Computed `overflow` values a user can't scroll. `hidden` is scrollable from
 * script but not by the keyboard or the wheel, so it counts as non-scrolling
 * here — making it a tab stop would be a focus trap with nowhere to go.
 */
const NON_SCROLLING_OVERFLOW = new Set(['clip', 'hidden']);

/**
 * Slack, in pixels, before an overflow counts. Sub-pixel layout rounding
 * routinely leaves `scrollHeight` a hair over `clientHeight` on boxes that
 * don't actually scroll.
 */
const OVERFLOW_TOLERANCE = 1;

/**
 * @summary The primary CP content surface: a padded, rounded container with
 * optional sticky header and footer regions. Panes wrap page sections, forms,
 * tables, and modal bodies.
 *
 * The header region only renders when the `label` attribute is set or one of
 * the `header`, `title`, or `header-actions` slots is filled; the footer only
 * renders when one of the `footer`, `footer-content`, `feedback`, `actions`,
 * `primary-action`, or `secondary-action` slots is filled. Presence is tracked
 * with reactive state fed by a light-DOM `MutationObserver` — `slotchange`
 * alone can't cover it, since a slot that isn't rendered can never fire one.
 *
 * `appearance` and `variant` are orthogonal and combine freely: `appearance`
 * picks the surface treatment (border/shadow/fill) while `variant` layers on a
 * semantic treatment (e.g. a scrollable `code` surface).
 *
 * A pane that clamps its height (`variant="code"`, or any pane given a
 * `--c-pane-max-block-size`) scrolls its content. Scrolling regions have to be
 * reachable by keyboard — a sighted keyboard or switch user with no pointer
 * otherwise can't read past the clamp (WCAG 2.1.1) — so when, and only when,
 * the content actually overflows, the pane makes its own scroll container a
 * labelled tab stop. Consumers don't (and shouldn't) put `tabindex` on the
 * host: the host isn't the scroller, and browsers only scroll the focused
 * element or a scrollable *ancestor* of it, never a descendant.
 *
 * @slot - The pane's body content, wrapped in the padded body region.
 * @slot header - The full header region. Replaces the default
 *   title/header-actions layout when provided, padding included.
 * @slot title - Title content shown at the start of the header; defaults to an
 *   `<h1>` containing the `label` attribute.
 * @slot header-actions - Action content shown at the end of the header.
 * @slot body - The full body region. Replaces the padded body wrapper (and the
 *   default slot inside it) when provided.
 * @slot footer - The full footer region. Replaces the default footer layout
 *   when provided, padding included.
 * @slot footer-content - The contents of the footer region. Replaces the
 *   default `feedback` + `actions` layout while keeping the footer chrome.
 * @slot feedback - Content shown at the start of the footer, e.g. a save
 *   status message.
 * @slot actions - Action content shown at the end of the footer. Replaces the
 *   default `secondary-action` + `primary-action` group.
 * @slot secondary-action - The footer's secondary action, e.g. a cancel button.
 * @slot primary-action - The footer's primary action, e.g. a submit button.
 *
 * @attr {'sm'|'md'|'lg'|'xl'|'none'|'0'} padding - Spacing applied to the header, body, and footer regions.
 *   Accepts `sm`, `md`, `lg`, and `xl` (mapped to `--c-spacing-*`), or
 *   `0`/`none`. Defaults to `lg`. Values off that scale are ignored; set
 *   `--c-pane-padding` for anything else. Supplied by the `Paddable` mixin,
 *   which writes it to `--_pane-spacing`.
 *
 * @csspart base - The pane's outermost element, which carries the surface
 *   treatment. Style it to override border/shadow/fill for a single pane. It's
 *   also the scroll container, so it takes `tabindex="0"`, `role="region"`, an
 *   `aria-label`, and the focus ring while the content overflows.
 * @csspart header - The default header region.
 * @csspart title - The `title` slot within the header.
 * @csspart header-actions - The wrapper around the `header-actions` slot.
 * @csspart body - The default padded body region.
 * @csspart footer - The default footer region.
 * @csspart footer-actions - The wrapper around the default
 *   `secondary-action` + `primary-action` group.
 *
 * @cssproperty --c-pane-background - Surface fill. Defaults to
 *   `--c-surface-raised` (`--c-surface-sunken` when `appearance="sunken"`,
 *   `--c-color-neutral-fill-quiet` when `variant="code"`).
 * @cssproperty --c-pane-text - Text color. Defaults to `inherit`.
 * @cssproperty --c-pane-border-color - Border color of the `plain` appearance.
 *   The `raised`/`outline`/`sunken` appearances and the `error`/`code` variants
 *   set their own; override those per-pane with `::part(base)`.
 * @cssproperty --c-pane-border-width - Border width. Defaults to `1px`.
 * @cssproperty --c-pane-border-style - Border style. Defaults to `solid`.
 * @cssproperty --c-pane-radius - Corner radius. Defaults to `--c-radius-md`.
 * @cssproperty --c-pane-shadow - Box shadow of the `plain` appearance. The
 *   `raised`/`outline`/`sunken` appearances set their own; `variant="code"`
 *   falls back to `none` so a code well stays flat under any appearance.
 * @cssproperty --c-pane-max-block-size - Maximum height before the pane
 *   scrolls. Defaults to `none` (500px when `variant="code"`). A pane that
 *   scrolls becomes keyboard-focusable; see the `base` part.
 * @cssproperty --c-pane-divider-color - Color of the footer's top border.
 *   Defaults to `--c-color-neutral-border-quiet`.
 * @cssproperty --c-pane-padding - Fallback spacing used when the `padding`
 *   attribute is absent. Defaults to `--c-spacing-lg`.
 * @cssproperty --c-pane-title-font-size - Font size of the default `<h1>`
 *   title. Defaults to `1.125rem`.
 * @cssproperty --c-pane-title-line-height - Line height of the default title.
 * @cssproperty --c-pane-title-font-weight - Font weight of the default title.
 */
export default class CraftPane extends Paddable(LitElement, {
  customProperty: '--_pane-spacing',
  defaultValue: 'lg',
}) {
  static override styles: CSSResultGroup = [hostStyles, styles];

  /**
   * Surface treatment.
   *
   * - `raised` — bordered with a raised shadow (the default)
   * - `outline` — bordered, no shadow
   * - `plain` — the bare surface tokens, no border color or shadow
   * - `sunken` — recessed fill with an inset shadow
   */
  @property({reflect: true}) appearance:
    | 'raised'
    | 'outline'
    | 'plain'
    | 'sunken' = PaneAppearance.Raised;

  /**
   * Semantic treatment layered on top of the appearance.
   *
   * - `plain` — no additional treatment (the default)
   * - `error` — danger fill, border, and text
   * - `code` — quiet fill for code output, scrolling past 500px
   */
  @property({reflect: true}) variant: 'plain' | 'error' | 'code' =
    PaneVariant.Plain;

  /**
   * Title shown in the header, rendered as an `<h1>` in the `title` slot.
   *
   * Named `label` rather than `title` on purpose: `title` is a global HTML
   * attribute, so it would paint a native browser tooltip over the whole pane
   * and be announced in addition to the visible heading.
   */
  @property() label = '';

  /**
   * Whether header content is currently slotted. The header and footer slots
   * only render when filled, so their presence can't be tracked with
   * `slotchange` — an unrendered slot never fires one. `LightDomController`
   * keeps this fresh when a consumer adds, removes, or re-slots content.
   */
  @state() private _hasSlottedHeader = false;

  @state() private _hasSlottedFooter = false;

  /**
   * Whether the scroll container currently has content to scroll to. Drives the
   * tab stop, so it's measured rather than assumed: a `code` pane shorter than
   * its clamp scrolls nowhere, and a tab stop that goes nowhere is noise.
   */
  @state() private _scrollable = false;

  /** The host's `aria-label`, mirrored into state so it re-renders the region. */
  @state() private _hostLabel = '';

  private _lightDom = new LightDomController(this, {
    attributeFilter: ['aria-label'],
    characterData: true,
    onChange: () => this._syncLightDom(),
  });

  private _resizeObserver?: ResizeObserver;

  /**
   * The boxes currently under the resize observer. `observe()` re-delivers an
   * initial notification every time it's called, so re-binding wholesale on
   * each render would have the observer feeding itself — the browser reports
   * that as "ResizeObserver loop completed with undelivered notifications".
   * Only genuine additions and removals are pushed through.
   */
  private _observedBoxes = new Set<Element>();

  override disconnectedCallback() {
    super.disconnectedCallback();
    this._resizeObserver?.disconnect();
    this._observedBoxes.clear();
  }

  /** The scroll container: the surface element, not the host. */
  private get _scroller(): HTMLElement | null {
    return this.renderRoot?.querySelector('.cp-pane') ?? null;
  }

  private _syncSlotPresence() {
    this._hasSlottedHeader = hasSlotted(
      this,
      'header',
      'title',
      'header-actions'
    );
    this._hasSlottedFooter = hasSlotted(
      this,
      'footer',
      'footer-content',
      'feedback',
      'actions',
      'primary-action',
      'secondary-action'
    );
  }

  /**
   * Everything that has to be re-read when the light DOM changes. The content
   * that overflows the scroll container lives out here too, so a mutation batch
   * is also the cue to re-measure. `MutationObserver` coalesces a batch into a
   * single callback, so this is one layout read per render, not per node.
   */
  private _syncLightDom() {
    this._syncSlotPresence();
    this._hostLabel = this.getAttribute('aria-label') ?? '';
    this._syncScrollable();
    this._observeScrollable();
  }

  /**
   * Re-measures whether the scroll container overflows. The computed `overflow`
   * is checked as well as the geometry, since a clamped `overflow: clip` pane
   * also reports a `scrollHeight` past its `clientHeight` without ever being
   * scrollable.
   */
  private _syncScrollable() {
    const scroller = this._scroller;

    if (!scroller?.isConnected) {
      this._scrollable = false;
      return;
    }

    const {overflowX, overflowY} = getComputedStyle(scroller);

    this._scrollable =
      (!NON_SCROLLING_OVERFLOW.has(overflowY) &&
        scroller.scrollHeight - scroller.clientHeight > OVERFLOW_TOLERANCE) ||
      (!NON_SCROLLING_OVERFLOW.has(overflowX) &&
        scroller.scrollWidth - scroller.clientWidth > OVERFLOW_TOLERANCE);
  }

  /**
   * Watches the scroll container along with the boxes that fill it. A clamped
   * scroller stops resizing once it hits its `max-block-size`, so watching it
   * alone would miss content growing past the clamp — the rendered regions and
   * the slotted light-DOM content are watched too. The scroller's own children
   * are `<slot>` elements (`display: contents`), so they're no use here.
   */
  private _observeScrollable() {
    if (typeof ResizeObserver === 'undefined') {
      return;
    }

    const observer = (this._resizeObserver ??= new ResizeObserver(() =>
      this._syncScrollable()
    ));

    const scroller = this._scroller;
    const boxes = new Set<Element>(
      scroller
        ? [
            scroller,
            ...scroller.querySelectorAll(
              '.cp-pane__header, .cp-pane__body, .cp-pane__footer'
            ),
            ...this.children,
          ]
        : []
    );

    for (const box of this._observedBoxes) {
      if (!boxes.has(box)) {
        observer.unobserve(box);
        this._observedBoxes.delete(box);
      }
    }

    for (const box of boxes) {
      if (!this._observedBoxes.has(box)) {
        observer.observe(box);
        this._observedBoxes.add(box);
      }
    }
  }

  /**
   * The scroll region's accessible name. `role="region"` is only useful with
   * one, and a code pane rarely has a visible title to borrow, so the host's
   * `aria-label` is honored as a naming hook before falling back to a generic
   * name.
   */
  private get _scrollRegionLabel(): string {
    return this.label || this._hostLabel || t('Scrollable region');
  }

  /**
   * Focus lands on the scroll container, not the host — focusing the host would
   * put the scroller out of reach of the arrow keys, which is the bug this
   * whole dance exists to prevent.
   */
  override focus(options?: FocusOptions) {
    if (this._scrollable && this._scroller) {
      this._scroller.focus(options);
      return;
    }

    super.focus(options);
  }

  /**
   * Re-binds the resize observer to the freshly rendered regions. The measuring
   * is deliberately left to the observers rather than done here: a
   * `ResizeObserver` always delivers an initial callback for anything it starts
   * observing, so the first render still gets measured, without writing to
   * reactive state from inside an update.
   */
  protected override updated(changed: PropertyValues) {
    super.updated(changed);
    this._observeScrollable();
  }

  protected override render() {
    const showHeader = !!this.label || this._hasSlottedHeader;

    return html`
      <div
        class="cp-pane"
        part="base"
        style="${styleMap(this.paddingStyles)}"
        tabindex="${this._scrollable ? '0' : nothing}"
        role="${this._scrollable ? 'region' : nothing}"
        aria-label="${this._scrollable ? this._scrollRegionLabel : nothing}"
      >
        ${showHeader
          ? html`<slot name="header">
              <div class="cp-pane__header" part="header">
                <slot name="title" part="title">
                  ${this.label
                    ? html`<h1 class="cp-pane__title">${this.label}</h1>`
                    : nothing}
                </slot>
                <div class="cp-pane__header-actions" part="header-actions">
                  <slot name="header-actions"></slot>
                </div>
              </div>
            </slot>`
          : nothing}

        <slot name="body">
          <div class="cp-pane__body" part="body">
            <slot></slot>
          </div>
        </slot>

        ${this._hasSlottedFooter
          ? html`<slot name="footer">
              <div class="cp-pane__footer" part="footer">
                <slot name="footer-content">
                  <slot name="feedback"></slot>
                  <div class="cp-pane__spacer"></div>
                  <slot name="actions">
                    <div class="cp-pane__footer-actions" part="footer-actions">
                      <slot name="secondary-action"></slot>
                      <slot name="primary-action"></slot>
                    </div>
                  </slot>
                </slot>
              </div>
            </slot>`
          : nothing}
      </div>
    `;
  }
}

if (!customElements.get('craft-pane')) {
  customElements.define('craft-pane', CraftPane);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-pane': CraftPane;
  }
}
