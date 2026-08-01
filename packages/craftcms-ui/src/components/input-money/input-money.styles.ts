import {css} from 'lit';

export default css`
  [data-money-currency] {
    align-items: center;
    align-self: stretch;
    border: var(--c-input-border, 1px solid var(--c-form-control-border));
    border-inline-end: 0;
    border-radius: var(--c-input-radius, var(--c-radius-sm)) 0 0
      var(--c-input-radius, var(--c-radius-sm));
    display: flex;
    padding-inline: var(--c-input-spacing-inline);
    white-space: nowrap;
  }
`;
