import {css, html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';
import {classMap} from 'lit/directives/class-map.js';

export default class CraftPane extends LitElement {
  static override styles = [
    css`
      .cp-pane {
        display: block;
        border: 1px solid var(--c-color-border-quiet);
        border-radius: var(--c-radius-md);
      }

      .cp-pane--lg {
        padding: var(--c-spacing-lg);
      }

      .cp-pane--raised {
        background-color: var(--c-surface-raised);
        box-shadow: var(--c-shadow-raised);
      }
    `,
  ];

  @property() appearance: 'raised' | 'sunken' | 'none' = 'raised';
  @property() size: 'sm' | 'md' | 'lg' | 'none' = 'lg';

  protected override render() {
    return html`<div
      class="${classMap({
        'cp-pane': true,
        'cp-pane--raised': this.appearance === 'raised',
        'cp-pane--sunken': this.appearance === 'sunken',
        'cp-pane--lg': this.size === 'lg',
        'cp-pane--md': this.size === 'md',
        'cp-pane--sm': this.size === 'sm',
      })}"
    >
      <slot></slot>
    </div>`;
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
