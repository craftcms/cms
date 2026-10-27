import type CraftDialog from '../components/dialog/dialog.js';
import type CraftInputCopy from '../components/input-copy/input-copy.js';
import type CraftTextarea from '../components/textarea/textarea.js';
import type CraftCopyButton from '../components/copy-button/copy-button.js';

export interface CopyTextPromptConfig {
  /** Shown as the dialog's header/accessible name (e.g. "Full URL"). */
  label?: string;
  /** The value to display and copy. */
  value: string;
  id?: string;
  /** Render a multi-line textarea, with the copy button in the footer, instead of a single-line input. */
  textarea?: boolean;
  /** Rows for the textarea variant. */
  rows?: number;
  size?: number;
  class?: string | string[];
  /** Where focus goes once the dialog closes. See `CraftDialog.returnFocus`. */
  returnFocus?: CraftDialog['returnFocus'];
}

/**
 * Opens a modal dialog showing `value` in a read-only field with a
 * copy-to-clipboard button — the jQuery-free twin of the legacy
 * `Craft.ui.createCopyTextPrompt`.
 *
 * Built entirely on `@craftcms/ui` components: `<craft-dialog>` (the modal) and
 * `<craft-input-copy>`, or a `<craft-textarea>` with a `<craft-copy-button>` in
 * the footer (which copies via `navigator.clipboard` and shows its own
 * feedback, replacing the legacy `execCommand('copy')` + `displayNotice`).
 * The dialog is appended to `<body>` and shown immediately; it closes once the
 * value is copied, and removes itself from the document once it has closed.
 * Returns the dialog element (callers use it fire-and-forget).
 */
export function createCopyTextPrompt(
  config: CopyTextPromptConfig
): CraftDialog {
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  if (config.label) {
    dialog.setAttribute('label', config.label);
  }
  if (config.returnFocus) {
    dialog.returnFocus = config.returnFocus;
  }

  dialog.append(
    ...(config.textarea
      ? buildTextareaField(config)
      : [buildInputField(config)])
  );

  // Legacy hid the modal once the value was copied.
  dialog.addEventListener('craft-copy', () => {
    dialog.opened = false;
  });

  // Nothing reopens it, so it goes once it has closed. The target check skips
  // the same event bubbling up from an overlay inside it.
  dialog.addEventListener('craft-after-hide', (event) => {
    if (event.target === dialog) {
      dialog.remove();
    }
  });

  dialog.setAttribute('open', '');
  document.body.appendChild(dialog);

  return dialog;
}

function buildInputField(config: CopyTextPromptConfig): HTMLElement {
  const field = document.createElement('craft-input-copy') as CraftInputCopy;
  field.label = config.label ?? '';
  field.labelSrOnly = true;
  field.modelValue = config.value;
  // Legacy sizing: fit the value, clamped to [25, 50].
  field.inputSize =
    config.size ?? Math.max(Math.min(config.value.length, 50), 25);
  if (config.id) {
    field.id = config.id;
  }
  field.classList.add(...classes(config));

  return field;
}

function buildTextareaField(config: CopyTextPromptConfig): HTMLElement[] {
  const field = document.createElement('craft-textarea') as CraftTextarea;
  field.label = config.label ?? '';
  field.labelSrOnly = true;
  field.modelValue = config.value;
  field.readOnly = true;
  field.rows = config.rows ?? 2;
  if (config.id) {
    field.id = config.id;
  }
  field.classList.add(...classes(config));
  // The legacy prompt's `code` class set the value in a monospace font.
  field.monospace = field.classList.contains('code');

  const copyButton = document.createElement(
    'craft-copy-button'
  ) as CraftCopyButton;
  copyButton.slot = 'footer';
  copyButton.value = config.value;

  return [field, copyButton];
}

function classes(config: CopyTextPromptConfig): string[] {
  return (
    Array.isArray(config.class) ? config.class : [config.class ?? '']
  ).filter(Boolean);
}
