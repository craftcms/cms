import {LitElement, nothing} from 'lit';
import {literal, html} from 'lit/static-html.js';
import {styleMap} from 'lit/directives/style-map.js';
import {property, state} from 'lit/decorators.js';
import {ifDefined} from 'lit/directives/if-defined.js';
import '../badge-indicator/badge-indicator';
import styles from './nav-item.styles';
import {t} from '@src/utilities/translate.js';
import {classMap} from 'lit/directives/class-map.js';
import {Appearance} from '@src/constants/appearances';

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

  /** Where the subnav disclosure toggle is rendered. */
  @property({attribute: 'toggle-position'})
  togglePosition: 'prefix' | 'suffix' = 'suffix';

  @state()
  subnavState: string = 'closed';

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
  }

  toggleSubnav(event: Event) {
    event.preventDefault();
    event.stopPropagation();
    this.subnavState = this.subnavState === 'open' ? 'closed' : 'open';
  }

  renderIconItem(showToggle: boolean) {
    const itemId = `item-${this.id}`;

    return html`
      <a
        class="${classMap({
          'nav-item': true,
          'nav-item--icon': true,
          'nav-item--static': !this.href,
        })}"
        id="${itemId}"
        href="${ifDefined(this.href || undefined)}"
        aria-current="${this.active ? 'page' : false}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(showToggle)}
      </a>
      <craft-tooltip for="${itemId}" placement="right-start"
        ><slot></slot
      ></craft-tooltip>
    `;
  }

  renderSubnavToggle() {
    return html`
      <craft-button
        @click="${this.toggleSubnav}"
        appearance="${Appearance.Plain}"
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
    return html`
      <div
        class="${classMap({
          'nav-item': true,
          'nav-item--prefixed': hasPrefix,
          'nav-item--flush': this.flush,
          'nav-item--static': !this.href,
        })}"
      >
        ${hasPrefix ? this.renderPrefix(showToggle) : nothing}
        ${this.renderInteractiveItem()}
        ${this.renderSuffix(showToggle)}
      </div>
    `;
  }

  renderInteractiveItem() {
    const tagName = this.href ? literal`a` : literal`button`;
    return html`
      <${tagName}
        class="nav-item__action-item"
        href="${ifDefined(this.href || undefined)}"
        aria-current="${this.active ? 'page' : false}"
      >
        <slot
          id="${this.id}-label"
          @slotchange="${() => this.requestUpdate()}"
        ></slot>
      </${tagName}>
    `;
  }

  override render() {
    const hasSubnav = !!this.querySelector('[slot="subnav"]');
    // No label means no toggle, and no way to collapse.
    const showToggle = hasSubnav && this.hasLabel;
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
          ? this.renderIconItem(showToggle)
          : this.renderItem(showToggle, hasPrefix)}
        ${hasSubnav
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
