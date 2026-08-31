import {css} from 'lit';

// Ported from the legacy packages/craftcms-legacy/cp/src/css/_image_editor.scss
// `.slide-rule` block into the component's shadow root. `--focus-ring` / `--white`
// inherit from the page; the accent color is the legacy hard-coded value.
export default css`
  :host {
    display: block;
  }

  .slide-rule {
    position: relative;
    padding-block: 10px;
    padding-inline: 0;
    outline: none;
  }

  .slide-rule:focus-visible .cursor::after {
    box-shadow: var(--focus-ring);
  }

  .cursor {
    position: absolute;
    margin-inline-start: calc(-4 / 16 * 1rem);
    margin-block-start: 4px;
    inset-inline-start: 50%;
    z-index: var(--c-z-raised, 1);
    width: 0;
    height: 0;
    border-inline-start: calc(5 / 16 * 1rem) solid transparent;
    border-inline-end: calc(5 / 16 * 1rem) solid transparent;
    border-block-start: calc(5 / 16 * 1rem) solid #63a6e1;
  }

  .cursor::after {
    content: '';
    width: calc(20 / 16 * 1rem);
    height: calc(50 / 16 * 1rem);
    position: absolute;
    inset-block-start: -15px;
    inset-inline-start: 50%;
    transform: translateX(-50%);
  }

  .overlay {
    z-index: var(--c-z-floating, 2);
    position: absolute;
    inset-block: 0 1px;
    inset-inline: 0;
    pointer-events: none;
    background-image: linear-gradient(
      to right,
      var(--gray-900) 0%,
      transparent 15%,
      transparent 85%,
      var(--gray-900) 100%
    );
  }

  .graduations {
    white-space: nowrap;
    height: 40px;
    position: relative;
  }

  .graduations ul {
    position: relative;
    float: inline-start;
    height: 40px;
    margin: 0;
    padding: 0;
    list-style: none;

    /* "left" (not a logical property) because that's what the JS sets. */
    transition: 200ms left linear; /* stylelint-disable-line */
  }

  .slide-rule.dragging .graduations ul {
    transition: none;
  }

  .graduations ul li {
    display: inline-block;
    font-size: 8px;
    position: relative;
    width: 10px;
  }

  .graduations ul li:hover {
    cursor: pointer;
  }

  .graduations ul li:hover::before {
    border-inline-start-color: #63a6e1;
  }

  .graduations ul li:not(.main-graduation) {
    inset-inline-start: 1px;
  }

  .graduations ul li.main-graduation::before {
    border-inline-start-width: 4px;
    height: 10px;
  }

  .graduations ul li .label {
    width: 20px;
    position: absolute;
    inset-block-start: 10px;
    inset-inline-start: -9px;
    display: none;
    text-align: center;
  }

  .graduations ul li.main-graduation .label {
    display: block;
    cursor: default;
  }

  .graduations ul li.selected::before {
    border-inline-start-color: #63a6e1;
  }

  .graduations ul li::before {
    content: '';
    position: absolute;
    border-inline-start: 2px solid var(--white);
    height: 6px;
    inset-block-start: 0;
    inset-inline-start: 0;
  }
`;
