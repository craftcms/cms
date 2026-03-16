import type {CSSResultGroup} from 'lit';
import {LionDialog} from '@lion/ui/dialog.js';
import styles from './dialog.styles';

/**
 * @summary Dialog that extends the LionDialog web component
 */
export default class CraftDialog extends LionDialog {
  static override get styles(): CSSResultGroup {
    return [super.styles ?? [], styles];
  }
}

if (!customElements.get('craft-dialog')) {
  customElements.define('craft-dialog', CraftDialog);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-dialog': CraftDialog;
  }
}
