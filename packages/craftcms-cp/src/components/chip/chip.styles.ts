import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  .chip {
    color: var(--c-chip-fg, var(--c-color-neutral-on-quiet));
    display: inline-flex;
    min-height: var(--c-chip-height, var(--c-size-control-sm));
    min-width: auto;
    border-radius: var(--c-chip-radius, var(--c-radius-md));
    border: var(--c-chip-border, 1px solid var(--c-color-neutral-border-quiet));
    background-color: var(--c-chip-bg, var(--c-color-neutral-fill-quiet));
    padding-inline: var(--c-chip-spacing-inline, var(--c-spacing-md));
    padding-block: var(--c-chip-spacing-block, var(--c-spacing-sm));
    align-items: start;
    box-shadow: var(--c-chip-shadow, var(--c-shadow-sm));
  }

  .chip[appearance='plain'],
  .chip--plain {
    padding-block: 0;
    padding-inline: 0;
    border-color: transparent;
    background-color: transparent;
    box-shadow: none;
  }

  .chip[size='small'],
  .chip--small {
    padding-block: 0;
    min-height: var(--c-size-control-sm);
  }

  chip[size='medium'],
  .chip--medium {
    padding-block: 0;
    min-height: var(--c-size-control-md);
  }

  .chip__prefix,
  .chip__body,
  .chip__suffix {
    display: inline-flex;
    flex-direction: column;
    align-self: center;
  }

  .chip__body {
    flex: 1 1 auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .chip__prefix {
    padding-inline-end: var(--c-spacing-md);
  }

  .chip__suffix {
    padding-inline-start: var(--c-spacing-md);
  }

  :host(:not([variant='plain'])) .chip__suffix {
    margin-inline-end: calc(var(--c-spacing-sm) * -1);
  }
`;
