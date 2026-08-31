import {css} from 'lit';

export default css`
  :host {
    /* Overall size of the thumbnail box. */
    --c-thumbnail-size: calc(30rem / 16);
    /* Corner radius applied when [rounded] is set. Defaults to a full circle. */
    --c-thumbnail-radius: var(--c-radius-full);
    /* Size of a single checker square. */
    --c-thumbnail-checker-size: 8px;
    /* Color of the checker squares. Matches the Craft 5 \`.thumb.checkered\` pattern. */
    --c-thumbnail-checker-color: hsl(211 13% 65% / 0.25);

    --_checker-half: calc(var(--c-thumbnail-checker-size) / 2);

    display: contents;
  }

  .thumbnail {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--c-thumbnail-size);
    height: var(--c-thumbnail-size);
    overflow: clip;
    max-width: 100%;
    max-height: 100%;
  }

  .thumbnail__image,
  ::slotted(img),
  ::slotted(svg) {
    display: block;
    flex-shrink: 0;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }

  /* h/t https://gist.github.com/dfrankland/f6fed3e3ccc42e3de482b324126f9542 */
  .thumbnail--checkered {
    background-image:
      linear-gradient(
        45deg,
        var(--c-thumbnail-checker-color) 25%,
        transparent 25%
      ),
      linear-gradient(
        135deg,
        var(--c-thumbnail-checker-color) 25%,
        transparent 25%
      ),
      linear-gradient(
        45deg,
        transparent 75%,
        var(--c-thumbnail-checker-color) 75%
      ),
      linear-gradient(
        135deg,
        transparent 75%,
        var(--c-thumbnail-checker-color) 75%
      );
    background-size: var(--c-thumbnail-checker-size)
      var(--c-thumbnail-checker-size);
    background-position:
      0 0,
      var(--_checker-half) 0,
      var(--_checker-half) calc(-1 * var(--_checker-half)),
      0 var(--_checker-half);
  }

  .thumbnail--rounded .thumbnail__image,
  .thumbnail--rounded ::slotted(img),
  .thumbnail--rounded ::slotted(svg) {
    border-radius: var(--c-thumbnail-radius);
  }
`;
