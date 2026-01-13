import {html, LitElement, css, nothing} from 'lit';
import {styleMap} from 'lit/directives/style-map.js';
import {property, state} from 'lit/decorators.js';
import styles from './nav-item.styles';
import {t} from '@craftcms/cp/utilities/translate.ts.mjs';

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
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon
                ? html` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`
                : html` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator ? html`<span class="indicator"></span>` : nothing}
          </slot>
        </span>

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
      </a>
      <c-tooltip for="${itemId}" placement="right-start"
        ><slot></slot
      ></c-tooltip>
    `;
  }

  renderItem(hasSubnav: boolean) {
    return html`
      <a
        class="nav-item"
        href="${this.url}"
        aria-current="${this.active ? 'page' : false}"
      >
        <span class="nav-item__prefix">
          <slot name="prefix">
            <slot name="icon">
              ${this.icon
                ? html` <craft-icon
                    name="${this.icon}"
                    class="nav-icon"
                  ></craft-icon>`
                : html` <span class="active-indicator"></span> `}
            </slot>
            ${this.indicator ? html`<span class="indicator"></span>` : nothing}
          </slot>
        </span>
        <slot id="${this.id}-label"></slot>

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
                    aria-labelledby="${this.id}-toggle-icon ${this.id}-label"
                  >
                    <craft-icon
                      id="${this.id}-toggle-icon""
                      name="${this.subnavState === 'closed'
                        ? 'chevron-down'
                        : 'chevron-up'}"
                      style="font-size: calc(10rem / 16)"
                      label="${t('app', 'Toggle subnavigation')}"
                    ></craft-icon>
                  </craft-button>
                `
              : nothing}
          </slot>
        </div>
      </a>
    `;
  }

  override render() {
    const hasSubnav = !!this.querySelector('[slot="subnav"]');
    return html`
      <li>
        ${this.iconOnly
          ? this.renderIconItem(hasSubnav)
          : this.renderItem(hasSubnav)}
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
