import {css} from 'lit';

export default css`
  .badge-indicator {
    --badge-color: var(--c-color-accent-fill-loud);
    --text-color: white;
    --badge-size: calc(8rem / 16);
    display: inline-flex;
    min-width: var(--badge-size);
    min-height: var(--badge-size);
    justify-content: center;
    align-items: center;
    background-color: var(--badge-color);
    color: var(--text-color);
    border-radius: var(--c-radius-full);
    border: 2px solid var(--c-text-white);
  }

  .badge-indicator--secondary {
    --badge-color: var(--c-color-brand-fill-loud);
  }

  .badge-indicator--inverse {
    --badge-color: var(--c-color-neutral-fill-normal);
    --text-color: var(--c-text-default);
  }

  .badge-indicator--with-number {
    --badge-size: var(--c-size-icon-md);
    padding: calc(2rem / 16);
  }

  .number {
    display: inline-flex;
    font-size: var(--c-text-xs);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
  }
`;
