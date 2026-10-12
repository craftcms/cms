import {css} from 'lit';

export default css`
  :host {
    --_button-radius: var(--c-button-radius, var(--c-form-control-radius));
    display: flex;
    width: fit-content;
    border-radius: var(--_button-radius);
  }

  :host([data-focus-ring]) {
    outline: var(--c-focus-outline-width) solid var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  :host([data-focus-ring]) ::slotted(*) {
    --c-focus-outline-width: 0px;
  }

  ::slotted(*) {
    --c-button-radius-start-start: 0;
    --c-button-radius-start-end: 0;
    --c-button-radius-end-start: 0;
    --c-button-radius-end-end: 0;
  }

  ::slotted(:not([data-button-group-start])) {
    margin-inline-start: calc(var(--c-button-border-width, 1px) * -1);
  }

  ::slotted(:hover),
  ::slotted([active]),
  ::slotted([aria-pressed='true']) {
    z-index: 1;
  }

  ::slotted(:focus-visible) {
    z-index: 2;
  }

  ::slotted([data-button-group-start]) {
    --c-button-radius-start-start: var(--_button-radius);
    --c-button-radius-end-start: var(--_button-radius);
  }

  ::slotted([data-button-group-end]) {
    --c-button-radius-start-end: var(--_button-radius);
    --c-button-radius-end-end: var(--_button-radius);
  }

  :host([name]:not([multiple])) ::slotted([aria-pressed='true']) {
    cursor: default;
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
    border-color: var(--c-color-border-loud);
    box-shadow: none;
  }
`;
