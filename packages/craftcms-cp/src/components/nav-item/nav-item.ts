import {html, LitElement, css, nothing} from 'lit';
import {styleMap} from 'lit/directives/style-map.js';
import {property, state} from 'lit/decorators.js';

/**
 *
 */
export default class CraftNavItem extends LitElement {
  static override styles = css`
    .nav-item {
      display: grid;
      gap: var(--c-spacing-md);
      grid-template-columns: calc(24rem / 16) 1fr auto;
      align-items: center;
      text-decoration: none;
      color: inherit;
      padding-inline: var(--c-spacing-sm);
      padding-block: var(--c-spacing-sm);
      border-radius: var(--c-radius-md);
      position: relative;
    }

    :host([active]) .nav-item {
      &:before {
        content: '';
        position: absolute;
        inset-inline-start: 0;
        inset-block-start: 12%;
        width: calc(3rem / 16);
        height: 76%;
        border-radius: calc(2rem / 16);
        background-color: currentColor;
        transform: translateX(-200%);
      }
    }

    .nav-item:hover:not(:has(craft-button:hover)) {
      background-color: color-mix(in srgb, currentColor, transparent 95%);
    }

    .nav-item__prefix {
      position: relative;
      display: grid;
      justify-content: center;
      align-items: center;
      aspect-ratio: 1;
      width: 100%;
    }

    .active-indicator {
      display: inline-block;
      aspect-ratio: 1;
      width: calc(4rem / 16);
      border-radius: var(--c-radius-full);
      background-color: currentColor;

      :host([active]) & {
        width: calc(6rem / 16);
      }
    }

    .indicator {
      display: inline-block;
      aspect-ratio: 1;
      width: calc(6rem / 16);
      border-radius: var(--c-radius-full);
      background-color: var(--c-color-accent-bg-emphasis);
      border: 1px solid var(--c-color-accent-border-emphasis);
      outline: 2px solid Canvas;
      position: absolute;
      inset-inline-end: 0;
      inset-block-end: 0;
    }

    craft-button {
      //outline: 1px solid red;
      //aspect-ratio: 1;
    }

    .subnav {
      margin-block-start: var(--c-spacing-sm);
      margin-inline-start: calc(
        (var(--c-size-icon-md) / 2) + var(--c-spacing-sm) + 1px
      );
      padding-inline: var(--c-spacing-sm);
      border-left: 2px solid color-mix(in srgb, currentColor, transparent 90%);
    }
  `;

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

  @state()
  subnavState: string = 'open';

  constructor() {
    super();
    this.id = this.id || Math.random().toString(36).substring(2, 6);
  }

  toggleSubnav(event: Event) {
    event.preventDefault();
    event.stopPropagation();
    this.subnavState = this.subnavState === 'open' ? 'closed' : 'open';
  }

  override render() {
    const hasSubnav = !!this.querySelector('[slot="subnav"]');

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
        <slot></slot>

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
