import {css, html, LitElement} from 'lit';
import {OverlayMixin, withDropdownConfig} from '@lion/ui/overlays.js';
import {queryAssignedElements} from 'lit/decorators.js';
import type CraftActionItem from '@src/components/action-item/action-item';
import {uuid} from '@lion/ui/core.js';

/**
 * @slot - Items to be rendered in the menu.
 * @slot invoker - Element that triggers the menu.
 * @slot backdrop - Element that covers the screen when the menu is open.
 * @slot content - Content to be rendered inside the menu.
 */
export default class CraftActionMenu extends OverlayMixin(LitElement) {
  static override styles = css`
    ::slotted([slot='content']) {
      font-size: var(--c-text-base);
      font-weight: 400;
      display: grid;
      gap: var(--c-spacing-xs);
      border: 1px solid var(--c-color-neutral-border-quiet);
      border-radius: var(--c-radius-md);
      background-color: var(--c-surface-overlay);
      box-shadow: var(--c-shadow-sm);
      padding: var(--c-spacing-sm);
      min-width: calc(180rem / 16);
      max-width: calc(320rem / 16);
    }

    ::slotted(hr) {
      margin: 0;
    }
  `;

  private uid: string;

  // @ts-ignore
  _defineOverlayConfig() {
    return {
      ...withDropdownConfig(),
    };
  }

  private __setupInvoker() {
    const invoker = this._overlayInvokerNode;
    if (invoker) {
      invoker.setAttribute('id', `invoker-${this.uid}`);
      invoker.setAttribute('aria-controls', `content-${this.uid}`);
    }
  }

  private __setupContent() {
    const content = this._overlayContentNode;
    if (content) {
      content.setAttribute('id', `content-${this.uid}`);
      content.setAttribute('role', 'none');
    }
  }

  override _setupOverlayCtrl() {
    super._setupOverlayCtrl();
    this.__setupInvoker();
    this.__setupContent();
  }

  override firstUpdated() {
    this.uid = uuid();
  }

  protected override render(): unknown {
    return html`
      <slot name="invoker"></slot>
      <slot name="backdrop"></slot>
      <slot name="content"></slot>
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
