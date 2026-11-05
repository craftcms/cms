import {css} from 'lit';

export default css`
  :host {
    --c-avatar-color-start: red;
    --c-avatar-color-end: blue;

    --size: calc(30rem / 16);
    display: contents;
  }

  .avatar {
    display: inline-flex;
    width: var(--size);
    aspect-ratio: 1;
    color: var(--c-avatar-color-text, var(--c-text-default));
    background-color: var(--c-surface-raised);
    border-radius: var(--c-radius-full);
  }

  .avatar__text {
    line-height: 1;
    font-weight: 500;
    font-family: var(--c-font-body, sans-serif);
    text-anchor: middle;
    fill: currentColor;
    user-select: none;
    pointer-events: none;
  }
`;
