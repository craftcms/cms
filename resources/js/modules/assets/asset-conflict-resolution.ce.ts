import {t} from '@craftcms/ui';
import {html, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import type {ConflictResolution} from './assetMover';

/**
 * The Cancel, Keep both, and Replace choices for a filename conflict, shared by
 * the move-conflict dialog and upload notifications, and by the comparison of
 * an upload with images it looks like a copy of. The buttons render as direct
 * children, so set layout classes on the element itself.
 *
 * @fires craft-conflict-resolve - A choice was made; `detail` is the `ConflictResolution`.
 */
@customElement('craft-asset-conflict-resolution')
export class AssetConflictResolutionElement extends LitElement {
  /**
   * Whether the conflict is with images an upload looks like a copy of, rather
   * than a file with the same name, which offers Keep it instead of Keep both.
   */
  @property({type: Boolean})
  duplicate = false;

  /**
   * Whether the choices are for an upload that's held until one is made, so
   * Cancel discards it. It reads Discard upload instead, after Keep both.
   */
  @property({type: Boolean})
  discards = false;

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
      >${this.duplicate ? t('Keep it') : t('Keep both')}</craft-button
    >`;
    const replace = html`<craft-button
      variant="danger"
      @click=${() => this.resolve('replace')}
      >${t('Replace')}</craft-button
    >`;

    return this.discards
      ? html`${keep}${cancel}${replace}`
      : html`${cancel}${keep}${replace}`;
  }
}
