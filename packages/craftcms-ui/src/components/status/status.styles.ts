import {css} from 'lit';

export default css`
  :host {
    display: inline-flex;
    --size: var(--c-size-icon-xs);
  }

  /* The fill reaches 3:1 on every surface but a selected card's header, whose
     fill can be any mid-tone. That header sets --c-status-ring to its text
     color, so the ring reaches 3:1 there instead. */
  .status {
    display: inline-flex;
    width: var(--size);
    aspect-ratio: 1;
    border-radius: var(--c-radius-full);
    border: 1px solid transparent;
  }

  .status--live {
    background-color: var(--c-status-live-fill);
    border-color: var(--c-status-ring, var(--c-status-live-border));
  }

  .status--enabled {
    background-color: var(--c-status-enabled-fill);
    border-color: var(--c-status-ring, var(--c-status-enabled-border));
  }

  .status--pending {
    background-color: var(--c-status-pending-fill);
    border-color: var(--c-status-ring, var(--c-status-pending-border));
  }

  .status--expired {
    background-color: var(--c-status-expired-fill);
    border-color: var(--c-status-ring, var(--c-status-expired-border));
  }

  .status--disabled {
    background-color: var(--c-status-disabled-fill);
    border: 1px solid var(--c-status-ring, var(--c-status-disabled-border));
  }

  .status--square,
  .status--triangle {
    border-radius: var(--c-status-shape-radius, var(--c-radius-full));
  }

  .status--triangle {
    clip-path: var(--c-status-triangle-clip, none);
  }
`;
