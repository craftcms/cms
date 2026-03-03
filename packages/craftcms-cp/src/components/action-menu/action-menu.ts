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
      max-width: calc(240rem / 16);
    }

    ::slotted(hr) {
      margin: 0;
    }
  `;

  @queryAssignedElements({selector: 'craft-action-item'})
  actionItems!: CraftActionItem[];

  @queryAssignedElements({slot: 'invoker'})
  invokerNodes!: HTMLElement[];

  @queryAssignedElements({slot: 'content'})
  contentNodes!: HTMLElement[];

  private uid: string;

  // @ts-ignore
  _defineOverlayConfig() {
    return {
      ...withDropdownConfig(),
    };
  }

  private _addEventListeners() {
    // Close the menu when an item is clicked.
    // @TODO is this good or bad?
    this.actionItems.forEach((item) => {
      item.addEventListener('click', (e) => {
        e.target?.dispatchEvent(new Event('close-overlay', {bubbles: true}));
      });
    });
  }

  private _setupInvoker() {
    const firstInvoker = this.invokerNodes[0];
    if (firstInvoker) {
      firstInvoker.setAttribute('id', `invoker-${this.uid}`);
      firstInvoker.setAttribute('aria-controls', `content-${this.uid}`);
    }
  }

  private _setupContent() {
    const firstContent = this.contentNodes[0];
    if (firstContent) {
      firstContent.setAttribute('id', `content-${this.uid}`);
      firstContent.setAttribute('role', 'none');
    }
  }

  override _setupOverlayCtrl() {
    super._setupOverlayCtrl();
    this._setupInvoker();
    this._setupContent();
  }

  override firstUpdated() {
    this.uid = uuid();
    this._addEventListeners();
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
