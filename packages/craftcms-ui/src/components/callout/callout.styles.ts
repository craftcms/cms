import { css } from "lit";

export default css`
  :host {
    display: contents;
  }

  .callout {
    --_radius: var(--c-callout-radius, var(--c-radius-md));
    display: grid;
    grid-template-areas: "icon title action" "icon description action";
    grid-template-columns: minmax(0, 1rem) 1fr minmax(0, max-content);
    gap: 0 var(--c-spacing-sm);
    align-items: start;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    border: 1px solid transparent;
  }

  .callout--hide-icon {
    grid-template-areas: "title action" "description action";
    grid-template-columns: 1fr minmax(0, max-content);

    .callout__icon {
      display: none;
    }
  }

  .callout__title {
    display: flex;
    font-weight: bold;
    grid-area: title;
  }

  .callout__description {
    grid-area: description;
  }

  .callout__action {
    grid-area: action;
    margin-inline-start: auto;
  }

  .callout__icon {
    width: auto;
    height: 1lh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    grid-area: icon;
  }

  ::slotted(code) {
    font-size: 0.9em;
    display: inline-flex;
    padding: 0 var(--c-spacing-sm);
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

  :host([rounded~="all"]) .callout {
    border-radius: var(--_radius);
  }

  :host([rounded~="none"]) .callout {
    border-radius: 0;
  }

  :host([rounded~="start"]) .callout {
    border-start-start-radius: var(--_radius);
    border-start-end-radius: var(--_radius);
  }

  :host([rounded~="end"]) .callout {
    border-end-start-radius: var(--_radius);
    border-end-end-radius: var(--_radius);
  }

  :host([appearance~="solid"]) .callout {
    --c-text-link: var(--c-color-on-loud);
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
    border-color: var(--c-color-border-loud);
  }

  :host([appearance~="fill"]) .callout {
    --c-text-link: var(--c-color-on-normal);
    border-color: transparent;
    background-color: var(--c-color-fill-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~="outline-fill"]) .callout {
    --c-text-link: var(--c-color-on-normal);
    border-color: var(--c-color-border-normal);
    background-color: var(--c-color-fill-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~="outline"]) .callout {
    --c-text-link: var(--c-color-on-quiet);
    border-color: var(--c-color-border-quiet);
    background-color: transparent;
    color: var(--c-color-on-quiet);
  }

  :host([appearance~="plain"]) .callout {
    --c-text-link: var(--c-color-on-quiet);
    background-color: transparent;
    border-color: transparent;
    color: var(--c-color-on-quiet);
  }
`;
