import {css} from 'lit';
import {queryAssignedElements} from 'lit/decorators.js';
import type CraftActionItem from '@src/components/action-item/action-item';
import CraftPopover from '../popover/popover.js';

/**
 * An action menu built on craft-popover.
 *
 * @slot invoker - Element that triggers the menu.
 * @slot content - Action items to be rendered in the menu.
 */
export default class CraftActionMenu extends CraftPopover {
  static override styles = [
    ...CraftPopover.styles,
    css`
      ::slotted([slot='content']) hr {
        margin: 0;
      }
    `,
  ];

  @queryAssignedElements({slot: 'content'})
  contentNodes!: HTMLElement[];

  private _addEventListeners() {
    const content = this.contentNodes[0];
    if (!content) return;

    content
      .querySelectorAll<CraftActionItem>('craft-action-item')
      .forEach((item) => {
        item.addEventListener('click', () => {
          this.opened = false;
        });
      });
  }

  override _setupOverlayCtrl() {
    super._setupOverlayCtrl();
    this._addEventListeners();
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
