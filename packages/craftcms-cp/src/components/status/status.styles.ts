import {css} from 'lit';

export default css`
  :host {
    display: inline-flex;
    --size: var(--c-size-icon-xs);
  }

  .status {
    display: inline-flex;
    width: var(--size);
    aspect-ratio: 1;
    border-radius: var(--c-radius-full);
    border: 1px solid transparent;
  }

  .status--live {
    background-color: var(--c-status-live-bg);
    border-color: var(--c-status-live-border);
  }

  .status--enabled {
    background-color: var(--c-status-enabled-bg);
    border-color: var(--c-status-enabled-border);
  }

  .status--pending {
    background-color: var(--c-status-pending-bg);
    border-color: var(--c-status-pending-border);
  }

  .status--expired {
    background-color: var(--c-status-expired-bg);
    border-color: var(--c-status-expired-border);
  }

  .status--disabled {
    background-color: var(--c-status-disabled-bg);
    border: 1px solid var(--c-status-disabled-border);
  }
`;
