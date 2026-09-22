import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  :host([hidden]) {
    display: none;
  }

  .action-item {
    color: var(--c-color-on-quiet, inherit);
    background-color: transparent;

    font: inherit;
    text-align: left;
    display: flex;
    width: 100%;
    align-items: center;
    text-decoration: none;
    padding-inline: var(--c-spacing-sm);
    padding-block: var(--c-spacing-sm);
    border-radius: var(--c-radius-md);
    position: relative;
    border: 1px solid transparent;
  }

  @media (hover: hover) {
    :host(:hover:not([active])) .action-item:not(:disabled) {
      background-color: var(
        --c-color-fill-quiet,
        var(--c-color-neutral-fill-quiet)
      );
      color: var(--c-color-on-quiet, var(--c-color-neutral-on-quiet));
    }
  }

  /* Matches the main navigation's current item. */
  :host([active]) .action-item {
    background-color: var(--c-color-accent-fill-quiet);
    border-color: var(--c-color-accent-border-loud);
    color: var(--c-color-accent-on-quiet);
  }

  .action-item:disabled {
    opacity: 0.5;
  }

  .action-item:not(:disabled) {
    cursor: pointer;
  }

  .action-item__check,
  .action-item__icon,
  .action-item__suffix {
    min-height: 1lh;
  }

  .action-item__check,
  .action-item__icon {
    min-width: 1lh;
    display: inline-grid;
    place-items: center;
    align-self: start;
  }

  .action-item__check {
    aspect-ratio: 1;
  }

  .action-item__suffix {
    align-self: center;
  }

  craft-shortcut {
    margin-inline-start: var(--c-spacing-sm);
  }

  .action-item__label {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-inline: var(--c-spacing-sm);
  }

  :host([variant='danger']) .action-item {
    color: var(--c-color-on-quiet);
  }

  @media (hover: hover) {
    :host(:hover[variant='danger']:not([active])) .action-item:not(:disabled) {
      background-color: var(--c-color-fill-quiet);
      color: var(--c-color-on-quiet);
    }
  }
`;
