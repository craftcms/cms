import {css} from 'lit';

export default css`
  :host {
    --_button-radius: var(--c-button-radius, var(--c-form-control-radius));
    display: flex;
  }

  ::slotted(*) {
    --c-button-radius-start-start: 0;
    --c-button-radius-start-end: 0;
    --c-button-radius-end-start: 0;
    --c-button-radius-end-end: 0;
  }

  ::slotted(:not([data-button-group-start])) {
    --c-button-group-border-start-color: var(--c-color-neutral-border-quiet);
    --c-button-group-overlap: calc(var(--c-button-border-width, 1px) * -1);
    margin-inline-start: var(--c-button-group-overlap);
  }

  ::slotted(:not([data-button-group-end])) {
    --c-button-group-border-end-color: var(--c-color-neutral-border-quiet);
  }

  ::slotted([data-button-group-divider]) {
    --c-button-divider-start-color: color-mix(
      in srgb,
      currentColor 25%,
      transparent
    );
  }

  ::slotted([data-button-group-divider-end]) {
    --c-button-divider-end-color: color-mix(
      in srgb,
      currentColor 25%,
      transparent
    );
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

  /* Picking the selected option again does nothing. An owner may manage the
     selection itself, so that's any pressed button but an independent toggle. */
  :host(:not([multiple])) ::slotted([aria-pressed='true']:not([toggle])) {
    cursor: default;
  }

  :host([name]:not([multiple]):not([subtle])) ::slotted([aria-pressed='true']) {
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
    border-color: var(--c-color-border-loud);
    box-shadow: none;
  }

  /* Buttons sit on a quiet track instead of sharing borders. */
  :host([subtle]) {
    --_track-padding: var(--c-spacing-sm);
    gap: var(--_track-padding);
    padding: var(--_track-padding);
    border-radius: calc(var(--_button-radius) + var(--_track-padding));
    background-color: var(--c-color-neutral-fill-quiet);
  }

  :host([subtle]) ::slotted(*) {
    --c-button-radius-start-start: var(--_button-radius);
    --c-button-radius-start-end: var(--c-button-radius-start-start);
    --c-button-radius-end-start: var(--c-button-radius-start-start);
    --c-button-radius-end-end: var(--c-button-radius-start-start);
    --c-button-group-border-start-color: transparent;
    --c-button-group-border-end-color: transparent;
    --c-button-divider-start-color: transparent;
    --c-button-divider-end-color: transparent;
    margin-inline-start: 0;
    border-color: transparent;
    background-color: transparent;
    color: var(--c-color-neutral-on-quiet);
    box-shadow: none;
  }

  @media (hover: hover) {
    :host([subtle]) ::slotted(:hover:not([aria-pressed='true'])) {
      background-color: color-mix(
        in srgb,
        var(--c-surface-raised) 60%,
        transparent
      );
      color: var(--c-color-neutral-on-normal);
    }
  }

  /* The border carries the selection, so it keeps 3:1 against the track. */
  :host([subtle]) ::slotted([aria-pressed='true']) {
    border-color: var(--color-slate-500);
    background-color: var(--c-surface-raised);
    color: var(--c-color-neutral-on-normal);
    box-shadow: var(--c-shadow-xs);
  }
`;
