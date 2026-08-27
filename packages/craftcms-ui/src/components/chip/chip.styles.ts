import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  ::slotted([slot='status']) {
    display: inline-flex;
  }

  .cp-chip {
    --_min-height: var(--c-chip-height, none);
    --_thumb-size: calc(24rem / 16);
    --_radius: var(--c-chip-radius, var(--c-radius-md));
    --_fill: var(--c-color-fill-quiet, var(--c-surface-raised));
    padding: 0;
    display: inline-flex;
    min-width: auto;
    border-radius: var(--_radius);
    align-items: center;
    box-shadow: var(--c-chip-shadow, var(--c-shadow-sm));

    /* colorable styles */
    color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    border-width: var(--c-chip-border-width, 1px);
    border-style: var(--c-chip-border-style, solid);
    border-color: var(
      --c-color-border-quiet,
      var(--c-color-neutral-border-quiet)
    );
    background-color: var(--c-surface-raised);
    overflow: clip;
  }

  .cp-chip__body ::slotted(a) {
    text-decoration: none;
    font-weight: bold;
    display: flex;
  }

  .cp-chip[appearance='plain'],
  .cp-chip--plain {
    --_min-height: none;
    padding-block: 0;
    padding-inline: 0;
    border-color: transparent;
    background-color: transparent;
    box-shadow: none;
  }

  .cp-chip[size='small'],
  .cp-chip--small {
    padding-block: calc(var(--c-spacing-xs) / 2);
  }

  .cp-chip[size='medium'],
  .cp-chip--medium {
    padding-block: 0;
    min-height: var(--c-size-control-md);
  }

  /*
   * Selected state, matching a selected thumbnail tile in the element index
   * (.thumbsview > li.sel .thumb-tile) so a selection reads the same however the
   * elements are being shown.
   *
   * Specificity puts this above the appearance and size variants deliberately:
   * a selected chip stays legible as selected even when it is plain.
   */
  :host([selected]) .cp-chip {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: var(--c-color-accent-border-quiet);
  }

  .cp-chip__prefix,
  .cp-chip__body,
  .cp-chip__suffix {
    display: inline-flex;
    flex-direction: column;
    min-height: var(--_min-height);
  }

  .cp-chip__body {
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: center;
    flex-direction: row;
    flex-wrap: nowrap;
    flex: 1 1 auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cp-chip__prefix {
    position: relative;
    padding-inline-end: var(--c-spacing-sm);
    display: flex;
    align-items: center;
    flex-direction: row;
    flex-wrap: nowrap;
  }

  .cp-chip__suffix {
    display: flex;
    padding-inline-start: var(--c-spacing-md);
  }

  .cp-chip__status {
    display: inline-flex;
    padding-inline: var(--c-spacing-xs);
  }

  .cp-chip__thumbnail {
    position: relative;
    padding: var(--c-spacing-sm);
  }
`;
