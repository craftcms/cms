import {LionSelectedFileList} from '@lion/ui/input-file.js';
import {css, html} from 'lit';
import '../icon/icon.js';
import '../button/button.js';
import type {InputFile} from '@lion/ui/types/input-file.js';

export default class CraftSelectedFileList extends LionSelectedFileList {
  static override get styles() {
    return [
      ...super.styles,
      css`
        ul {
          display: flex;
          flex-direction: column;
          gap: var(--c-spacing-sm);
        }

        li {
          display: flex;
          align-items: center;
          gap: var(--c-spacing-sm);
          padding: var(--c-spacing-sm);
          border: 1px solid var(--c-color-neutral-border-subtle);
          border-radius: var(--c-radius-sm);
          background-color: var(--c-bg-surface);
        }

        .file-name {
          flex: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }

        .remove-button {
          flex-shrink: 0;
        }

        .preview-thumb {
          width: var(--thumbnail-size, calc(120rem / 16));
          height: auto;
        }

        .selected__list__item__label {
          display: flex;
          align-items: center;
          gap: var(--c-spacing-sm);
        }

        .selected__list__item__remove-button {
          margin-inline-start: var(--c-spacing-md);
        }
      `,
    ];
  }

  // eslint-disable-next-line no-unused-vars
  override _listItemAfterTemplate(file: InputFile, fileUuid: string) {
    return html`
      <craft-button
        icon
        size="small"
        variant="plain"
        class="selected__list__item__remove-button"
        aria-label="${this.msgLit('lion-input-file:removeButtonLabel', {
          fileName: file.systemFile.name,
        })}"
        @click=${() => this._removeFile(file)}
      >
        ${this._removeButtonContentTemplate()}
      </craft-bbutton>
    `;
  }

  override _removeButtonContentTemplate() {
    return html`<craft-icon name="x"></craft-icon>`;
  }

  override _listItemBeforeTemplate(file: InputFile) {
    return html`<img src="${file.downloadUrl}" alt="" class="preview-thumb" />`;
  }
}
