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

  .tabs__tab-group {
    gap: var(--c-tabs-tab-gap, var(--c-spacing-md));
    border-block-end: 1px solid
      var(--c-tabs-border, var(--c-color-neutral-border-quiet));
  }

  .tabs__panels {
    /* Lion gives the panels tabindex="0"; the ring belongs on the panel, not
       clipped by the strip's rule. */
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

  :host([layout='vertical']) .tabs__tab-group {
    flex-direction: column;
    border-block-end: none;
    border-inline-end: 1px solid
      var(--c-tabs-border, var(--c-color-neutral-border-quiet));
  }

  :host([layout='vertical']) .tabs__panels {
    flex: 1;
  }
`;
