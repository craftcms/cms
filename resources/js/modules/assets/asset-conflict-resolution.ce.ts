import {t} from '@craftcms/ui';
import {html, LitElement, nothing} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import type {ConflictResolution} from './assetMover';

/**
 * The Cancel, Keep both, and Replace choices for a filename conflict, shared by
 * the move-conflict dialog and upload notifications, which also offer them for
 * uploads that look like copies of other images. The buttons render as
 * direct children, so set layout classes on the element itself.
 *
 * @fires craft-conflict-resolve - A choice was made; `detail` is the `ConflictResolution`.
 */
@customElement('craft-asset-conflict-resolution')
export class AssetConflictResolutionElement extends LitElement {
  /** Whether to offer Replace, for when there's one file it would replace. */
  @property({type: Boolean})
  replaceable = true;

  protected override createRenderRoot(): HTMLElement {
    return this;
  }

  private resolve(choice: ConflictResolution): void {
    this.dispatchEvent(
      new CustomEvent<ConflictResolution>('craft-conflict-resolve', {
        detail: choice,
        bubbles: true,
        composed: true,
      })
    );
  }

  protected override render() {
    return html`
      <craft-button @click=${() => this.resolve('cancel')}
        >${t('Cancel')}</craft-button
      >
      <craft-button @click=${() => this.resolve('keepBoth')}
        >${t('Keep both')}</craft-button
      >
      ${this.replaceable
        ? html`<craft-button
            variant="danger"
            @click=${() => this.resolve('replace')}
            >${t('Replace')}</craft-button
          >`
        : nothing}
    `;
  }
}
