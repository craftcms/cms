import {html, LitElement} from 'lit';

/**
 * @summary Lays fields out on a twelve-column grid that collapses as its
 * container narrows. Children take a full row by default, and a `width-*`
 * class gives them a fraction of one.
 *
 * The breakpoints are container queries rather than media queries, so a group
 * inside a narrow pane stacks even on a wide screen — the layout follows the
 * space the fields actually have.
 *
 * @slot - The fields to lay out. Add `width-25`, `width-33`, `width-50`,
 *   `width-66`, or `width-75` to a child to give it a fraction of the row.
 */
export default class CraftFieldGroup extends LitElement {
  protected override render() {
    return html`
      <style>
        craft-field-group {
          display: grid;
          grid-template-columns: repeat(12, minmax(0, 1fr));
          gap: var(--gap, var(--c-spacing-lg));
          container-type: inline-size;
        }

        craft-field-group > * {
          grid-column: 1 / -1;
          min-width: 0;
        }

        @container (min-width: 50rem) {
          craft-field-group > .width-25 {
            grid-column: span 3;
          }

          craft-field-group > .width-33 {
            grid-column: span 4;
          }

          craft-field-group > .width-50 {
            grid-column: span 6;
          }

          craft-field-group > .width-66 {
            grid-column: span 8;
          }

          craft-field-group > .width-75 {
            grid-column: span 9;
          }
        }

        @container (min-width: 25rem) and (max-width: calc(50rem - 1px)) {
          craft-field-group > .width-25 {
            grid-column: span 6;
          }
        }
      </style>
      <slot></slot>
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }
}

if (!customElements.get('craft-field-group')) {
  customElements.define('craft-field-group', CraftFieldGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-field-group': CraftFieldGroup;
  }
}
