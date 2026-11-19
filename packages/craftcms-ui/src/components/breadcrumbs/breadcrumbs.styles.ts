import {css} from 'lit';

export default css`
  /* Matches the space before each separator: a crumb's own gap and the
     separator's margin (see craft-breadcrumb-item). */
  :host {
    display: block;
    min-width: 0;
    gap: var(--c-spacing-md);
  }

  [hidden] {
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
  ::slotted(*) {
    flex: none;
    order: 2;
  }

  /* The menu takes the place of the crumbs between the first and the rest. */
  ::slotted(:first-child) {
    order: 0;
  }

  .breadcrumbs__overflow {
    flex: none;
    order: 1;
  }

  /* Except the current page, which is never collapsed: when there's no room
     left for it, it truncates. Not while measuring, which needs every crumb's
     natural width. */
  .breadcrumbs:not(.breadcrumbs--measuring) ::slotted(:last-child) {
    flex: 0 1 auto;
    min-width: 0;
  }
`;
