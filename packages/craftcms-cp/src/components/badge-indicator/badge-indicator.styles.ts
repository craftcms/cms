import {css} from 'lit';

export default css`
  .badge-indicator {
    --badge-color: var(--c-color-accent-bg-emphasis);
    --badge-size: var(--c-size-icon-xs);
    display: inline-flex;
    min-width: var(--badge-size);
    min-height: var(--badge-size);
    justify-content: center;
    align-items: center;
    background-color: var(--badge-color);
    color: white;
    border-radius: var(--c-radius-full);
    
    &.secondary {
      --badge-color: var(--c-color-brand-bg-emphasis);
    }
  }
  
  .badge-indicator--with-number {
    --badge-size: var(--c-size-icon-md);
    padding: calc(6rem / 16);
  }
  
  .number {
    display: inline-flex;
    font-size: var(--c-text-xs);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
  }
`;
