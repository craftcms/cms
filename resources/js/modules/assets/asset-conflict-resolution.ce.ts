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
  /**
   * Whether there's more than one file to keep alongside, such as images an
   * upload looks like a copy of, which offers Keep all instead of Keep both.
   */
  @property({type: Boolean})
  multiple = false;

  /** Whether Replace is left out, such as when there's no one file it would replace. */
  @property({type: Boolean, attribute: 'hide-replace'})
  hideReplace = false;

  /**
   * Whether the choices are for an upload that's held until one is made, so
   * Cancel discards it. It reads Discard upload instead, after Keep both and
   * `extraButtons`.
   */
  @property({type: Boolean})
  discards = false;

  /** More buttons, placed after Keep both, when `discards` is set. */
  @property({attribute: false})
  extraButtons: unknown = nothing;

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
    const cancel = html`<craft-button @click=${() => this.resolve('cancel')}
      >${this.discards ? t('Discard upload') : t('Cancel')}</craft-button
    >`;
    const keep = html`<craft-button @click=${() => this.resolve('keepBoth')}
      >${this.multiple ? t('Keep all') : t('Keep both')}</craft-button
    >`;
    const replace = this.hideReplace
      ? nothing
      : html`<craft-button
          variant="danger"
          @click=${() => this.resolve('replace')}
          >${t('Replace')}</craft-button
        >`;

    return this.discards
      ? html`${keep}${this.extraButtons}${cancel}${replace}`
      : html`${cancel}${keep}${replace}`;
  }
}
