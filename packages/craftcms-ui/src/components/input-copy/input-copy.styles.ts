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
    overflow: visible;
    padding-inline: 0;
    border-color: transparent;
    border-start-end-radius: var(--c-input-radius, var(--c-radius-sm));
    border-end-end-radius: var(--c-input-radius, var(--c-radius-sm));
    background-color: var(--c-color-neutral-fill-normal);
    color: var(--c-color-neutral-on-normal);
  }

  .input-group__suffix:hover {
    background-color: hsl(
      from var(--c-color-neutral-fill-normal) h s calc(l - 5)
    );
  }

  ::slotted(craft-copy-button) {
    --c-focus-outline-offset: calc(var(--c-focus-outline-width) * -1);
    align-self: stretch;
    justify-self: stretch;
    min-inline-size: var(--_control-height);
    margin-block: calc(var(--c-input-border-width) * -1);
    margin-inline-end: calc(var(--c-input-border-width) * -1);
    border-start-end-radius: var(--c-input-radius, var(--c-radius-sm));
    border-end-end-radius: var(--c-input-radius, var(--c-radius-sm));
  }
`;
