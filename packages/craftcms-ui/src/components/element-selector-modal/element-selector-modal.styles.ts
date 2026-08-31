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
      A floor, not a fixed height. The surface grows with the result list up to
      the max-block-size above and then scrolls inside; a short list gets a
      short dialog rather than a tall one full of empty space. The floor keeps
      a one-row result from collapsing into a sliver.
    */
    --_dialog-min-block-size: var(--c-dialog-min-block-size, 400px);
  }

  .surface {
    min-block-size: min(
      var(--_dialog-min-block-size),
      var(--_dialog-max-block-size)
    );
  }

  :host([fullscreen]) {
    --_dialog-min-block-size: var(--c-dialog-min-block-size, 100dvh);
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

  /*
    The close button is always present, so the header always has something in
    it. With the title hidden the heading is taken out of flow, leaving the
    button as the only flex item — and space-between would then push it to the
    left, so it is pinned to the end instead.
  */
  .header {
    justify-content: flex-end;
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
    justify-content: space-between;
    padding-block-end: var(--c-spacing-md);
  }

  /*
    The footer holds two groups. The base dialog packs its footer to the end, so
    an auto margin on the first group is what splits them: it absorbs the free
    space, holding secondary actions at the start and leaving the primary
    buttons flush right. It works the same when the secondary group is empty,
    which is the common case.
  */
  .footer__group {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .footer__group--secondary {
    margin-inline-end: auto;
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
