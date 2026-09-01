import OverType, {
  type Options,
  type OverType as OverTypeInstance,
} from 'overtype';
import {t} from '@craftcms/ui';
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
import {replaceMarkdownGuideButton, toolbarItems} from './behaviors/toolbar';
import {fileUploadOptions} from './behaviors/uploads';
import './markdown-field.css';

type RenderOptions = Options & {
  onRender?: (
    preview: HTMLElement,
    mode: 'normal' | 'preview',
    editor: OverTypeInstance
  ) => void;
};

interface TextareaAttributes {
  class: string;
  rows: number;
  id?: string;
  name?: string;
  'aria-describedby'?: string;
  maxlength?: number;
  disabled?: string;
  required?: string;
  'aria-invalid'?: string;
}

@customElement('craft-markdown-field')
class MarkdownField extends LitElement {
  private assetController: AssetController | null = null;
  private cleanups: Array<() => void> = [];
  private editor: OverTypeInstance | null = null;
  private linkPopoverController: LinkPopoverController | null = null;
  private previewController: PreviewController | null = null;
  private resolvedInputId: string | null = null;
  private formValue: string | null = null;
  private updateCharCounter: (() => void) | null = null;

  @property({attribute: 'asset-any-uploader', type: Boolean})
  assetAnyUploader = false;

  @property({attribute: 'asset-sources', type: Array})
  assetSources: string[] = [];

  @property({attribute: 'described-by'})
  describedBy: string | null = null;

  @property({type: Boolean})
  disabled = false;

  @property({type: Boolean})
  required = false;

  @property({attribute: 'aria-invalid', type: Boolean})
  invalid = false;

  @property()
  flavor = 'gfm';

  @property({type: Boolean})
  encode = false;

  @property({attribute: 'inline-only', type: Boolean})
  inlineOnly = false;

  @property({attribute: 'sanitize-html', type: Boolean})
  sanitizeHtml = false;

  @property({attribute: 'html-sanitizer'})
  htmlSanitizer: string | null = null;

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

  get value(): string {
    return this.editor?.getValue() ?? this.formValue ?? this.textContent ?? '';
  }

  set value(value: string) {
    this.formValue = value;
    this.editor?.setValue(value);
    this.updateCharCounter?.();
  }

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
    this.formValue = editor.getValue();
    this.editor.preview.classList.add('markdown-field-preview');

    const charCounterCleanup = this.addCharCounter(editor);
    this.addTypeIndicator(editor);

    const previewController = createPreviewController(
      editor,
      this.flavor,
      this.encode,
      this.inlineOnly,
      this.sanitizeHtml,
      this.htmlSanitizer,
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
      replaceMarkdownGuideButton(editor),
      registerLinkPasteBehavior(editor, previewController),
      registerShortcutBehavior(editor, previewController),
    ];

    this.syncEditorState();
    requestAnimationFrame(() => {
      if (this.editor === editor) {
        editor.setValue(editor.getValue());
      }
    });
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
    const options: RenderOptions = {
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
      toolbar: this.showToolbar && (toolbarButtons?.length ?? 0) > 0,
      toolbarButtons,
      value: this.value,
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
    const icon = document.createElement('craft-icon');
    icon.setAttribute('name', 'markdown');
    icon.setAttribute('family', 'brands');
    icon.setAttribute('aria-hidden', 'true');
    indicator.append(icon);

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
    this.updateCharCounter = updateCounter;
    editor.textarea.addEventListener('input', updateCounter);
    this.footerControls(editor).appendChild(counter);

    return () => {
      editor.textarea.removeEventListener('input', updateCounter);
      this.updateCharCounter = null;
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

  private textareaProps(inputId: string): TextareaAttributes {
    const props: TextareaAttributes = {
      class: 'nicetext code',
      rows: this.rows,
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

    if (this.required) {
      props.required = 'required';
    }

    if (this.invalid) {
      props['aria-invalid'] = 'true';
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
    const jquery = window.jQuery ?? window.$;

    if (!form || !jquery) {
      return;
    }

    const $form = jquery(form);
    const initialValue = $form.data('initialSerializedValue');

    if (Object(initialValue).constructor !== String) {
      return;
    }

    const serializer = $form.data('serializer');
    const serialized =
      serializer instanceof Function ? serializer() : $form.serialize();

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
    this.formValue = this.editor?.getValue() ?? this.formValue;

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

  private syncEditorState(): void {
    if (!this.editor) {
      return;
    }

    this.editor.textarea.name = this.name ?? '';
    this.editor.textarea.disabled = this.disabled;
    this.editor.textarea.required = this.required;
    if (this.invalid) {
      this.editor.textarea.setAttribute('aria-invalid', 'true');
    } else {
      this.editor.textarea.removeAttribute('aria-invalid');
    }
    this.editor.wrapper
      .querySelectorAll<HTMLButtonElement>('button')
      .forEach((button) => (button.disabled = this.disabled));

    if (this.disabled || !this.showToolbar) {
      this.editor.toolbar?.hide();
    } else {
      this.editor.toolbar?.show();
    }
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override shouldUpdate(): boolean {
    this.syncEditorState();

    return false;
  }
}

export default MarkdownField;
