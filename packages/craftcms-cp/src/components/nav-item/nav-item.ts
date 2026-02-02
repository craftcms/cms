import {html, LitElement, nothing} from 'lit';
import {styleMap} from 'lit/directives/style-map.js';
import {property, state} from 'lit/decorators.js';
import styles from './nav-item.styles';
import {classMap} from 'lit/directives/class-map.js';

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
  url: string;

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

  @state()
  subnavState: string = 'closed';

  constructor() {
    super();
    this.id = this.id || Math.random().toString(36).substring(2, 6);
  }

  override connectedCallback() {
    super.connectedCallback();
    // Default to open when the item is active
    this.subnavState = this.active ? 'open' : 'closed';
  }

  toggleSubnav(event: Event) {
    event.preventDefault();
    event.stopPropagation();
    this.subnavState = this.subnavState === 'open' ? 'closed' : 'open';
  }

  renderIconItem(hasSubnav: boolean) {
    const itemId = `item-${this.id}`;

    return html`
      <a
        class="nav-item"
        id="${itemId}"
        href="${this.url}"
        aria-current="${this.active ? 'page' : false}"
      >
        ${this.renderPrefix()} ${this.renderSuffix(hasSubnav)}
      </a>
      <c-tooltip for="${itemId}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `;
  }

  renderPrefix() {
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
          ${this.indicator ? html`<span class="indicator"></span>` : nothing}
        </slot>
      </span>
    `;
  }

  renderSuffix(hasSubnav: boolean = false) {
    return html`
      <div class="nav-item__suffix">
        <slot name="suffix">
          ${hasSubnav
            ? html`
                <craft-button
                  @click="${this.toggleSubnav}"
                  icon
                  size="small"
                  aria-controls="${this.id}-subnav"
                  aria-expanded="${this.subnavState === 'open'
                    ? 'true'
                    : 'false'}"
                >
                  <craft-icon
                    name="${this.subnavState === 'closed'
                      ? 'chevron-down'
                      : 'chevron-up'}"
                    style="font-size: calc(10rem / 16)"
                  ></craft-icon>
                </craft-button>
              `
            : nothing}
        </slot>
      </div>
    `;
  }

  renderItem(hasSubnav: boolean, hasPrefix: boolean = false) {
    return html`
      <a
        class="${classMap({
          'nav-item': true,
          'nav-item--prefixed': hasPrefix,
        })}"
        href="${this.url}"
        aria-current="${this.active ? 'page' : false}"
      >
        ${hasPrefix ? this.renderPrefix() : nothing}
        <slot></slot>
        ${this.renderSuffix(hasSubnav)}
      </a>
    `;
  }

  override render() {
    const hasSubnav = !!this.querySelector('[slot="subnav"]');
    const hasPrefix =
      !!this.icon ||
      !!this.querySelector('[slot="prefix"]') ||
      !!this.querySelector('[slot="icon"]');

    return html`
      <li>
        ${this.iconOnly
          ? this.renderIconItem(hasSubnav)
          : this.renderItem(hasSubnav, hasPrefix)}
        ${hasSubnav
          ? html`
              <div
                class="subnav"
                id="${this.id}-subnav"
                style="${styleMap({
                  display: this.subnavState === 'open' ? 'block' : 'none',
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
