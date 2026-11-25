import {html, LitElement} from 'lit';
import {property} from 'lit/decorators.js';

/** How a metadata list lays out its items. */
export type MetadataListLayout = 'auto' | 'stacked' | 'inline' | 'row';

/**
 * @summary Lays out a description list of facts about something, such as an
 * element's ID, status and dates. Wrap a native `<dl>` whose items are each a
 * `<div>` holding a `<dt>` and its `<dd>`.
 *
 * The list stays in the light DOM, so the terms and their descriptions keep
 * their native semantics. `auto` puts each label beside its value where the
 * list has room, and above it where it doesn't, measured by the list's own
 * width rather than the screen's.
 *
 * @slot - A `<dl>` of `<div>`s, each a `<dt>` and its `<dd>`.
 */
export default class CraftMetadataList extends LitElement {
  /**
   * `auto` (the default) switches between `stacked` and `inline` by the list's
   * width; `stacked` puts each label above its value; `inline` puts each label
   * beside its value, the values lined up in a column; `row` lays the items out
   * side by side, each label above its value.
   */
  @property({reflect: true}) layout: MetadataListLayout = 'auto';

  protected override render() {
    return html`
      <style>
        craft-metadata-list {
          display: block;
          container-type: inline-size;
        }

        craft-metadata-list > dl {
          display: grid;
          gap: var(--c-spacing-md);
          margin: 0;
          padding: 0;
        }

        craft-metadata-list > dl > div {
          display: grid;
          align-items: baseline;
          min-width: 0;
        }

        /* a lone badge or chip is taller than a line of text, so it centers on its label */
        craft-metadata-list
          > dl
          > div:has(
            > dd > :is(craft-badge, craft-chip, .cp-color-bar):only-child
          ) {
          align-items: center;
        }

        craft-metadata-list dt {
          font-size: var(--c-text-sm);
          font-weight: var(--font-weight-bold, 700);
        }

        craft-metadata-list dd {
          margin: 0;
          min-width: 0;
        }

        craft-metadata-list[layout='inline'] > dl {
          grid-template-columns: minmax(max-content, 25%) minmax(0, 1fr);
        }

        craft-metadata-list[layout='inline'] > dl > div {
          grid-column: span 2;
          grid-template-columns: subgrid;
          gap: unset;
        }

        /* Roughly 250px */
        @container (width > 16.25rem) {
          craft-metadata-list:is(:not([layout]), [layout='auto']) > dl {
            grid-template-columns: minmax(max-content, 25%) minmax(0, 1fr);
          }

          craft-metadata-list:is(:not([layout]), [layout='auto']) > dl > div {
            grid-column: span 2;
            grid-template-columns: subgrid;
            gap: unset;
          }
        }

        craft-metadata-list[layout='row'] > dl {
          grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
          column-gap: var(--c-spacing-2xl);
        }
      </style>
    `;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }
}

if (!customElements.get('craft-metadata-list')) {
  customElements.define('craft-metadata-list', CraftMetadataList);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-metadata-list': CraftMetadataList;
  }
}
