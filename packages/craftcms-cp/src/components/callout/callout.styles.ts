import {css} from 'lit';

export default css`
  :host {
    --c-color-bg-emphasis: var(--c-color-neutral-bg-emphasis);
    --c-color-bg-normal: var(--c-color-neutral-bg-normal);
    --c-color-bg-subtle: var(--c-color-neutral-bg-subtle);
    --c-color-border-emphasis: var(--c-color-neutral-border-emphasis);
    --c-color-border-normal: var(--c-color-neutral-border-normal);
    --c-color-border-subtle: var(--c-color-neutral-border-subtle);
    --c-color-on-emphasis: var(--c-color-neutral-on-emphasis);
    --c-color-on-normal: var(--c-color-neutral-on-normal);
    --c-color-on-subtle: var(--c-color-neutral-on-subtle);
    --_radius: var(--c-callout-radius, var(--c-radius-md));
    display: flex;
    gap: var(--c-spacing-sm);
    align-items: start;
    padding: var(--c-spacing-md);
    border: 1px solid transparent;
  }

  .callout__body {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .callout__title {
    font-weight: bold;
  }

  .callout__icon {
    width: auto;
    height: 1lh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }

  ::slotted(code) {
    font-size: 0.9em;
    display: inline-flex;
    padding: 0 var(--c-spacing-sm);
    border: 1px solid rgba(0, 0, 0, 0.2);
    background-color: rgba(0, 0, 0, 0.05);
    border-radius: var(--c-radius-sm);
  }

  :host([inline]) {
    display: inline-flex;
    padding-inline: var(--c-spacing-sm);
    padding-block: 0;
    line-height: 1.25rem;
    font-size: 0.9em;
  }

  :host([rounded~='all']) {
    border-radius: var(--_radius);
  }

  :host([rounded~='none']) {
    border-radius: 0;
  }

  :host([rounded~='start']) {
    border-start-start-radius: var(--_radius);
    border-start-end-radius: var(--_radius);
  }

  :host([rounded~='end']) {
    border-end-start-radius: var(--_radius);
    border-end-end-radius: var(--_radius);
  }

  :host([appearance~='accent']) {
    --c-fg-link: var(--c-color-on-emphasis);
    background-color: var(--c-color-bg-emphasis);
    color: var(--c-color-on-emphasis);
    border-color: var(--c-color-border-emphasis);
  }

  :host([appearance~='fill']) {
    --c-fg-link: var(--c-color-on-normal);
    border-color: transparent;
    background-color: var(--c-color-bg-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline-fill']) {
    --c-fg-link: var(--c-color-on-normal);
    border-color: var(--c-color-border-normal);
    background-color: var(--c-color-bg-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline']) {
    --c-fg-link: var(--c-color-on-subtle);
    border-color: var(--c-color-border-subtle);
    background-color: transparent;
    color: var(--c-color-on-subtle);
  }

  :host([appearance~='plain']) {
    --c-fg-link: var(--c-color-on-subtle);
    background-color: transparent;
    border-color: transparent;
    color: var(--c-color-on-subtle);
  }
`;
