import {css} from 'lit';

export default css`
  :host {
    display: block;
    /* Allow the element to shrink below its content size in flex/grid layouts
       so the text actually truncates instead of forcing the container wider. */
    min-width: 0;
  }

  .truncate {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
`;
