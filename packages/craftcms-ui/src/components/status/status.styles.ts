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
    --_ring: var(--c-status-ring, var(--_border));
    position: relative;
    display: inline-flex;
    width: var(--size);
    aspect-ratio: 1;
    border-radius: var(--c-radius-full);
    border: 1px solid var(--_ring);
    background-color: var(--_fill);
  }

  .status--live {
    --_fill: var(--c-status-live-fill);
    --_border: var(--c-status-live-border);
  }

  .status--enabled {
    --_fill: var(--c-status-enabled-fill);
    --_border: var(--c-status-enabled-border);
  }

  .status--pending {
    --_fill: var(--c-status-pending-fill);
    --_border: var(--c-status-pending-border);
  }

  .status--expired {
    --_fill: var(--c-status-expired-fill);
    --_border: var(--c-status-expired-border);
  }

  .status--disabled {
    --_fill: var(--c-status-disabled-fill);
    --_border: var(--c-status-disabled-border);
  }

  .status--square,
  .status--triangle {
    border-radius: var(--c-status-shape-radius, var(--c-radius-full));
  }

  /* A clip-path clips the border with the box, so the triangle would keep its
     ring only along its base. A layer above it paints the ring as a band
     along all three edges instead. The band only exists with the use-shapes
     preference on; otherwise the dot is a circle with its border, like the
     other states. */
  .status--triangle {
    clip-path: var(--c-status-triangle-clip, none);
  }

  .status--triangle::before {
    content: '';
    position: absolute;
    inset: -1px;
    background-color: var(--_ring);
    clip-path: var(--c-status-triangle-ring-clip, inset(50%));
  }

  /* Forced colors would replace the band's background with the page's, so the
     triangle would lose its outline. Every state draws as an outline
     instead; its name carries the state. */
  @media (forced-colors: active) {
    .status {
      forced-color-adjust: none;
      border-color: CanvasText;
      background-color: Canvas;
    }

    .status--triangle::before {
      background-color: CanvasText;
    }
  }
`;
