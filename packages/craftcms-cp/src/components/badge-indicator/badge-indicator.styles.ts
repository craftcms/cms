import {css} from 'lit';

export default css`
  .badge-indicator {
    display: inline-flex;
    width: var(--c-size-icon-xs);
    height: var(--c-size-icon-xs);
    justify-content: center;
    align-items: center;
    background-color: var(--c-color-accent-bg-emphasis);
    color: white;
    border-radius: var(--c-radius-full);
  }
  
  .badge-indicator--with-number {
    font-size: var(--c-text-xs);
    font-weight: 600;
    line-height: 1;
    height: calc(28rem / 16);
    width: calc(28rem / 16);
  }
`;
