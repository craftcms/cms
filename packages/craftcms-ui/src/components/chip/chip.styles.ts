import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  ::slotted([slot='status']) {
    display: inline-flex;
  }

  .cp-chip {
    --_chip-spacing: 0.25em;
    --_thumb-size: calc(30rem / 16);
    --_radius: var(--c-radius-md);
    padding: 0;
    display: inline-flex;
    border-radius: var(--_radius);
    align-items: center;
    box-shadow: var(--c-chip-shadow, var(--c-shadow-sm));
    background-color: var(--c-chip-fill, var(--c-surface-raised));

    border-width: var(--c-chip-border-width, 1px);
    border-style: var(--c-chip-border-style, solid);
    overflow: clip;
  }

  .cp-chip__body ::slotted(a) {
    text-decoration: none;
    font-weight: bold;
    display: flex;
  }

  /*
   * Appearance tiers, mirroring craft-callout so the two read at the same
   * intensity for a given variant. The variant remaps the generic
   * --c-color-* tokens; each tier below picks which loudness of them to use.
   */
  :host([appearance~='solid']) .cp-chip {
    background-color: var(--c-color-fill-loud);
    border-color: var(--c-color-border-loud);
    color: var(--c-color-on-loud);
  }

  :host([appearance~='fill']) .cp-chip {
    background-color: var(--c-color-fill-normal);
    border-color: transparent;
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline-fill']) .cp-chip {
    background-color: var(--c-color-fill-normal);
    border-color: var(--c-color-border-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline']) .cp-chip {
    background-color: transparent;
    border-color: var(--c-color-border-quiet);
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='plain']) .cp-chip {
    background-color: transparent;
    border-color: transparent;
    color: var(--c-color-on-quiet);
  }

  /*
   * Without an author-chosen colour the chip stamps data-color="white", whose
   * fill, border, and text are all static colours — they stay light in dark
   * mode. A default chip takes the theme-aware surface instead, paired with
   * the same text and border tokens craft-pane uses on that surface, so the
   * three move together. Scoped to the two filled tiers so outline and plain
   * stay transparent, and to the stamped colour so a variant still fills with
   * its own.
   */
  :host([data-color='white'][appearance~='fill']) .cp-chip,
  :host([data-color='white'][appearance~='outline-fill']) .cp-chip {
    background-color: var(--c-chip-fill, var(--c-surface-raised));
    border-color: var(
      --c-chip-border-color,
      var(--c-color-neutral-border-quiet)
    );
    color: var(--c-chip-text, var(--c-text-default));
  }

  /* Layout side of plain: no chrome, so no padding or shadow either. */
  .cp-chip--plain {
    padding-block: 0;
    padding-inline: 0;
    box-shadow: none;
  }

  .cp-chip--small {
    --_chip-spacing: 0.25em;
  }

  .cp-chip--medium {
    --_chip-spacing: 0.5em;
    --_thumb-size: calc(34rem / 16);
  }

  .cp-chip--large {
    --_chip-spacing: 1em;
    --_thumb-size: calc(40rem / 16);
  }

  .cp-chip__prefix,
  .cp-chip__body,
  .cp-chip__suffix {
    display: inline-flex;
    flex-direction: column;
  }

  .cp-chip__body {
    padding: calc(var(--_chip-spacing) / 2) var(--_chip-spacing);
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

  /* Prefix gets no padding on its own because each prefix item has different spacing needs */
  .cp-chip__prefix {
    position: relative;
    display: flex;
    align-items: center;
    flex-direction: row;
    flex-wrap: nowrap;
  }

  .cp-chip__suffix {
    padding: calc(var(--_chip-spacing) / 2);
    padding-inline-start: var(--_chip-spacing);
    display: flex;
  }

  .cp-chip__status,
  .cp-chip__icon {
    display: inline-flex;
    padding-inline: var(--_chip-spacing);
  }

  .cp-chip__thumbnail {
    display: flex;
    position: relative;
    width: var(--_thumb-size);
    aspect-ratio: 1;
    padding-inline-end: var(--_chip-spacing);
  }
`;
