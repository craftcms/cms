import {html, LitElement, nothing} from 'lit';
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

/**
 * @summary One entry in the control panel's navigation — a link with an
 * optional icon, an indicator, and a nested subnav.
 *
 * Items live inside a `craft-nav-list`, which supplies the list semantics that
 * make the group announce as a list rather than as loose links.
 *
 * When the navigation is collapsed to icons, an item with a subnav shows it in
 * a flyout instead of inline, so the children stay reachable at any width.
 *
 * @slot - The item's label.
 * @slot subnav - Nested `craft-nav-item`s, shown beneath the item or in a
 *   flyout when collapsed.
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

  /**
   * Id of the item, used to tie a subnav to the item that owns it. Generated
   * when not supplied.
   */
  @property()
  override id: string;

  /**
   * Collapses the item to its icon, hiding the label. The label is still
   * announced, and a subnav moves into a flyout rather than disappearing.
   */
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

  @state()
  protected subnavState: string = 'closed';

  /**
   * Whether the icon-only flyout is showing. Collapsed to an icon, an item has
   * nowhere to put its subnav, so it moves into a popover on hover or focus.
   */
  @state()
  flyoutOpen: boolean = false;

  /**
   * Long enough to cross the gap between the icon and the flyout without the
   * flyout closing underneath the pointer.
   */
  static flyoutCloseDelay = 150;

  #flyoutCloseTimer?: ReturnType<typeof setTimeout>;

  #hoverListeners?: AbortController;

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
    this.addEventListener('focusin', this.#openFlyout, {signal});
    this.addEventListener('focusout', this.#scheduleFlyoutClose, {signal});
  }

  override disconnectedCallback() {
    this.#hoverListeners?.abort();
    clearTimeout(this.#flyoutCloseTimer);
    super.disconnectedCallback();
  }

  #openFlyout = () => {
    clearTimeout(this.#flyoutCloseTimer);
    this.flyoutOpen = true;
  };

  #scheduleFlyoutClose = () => {
    clearTimeout(this.#flyoutCloseTimer);
    this.#flyoutCloseTimer = setTimeout(() => {
      this.flyoutOpen = false;
    }, CraftNavItem.flyoutCloseDelay);
  };

  // The overlay closes itself on Escape and outside clicks; follow it, or the
  // state would say open and the next hover wouldn't reopen it.
  #onFlyoutOpenedChanged = (event: Event) => {
    this.flyoutOpen = (event.target as {opened?: boolean}).opened === true;
  };

  /**

   * Expands or collapses the subnav.

   */

  toggleSubnav(event: Event) {
    event.preventDefault();
    event.stopPropagation();
    this.subnavState = this.subnavState === 'open' ? 'closed' : 'open';
  }

  protected renderIconItem(hasSubnav: boolean) {
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
          ? html`
              <craft-popover
                for="${itemId}"
                placement="right-start"
                .opened="${this.flyoutOpen}"
                @opened-changed="${this.#onFlyoutOpenedChanged}"
              >
                <div class="flyout">
                  <div class="flyout__label"><slot></slot></div>
                  <slot name="subnav"></slot>
                </div>
              </craft-popover>
            `
          : html`<craft-tooltip for="${itemId}" placement="right-start"
              ><slot></slot
            ></craft-tooltip>`
      }
    `;
  }

  protected renderSubnavToggle() {
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

  protected renderPrefix(showToggle: boolean = false) {
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

  protected renderSuffix(showToggle: boolean = false) {
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

  protected renderItem(showToggle: boolean, hasPrefix: boolean = false) {
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
    // No label means no toggle, and no way to collapse. A `group` item is a
    // permanent semantic grouping: it never shows a toggle and its subnav
    // stays open (subnavOpen falls back to true when there's no toggle).
    const showToggle = hasSubnav && this.hasLabel && !this.group;
    const toggleInPrefix = showToggle && this.togglePosition === 'prefix';
    const hasPrefix =
      toggleInPrefix ||
      !!this.icon ||
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]');
    const subnavOpen = showToggle ? this.subnavState === 'open' : true;
    // A `slot` can only project its content in one place. Collapsed to a rail
    // there's no room to indent a subnav, so it renders inside the flyout
    // instead of here.
    const inlineSubnav = hasSubnav && !this.iconOnly;

    return html`
      <li>
        ${this.iconOnly
          ? this.renderIconItem(hasSubnav)
          : this.renderItem(showToggle, hasPrefix)}
        ${inlineSubnav
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
