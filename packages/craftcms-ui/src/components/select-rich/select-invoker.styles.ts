import {css} from 'lit';
import {baseComboboxStyles} from '@src/styles/form.styles';

export default css`
  :host {
    ${baseComboboxStyles}
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
    cursor: pointer;
    padding-block: 0;
    padding-inline: var(--c-input-spacing-inline);
    font: inherit;
    overflow: clip;
  }

  /* The usual focus ring, standing off the control, in place of LionButton's
     hard-coded one (flush against its edge). As specific as LionButton's
     :focus:not([disabled]) rule, so it wins on order. Contexts that inset
     rings, such as table cells, do so through the same properties. */
  :host(:focus-visible:not([disabled])) {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  /* Replace LionButton's hard-coded hover, press, and disabled fills. A
     select keeps its fill throughout, as a plain select does. */
  :host(:hover),
  :host(:active),
  :host([active]) {
    background-color: var(--c-input-fill, var(--c-form-control-fill));
  }

  :host([disabled]) {
    cursor: not-allowed;
    opacity: 0.5;
    background-color: var(--c-input-fill, var(--c-form-control-fill));
    color: inherit;
  }

  #content-wrapper {
    position: relative;
    pointer-events: none;
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
  }

  /* The value gives way to its badge: it's the part that's cut short. */
  .value {
    min-width: 0;
    /* Clipped for the ellipsis, but with room for the ring and shadow of
       content at its edges, such as a selected color's swatch. */
    overflow: clip;
    overflow-clip-margin: 2px;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  ::slotted([slot='badge']) {
    flex: none;
  }

  .indicator {
    flex: 0 0 auto;
    font-size: 0.8em;
  }
`;
