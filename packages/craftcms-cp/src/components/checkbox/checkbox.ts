import {LionCheckbox} from '@lion/ui/checkbox-group.js';
import {css} from 'lit';

export default class CraftCheckbox extends LionCheckbox {
  static override get styles() {
    return [
      ...LionCheckbox.styles,
      css`
        :host {
          display: flex;
          gap: var(--c-spacing-sm);
        }

        ::slotted(label) {
          font: inherit;
        }

        ::slotted([slot='input']) {
          background-color: var(--c-input-bg, var(--c-form-control-bg));
          border: var(--c-input-border, 1px solid var(--c-form-control-border));
          border-radius: var(--c-input-radius, var(--c-radius-sm));
        }

        .choice-field__help-text {
          font-size: 1em;
          color: var(--c-fg-muted);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-checkbox')) {
  customElements.define('craft-checkbox', CraftCheckbox);
}
