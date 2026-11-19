import {css} from 'lit';

export default css`
  :host {
    --_thumbnail-size: var(--c-media-item-thumbnail-size, calc(40rem / 16));
    display: block;
    min-width: 0;
  }

  :host([hidden]) {
    display: none;
  }

  .media-item {
    display: flex;
    align-items: flex-start;
    gap: var(--c-spacing-md);
  }

  :host([truncate]) .media-item {
    align-items: center;
  }

  .media-item__thumbnail {
    --c-thumbnail-size: var(--_thumbnail-size);
    --c-thumb-icon-size: var(--_thumbnail-size);
    display: flex;
    flex: none;
    align-items: center;
    justify-content: center;
    inline-size: var(--_thumbnail-size);
    block-size: var(--_thumbnail-size);
    overflow: hidden;
    border-radius: var(--c-radius-sm);
  }

  ::slotted([slot='thumbnail']) {
    inline-size: 100%;
    block-size: 100%;
    object-fit: cover;
  }

  .media-item__thumbnail--icon {
    container-type: size;
    color: var(--c-color-neutral-fill-loud);
    background-color: var(--c-color-neutral-fill-quiet);

    craft-icon {
      --c-icon-size: 55cqmin;
      opacity: 0.3;
    }
  }

  .media-item__content {
    flex: 1;
    min-width: 0;
  }

  .media-item__body {
    margin-block-start: var(--c-spacing-xs);
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .media-item__heading:only-child,
  .media-item__body:only-child {
    margin-block-start: 0;
  }

  :host([truncate]) :is(.media-item__heading, .media-item__body),
  :host([truncate]) ::slotted([slot='heading']),
  :host([truncate]) .media-item__body ::slotted(*) {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .media-item__suffix {
    display: flex;
    flex: none;
    align-items: center;
  }
`;
