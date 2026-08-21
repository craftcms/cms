import {css} from 'lit';

export default css`
  :host {
    /*
      An element index needs room: a near-fullscreen surface rather than the
      confirmation-sized default craft-dialog ships with.
    */
    --_dialog-inline-size: var(--c-dialog-inline-size, min(96vw, 80rem));
    --_dialog-min-inline-size: var(
      --c-dialog-min-inline-size,
      min(96vw, 20rem)
    );
    --_dialog-max-inline-size: var(--c-dialog-max-inline-size, 96vw);
    --_dialog-max-block-size: var(--c-dialog-max-block-size, 90dvh);

    /*
      The index has its own internal scrolling regions (a sticky toolbar over a
      scrolling result list), so it needs a resolved height to fill rather than
      a body that grows to fit it.
    */
    --_dialog-block-size: var(--c-dialog-block-size, 90dvh);
  }

  .surface {
    block-size: var(--_dialog-block-size);
  }

  :host([fullscreen]) {
    --_dialog-block-size: var(--c-dialog-block-size, 100dvh);
  }

  /* The index supplies its own padding, and its sidebar runs edge to edge. */
  .body {
    padding: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .loading {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    min-block-size: 0;
  }

  .header {
    padding-block-end: 0;
  }

  /*
    Kept in the accessibility tree when the title is hidden: the <dialog> is
    labelled by it, so removing it would leave the dialog unnamed.
  */
  .title--hidden {
    position: absolute;
    inline-size: 1px;
    block-size: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
    border: 0;
  }

  :host([show-title]) .header {
    padding-block-end: var(--c-spacing-md);
  }

  .footer {
    /* Secondary actions left, primary right — as the legacy footer did. */
    justify-content: space-between;
    align-items: center;
    gap: var(--c-spacing-md);
    padding-block-start: var(--c-spacing-md);
    border-block-start: 1px solid var(--c-border-subtle, transparent);
  }

  .footer__group {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  /*
    While a selection is being saved, the index and the secondary actions stop
    responding. The interaction block itself is the inert attribute set in
    renderBody/renderFooter, which reaches slotted content this component does
    not own; these rules only supply the matching visual state.
  */
  :host([busy]) .body,
  :host([busy]) .footer__group--secondary {
    opacity: 0.6;
  }
`;
