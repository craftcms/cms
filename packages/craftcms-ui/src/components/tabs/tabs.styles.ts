import {css} from 'lit';

export default css`
  :host {
    display: block;
  }

  .tabs {
    display: flex;
    flex-direction: column;
    gap: var(--c-tabs-gap, var(--c-spacing-lg));
  }

  /*
   * The rule lives on the strip rather than the tablist so it runs under the
   * overflow menu too, which sits beside the tablist rather than inside it.
   */
  .tabs__strip {
    display: flex;
    align-items: center;
    min-width: 0;
    border-block-end: 1px solid
      var(--c-tabs-border, var(--c-color-neutral-border-quiet));
  }

  .tabs__tab-group {
    flex: 1;
    min-width: 0;
    gap: var(--c-tabs-tab-gap, var(--c-spacing-md));
  }

  /*
   * Tabs hold their natural width instead of shrinking to fit: overflow is
   * resolved by collapsing whole tabs into the menu, and a row of squeezed,
   * half-legible tabs would defeat the measurement that decides which.
   */
  ::slotted([slot='tab']) {
    flex: none;
  }

  /*
   * craft-popover gives itself display: contents, which would leave the menu
   * with no box to lay out or measure — and, being an author style, would also
   * beat the UA's [hidden] rule. Rules here are in the outer tree relative to
   * the menu's shadow root, so they win over its :host block.
   */
  .tabs__overflow {
    display: flex;
    align-items: center;
    flex: none;
    /* Above the tabs, so the invoker's own click-target pseudo-element can't
       be covered by the tab beside it. */
    position: relative;
    z-index: 1;
  }

  .tabs__overflow[hidden] {
    display: none;
  }

  .tabs__panels {
    min-width: 0;
  }

  /*
   * Custom properties set here inherit into the slotted <craft-tab>s (they're
   * light-DOM children), which is how the tabs move their selected indicator
   * from the block end to the inline end without reading the layout
   * themselves.
   */
  :host([layout='vertical']) {
    --c-tab-indicator-inset-block-start: 0;
    --c-tab-indicator-inset-block-end: 0;
    --c-tab-indicator-inset-inline-start: auto;
    --c-tab-indicator-inset-inline-end: -1px;
    --c-tab-indicator-block-size: auto;
    --c-tab-indicator-inline-size: calc(2rem / 16);
  }

  :host([layout='vertical']) .tabs {
    flex-direction: row;
  }

  :host([layout='vertical']) .tabs__strip {
    align-items: stretch;
    border-block-end: none;
    border-inline-end: 1px solid
      var(--c-tabs-border, var(--c-color-neutral-border-quiet));
  }

  :host([layout='vertical']) .tabs__tab-group {
    flex-direction: column;
  }

  :host([layout='vertical']) .tabs__panels {
    flex: 1;
  }
`;
