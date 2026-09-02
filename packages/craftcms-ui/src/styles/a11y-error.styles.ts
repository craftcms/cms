import {css} from 'lit';

/**
 * Marks a component that has been used in a way assistive technology cannot
 * present — a button with no accessible name, a control nested inside another
 * control.
 *
 * Deliberately loud. These are mistakes to fix rather than states to handle,
 * and the ones that matter are invisible to a sighted developer testing with a
 * mouse, so the component says so where it went wrong.
 */
export default css`
  .a11y-error {
    position: relative;
    outline: 2px solid var(--c-color-danger-border-normal) !important;
    background-color: rgba(255, 0, 0, 0.1) !important;

    &:after {
      content: '!';
      position: absolute;
      display: inline-flex;
      font-size: calc(11rem / 16);
      padding: 0.125em 0.5em 0.25em;
      inset-block-start: -2px;
      inset-inline-start: 0;
      background: var(--c-color-danger-bg-emphasis);
      color: white;
      transform: translateX(-100%);
    }
  }
`;
