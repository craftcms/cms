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
    z-index: 1;
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
    z-index: 2;
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
    /*
      This element is the window the strip slides behind, and the positioning
      maths centres the strip on its width — so it has to be the width of the
      rule, not of the strip inside it. Without the clip it sizes to its
      content, and the strip is centred on a box wider than the one the cursor
      sits in the middle of: about 20 degrees out for the default range.

      The legacy markup got this from a wrapper around the component --
      .straightening, with a max-width and its own clip -- so the component
      owns it now rather than depending on where it is placed.
    */
    overflow: hidden;
  }

  .graduations ul {
    position: relative;
    float: inline-start;
    height: 40px;
    margin: 0;
    padding: 0;
    list-style: none;
    /*
      Flex, so the graduations sit flush against each other. As inline-blocks
      the newlines between them in the template each rendered as a space,
      making the strip ~3.8px per graduation wider than the 10px apiece the
      positioning maths assumes -- which put zero nowhere near the cursor.
    */
    display: flex;

    /* "left" (not a logical property) because that's what the JS sets. */
    transition: 200ms left linear; /* stylelint-disable-line */
  }

  .slide-rule.dragging .graduations ul {
    transition: none;
  }

  .graduations ul li {
    /* Never shrink: the strip has to keep 10px a graduation, whatever the
       width of the window it slides behind. */
    flex: none;
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
