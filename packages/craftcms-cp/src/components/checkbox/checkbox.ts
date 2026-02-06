import {LionCheckbox} from '@lion/ui/checkbox-group.js';
import {css} from 'lit';

export default class CraftCheckbox extends LionCheckbox {
  static override get styles() {
    return [
      ...LionCheckbox.styles,
      css`
        /* same as radio, potentially consolidate */
        :host {
          display: grid;
          align-items: center;
          gap: 0 var(--c-spacing-md);
          grid-template-areas: 'input label' '. help-text';
          grid-template-columns: auto 1fr;
          grid-template-rows: repeat(2, auto);
        }

        ::slotted(label) {
          font: inherit;
          grid-area: label;
        }

        ::slotted([slot='input']) {
          background-color: var(--c-input-bg, var(--c-form-control-bg));
          border: var(--c-input-border, 1px solid var(--c-form-control-border));
          border-radius: var(--c-input-radius, var(--c-radius-sm));
        }

        .choice-field__help-text {
          font-size: 1em;
          color: var(--c-fg-muted);
          grid-area: help-text;
        }
      `,
    ];
  }
}

if (!customElements.get('craft-checkbox')) {
  customElements.define('craft-checkbox', CraftCheckbox);
}
