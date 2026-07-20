import {css} from 'lit';

/**
 * If you need to make an element hidden but still accessible to screen readers
 * import these styles into your web component and then use the `.cp-visually-hidden`
 */
export default css`
  .cp-visually-hidden:not(:focus-within) {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    border: none !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    padding: 0 !important;
  }
`;
