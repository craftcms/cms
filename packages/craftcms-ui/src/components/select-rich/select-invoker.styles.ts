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

  :host([disabled]) {
    cursor: not-allowed;
    opacity: 0.5;
  }

  #content-wrapper {
    position: relative;
    pointer-events: none;
    flex: 1 1 auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .indicator {
    flex: 0 0 auto;
    font-size: 0.8em;
  }
`;
