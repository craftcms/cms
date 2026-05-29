import OverType, {
  type Options,
  type OverType as OverTypeInstance,
} from 'overtype';
import {t} from '@craftcms/cp';
import {LitElement} from 'lit';
import {customElement, property} from 'lit/decorators.js';
import {createAssetController, type AssetController} from './behaviors/assets';
import {registerLinkPasteBehavior} from './behaviors/link-paste';
import {
  createLinkPopoverController,
  type LinkPopoverController,
} from './behaviors/link-popover';
import {
  createPreviewController,
  type PreviewController,
} from './behaviors/preview';
import {registerShortcutBehavior} from './behaviors/shortcuts';
import {themeOptions} from './behaviors/theme';
import {toolbarItems} from './behaviors/toolbar';
import {fileUploadOptions} from './behaviors/uploads';
import markdownIcon from '@icons/brands/markdown.svg?raw';
import './MarkdownField.css';

@customElement('craft-markdown-field')
class MarkdownField extends LitElement {
  private assetController: AssetController | null = null;
  private cleanups: Array<() => void> = [];
  private editor: OverTypeInstance | null = null;
  private linkPopoverController: LinkPopoverController | null = null;
  private previewController: PreviewController | null = null;
  private resolvedInputId: string | null = null;

  @property({attribute: 'asset-any-uploader', type: Boolean})
  assetAnyUploader = false;

  @property({attribute: 'asset-sources', type: Array})
  assetSources: string[] = [];

  @property({attribute: 'described-by'})
  describedBy: string | null = null;

  @property({type: Boolean})
  disabled = false;

  @property()
  flavor = 'gfm';

  @property({type: Boolean})
  encode = false;

  @property({attribute: 'inline-only', type: Boolean})
  inlineOnly = false;

  @property({attribute: 'link-advanced-fields', type: Array})
  linkAdvancedFields: string[] = [];

  @property({attribute: 'link-types', type: Array})
  linkTypes: unknown[] = [];

  @property({attribute: 'max-length', type: Number})
  maxLength: number | null = null;

  @property()
  name: string | null = null;

  @property()
  placeholder: string | null = null;

  @property({attribute: 'preview-delay', type: Number})
  previewDelay = 250;

  @property({type: Number})
  rows = 8;

  @property({attribute: 'show-stats', type: Boolean})
  showStats = false;

  @property({attribute: 'show-link-label-field', type: Boolean})
  showLinkLabelField = false;

  @property({attribute: 'toolbar-buttons', type: Array})
  toolbarButtons: string[] = [];

  @property({attribute: 'show-toolbar', type: Boolean})
  showToolbar = false;

  @property({attribute: 'upload-folder-id', type: Number})
  uploadFolderId: number | null = null;

  @property({attribute: 'upload-site-id'})
  uploadSiteId: number | string = '';

  override connectedCallback(): void {
    super.connectedCallback();

    this.initializeEditor();
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();

    this.destroy();
  }

  private initializeEditor(): void {
    if (this.editor) {
      return;
    }

    const inputId = this.releaseInputIdToTextarea();

    const toolbarButtons = toolbarItems(
      {
        assetSources: this.assetSources,
        toolbarButtons: this.toolbarButtons,
        uploadFolderId: this.uploadFolderId,
      },
      {
        openLinkPopover: (event) => this.linkPopoverController?.open(event),
        openAssetSelector: () => this.assetController?.open(),
        togglePreview: () => this.previewController?.toggle(),
      }
    );
    const [editor] = new OverType(
      this,
      this.editorOptions(inputId, toolbarButtons)
    );

    if (!editor) {
      return;
    }

    this.editor = editor;
    this.editor.preview.classList.add('markdown-field-preview');

    const charCounterCleanup = this.addCharCounter(editor);
    this.addTypeIndicator(editor);

    const previewController = createPreviewController(
      editor,
      this.flavor,
      this.encode,
      this.inlineOnly,
      this.previewDelay
    );
    this.previewController = previewController;
    this.linkPopoverController = createLinkPopoverController(
      editor,
      previewController,
      {
        advancedFields: this.linkAdvancedFields,
        showLabelField: this.showLinkLabelField,
        types: this.linkTypes,
      }
    );
    this.assetController = createAssetController(
      editor,
      this.assetAnyUploader ? {uploaderId: null} : {},
      this.assetSources,
      previewController
    );
    this.cleanups = [
      () => previewController.destroy(),
      () => this.linkPopoverController?.destroy(),
      ...(charCounterCleanup ? [charCounterCleanup] : []),
      registerLinkPasteBehavior(editor, previewController),
      registerShortcutBehavior(editor, previewController),
    ];

    this.syncInitialFormValue(editor.textarea.name);
  }

  private releaseInputIdToTextarea(): string {
    if (this.resolvedInputId !== null) {
      return this.resolvedInputId;
    }

    this.resolvedInputId = this.id;

    // The host starts with the field id so first paint still has a targetable
    // element. Once OverType creates the real textarea, move the host id aside.
    if (this.resolvedInputId) {
      this.id = `${this.resolvedInputId}-editor`;
    }

    return this.resolvedInputId;
  }

