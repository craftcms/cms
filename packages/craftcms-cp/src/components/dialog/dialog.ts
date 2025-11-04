import {css} from 'lit';
import WaDialog from '@awesome.me/webawesome/dist/components/dialog/dialog.js';

/**
 * craft-dialog extends wa-dialog from web awesome and adds custom styling.
 * Anything you can do with that works here.
 */
export default class CraftDialog extends WaDialog {
  static override get styles() {
    return [
      WaDialog.styles,
      css`
        :host {
          --spacing: var(--c-spacing-lg);
          --wa-space-2xl: var(--c-spacing-xl);
        }

        .title {
          font-size: 1.25em;
        }

        .header {
          padding-inline: var(--c-spacing-lg);
          padding-block-start: var(--c-spacing-lg);
          padding-block-end: var(--c-spacing-md);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-dialog')) {
  customElements.define('craft-dialog', CraftDialog);
}
