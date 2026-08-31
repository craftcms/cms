import {LionCheckboxIndeterminate} from '@lion/ui/checkbox-group.js';
import {css} from 'lit';

/**
 * @summary A parent checkbox that reflects the state of the checkboxes nested
 * under it — checked when all are, indeterminate when only some are, and
 * unchecked when none are.
 *
 * Toggling it sets every child to match, which is what makes it a "select
 * all". The indeterminate state is presentational: it is never a value the
 * form posts, only a summary of the children that do.
 *
 * @slot - The child `craft-checkbox`es this one summarises.
 * @slot label - The checkbox's label.
 */
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
