import {css} from 'lit';

export default css`
  :host {
    --craft-tooltip-font-size: calc(12rem / 16);
    display: inline-block;
  }

  slot {
    display: inline-flex;
  }

  .copy-attribute {
    font-family: var(--font-mono);
    font-size: var(--c-copy-attribute-font-size, var(--c-text-sm));
    flex-wrap: nowrap;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
  }

  .copy-attribute::part(button) {
    --_border: var(
      --c-copy-attribute-border,
      1px solid hsla(209, 20%, 25%, 0.1)
    );
    border-radius: var(--c-copy-attribute-radius, 4px);
    background-color: var(--c-copy-attribute-bg, transparent);
    color: var(--c-copy-attribute-fg, inherit);
    border: var(--_border);
    padding-inline: 5px;
    min-height: calc(20rem / 16);
  }

  .copy-attribute::part(button):not(.copy-attribute--success):not(
      .copy-attribute--error
    ):hover,
  .copy-attribute::part(button):not(.copy-attribute--success):not(
      .copy-attribute--error
    ):focus {
    border-color: var(--c-border-subtle);
    color: var(--c-text-default);
  }

  .copy-attribute--success::part(button) {
    background-color: var(
      --c-copy-attribute-success-bg,
      var(--c-copy-attribute-bg)
    );
    color: var(--c-copy-attribute-success-fg, var(--c-copy-attribute-fg));
    border: var(--c-copy-attribute-success-border, var(--_border));
  }

  .copy-attribute--error::part(button) {
    background-color: var(
      --c-copy-attribute-error-bg,
      var(--c-copy-attribute-bg)
    );
    color: var(--c-copy-attribute-error-fg, var(--c-copy-attribute-fg));
    border: var(--c-copy-attribute-error-border, var(--_border));
  }

  .icon {
    display: inline-block;
    width: 0.9em;
    height: 0.9em;
  }

  svg {
    fill: currentColor;
    width: 100%;
    height: 100%;
  }
`;
