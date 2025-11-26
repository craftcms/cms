import {css, html, LitElement} from 'lit';
import {OverlayMixin, withDropdownConfig} from '@lion/ui/overlays.js';
import {property, queryAssignedElements} from 'lit/decorators.js';
import type CraftActionItem from '@/components/action-item/action-item';

/**
 * @slot - Items to be rendered in the menu.
 * @slot invoker - Element that triggers the menu.
 * @slot backdrop - Element that covers the screen when the menu is open.
 */
export default class CraftActionMenu extends OverlayMixin(LitElement) {
  static override styles = css`
    .menu {
      display: grid;
      gap: var(--c-spacing-sm);
      border: 1px solid var(--c-color-neutral-border-subtle);
      border-radius: var(--c-radius-md);
      background-color: var(--c-bg-overlay);
      box-shadow: var(--c-shadow-sm);
      padding: var(--c-spacing-sm);
    }

    ::slotted(hr) {
      margin: 0;
    }
  `;

  @queryAssignedElements({selector: 'craft-action-item'})
  actionItems!: CraftActionItem[];

  // @ts-ignore
  _defineOverlayConfig() {
    return {
      placementMode: 'global',
      ...withDropdownConfig(),
    };
  }

  // @ts-ignore
  get _overlayContentNode() {
    return this.shadowRoot?.querySelector('.menu');
  }

  override firstUpdated() {
    // Close the menu when an item is clicked.
    // @TODO is this good or bad?
    this.actionItems.forEach((item) => {
      item.addEventListener('click', (e) => {
        e.target?.dispatchEvent(new Event('close-overlay', {bubbles: true}));
      });
    });
  }

  protected override render(): unknown {
    return html`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>

      <div class="menu">
        <slot></slot>
      </div>
    `;
  }
}

if (!customElements.get('craft-action-menu')) {
  customElements.define('craft-action-menu', CraftActionMenu);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-action-menu': CraftActionMenu;
  }
}
