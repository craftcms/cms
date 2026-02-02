import {css} from 'lit';
export default css`
  :host([checked]) {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  :host {
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    font: inherit;
    border-radius: var(--c-radius-sm);
  }

  :host(:hover) {
    background-color: var(--c-color-neutral-bg-normal);
    color: var(--c-color-neutral-on-normal);
  }

  :host([active]) {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  :host([checked]) {
    background-color: var(--c-color-neutral-bg-emphasis);
    color: var(--c-color-neutral-on-emphasis);
  }

  :host([disabled]) {
    color: var(--c-color-neutral-on-normal);
  }

  .hint {
    color: color-mix(in srgb, currentColor, transparent 25%);
    align-self: end;
  }

  :host([active]) .hint {
    color: var(--c-color-neutral-on-emphasis);
  }

  .choice-field__label {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    max-width: 100%;
  }
`;