  private editorOptions(
    inputId: string,
    toolbarButtons: Options['toolbarButtons']
  ): Options {
    const options: Options = {
      autoResize: true,
      fontFamily: 'var(--c-font-mono)',
      fontSize: 'var(--c-text-base)',
      lineHeight: 'var(--c-leading-normal)',
      maxHeight: null,
      minHeight: undefined,
      padding: 'var(--c-spacing-md) var(--c-input-spacing-inline)',
      placeholder: this.placeholder ?? '',
      showStats: this.showStats,
      statsFormatter: this.statsFormatter,
      smartLists: true,
      spellcheck: false,
      textareaProps: this.textareaProps(inputId),
      theme: themeOptions(),
      toolbar:
        !this.disabled && this.showToolbar && (toolbarButtons?.length ?? 0) > 0,
      toolbarButtons,
      value: this.textContent ?? '',
    };

    const uploadOptions = this.disabled
      ? undefined
      : fileUploadOptions(this.uploadFolderId, this.uploadSiteId);

    if (uploadOptions) {
      options.fileUpload = uploadOptions;
    }

    return options;
  }

  private statsFormatter: NonNullable<Options['statsFormatter']> = (stats) => {
    return `
      <div class="overtype-stat">
        <span>${stats.chars} ${t('chars')}, ${stats.words} ${t('words')}, ${stats.lines} ${t('lines')}</span>
      </div>
      <div class="overtype-stat">${t('Line')} ${stats.line}, ${t('Col')} ${stats.column}</div>
    `;
  };

  private addTypeIndicator(editor: OverTypeInstance): void {
    if (this.showToolbar) {
      return;
    }

    const indicator = document.createElement('div');
    indicator.className = 'markdown-field-type-indicator';
    indicator.setAttribute('aria-label', t('Markdown'));
    indicator.setAttribute('role', 'img');
    indicator.innerHTML = markdownIcon.replace(
      '<svg ',
      '<svg aria-hidden="true" focusable="false" '
    );

    this.footerControls(editor).appendChild(indicator);
  }

  private addCharCounter(editor: OverTypeInstance): (() => void) | null {
    if (!this.maxLength) {
      return null;
    }

    const maxLength = this.maxLength;
    const counter = document.createElement('div');
    counter.className = 'markdown-field-char-counter';
    counter.setAttribute('aria-live', 'polite');

    const updateCounter = () => {
      const charsLeft = maxLength - editor.textarea.value.length;

      counter.textContent = String(charsLeft);
      counter.setAttribute(
        'aria-label',
        t('Characters left: {chars, number}', {chars: charsLeft})
      );
      counter.classList.toggle('negative-chars-left', charsLeft < 0);
    };

    updateCounter();
    editor.textarea.addEventListener('input', updateCounter);
    this.footerControls(editor).appendChild(counter);

    return () => {
      editor.textarea.removeEventListener('input', updateCounter);
    };
  }

  private footerControls(editor: OverTypeInstance): HTMLElement {
    let controls = editor.wrapper.querySelector<HTMLElement>(
      '.markdown-field-footer-controls'
    );

    if (!controls) {
      controls = document.createElement('div');
      controls.className = 'markdown-field-footer-controls';
      editor.wrapper.appendChild(controls);
    }

    return controls;
  }

  private textareaProps(inputId: string): Record<string, string | number> {
    const props: Record<string, string | number> = {
      class: 'nicetext code',
    };

    if (inputId) {
      props.id = inputId;
    }

    if (this.name) {
      props.name = this.name;
    }

    if (this.describedBy) {
      props['aria-describedby'] = this.describedBy;
    }

    if (this.maxLength) {
      props.maxlength = this.maxLength;
    }

    if (this.disabled) {
      props.disabled = 'disabled';
    }

    return props;
  }

  // Craft records a form's initial serialized value before this dynamically
  // imported component creates its textarea, so the form can look dirty on
  // load. If the only serialization difference is this new input, refresh the
  // baseline to include it.
  private syncInitialFormValue(inputName: string): void {
    if (!inputName) {
      return;
    }

    const form = this.closest('form');
    const jquery =
      (window as Window & {jQuery?: any; $?: any}).jQuery ??
      (window as Window & {jQuery?: any; $?: any}).$;

    if (!form || !jquery) {
      return;
    }

    const $form = jquery(form);
    const initialValue = $form.data('initialSerializedValue');

    if (typeof initialValue !== 'string') {
      return;
    }

    const serializer = $form.data('serializer');
    const serialized =
      typeof serializer === 'function' ? serializer() : $form.serialize();

    if (this.serializedWithoutInput(serialized, inputName) !== initialValue) {
      return;
    }

    $form.data('initialSerializedValue', serialized);
  }

  private serializedWithoutInput(
    serialized: string,
    inputName: string
  ): string {
    return serialized
      .split('&')
      .filter((param) => this.serializedParamName(param) !== inputName)
      .join('&');
  }

  private serializedParamName(param: string): string {
    const [name = ''] = param.split('=');

    return decodeURIComponent(name.replace(/\+/g, '%20'));
  }

  private destroy(): void {
    for (const cleanup of this.cleanups) {
      cleanup();
    }

    this.editor?.destroy();
    this.assetController = null;
    this.cleanups = [];
    this.editor = null;
    this.linkPopoverController = null;
    this.previewController = null;
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override shouldUpdate(): boolean {
    return false;
  }
}

export default MarkdownField;
