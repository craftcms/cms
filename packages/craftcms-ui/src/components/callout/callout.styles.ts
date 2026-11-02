import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  .callout {
    --_radius: var(--c-callout-radius, var(--c-radius-md));
    --_callout-padding-block: var(
      --c-callout-padding-block,
      var(--c-spacing-sm)
    );
    --_callout-padding-inline: var(
      --c-callout-padding-inline,
      var(--c-spacing-sm)
    );
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, max-content);
    align-items: start;
    padding-block: var(--_callout-padding-block);
    padding-inline: var(--_callout-padding-inline);
    border: 1px solid transparent;
    /* The display: contents host cannot take a grid span, so its rendered box does. */
    grid-column: 1 / -1;
  }

  .callout__body {
    display: flex;
    align-items: start;
    align-self: center;
    min-width: 0;
  }

  .callout__text {
    display: grid;
    flex: 1;
    min-width: 0;
  }

  .callout--hide-icon .callout__icon {
    display: none;
  }

  .callout--small {
    font-size: var(--c-text-sm);
    column-gap: var(--c-spacing-xs);

    .callout__body {
      column-gap: var(--c-spacing-xs);
    }
  }

  .callout__title,
  .callout__description {
    padding-inline: var(--c-callout-text-padding-inline, var(--c-spacing-sm));
    --c-link-decoration: underline;
    --c-link-decoration-hover: none;
  }

  .callout__title {
    display: flex;
    font-weight: bold;
  }

  .callout__action {
    margin-inline-start: auto;
  }

  .callout__icon {
    width: auto;
    height: 1lh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding-inline: var(--c-callout-icon-padding-inline, var(--c-spacing-sm));
  }

  ::slotted(code) {
    font-size: 0.9em;
    display: inline-flex;
    padding-block: 0;
    padding-inline: var(--c-spacing-sm);
    border: 1px solid rgba(0, 0, 0, 0.2);
    background-color: rgba(0, 0, 0, 0.05);
    border-radius: var(--c-radius-sm);
  }

  :host([inline]) {
    display: inline-flex;
    padding-inline: var(--c-spacing-sm);
    padding-block: 0;
    line-height: 1.25rem;
    font-size: 0.9em;
  }

  :host([rounded~='all']) .callout {
    border-radius: var(--_radius);
  }

  :host([rounded~='none']) .callout {
    border-radius: 0;
  }

  :host([rounded~='start']) .callout {
    border-start-start-radius: var(--_radius);
    border-start-end-radius: var(--_radius);
  }

  :host([rounded~='end']) .callout {
    border-end-start-radius: var(--_radius);
    border-end-end-radius: var(--_radius);
  }

  :host([appearance~='solid']) .callout {
    --c-text-link: var(--c-color-on-loud);
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-loud),
      var(--c-surface-default) var(--c-callout-fill-mute, 0%)
    );
    color: var(--c-color-on-loud);
    border-color: var(--c-color-border-loud);
  }

  :host([appearance~='fill']) .callout {
    --c-text-link: var(--c-color-on-normal);
    border-color: transparent;
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-normal),
      var(--c-surface-default) var(--c-callout-fill-mute, 0%)
    );
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline-fill']) .callout {
    --c-text-link: var(--c-color-on-normal);
    border-color: var(--c-color-border-normal);
    background-color: color-mix(
      in oklab,
      var(--c-color-fill-normal),
      var(--c-surface-default) var(--c-callout-fill-mute, 0%)
    );
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline']) .callout {
    --c-text-link: var(--c-color-on-quiet);
    border-color: var(--c-color-border-quiet);
    background-color: transparent;
    color: var(--c-color-on-quiet);
  }

  :host([appearance~='plain']) .callout {
    --c-text-link: var(--c-color-on-quiet);
    background-color: transparent;
    border-color: transparent;
    color: var(--c-color-on-quiet);
  }
`;
