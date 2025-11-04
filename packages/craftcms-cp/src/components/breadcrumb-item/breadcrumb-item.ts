import {css, html, LitElement} from 'lit';
import {customElement} from 'lit/decorators.js';
import WaBreadcrumbItem from '@awesome.me/webawesome/dist/components/breadcrumb-item/breadcrumb-item.js';

export default class CraftBreadcrumbItem extends WaBreadcrumbItem {
  static override get styles() {
    return [
      WaBreadcrumbItem.styles,
      css`
        :host {
          --wa-font-weight-action: 400;
          --wa-space-s: var(--c-spacing-sm);
          white-space: nowrap;
          display: inline-flex;
          align-items: center;
          color: inherit;
        }

        .start {
          margin-inline-end: var(--c-spacing-sm);
        }

        .end {
          margin-inline-start: var(--c-spacing-sm);
        }

        .separator {
          color: var(--c-fg-muted);
          margin: 0 var(--c-spacing-md);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-breadcrumb-item')) {
  customElements.define('craft-breadcrumb-item', CraftBreadcrumbItem);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-breadcrumb-item': CraftBreadcrumbItem;
  }
}
