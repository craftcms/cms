import {css} from 'lit';

export default css`
  :host([variant='neutral']) {
    --c-color-static-fill: var(--c-color-static-neutral-fill);
    --c-color-static-on: var(--c-color-static-neutral-on);
    --c-color-static-border: var(--c-color-static-neutral-border);
  }

  :host([variant='danger']) {
    --c-color-static-fill: var(--c-color-static-danger-fill);
    --c-color-static-on: var(--c-color-static-danger-on);
    --c-color-static-border: var(--c-color-static-danger-border);
  }

  :host([variant='info']) {
    --c-color-static-fill: var(--c-color-static-info-fill);
    --c-color-static-on: var(--c-color-static-info-on);
    --c-color-static-border: var(--c-color-static-info-border);
  }

  :host([variant='warning']) {
    --c-color-static-fill: var(--c-color-static-warning-fill);
    --c-color-static-on: var(--c-color-static-warning-on);
    --c-color-static-border: var(--c-color-static-warning-border);
  }

  :host([variant='success']) {
    --c-color-static-fill: var(--c-color-static-success-fill);
    --c-color-static-on: var(--c-color-static-success-on);
    --c-color-static-border: var(--c-color-static-success-border);
  }
`;
