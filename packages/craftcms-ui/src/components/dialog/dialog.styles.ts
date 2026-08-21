import {css} from 'lit';

export default css`
  :host {
    display: contents;

    /*
      Sizing hooks. Declared on \`:host\` so an outer-tree rule or an inline
      style on the host still wins — shadow-tree \`:host\` rules always lose to
      the outer tree — and so a subclass can go near-fullscreen without
      reaching into this component's internals.
    */
    --_dialog-inline-size: var(--c-dialog-inline-size, auto);
    --_dialog-min-inline-size: var(
      --c-dialog-min-inline-size,
      min(90vw, 24rem)
    );
    --_dialog-max-inline-size: var(
      --c-dialog-max-inline-size,
      min(90vw, 40rem)
    );
    --_dialog-max-block-size: var(--c-dialog-max-block-size, 85dvh);
  }

  :host([fullscreen]) {
    --_dialog-inline-size: var(--c-dialog-inline-size, 100vw);
    --_dialog-min-inline-size: var(--c-dialog-min-inline-size, 100vw);
    --_dialog-max-inline-size: var(--c-dialog-max-inline-size, 100vw);
    --_dialog-max-block-size: var(--c-dialog-max-block-size, 100dvh);
  }

  dialog {
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    max-width: none;
    max-height: none;
    overflow: visible;
  }

  dialog::backdrop {
    background-color: rgb(0 0 0 / 0.25);
  }

  /*
    Non-modal dialogs get no \`::backdrop\` — that pseudo only paints for the
    top layer — so one is rendered instead. \`show()\` also leaves the dialog in
    normal flow, hence the fixed positioning to centre it.
  */
  .backdrop {
    position: fixed;
    inset: 0;
    background-color: rgb(0 0 0 / 0.25);
  }

  :host([non-modal]) dialog {
    position: fixed;
    inset: 0;
    margin: auto;
    z-index: 100;
  }

  .surface {
    display: grid;
    /* header / body / footer — the body is the only row that flexes. */
    grid-template-rows: auto 1fr auto;
    inline-size: var(--_dialog-inline-size);
    min-inline-size: var(--_dialog-min-inline-size);
    max-inline-size: var(--_dialog-max-inline-size);
    max-block-size: var(--_dialog-max-block-size);
    background-color: var(--c-surface-raised);
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-lg);
    overflow: hidden;
  }

  :host([fullscreen]) .surface {
    border-radius: 0;
  }

  .header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg);
    padding-block-start: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-md);
  }

  .title {
    font-size: 1.25em;
    margin: 0;
  }

  .close {
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
    padding: var(--c-spacing-xs);
    line-height: 1;
  }

  .body {
    /*
      Pairs with \`1fr\` above: without an explicit \`min-block-size: 0\` a grid
      item floors at its content height, so a long slotted index would grow the
      surface past \`--_dialog-max-block-size\` instead of scrolling inside it.
    */
    min-block-size: 0;
    overflow: auto;
    padding-inline: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-lg);
  }

  .footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-lg);
  }
`;
