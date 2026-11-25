import {css} from 'lit';

export default css`
  /* Matches the space before each separator: a crumb's own gap and the
     separator's margin (see craft-breadcrumb-item). */
  :host {
    display: block;
    min-width: 0;
    gap: var(--c-spacing-md);
    /* The width of every crumb, set when the trail is measured, up to the
       container's. Sized by its content alone, a collapsed trail would keep
       its collapsed width in a container sized by its content, and never be
       given the room to bring its crumbs back. */
    inline-size: var(--_natural-inline-size, auto);
    max-inline-size: 100%;
  }

  /* Crumbs too, collapsed here or hidden by their author: important, so a
     page's own \`display\` for them can't show them again. */
  [hidden],
  ::slotted([hidden]) {
    display: none !important;
  }

  .breadcrumbs {
    display: flex;
    align-items: center;
    min-width: 0;
    /* The host isn't the flex container, so a gap set on it (e.g. a utility
       class) is handed down to the one that is. */
    gap: inherit;
  }

  /* Crumbs hold their natural width: the trail makes room by collapsing whole
     crumbs into the menu, not by squeezing them. */
  ::slotted(*),
  .breadcrumbs__overflow {
    flex: none;
  }

  /* Except the current page, which is never collapsed: when there's no room
     left for it, it truncates. Not while measuring, which needs every crumb's
     natural width. */
  .breadcrumbs:not(.breadcrumbs--measuring) ::slotted([aria-current='page']) {
    flex: 0 1 auto;
    min-width: 0;
  }
`;
