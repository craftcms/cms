import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {html as staticHtml, literal} from 'lit/static-html.js';
import {styleMap} from 'lit/directives/style-map.js';
import {property, state} from 'lit/decorators.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import '../badge-indicator/badge-indicator';
import '../popover/popover.js';
import styles from './nav-item.styles';
import {t} from '@src/utilities/translate.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance} from '@src/constants/appearances';
import {
  flyoutHoverIntent,
  type HoverIntentMember,
} from '@src/utilities/hover-intent.js';

/**
 *
 */
export default class CraftNavItem extends LitElement {
  static override styles = styles;

  /** Icon to render within the prefix. */
  @property()
  icon: string;

  /** The URL of the navigation item. */
  @property()
  href: string;

  /** Displays the item as active. */
  @property({type: Boolean, reflect: true})
  active: boolean = false;

  /** Opens the item in a new tab and displays an external link icon in the suffix. */
  @property({type: Boolean})
  external: boolean = false;

  /** Displays an indicator in the prefix. */
  @property({type: Boolean})
  indicator: boolean = false;

  @property()
  override id: string;

  @property({reflect: true, type: Boolean, attribute: 'icon-only'})
  iconOnly: boolean = false;

  /** Compensate for padding with a negative margin for better visual alignment */
  @property()
  flush: boolean = false;

  /** Whether the subnav starts open or closed. Active items always start open. */
  @property({reflect: true, attribute: 'initial-state'})
  initialState: 'open' | 'closed' = 'closed';

  /**
   * Renders the item as a non-collapsible semantic group. When it has a
   * `subnav`, no disclosure toggle is shown and the subnav stays open — a
   * grouping without the visual collapse affordance. Reflected, so it can be
   * styled via the `[group]` attribute (e.g. `:host([group])`).
   */
  @property({type: Boolean, reflect: true})
  group: boolean = false;

  /** Where the subnav disclosure toggle is rendered. */
  @property({attribute: 'toggle-position'})
  togglePosition: 'prefix' | 'suffix' = 'suffix';

  /**
   * Where a subnav renders: indented beneath the item, or in a flyout beside
   * it. Depth runs out of horizontal room long before the nav runs out of
   * levels, so anything past the first is better off in a popover.
   *
   * `iconOnly` forces `flyout` regardless — collapsed to a rail there's
   * nowhere to indent to.
   */
  @property({attribute: 'subnav-display', reflect: true})
  subnavDisplay: 'inline' | 'flyout' = 'inline';

  @state()
  subnavState: string = 'closed';

  /**
   * Whether the icon-only flyout is showing. Collapsed to an icon, an item has
   * nowhere to put its subnav, so it moves into a popover on hover or focus.
   */
  @state()
  flyoutOpen: boolean = false;

