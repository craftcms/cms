import {css} from 'lit';
export default css`
  :host {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    column-gap: var(--c-spacing-sm);
    --c-option-wide-threshold: 640;
    /* LionOption hard-codes a white fill */
    background-color: transparent;
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    font: inherit;
    border-radius: var(--c-radius-sm);
  }

  /* LionOption fills a checked option; here the check mark marks it */
  :host([checked]) {
    background-color: transparent;
  }

  :host(:hover) {
    background-color: var(--c-color-neutral-fill-normal);
    color: var(--c-color-neutral-on-normal);
  }

  :host([active]) {
    background-color: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
  }

  :host([disabled]) {
    color: var(--c-color-neutral-on-normal);
  }

  .hint {
    color: color-mix(in srgb, currentColor, transparent 25%);
    align-self: end;
    font-size: 0.8em;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  :host([active]) .hint {
    color: var(--c-color-neutral-on-loud);
  }

  .choice-field__label {
    display: grid;
    max-width: 100%;
  }

  .content {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
  }

  @media (forced-colors: active) {
    :host([active]) {
      outline: var(--c-focus-outline-width) solid Highlight;
      outline-offset: calc(var(--c-focus-outline-width) * -1);
    }
  }

  :host([wide]) .choice-field__label {
    align-items: baseline;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
`;
