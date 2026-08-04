import {LionCheckboxIndeterminate} from '@lion/ui/checkbox-group.js';
import {css} from 'lit';

export default class CraftCheckboxIndeterminate extends LionCheckboxIndeterminate {
  static override get styles() {
    return [
      ...LionCheckboxIndeterminate.styles,
      css`
        :host {
          display: flex;
          align-items: center;
          gap: 0 var(--c-spacing-md);
        }

        ::slotted(label) {
          font-weight: bold;
        }

        ::slotted(*) {
          padding-left: 0;
        }
      `,
    ];
  }
}

if (!customElements.get('craft-checkbox-indeterminate')) {
  customElements.define(
    'craft-checkbox-indeterminate',
    CraftCheckboxIndeterminate
  );
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-checkbox-indeterminate': CraftCheckboxIndeterminate;
  }
}