  /**
   * Timing is shared, not per-item: the first flyout waits out a warm-up, the
   * rest of the group opens instantly, and opening one closes its siblings
   * immediately rather than leaving them to overlap through their own close
   * delay.
   */
  #hoverIntent: HoverIntentMember = {
    element: this,
    setOpen: (open) => {
      this.flyoutOpen = open;
    },
  };

  #hoverListeners?: AbortController;

  /** Torn down when the flyout closes; see {@link fitFlyout}. */
  #flyoutFitListeners?: AbortController;

  /** Space left between a flyout and the bottom of the screen, in px. */
  static flyoutViewportMargin = 16;

  /** Whether the default slot (the item's label) has any content. */
  private get hasLabel(): boolean {
    return Array.from(this.childNodes).some((node) => {
      if (node.nodeType === Node.TEXT_NODE) {
        return !!node.textContent?.trim();
      }

      return (
        node.nodeType === Node.ELEMENT_NODE &&
        !(node as Element).hasAttribute('slot')
      );
    });
  }

  constructor() {
    super();
    this.id = this.id || Math.random().toString(36).substring(2, 6);
  }

  override connectedCallback() {
    super.connectedCallback();
    // Default to open when the item is active, or when explicitly requested.
    this.subnavState =
      this.active || this.initialState === 'open' ? 'open' : 'closed';

    // Bound to the host rather than the link, so travelling into the flyout
    // counts as staying inside: its content is a descendant of this element,
    // wherever the overlay paints it.
    const {signal} = (this.#hoverListeners = new AbortController());
    this.addEventListener('mouseenter', this.#openFlyout, {signal});
    this.addEventListener('mouseleave', this.#scheduleFlyoutClose, {signal});
    this.addEventListener('focusin', this.#focusFlyout, {signal});
    this.addEventListener('focusout', this.#scheduleFlyoutClose, {signal});
  }

  override updated(changed: PropertyValues<this>) {
    if (changed.has('flyoutOpen')) {
      if (this.flyoutOpen) {
        this.#watchFlyoutFit();
      } else {
        this.#releaseFlyoutFit();
      }
    }
  }

  /**
   * Caps the flyout to the room below wherever it landed.
   *
   * The overlay is positioned rather than laid out, so a menu taller than the
   * space under its item runs off the bottom of the screen and the items down
   * there can't be reached at all. Measuring after the fact — rather than
   * predicting from the item's position — means this holds however the overlay
   * chose to place itself, including when it flips.
   */
  fitFlyout = () => {
    const flyout = this.shadowRoot?.querySelector<HTMLElement>('.flyout');

    if (!flyout) {
      return;
    }

    const {top} = flyout.getBoundingClientRect();
    const available =
      window.innerHeight - top - CraftNavItem.flyoutViewportMargin;

    // Zero everywhere means there's no layout to measure — a test environment
    // without one, or a flyout that hasn't been placed yet. Leave the
    // stylesheet's fallback in charge rather than pinning it shut.
    if (!window.innerHeight || available <= 0) {
      flyout.style.removeProperty('--flyout-max-block-size');

      return;
    }

    flyout.style.setProperty('--flyout-max-block-size', `${available}px`);
  };

  #watchFlyoutFit(): void {
    this.#releaseFlyoutFit();

    const {signal} = (this.#flyoutFitListeners = new AbortController());
    window.addEventListener('resize', this.fitFlyout, {signal});

    // After the frame the overlay positions itself in, so the measurement is
    // of where it ended up rather than where it started.
    requestAnimationFrame(this.fitFlyout);
  }

  #releaseFlyoutFit(): void {
    this.#flyoutFitListeners?.abort();
    this.#flyoutFitListeners = undefined;
    this.shadowRoot
      ?.querySelector<HTMLElement>('.flyout')
      ?.style.removeProperty('--flyout-max-block-size');
  }

  override willUpdate(changed: PropertyValues<this>) {
    // `connectedCallback` sets this once. An Inertia visit patches these
    // elements rather than recreating them, so a nav whose selection moved
    // under it would keep whichever branch it first rendered open. Re-sync on
    // change only, so a manual toggle survives unrelated re-renders.
    if (changed.has('active') || changed.has('initialState')) {
      this.subnavState =
        this.active || this.initialState === 'open' ? 'open' : 'closed';
    }
  }

  override disconnectedCallback() {
    this.#hoverListeners?.abort();
    this.#flyoutFitListeners?.abort();
    flyoutHoverIntent.remove(this.#hoverIntent);
    super.disconnectedCallback();
  }

  #openFlyout = () => {
    flyoutHoverIntent.requestOpen(this.#hoverIntent);
  };

  // Tabbing to an item is deliberate in a way that sweeping a pointer past it
  // isn't, so focus skips the warm-up.
  #focusFlyout = () => {
    flyoutHoverIntent.requestOpen(this.#hoverIntent, {immediate: true});
  };

  #scheduleFlyoutClose = () => {
    flyoutHoverIntent.requestClose(this.#hoverIntent);
  };

  // The overlay closes itself on Escape and outside clicks; follow it, or the
  // state would say open and the next hover wouldn't reopen it.
  #onFlyoutOpenedChanged = (event: Event) => {
    const opened = (event.target as {opened?: boolean}).opened === true;

    this.flyoutOpen = opened;

    if (!opened) {
      flyoutHoverIntent.notifyClosed(this.#hoverIntent);
    }
  };

  toggleSubnav(event: Event) {
    event.preventDefault();
    event.stopPropagation();
    this.subnavState = this.subnavState === 'open' ? 'closed' : 'open';
  }

  renderIconItem(hasSubnav: boolean) {
    const itemId = `item-${this.id}`;
    // Without an href there's nothing to link to, so render a plain span.
    const tag = this.href ? literal`a` : literal`span`;

    return staticHtml`
      <${tag}
        class="${classMap({
          'nav-item': true,
          'nav-item--icon': true,
          'nav-item--static': !this.href,
        })}"
        id="${itemId}"
        href="${ifDefined(this.href || undefined)}"
        aria-current="${this.href ? (this.active ? 'page' : 'false') : nothing}"
        aria-expanded="${hasSubnav ? (this.flyoutOpen ? 'true' : 'false') : nothing}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(false)}
      </${tag}>
      ${
        hasSubnav
          ? this.renderFlyout(itemId, true)
          : html`<craft-tooltip for="${itemId}" placement="right-start"
              ><slot></slot
            ></craft-tooltip>`
      }
    `;
  }

  /**
   * The subnav in a popover beside the item.
   *
   * `withLabel` heads the flyout with the item's own label, standing in for
   * the tooltip a childless item would get. Only the rail can do that: a
   * labelled item has already projected the default slot into itself, and a
   * slot can only render its content in one place.
   */
  renderFlyout(itemId: string, withLabel: boolean) {
    return html`
      <craft-popover
        for="${itemId}"
        placement="right-start"
        .opened="${this.flyoutOpen}"
        @opened-changed="${this.#onFlyoutOpenedChanged}"
      >
        <div class="flyout">
          ${withLabel
            ? html`<div class="flyout__label"><slot></slot></div>`
            : nothing}
          <slot name="subnav"></slot>
        </div>
      </craft-popover>
    `;
  }

  renderSubnavToggle() {
    return html`
      <craft-button
        @click="${this.toggleSubnav}"
        variant="${Appearance.Plain}"
        icon
        size="small"
        aria-controls="${this.id}-subnav"
        aria-expanded="${this.subnavState === 'open' ? 'true' : 'false'}"
        aria-labelledby="${this.id}-toggle-icon ${this.id}-label"
      >
        <craft-icon
          id="${this.id}-toggle-icon"
          name="${this.subnavState === 'closed'
            ? 'chevron-down'
            : 'chevron-up'}"
          style="font-size: calc(10rem / 16)"
          label="${t('Toggle subnavigation')}"
        ></craft-icon>
      </craft-button>
    `;
  }

  renderPrefix(showToggle: boolean = false) {
    if (showToggle && this.togglePosition === 'prefix') {
      return html`
        <span class="nav-item__prefix">${this.renderSubnavToggle()}</span>
      `;
    }

    return html`
      <span class="nav-item__prefix">
        <slot name="prefix">
          <slot name="icon">
            ${this.icon
              ? html` <craft-icon
                  name="${this.icon}"
                  class="nav-icon"
                ></craft-icon>`
              : nothing}
          </slot>
          ${this.indicator
            ? html`<craft-badge-indicator
                altText="${t('Has Notifications')}"
              />`
            : nothing}
        </slot>
      </span>
    `;
  }

  renderSuffix(showToggle: boolean = false) {
    return html`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${showToggle && this.togglePosition === 'suffix'
            ? this.renderSubnavToggle()
            : nothing}
        </slot>
      </div>
    `;
  }

  renderItem(showToggle: boolean, hasPrefix: boolean = false) {
    // Without an href there's nothing to link to, so render a plain span.
    const tag = this.href ? literal`a` : literal`span`;

    return staticHtml`
      <${tag}
        class="${classMap({
          'nav-item': true,
          'nav-item--prefixed': hasPrefix,
          'nav-item--flush': this.flush,
          'nav-item--static': !this.href,
        })}"
        id="item-${this.id}"
        href="${ifDefined(this.href || undefined)}"
        aria-current="${this.href ? (this.active ? 'page' : 'false') : nothing}"
      >
        ${hasPrefix ? this.renderPrefix(showToggle) : nothing}
        <slot
          id="${this.id}-label"
          @slotchange="${() => this.requestUpdate()}"
        ></slot>
        ${this.renderSuffix(showToggle)}
      </${tag}>
    `;
  }

  override render() {
    const hasSubnav = !!this.querySelector('[slot="subnav"]');
    // A `slot` can only project its content in one place, so the subnav is
    // either indented below or in the flyout, never both.
    const useFlyout =
      hasSubnav && (this.iconOnly || this.subnavDisplay === 'flyout');
    // No label means no toggle, and no way to collapse. A `group` item is a
    // permanent semantic grouping: it never shows a toggle and its subnav
    // stays open (subnavOpen falls back to true when there's no toggle).
    // There's nothing to collapse either when the subnav lives in a flyout.
    const showToggle = hasSubnav && this.hasLabel && !this.group && !useFlyout;
    const toggleInPrefix = showToggle && this.togglePosition === 'prefix';
    const hasPrefix =
      toggleInPrefix ||
      !!this.icon ||
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]');
    const subnavOpen = showToggle ? this.subnavState === 'open' : true;

    return html`
      <li>
        ${this.iconOnly
          ? this.renderIconItem(hasSubnav)
          : this.renderItem(showToggle, hasPrefix)}
        ${!this.iconOnly && useFlyout
          ? this.renderFlyout(`item-${this.id}`, false)
          : nothing}
        ${hasSubnav && !useFlyout
          ? html`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${styleMap({
                  display: subnavOpen ? 'block' : 'none',
                })}"
              >
                <slot name="subnav"></slot>
              </div>
            `
          : nothing}
      </li>
    `;
  }
}

if (!customElements.get('craft-nav-item')) {
  customElements.define('craft-nav-item', CraftNavItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-nav-item': CraftNavItem;
  }
}
