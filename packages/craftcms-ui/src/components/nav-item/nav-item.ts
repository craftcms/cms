import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {html as staticHtml, literal} from 'lit/static-html.js';
import {styleMap} from 'lit/directives/style-map.js';
import {property, state} from 'lit/decorators.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import '../badge-indicator/badge-indicator';
import '../button/button.js';
import '../icon/icon.js';
import '../popover/popover.js';
import '../tooltip/tooltip.js';
import styles from './nav-item.styles';
import {t} from '@src/utilities/translate.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance} from '@src/constants/appearances';
import {
  flyoutHoverIntent,
  type HoverIntentMember,
} from '@src/utilities/hover-intent.js';
import {dispatchNavigateEvent} from '@src/utilities/navigate-event.js';

/**
 * One row of a navigation: a link, a heading over a run of them, or a branch
 * with a subnav.
 *
 * A branch shows its subnav one of two ways — indented beneath it, or in a
 * flyout beside it — chosen by the caller through `subnav-display`, since only
 * the nav as a whole knows which branch you're in.
 *
 * Collapsed to a rail (`icon-only`) the row is the icon and nothing else: the
 * label moves to a tooltip or heads the flyout, an icon-less item stands its
 * first letter in, and a heading becomes the rule between the runs it
 * separates.
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
   * Unset, an expanded item indents and a rail flies out — a rail has nowhere
   * to indent to. Set explicitly, a rail honours `inline` as well: a column of
   * stand-in icons under the parent, for the branch you're in.
   */
  @property({attribute: 'subnav-display', reflect: true})
  subnavDisplay?: 'inline' | 'flyout';

  @state()
  subnavState: 'open' | 'closed' = 'closed';

  /**
   * Whether the flyout is showing. Opened by hover or by the item's own
   * disclosure — deliberately not by focus, which would put every child of
   * every branch in the tab order.
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

  /** The default slot's content: the label, as opposed to a named slot. */
  private get labelNodes(): ChildNode[] {
    return Array.from(this.childNodes).filter(
      (node) =>
        node.nodeType === Node.TEXT_NODE ||
        (node.nodeType === Node.ELEMENT_NODE &&
          !(node as Element).hasAttribute('slot'))
    );
  }

  /**
   * The item's own text, without the subnav's. Collapsed to a rail the label
   * is projected into the flyout or the tooltip, leaving the item itself with
   * nothing to be named by.
   */
  private get labelText(): string {
    return this.labelNodes
      .map((node) => node.textContent ?? '')
      .join(' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  /**
   * Whether there's a label at all. Not `labelText`: an element in the default
   * slot is a label even before it has any text of its own.
   */
  private get hasLabel(): boolean {
    return this.labelNodes.some(
      (node) => node.nodeType !== Node.TEXT_NODE || !!node.textContent?.trim()
    );
  }

  /** The row itself, which the flyout and the tooltip anchor to. */
  private get itemId(): string {
    return `item-${this.id}`;
  }

  /**
   * Whichever subnav container this item renders. Inline and flyout subnavs
   * are mutually exclusive, so one id serves both and `aria-controls` doesn't
   * have to care which is in play.
   */
  private get subnavId(): string {
    return `${this.id}-subnav`;
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
   * Caps the flyout to the room below wherever it landed, so a subnav longer
   * than that scrolls instead of running off the bottom of the screen.
   *
   * The cap goes on the popover, whose pane is already the scrolling one —
   * capping the content inside it instead would nest a second scroller within
   * the first, each with its own idea of how tall it may be.
   *
   * Measured after the fact rather than predicted from the item's position, so
   * it holds however the overlay chose to place itself, including when it
   * flips.
   */
  fitFlyout = () => {
    const popover =
      this.shadowRoot?.querySelector<HTMLElement>('craft-popover');
    const flyout = this.shadowRoot?.querySelector<HTMLElement>('.flyout');

    if (!popover || !flyout) {
      return;
    }

    const {top} = flyout.getBoundingClientRect();
    const available =
      window.innerHeight - top - CraftNavItem.flyoutViewportMargin;

    // Zero everywhere means there's no layout to measure — a test environment
    // without one, or a flyout that hasn't been placed yet. Leave the
    // stylesheet's fallback in charge rather than pinning it shut.
    if (!window.innerHeight || available <= 0) {
      popover.style.removeProperty('--popover-max-block-size');

      return;
    }

    popover.style.setProperty('--popover-max-block-size', `${available}px`);
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
      ?.querySelector<HTMLElement>('craft-popover')
      ?.style.removeProperty('--popover-max-block-size');
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

  /**
   * Focus or the pointer leaving the item.
   *
   * `focusout` bubbles from inside the flyout as well, so tabbing from the
   * item into its own subnav would otherwise shut the very thing being tabbed
   * into. Anything in the shadow tree retargets to the host and the subnav's
   * items are light-DOM descendants, so both read as still being here.
   *
   * `mouseleave` doesn't fire moving between descendants, so this only ever
   * narrows the focus case.
   */
  #scheduleFlyoutClose = (event: Event) => {
    const moved = (event as FocusEvent).relatedTarget;

    if (moved instanceof Node && (moved === this || this.contains(moved))) {
      return;
    }

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

  #handleLinkClick = (event: MouseEvent) => {
    if (!this.href) {
      return;
    }
    dispatchNavigateEvent(this, this.href, event);
  };

  /**
   * The tag the item's own row is built from. An item that goes somewhere is a
   * link; one that only discloses a flyout is a button, so it can be reached by
   * keyboard and can carry `aria-expanded`; one that does neither is a label.
   */
  actionTag(useFlyout: boolean) {
    if (this.href) {
      return literal`a`;
    }

    return useFlyout ? literal`button` : literal`span`;
  }

  /** A bare `<button>` defaults to submit, which would post its form. */
  buttonType(useFlyout: boolean) {
    return !this.href && useFlyout ? 'button' : nothing;
  }

  #toggleFlyout = (event: Event) => {
    event.preventDefault();
    event.stopPropagation();

    if (this.flyoutOpen) {
      this.flyoutOpen = false;
      flyoutHoverIntent.notifyClosed(this.#hoverIntent);
    } else {
      flyoutHoverIntent.requestOpen(this.#hoverIntent, {immediate: true});
    }
  };

  renderIconItem(hasSubnav: boolean, useFlyout: boolean) {
    const tag = this.actionTag(useFlyout);

    return staticHtml`
      <${tag}
        class="${classMap({
          'nav-item': true,
          'nav-item--icon': true,
          'nav-item--static': !this.href && !useFlyout,
        })}"
        id="${this.itemId}"
        type="${this.buttonType(useFlyout)}"
        href="${ifDefined(this.href || undefined)}"
        aria-current="${this.href ? (this.active ? 'page' : 'false') : nothing}"
        aria-expanded="${useFlyout ? (this.flyoutOpen ? 'true' : 'false') : nothing}"
        aria-controls="${useFlyout ? this.subnavId : nothing}"
        aria-label="${
          (this.href || hasSubnav) && this.labelText ? this.labelText : nothing
        }"
        @click="${this.href ? this.#handleLinkClick : this.#toggleFlyout}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(false)}
      </${tag}>
      ${
        useFlyout
          ? html`<div class="rail-toggle">${this.renderFlyoutToggle()}</div>`
          : nothing
      }
      ${
        useFlyout
          ? this.renderFlyout(true)
          : html`<craft-tooltip for="${this.itemId}" placement="right"
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
  renderFlyout(withLabel: boolean) {
    return html`
      <craft-popover
        for="${this.itemId}"
        placement="right-start"
        without-invoker-aria
        .opened="${this.flyoutOpen}"
        @opened-changed="${this.#onFlyoutOpenedChanged}"
      >
        <div class="flyout" id="${this.subnavId}">
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
        aria-controls="${this.subnavId}"
        aria-expanded="${this.subnavState === 'open' ? 'true' : 'false'}"
        class="subnav-toggle"
        aria-labelledby="${this.iconOnly
          ? `${this.id}-toggle-icon`
          : `${this.id}-toggle-icon ${this.id}-label`}"
      >
        <craft-icon
          id="${this.id}-toggle-icon"
          name="${this.subnavState === 'closed'
            ? 'chevron-down'
            : 'chevron-up'}"
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
              : this.renderInitial()}
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

  /**
   * An item whose subnav opens beside it says so.
   *
   * A collapsible item has its chevron in its own toggle; a flyout gets this
   * one, which both marks the item as having more behind it and is how you
   * open it without a pointer.
   *
   * It has to exist: a flyout that opened on focus would put every child of
   * every branch in the tab order, so tabbing past a branch would mean tabbing
   * through it.
   */
  renderFlyoutToggle() {
    return html`
      <craft-button
        class="flyout-toggle"
        @click="${this.#toggleFlyout}"
        variant="${Appearance.Plain}"
        icon
        size="small"
        aria-controls="${this.subnavId}"
        aria-expanded="${this.flyoutOpen ? 'true' : 'false'}"
        aria-label="${t('Show submenu for “{item}”', {item: this.labelText})}"
      >
        <craft-icon
          class="flyout-indicator"
          name="chevron-right"
          aria-hidden="true"
        ></craft-icon>
      </craft-button>
    `;
  }

  /**
   * A stand-in icon for a collapsed item that hasn't got one: the first letter
   * of its label. Only the rail needs it — expanded, an icon-less row simply
   * has no prefix.
   *
   * Hidden from assistive tech: it's a picture of the label, and the item is
   * already named by `aria-label`.
   */
  renderInitial() {
    const initial = this.iconOnly ? this.labelText.at(0) : null;

    return initial
      ? html`<span class="nav-item__initial" aria-hidden="true"
          >${initial.toLocaleUpperCase()}</span
        >`
      : nothing;
  }

  renderSuffix(showToggle: boolean = false, showFlyoutToggle = false) {
    return html`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${showToggle && this.togglePosition === 'suffix'
            ? this.renderSubnavToggle()
            : nothing}
          ${showFlyoutToggle ? this.renderFlyoutToggle() : nothing}
        </slot>
      </div>
    `;
  }

  renderItem(showToggle: boolean, hasPrefix: boolean, useFlyout: boolean) {
    return staticHtml`
      <div
        class="${classMap({
          'nav-item': true,
          'nav-item--prefixed': hasPrefix,
          'nav-item--flush': this.flush,
          'nav-item--static': !this.href && !useFlyout,
        })}"
        id="${this.itemId}"
      >
        ${hasPrefix ? this.renderPrefix(showToggle) : nothing}
        ${this.renderInteractiveItem(useFlyout)}
        ${this.renderSuffix(showToggle, useFlyout)}
      </div>
    `;
  }

  renderInteractiveItem(useFlyout: boolean) {
    const tag = this.actionTag(useFlyout);
    return staticHtml`
      <${tag}
        class="nav-item__action-item"
        type="${this.buttonType(useFlyout)}"
        href="${ifDefined(this.href || undefined)}"
        aria-current="${this.href ? (this.active ? 'page' : 'false') : nothing}"
        aria-expanded="${useFlyout ? (this.flyoutOpen ? 'true' : 'false') : nothing}"
        aria-controls="${useFlyout ? this.subnavId : nothing}"
        @click="${this.href ? this.#handleLinkClick : this.#toggleFlyout}"
      >
        <slot
          id="${this.id}-label"
          @slotchange="${() => this.requestUpdate()}"
        ></slot>
      </${tag}>
    `;
  }

  override render() {
    const hasSubnav = !!this.querySelector('[slot="subnav"]');
    // A `slot` can only project its content in one place, so the subnav is
    // either indented below or in the flyout, never both.
    const display = this.subnavDisplay ?? (this.iconOnly ? 'flyout' : 'inline');
    const useFlyout = hasSubnav && display === 'flyout';
    // No label means no toggle, and no way to collapse. A `group` item is a
    // permanent semantic grouping: it never shows a toggle and its subnav
    // stays open (subnavOpen falls back to true when there's no toggle).
    // There's nothing to collapse either when the subnav lives in a flyout.
    const showToggle =
      hasSubnav &&
      !useFlyout &&
      !this.group &&
      (this.iconOnly || this.hasLabel);
    const toggleInPrefix = showToggle && this.togglePosition === 'prefix';
    // The badge sits in the prefix, so an item carrying one needs the column
    // even with no icon to share it with.
    const hasPrefix =
      toggleInPrefix ||
      !!this.icon ||
      this.indicator ||
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]');
    const subnavOpen = showToggle ? this.subnavState === 'open' : true;

    // Collapsed, a heading has no room for its name and nothing to sit above
    // but icons, so it becomes the rule between one run of them and the next.
    // It keeps the name for anything reading the nav aloud: a hidden row would
    // take its tooltip out of reach along with it.
    if (this.group && this.iconOnly) {
      return html`
        <li>
          <hr
            class="rail-separator"
            aria-label="${this.labelText || nothing}"
          />
          ${hasSubnav
            ? html`<div class="subnav" id="${this.subnavId}">
                <slot name="subnav"></slot>
              </div>`
            : nothing}
        </li>
      `;
    }

    return html`
      <li>
        ${this.iconOnly
          ? this.renderIconItem(hasSubnav, useFlyout)
          : this.renderItem(showToggle, hasPrefix, useFlyout)}
        ${this.iconOnly && showToggle
          ? html`<div class="rail-toggle">${this.renderSubnavToggle()}</div>`
          : nothing}
        ${!this.iconOnly && useFlyout ? this.renderFlyout(false) : nothing}
        ${hasSubnav && !useFlyout
          ? html`
              <div
                class="subnav"
                id="${this.subnavId}"
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
