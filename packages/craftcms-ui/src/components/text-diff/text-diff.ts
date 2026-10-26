import type {Change} from 'diff';
import {html, LitElement, nothing, type PropertyValues} from 'lit';
import {property, state} from 'lit/decorators.js';
import visuallyHiddenStyles from '@src/styles/visually-hidden.styles';
import {diffText, layoutDiff, type DiffPiece} from '@src/utilities/diff.js';
import {t} from '@src/utilities/translate.js';
import styles from './text-diff.styles.js';

import '../button/button.js';

/**
 * @summary Two versions of a text as one word-level diff: deleted text struck
 * through, and inserted text underlined. Each rewritten phrase shows as one
 * deletion and one insertion.
 *
 * With `collapse`, long unchanged stretches collapse to the text around each
 * change and each highlighted passage, with a button that shows all of it.
 *
 * @csspart text - The text.
 * @csspart toggle - The button that shows all of the text, or only the changes.
 */
export default class CraftTextDiff extends LitElement {
  static override styles = [visuallyHiddenStyles, styles];

  /** The text before the changes. When it's empty, `proposed` shows as it is. */
  @property() current = '';

  /** The text after the changes. */
  @property() proposed = '';

  /**
   * Passages of the `current` text to mark, such as ones a comment is about.
   * Each marks its first occurrence, and must match the text exactly.
   */
  @property({type: Array}) highlights: string[] = [];

  /**
   * Collapses long unchanged stretches, with a button that shows all of the
   * text.
   */
  @property({type: Boolean, reflect: true}) collapse = false;

  /**
   * What the text is, such as a field's name, which the button's accessible
   * name ends with, so several diffs' buttons can be told apart.
   */
  @property() label = '';

  /** Whether all of the text shows, rather than only the changes. */
  @state() private expanded = false;

  /** The diff, kept until the texts change, so showing all of it doesn't diff again. */
  private changes: Change[] = [];

  protected override willUpdate(changed: PropertyValues<this>) {
    if (changed.has('current') || changed.has('proposed')) {
      this.changes = diffText(this.current, this.proposed);
      this.expanded = false;
    }
  }

  protected override render() {
    const {pieces, collapsible} = layoutDiff(
      this.current,
      this.changes,
      this.highlights,
      !this.collapse || this.expanded
    );
    // On one line: the text keeps its whitespace, so the template's can't
    // show around it.
    const text = pieces.map((piece) => this.renderPiece(piece));

    return html`
      <p id="text" class="text" part="text">${text}</p>
      ${this.collapse && collapsible
        ? html`<craft-button
            part="toggle"
            type="button"
            variant="plain"
            size="small"
            flush="inline"
            aria-controls="text"
            aria-expanded=${this.expanded ? 'true' : 'false'}
            @click=${() => (this.expanded = !this.expanded)}
          >
            ${this.expanded
              ? t('Show changes only')
              : t('Show full text')}${this.label
              ? html`<span class="cp-visually-hidden"> ${this.label}</span>`
              : nothing}
          </craft-button>`
        : nothing}
    `;
  }

  private renderPiece(piece: DiffPiece) {
    const text = piece.parts.map((part) =>
      part.marked ? html`<mark>${part.text}</mark>` : html`${part.text}`
    );

    switch (piece.kind) {
      case 'gap':
        return html`<span class="gap"
          ><span aria-hidden="true">${text}</span
          ><span class="cp-visually-hidden">${t('Unchanged text')}</span></span
        >`;
      case 'delete':
        return html`<del
          ><span class="cp-visually-hidden">${t('Deleted:')} </span>${text}</del
        >`;
      case 'insert':
        return html`<ins
          ><span class="cp-visually-hidden">${t('Inserted:')} </span
          >${text}</ins
        >`;
      default:
        return text;
    }
  }
}

if (!customElements.get('craft-text-diff')) {
  customElements.define('craft-text-diff', CraftTextDiff);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-text-diff': CraftTextDiff;
  }
}
