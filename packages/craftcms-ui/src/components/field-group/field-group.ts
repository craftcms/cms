import {html, LitElement, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

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
 *
 * @cssproperty [--c-field-group-separator-spacing=var(--c-spacing-lg)] - In a
 *   `separated` group, the space on each side of a column hairline.
 */
export default class CraftFieldGroup extends LitElement {
  /**
   * Widens the gap between columns and draws a hairline down the middle of
   * it, between fields that share a row.
   */
  @property({type: Boolean, reflect: true}) separated = false;

  private readonly resizeObserver = new ResizeObserver(() =>
    this.scheduleColumnUpdate()
  );

  private readonly childObserver = new MutationObserver(() => {
    this.observeChildren();
    this.scheduleColumnUpdate();
  });

  private columnUpdateFrame: number | null = null;

  override connectedCallback(): void {
    super.connectedCallback();
    this.toggleColumnTracking();
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.stopColumnTracking();
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    if (changedProperties.has('separated')) {
      this.toggleColumnTracking();
    }
  }

  private toggleColumnTracking(): void {
    this.stopColumnTracking();

    if (!this.separated || !this.isConnected) {
      return;
    }

    this.childObserver.observe(this, {childList: true});
    this.observeChildren();
    this.scheduleColumnUpdate();
  }

  private stopColumnTracking(): void {
    this.childObserver.disconnect();
    this.resizeObserver.disconnect();
    if (this.columnUpdateFrame !== null) {
      cancelAnimationFrame(this.columnUpdateFrame);
      this.columnUpdateFrame = null;
    }
    for (const child of this.children) {
      child.removeAttribute('data-follows-column');
    }
  }

  /**
   * Watches the group and each child, since showing or hiding a child can
   * reflow the rows without resizing the group.
   */
  private observeChildren(): void {
    this.resizeObserver.disconnect();
    this.resizeObserver.observe(this);
    for (const child of this.children) {
      this.resizeObserver.observe(child);
    }
  }

  private scheduleColumnUpdate(): void {
    if (this.columnUpdateFrame !== null) {
      return;
    }

    this.columnUpdateFrame = requestAnimationFrame(() => {
      this.columnUpdateFrame = null;
      this.updateColumns();
    });
  }

  /**
   * Marks each child that sits beside the one before it, so its hairline can
   * be drawn. Rows break at different points as the container narrows and as
   * children show or hide, so this follows the layout rather than the widths.
   */
  private updateColumns(): void {
    let previous: HTMLElement | null = null;

    for (const child of this.children) {
      if (
        !(child instanceof HTMLElement) ||
        child.localName === 'style' ||
        child.localName === 'slot'
      ) {
        continue;
      }

      const shown = child.getClientRects().length > 0;
      child.toggleAttribute(
        'data-follows-column',
        shown && previous !== null && previous.offsetTop === child.offsetTop
      );

      if (shown) {
        previous = child;
      }
    }
  }

  protected override render() {
    return html`
      <style>
        craft-field-group {
          display: grid;
          grid-template-columns: repeat(12, minmax(0, 1fr));
          gap: var(--gap, calc(var(--c-spacing) * 6)) var(--c-spacing-md);
          container-type: inline-size;
        }

        craft-field-group > * {
          grid-column: 1 / -1;
          min-width: 0;
        }

        /* Capped so the eleven gaps never crowd out the columns in a narrow
           group, such as a details panel, where fields don't share rows. */
        craft-field-group[separated] {
          --_column-gap: min(
            calc(
              var(--c-field-group-separator-spacing, var(--c-spacing-lg)) * 2
            ),
            100% / 12
          );
          column-gap: var(--_column-gap);
        }

        craft-field-group[separated] > [data-follows-column] {
          position: relative;
        }

        /* Matches the column gap, but in cqi: a percentage here would resolve against the field, not the group. */
        craft-field-group[separated] > [data-follows-column]::before {
          content: '';
          position: absolute;
          inset-block: 0;
          inset-inline-start: calc(
            min(
                calc(
                  var(--c-field-group-separator-spacing, var(--c-spacing-lg)) *
                    2
                ),
                100cqi / 12
              ) /
              -2
          );
          border-inline-start: 1px solid var(--c-color-neutral-border-quiet);
          pointer-events: none;
        }

        craft-field-group.auto-widths > :not([class*='width-']) {
          width: fit-content;
        }

        craft-field-group.auto-widths > :not([class*='width-']) craft-select {
          width: max-content;
          max-width: 100%;
        }

        craft-field[fieldset]
          > craft-field-group.auto-widths:not(:has(> [class*='width-'])) {
          display: flex;
          flex-wrap: wrap;
          gap: var(--c-spacing-sm) var(--c-spacing-md);
        }

        /* Sub-labels of grouped inputs. --c-text-quiet is the lightest text
           color that still meets WCAG AA contrast at this size. */
        craft-field[fieldset] > craft-field-group.auto-widths {
          --c-field-label-font-size: var(--c-text-sm);
          --c-field-label-font-weight: var(--font-weight-semibold, 600);
          --c-field-label-color: var(--c-text-quiet);
          --c-field-label-line-height: 1;
          --c-field-label-margin-block-end: var(--c-spacing-sm);
          margin-block-start: var(--c-spacing-sm);
        }

        craft-field[fieldset][required]
          > craft-field-group
          craft-field[required]
          > [slot='label']
          > craft-icon[name='asterisk'][data-craft-field-decoration] {
          display: none;
        }

        @container (min-width: 30rem) {
          craft-field-group.auto-widths > :not([class*='width-']) {
            grid-column: span 2;
          }

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
            grid-column: span 6;
          }

          craft-field-group > .width-75 {
            grid-column: span 6;
          }
        }

        @container (min-width: 50rem) {
          craft-field-group.auto-widths > :not([class*='width-']) {
            grid-column: span 1;
          }

          craft-field-group.auto-widths
            > :not([class*='width-']):has(craft-select) {
            grid-column: span 2;
          }

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
