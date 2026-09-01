import {css} from 'lit';

export default css`
  /*
   * The container's overflow: clip exists to keep prefix/suffix backgrounds
   * from bleeding past the rounded border. By mirroring the container's radius
   * on the suffix end-corners directly, we can drop the clip and let the
   * tooltip escape the container bounds.
   */
  .input-group__container {
    overflow: visible;
  }

  .input-group__suffix {
    border-start-end-radius: var(--c-input-radius, var(--c-radius-sm));
    border-end-end-radius: var(--c-input-radius, var(--c-radius-sm));
  }
`;
