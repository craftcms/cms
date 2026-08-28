import {css} from 'lit';

export default css`
  :host {
    display: contents;
  }

  .callout {
    --_radius: var(--c-callout-radius, var(--c-radius-md));
    /*
      The two padding axes are declared separately because the callout's
      default is asymmetric — a tight block edge, a roomier inline one. The
      \`padding\` attribute writes both of these on this element when it's set,
      so these fallbacks are what a callout with no \`padding\` renders with.
    */
    --_callout-padding-block: var(
      --c-callout-padding-block,
      var(--c-spacing-sm)
    );
    --_callout-padding-inline: var(
      --c-callout-padding-inline,
      var(--c-spacing-sm)
    );
    display: grid;
    grid-template-areas: 'icon description action';
    grid-template-columns: auto 1fr minmax(0, max-content);
    align-items: start;
    padding: var(--_callout-padding-block) var(--_callout-padding-inline);
    border: 1px solid transparent;
  }

  .callout--title {
    grid-template-areas: 'icon title action' 'icon description action';
  }

  .callout--hide-icon {
    grid-template-areas: 'description action';
    grid-template-columns: 1fr minmax(0, max-content);

    .callout__icon {
      display: none;
    }
  }

  .callout--hide-icon.callout--title {
    grid-template-areas: 'title action' 'description action';
  }

  .callout--small {
    font-size: var(--c-text-sm);
    gap: 0 var(--c-spacing-xs);
  }
  
  .callout__title,
  .callout__description {
    padding-inline: var(--c-spacing-sm);
  }

  .callout__title {
    display: flex;
    font-weight: bold;
    grid-area: title;
  }

  .callout__description {
    grid-area: description;
    align-self: center;
    padding-inline: var(--c-spacing-sm);
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
    padding-inline: var(--c-spacing-sm);
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
    background-color: var(--c-color-fill-loud);
    color: var(--c-color-on-loud);
    border-color: var(--c-color-border-loud);
  }

  :host([appearance~='fill']) .callout {
    --c-text-link: var(--c-color-on-normal);
    border-color: transparent;
    background-color: var(--c-color-fill-normal);
    color: var(--c-color-on-normal);
  }

  :host([appearance~='outline-fill']) .callout {
    --c-text-link: var(--c-color-on-normal);
    border-color: var(--c-color-border-normal);
    background-color: var(--c-color-fill-normal);
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
