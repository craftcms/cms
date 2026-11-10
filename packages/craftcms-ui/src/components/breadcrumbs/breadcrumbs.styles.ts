import {css} from 'lit';

export default css`
  /* Matches the space before each separator: a crumb's own gap and the
     separator's margin (see craft-breadcrumb-item). */
  :host {
    gap: var(--c-spacing-md);
  }

  .breadcrumbs {
    display: flex;
    align-items: center;
    /* The host isn't the flex container, so a gap set on it (e.g. a utility
       class) is handed down to the one that is. */
    gap: inherit;
  }
`;
